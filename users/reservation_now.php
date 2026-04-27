<?php
session_start();
include('../db.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $redirect_url = basename($_SERVER['REQUEST_URI']);
    $_SESSION['login_redirect'] = $redirect_url;
    header("Location: login.php?redirect=" . urlencode($redirect_url));
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Check for Extension Request ---
$is_extension = false;
$ext_data = [];
$error = "";

// Check GET or POST for extend_id
$eid_param = isset($_GET['extend_id']) ? $_GET['extend_id'] : (isset($_POST['extend_id']) ? $_POST['extend_id'] : null);

if($eid_param){
    $eid = (int)$eid_param;
    // Verify it belongs to user and is Approved (Active)
    $e_query = mysqli_query($conn, "SELECT r.*, rm.room_type, rm.room_name FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.reservation_id=$eid AND r.user_id=$user_id AND r.status='Approved'");
    if($ext_data = mysqli_fetch_assoc($e_query)){
        $is_extension = true;
    }
}

// --- Prevent Multiple Bookings (Skip if extending) ---
if (!$is_extension) {
    $check_sql = "SELECT reservation_id FROM reservations WHERE user_id = ? AND status IN ('Pending', 'Approved')";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['swal'] = ['title' => 'Active Reservation', 'text' => 'You cannot book another room until your current booking is completed.', 'icon' => 'warning'];
        header("Location: my_reservations.php");
        exit();
    }
    mysqli_stmt_close($check_stmt);
}

// Fetch User Details
$user_lname = '';
$user_fname = '';
$user_mname = '';
$user_email = '';
$user_phone = '';
$user_gender = '';
$user_occupation = '';
$user_address = '';
$user_company = '';
$user_school_id_image = '';
$user_emergency_contact_name = '';
$user_emergency_contact_number = '';

$stmt = $conn->prepare("SELECT last_name, first_name, middle_name, email, phone_number, gender, occupation, address, company, school_id_image, emergency_contact_name, emergency_contact_number FROM users WHERE user_id = ?");

// Add this check
if (!$stmt) {
    die("Database Prepare Failed: " . $conn->error); 
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_lname, $user_fname, $user_mname, $user_email, $user_phone, $user_gender, $user_occupation, $user_address, $user_company, $user_school_id_image, $user_emergency_contact_name, $user_emergency_contact_number);
$stmt->fetch();
$stmt->close();
$user_name = $user_lname . ', ' . $user_fname . (!empty($user_mname) ? ' ' . $user_mname : '');

// Fetch ID Type if exists
$user_id_type = '';
$chk_id_type = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'id_type'");
if(mysqli_num_rows($chk_id_type) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN id_type VARCHAR(50) DEFAULT NULL");
} else {
    $q_id = mysqli_query($conn, "SELECT id_type FROM users WHERE user_id=$user_id");
    if($r_id = mysqli_fetch_assoc($q_id)) $user_id_type = $r_id['id_type'];
}

// Fetch Room Prices dynamically from DB for JS
$room_prices_js = [];
$price_query = mysqli_query($conn, "SELECT room_type, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, daily_price_bed, daily_price_room FROM rooms GROUP BY room_type");
while($row = mysqli_fetch_assoc($price_query)){
    $room_prices_js[$row['room_type']] = [
        'short_base' => $row['total_price'],
        'short_upper' => $row['price_upper'],
        'short_lower' => $row['price_lower'],
        'short_whole' => $row['price_whole'],
        'long_upper' => $row['long_term_price_upper'] ?? 0,
        'long_lower' => $row['long_term_price_lower'] ?? 0,
        'long_whole' => $row['long_term_price_whole'] ?? 0,
        'daily_bed' => $row['daily_price_bed'] ?? 0,
        'daily_room' => $row['daily_price_room'] ?? 0
    ];
}

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");

// Fetch House Rules
$q_rules = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='house_rules'");
$house_rules_file = ($row_rules = mysqli_fetch_assoc($q_rules)) ? $row_rules['setting_value'] : "";
$house_rules_url = !empty($house_rules_file) ? "../uploads/settings/" . htmlspecialchars($house_rules_file) : "#";

// Handle Extension (Pre-fill data)
$pre_cin = date("Y-m-d");
$pre_room = "";
$pre_bed = "Any";
$pre_duration = "";

if ($is_extension) {
    $pre_cin = $ext_data['end_date']; // Start new booking when old one ends
    $pre_room = $ext_data['room_type'];
} elseif(isset($_GET['room_type'])){
    $pre_room = htmlspecialchars($_GET['room_type']);
}

if(isset($_GET['bed_preference'])){
    $pre_bed = htmlspecialchars($_GET['bed_preference']);
}

if(isset($_GET['duration'])){
    $pre_duration = htmlspecialchars($_GET['duration']);
}

// Handle Submission
if (isset($_POST['confirm_booking'])) {
        $troom = $_POST['troom'] ?? '';
        $pre_room = $troom; // Persist room selection
        $cin = $_POST['cin'] ?? '';
        $cout = $_POST['cout'] ?? '';
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $payment_method = 'System'; // Default internal placeholder, actual payment happens later
        $agree_rules = isset($_POST['agree_rules']);
        $agree_fees = isset($_POST['agree_fees']);
        $typed_signature = trim($_POST['typed_signature'] ?? '');

        // Persist user input for form reload on error
        $user_gender = $_POST['gender'] ?? $user_gender;
        $user_occupation = $_POST['occupation'] ?? $user_occupation;
        $user_company = $_POST['company'] ?? $user_company;
        $user_address = $_POST['address'] ?? $user_address;
        $user_emergency_contact_name = $_POST['emergency_contact_name'] ?? $user_emergency_contact_name;
        $user_emergency_contact_number = $_POST['emergency_contact_number'] ?? $user_emergency_contact_number;
        
        $pre_bed = $bed_preference; // Persist bed preference

        if (empty($troom)) {
            $error = "Please select a valid room type.";
        }

        // Update Gender
        if(isset($_POST['gender']) && !empty($_POST['gender'])){
            $new_gender = mysqli_real_escape_string($conn, $_POST['gender']);
            mysqli_query($conn, "UPDATE users SET gender='$new_gender' WHERE user_id=$user_id");
            $user_gender = $new_gender; // Update local variable
        }

        // Update Occupation
        if(isset($_POST['occupation']) && !empty($_POST['occupation'])){
            $new_occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
            mysqli_query($conn, "UPDATE users SET occupation='$new_occupation' WHERE user_id=$user_id");
            $user_occupation = $new_occupation; // Update local variable
        }

        // Update Company
        if(isset($_POST['company']) && !empty($_POST['company'])){
            $new_company = mysqli_real_escape_string($conn, $_POST['company']);
            mysqli_query($conn, "UPDATE users SET company='$new_company' WHERE user_id=$user_id");
            $user_company = $new_company; // Update local variable
        }

        // Update Address
        if(isset($_POST['address']) && !empty($_POST['address'])){
            $new_address = mysqli_real_escape_string($conn, $_POST['address']);
            mysqli_query($conn, "UPDATE users SET address='$new_address' WHERE user_id=$user_id");
            $user_address = $new_address; // Update local variable
        }

        // Update Emergency Contact
        if(isset($_POST['emergency_contact_name']) && !empty($_POST['emergency_contact_name'])){
            $new_emergency_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
            mysqli_query($conn, "UPDATE users SET emergency_contact_name='$new_emergency_name' WHERE user_id=$user_id");
            $user_emergency_contact_name = $new_emergency_name;
        }
        if(isset($_POST['emergency_contact_number']) && !empty($_POST['emergency_contact_number'])){
            $new_emergency_number = mysqli_real_escape_string($conn, $_POST['emergency_contact_number']);
            mysqli_query($conn, "UPDATE users SET emergency_contact_number='$new_emergency_number' WHERE user_id=$user_id");
            $user_emergency_contact_number = $new_emergency_number;
        }

        // Update ID Type
        if(isset($_POST['id_type']) && !empty($_POST['id_type'])){
            $new_id_type = mysqli_real_escape_string($conn, $_POST['id_type']);
            mysqli_query($conn, "UPDATE users SET id_type='$new_id_type' WHERE user_id=$user_id");
            $user_id_type = $new_id_type;
        }

        // Handle Valid ID upload for students/employed
        $school_id_filename = $user_school_id_image; // Keep existing by default
        $needs_id = in_array($user_occupation, ['Student', 'Employed']);
        if(isset($_POST['occupation']) && in_array($_POST['occupation'], ['Student', 'Employed'])){
            $needs_id = true;
        }
        
        if($needs_id){
            // Check if new upload or keep existing
            if(isset($_FILES['school_id_image']) && $_FILES['school_id_image']['error'] == 0) {
                $target_dir = "../uploads/proofs/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $school_id_filename = time() . '_id_' . basename($_FILES["school_id_image"]["name"]);
                $target_file = $target_dir . $school_id_filename;
                if (!move_uploaded_file($_FILES["school_id_image"]["tmp_name"], $target_file)) {
                    $error = "Sorry, there was an error uploading your ID.";
                }
            } elseif(empty($user_school_id_image)) {
                if (empty($error)) $error = "Valid ID image is required.";
            }
            
            // Save/update ID if no error
            if(!$error && $school_id_filename){
                mysqli_query($conn, "UPDATE users SET school_id_image='$school_id_filename' WHERE user_id=$user_id");
            }
        }

        if (empty($error) && (!$agree_rules || !$agree_fees || empty($typed_signature))) {
            $error = "You must agree to the policies and provide a signature to proceed.";
        }
        $ref_number = null;
        $proof_filename = null;
        
        if (empty($cin) || empty($cout)) {
            if (empty($error)) $error = "Check-in and Check-out dates are required.";
        } else {
            // Calculate duration based on dates
            $d1 = new DateTime($cin);
            $d2 = new DateTime($cout);
            $interval = $d1->diff($d2);
            
            // Calculate accurate months for DB record
            $months = ($interval->y * 12) + $interval->m;
            // Minimal logic: if less than a month but has days, treat as 1 month for initial record
            if ($months == 0 && $interval->d > 0) $months = 1;
        }

        $specific_room_id = isset($_POST['specific_room_id']) ? (int)$_POST['specific_room_id'] : 0;
        $auto_assigned = ($specific_room_id > 0) ? 0 : 1;

        // --- EXTRACT COMPANIONS IF WHOLE ROOM ---
        $companions = [];
        if ($bed_preference == 'Whole Room' && in_array($troom, ['4-Bed', '6-Bed'])) {
            if (isset($_POST['comp_fname']) && is_array($_POST['comp_fname'])) {
                for ($i = 0; $i < count($_POST['comp_fname']); $i++) {
                    $c_lname = trim($_POST['comp_lname'][$i] ?? '');
                    $c_fname = trim($_POST['comp_fname'][$i] ?? '');
                    $c_mname = trim($_POST['comp_mname'][$i] ?? '');
                    $c_name = trim($c_lname . ', ' . $c_fname . ' ' . $c_mname);
                    
                    $c_gender = trim($_POST['comp_gender'][$i] ?? '');
                    $c_email = trim($_POST['comp_email'][$i] ?? '');
                    $c_phone = trim($_POST['comp_phone'][$i] ?? '');
                    
                    $c_id_image = null;
                    if (isset($_FILES['comp_id_image']['name'][$i]) && $_FILES['comp_id_image']['error'][$i] == 0) {
                        $target_dir = "../uploads/proofs/";
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        $c_id_image = time() . '_comp_' . $i . '_' . basename($_FILES["comp_id_image"]["name"][$i]);
                        move_uploaded_file($_FILES["comp_id_image"]["tmp_name"][$i], $target_dir . $c_id_image);
                    }

                    if (!empty($c_lname) && !empty($c_fname)) {
                        $companions[] = [
                            'name' => mysqli_real_escape_string($conn, $c_name),
                            'first_name' => mysqli_real_escape_string($conn, $c_fname),
                            'last_name' => mysqli_real_escape_string($conn, $c_lname),
                            'middle_name' => mysqli_real_escape_string($conn, $c_mname),
                            'gender' => mysqli_real_escape_string($conn, $c_gender),
                            'email' => mysqli_real_escape_string($conn, $c_email),
                            'phone' => mysqli_real_escape_string($conn, $c_phone),
                            'id_image' => $c_id_image ? mysqli_real_escape_string($conn, $c_id_image) : null
                        ];
                    }
                }
            }
        }
        $companions_json = !empty($companions) ? json_encode($companions) : null;

        // Downpayment / Reservation Fee Logic
        $amount_to_pay_now = isset($_POST['pay_only_downpayment']) ? 2000.00 : 0; // Halimbawa ₱2,000 fix fee

        // Find an available room of the selected type
        $found_room = null;
        if ($specific_room_id > 0) {
            $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, daily_price_bed, daily_price_room, gender FROM rooms WHERE room_id = ? AND is_archived=0";
            $r_stmt = $conn->prepare($r_sql);
            $r_stmt->bind_param("i", $specific_room_id);
        } else {
            $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, daily_price_bed, daily_price_room, gender FROM rooms WHERE room_type = ? AND availability = 'Available' AND is_archived=0";
            $r_stmt = $conn->prepare($r_sql);
            $r_stmt->bind_param("s", $troom);
        }
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();

        while($room = $r_res->fetch_assoc()) {
            if ($troom != 'Single' && $bed_preference != 'Whole Room' && $room['gender'] != 'Any' && $room['gender'] != $user_gender) {
                continue; // Skip if room gender restriction does not match user's gender
            }
            $rid = $room['room_id'];
            $capacity = $room['total_beds'];
            
            // Get detailed occupancy
            $cin_safe = mysqli_real_escape_string($conn, $cin);
            $cout_safe = mysqli_real_escape_string($conn, $cout);
            $q_occ = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout_safe' AND end_date > '$cin_safe' GROUP BY bed_preference";
            $res_occ = mysqli_query($conn, $q_occ);
            
            $occ_lower = 0; $occ_upper = 0; $occ_any = 0; $total_booked = 0;
            while($row_o = mysqli_fetch_assoc($res_occ)){
                $cnt = $row_o['cnt'];
                if($row_o['bed_preference'] == 'Whole Room') {
                    $total_booked += $capacity;
                    $occ_any += $capacity;
                } else {
                    $total_booked += $cnt;
                    if($row_o['bed_preference'] == 'Lower Bunk') $occ_lower += $cnt;
                    elseif($row_o['bed_preference'] == 'Upper Bunk') $occ_upper += $cnt;
                    else $occ_any += $cnt;
                }
            }

            if(($capacity - $total_booked) > 0){
                if (($troom == '4-Bed' || $troom == '6-Bed')) {
                    $cap_lower = ceil($capacity / 2);
                    $cap_upper = floor($capacity / 2);
                    
                    $avail_upper = max(0, $cap_upper - $occ_upper);
                    $avail_lower = max(0, $cap_lower - $occ_lower);
                    
                    if($occ_any > 0) {
                        $fill_lower = min($avail_lower, $occ_any);
                        $avail_lower -= $fill_lower;
                        $occ_any -= $fill_lower;
                        
                        $avail_upper -= $occ_any;
                        $avail_upper = max(0, $avail_upper);
                    }
                    
                    if($bed_preference == 'Lower Bunk'){
                        if($avail_lower > 0) { $found_room = $room; break; }
                    } elseif($bed_preference == 'Upper Bunk'){
                        if($avail_upper > 0) { $found_room = $room; break; }
                    } elseif($bed_preference == 'Whole Room'){
                        if($total_booked == 0) { $found_room = $room; break; }
                    } else {
                        $found_room = $room; break;
                    }
                } else {
                    $found_room = $room;
                    break;
                }
            }
        }

        if (!$error) { // Proceed only if no errors so far
            if ($found_room) {
                $room_id = $found_room['room_id'];
                
                // Determine price based on preference
                $monthly_price = $found_room['total_price'];
                if ($troom != 'Single' && $bed_preference == 'Upper Bunk') {
                    $monthly_price = ($found_room['price_upper'] > 0) ? $found_room['price_upper'] : $found_room['total_price'];
                } elseif ($troom != 'Single' && $bed_preference == 'Lower Bunk') {
                    $monthly_price = ($found_room['price_lower'] > 0) ? $found_room['price_lower'] : $found_room['total_price'];
                } elseif ($bed_preference == 'Whole Room') {
                    $monthly_price = ($found_room['price_whole'] > 0) ? $found_room['price_whole'] : ($found_room['total_price'] * $found_room['total_beds']);
                }
                
                // --- NEW CALCULATION LOGIC ---
                $term_type = $_POST['term_type'] ?? 'Short'; // Default
                $totalAmount = 0;
                $contract_price = 0;
                $security_deposit = 0;
                if (!$is_extension) {
                    if ($troom === 'Single') {
                        $security_deposit = 8000;
                    } elseif ($term_type === 'Long') {
                        $security_deposit = 3000;
                    } elseif ($term_type === 'Short') {
                        $security_deposit = 1000;
                    }
                }

                if ($term_type === 'Daily') {
                    // Daily Rate Calculation
                    $nights = $d1->diff($d2)->days;
                    if($nights < 1) $nights = 1;
                    $daily_rate = $found_room['daily_price_bed'] > 0 ? $found_room['daily_price_bed'] : 700; // Fallback
                    if($troom == 'Single') $daily_rate = $found_room['daily_price_room'] > 0 ? $found_room['daily_price_room'] : 1200;
                    if($bed_preference == 'Whole Room') $daily_rate = $found_room['daily_price_room'] > 0 ? $found_room['daily_price_room'] : ($daily_rate * $found_room['total_beds']);
                    
                    $contract_price = $nights * $daily_rate;
                    $totalAmount = $contract_price;
                    $status = "Pending"; 
                } elseif ($term_type === 'Long') {
                    // 6 Month Term Logic
                    // Determine Long Term Monthly Price
                    $lt_price = $monthly_price; // Default to short term if long term not set
                    if ($troom == 'Single') {
                        if ($found_room['long_term_price_whole'] > 0) $lt_price = $found_room['long_term_price_whole'];
                    } else {
                        if ($bed_preference == 'Upper Bunk' && $found_room['long_term_price_upper'] > 0) $lt_price = $found_room['long_term_price_upper'];
                        elseif ($bed_preference == 'Lower Bunk' && $found_room['long_term_price_lower'] > 0) $lt_price = $found_room['long_term_price_lower'];
                        elseif ($bed_preference == 'Whole Room' && $found_room['long_term_price_whole'] > 0) $lt_price = $found_room['long_term_price_whole'];
                    }

                    $contract_price = ($lt_price * 6) + $security_deposit;
                    $totalAmount = $lt_price + $security_deposit;
                    $status = "Pending";
                    $months = 6; // Force months to 6 for Long Term
                } else {
                    // Short Term (1 Month) Logic
                    $st_price = $monthly_price;
                    if ($troom != 'Single') {
                        if ($bed_preference == 'Upper Bunk' && $found_room['price_upper'] > 0) $st_price = $found_room['price_upper'];
                        elseif ($bed_preference == 'Lower Bunk' && $found_room['price_lower'] > 0) $st_price = $found_room['price_lower'];
                        elseif ($bed_preference == 'Whole Room' && $found_room['price_whole'] > 0) $st_price = $found_room['price_whole'];
                    }
                    $contract_price = $st_price + $security_deposit;
                    $totalAmount = $contract_price;
                    $status = "Pending";
                }

                // Check for carried over unpaid balances from previous reservations
                $prev_bal_q = mysqli_query($conn, "SELECT p.amount, p.description FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id = $user_id AND p.payment_status = 'Unpaid'");
                $prev_balance = 0;
                $carried_items = [];
                while($prev_bal_row = mysqli_fetch_assoc($prev_bal_q)) {
                    $prev_balance += (float)($prev_bal_row['amount'] ?? 0);
                    $c_desc = preg_replace('/\s*\(Carried over to.*?\)/i', '', $prev_bal_row['description']);
                    $c_desc = preg_replace('/\s*\[FULL\]\s*/i', '', $c_desc);
                    $c_desc = preg_replace('/\s*\(Parking ID: \d+\)/i', '', $c_desc);
                    $carried_items[] = trim($c_desc);
                }
                
                $totalAmount += $prev_balance; // Add previous debt to initial payment required

                if ($amount_to_pay_now > 0 && $amount_to_pay_now < $totalAmount) {
                    $initial_payment = $amount_to_pay_now;
                    $remaining = $totalAmount - $amount_to_pay_now;
                } else {
                    $initial_payment = $totalAmount;
                    $remaining = 0;
                }

                $reservation_id = 0;
                $exec_result = false;
                
                // Ensure extended_from column exists for linking extensions
                $check_col = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'extended_from'");
                if(mysqli_num_rows($check_col) == 0) {
                    mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN extended_from INT DEFAULT NULL");
                }

                // INSERT NEW RESERVATION (Always create new for approval, even if extension)
                
                // Reuse signature if extending
                $sig_img = ($is_extension && !empty($ext_data['signature_image'])) ? $ext_data['signature_image'] : null;
                
                if($sig_img) {
                    // Insert with signature
                    try {
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, signature_image, auto_assigned, occupation, company_or_school, contact_person_name, contact_person_number, security_deposit, companions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iissidsssissssds", $user_id, $room_id, $cin, $cout, $months, $contract_price, $status, $bed_preference, $sig_img, $auto_assigned, $user_occupation, $user_company, $user_emergency_contact_name, $user_emergency_contact_number, $security_deposit, $companions_json);
                    } catch (Exception $e) { $stmt = false; }
                } else {
                    // Standard insert
                    try {
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, auto_assigned, occupation, company_or_school, contact_person_name, contact_person_number, security_deposit, companions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if($stmt) $stmt->bind_param("iissidssissssds", $user_id, $room_id, $cin, $cout, $months, $contract_price, $status, $bed_preference, $auto_assigned, $user_occupation, $user_company, $user_emergency_contact_name, $user_emergency_contact_number, $security_deposit, $companions_json);
                    } catch (Exception $e) { $stmt = false; }
                }

                if ($stmt) {
                    $exec_result = $stmt->execute();
                    $stmt->close();
                    $reservation_id = $conn->insert_id;
                    
                    // Mark old unpaid payments as Carried Over/Cancelled to prevent double billing
                    if($prev_balance > 0 && $reservation_id) {
                        mysqli_query($conn, "UPDATE payments p JOIN reservations r ON p.reservation_id = r.reservation_id SET p.payment_status='Cancelled', p.description = CONCAT(p.description, ' (Carried over to Reservation #$reservation_id)') WHERE r.user_id=$user_id AND p.payment_status='Unpaid' AND r.reservation_id != $reservation_id");
                    }

                    // Link to original reservation if extension
                    if($is_extension && $reservation_id){
                        mysqli_query($conn, "UPDATE reservations SET extended_from=$eid WHERE reservation_id=$reservation_id");
                    }
                } else {
                    // Fallback logic for older DB versions omitted for brevity, assuming DB is up to date
                    $error = "Database Error: Could not prepare statement.";
                }

                if ($exec_result) {
                    // All online payments start as Unpaid until verified by admin
                    $p_status = 'Unpaid';
                    
                    // Split Security Deposit and Rent if applicable
                    if($security_deposit > 0) {
                        $rent_part = $initial_payment - $security_deposit;
                        
                        // 1. Insert Security Deposit
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, reference_number, proof_image, description) VALUES (?, ?, ?, ?, NOW(), ?, ?, 'Security Deposit')");
                        $pay_stmt->bind_param("idssss", $reservation_id, $security_deposit, $payment_method, $p_status, $ref_number, $proof_filename);
                        $pay_stmt->execute();
                        
                        // 2. Insert Rent Part
                        $rent_desc = ($term_type == 'Daily') ? "Daily Stay Payment" : "First Month Rent";
                        if ($prev_balance > 0 && !empty($carried_items)) {
                            $rent_desc .= " + " . implode(' + ', $carried_items);
                        }
                        if (strlen($rent_desc) > 250) $rent_desc = substr($rent_desc, 0, 247) . '...';
                        
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, reference_number, proof_image, description) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
                        $pay_stmt->bind_param("idsssss", $reservation_id, $rent_part, $payment_method, $p_status, $ref_number, $proof_filename, $rent_desc);
                        $pay_stmt->execute();
                        $pay_stmt->close();
                    } else {
                        // Direct insert for Extensions or zero deposit bookings
                        if ($initial_payment > 0) {
                            $rent_desc = ($is_extension) ? "Extension Rent Payment" : (($term_type == 'Daily') ? "Daily Stay Payment" : "First Month Rent");
                            if ($prev_balance > 0 && !empty($carried_items)) {
                                $rent_desc .= " + " . implode(' + ', $carried_items);
                            }
                            if (strlen($rent_desc) > 250) $rent_desc = substr($rent_desc, 0, 247) . '...';
                            
                            $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, reference_number, proof_image, description) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
                            $pay_stmt->bind_param("idsssss", $reservation_id, $initial_payment, $payment_method, $p_status, $ref_number, $proof_filename, $rent_desc);
                            $pay_stmt->execute();
                            $pay_stmt->close();
                        }
                    }

                    // Handle remaining balance record for partial payments
                    if ($remaining > 0) {
                        // Insert balance part
                        $bal_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, 'Unpaid', NOW(), ?)");
                        $b_desc = "Remaining Balance for Reservation #$reservation_id";
                        $bal_stmt->bind_param("idss", $reservation_id, $remaining, $payment_method, $b_desc);
                        $bal_stmt->execute();
                        $bal_stmt->close();
                    }

                    // Update the amount to be charged via Dragonpay if partial payment was selected
                    $totalAmount = $initial_payment;

                    // Generate the monthly payment schedule for Long Term (6 Months) contracts
                    if ($term_type === 'Long') {
                        for ($month_num = 2; $month_num <= 6; $month_num++) {
                            $rem_desc = "Month $month_num Rent";
                            $rem_status = "Unpaid";
                            $rem_date = date('Y-m-d H:i:s', strtotime($cin . " + " . ($month_num - 1) . " months"));
                            
                            $pay_stmt_rem = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'System', ?, ?, ?)");
                            if ($pay_stmt_rem) {
                                $pay_stmt_rem->bind_param("idsss", $reservation_id, $lt_price, $rem_status, $rem_date, $rem_desc);
                                $pay_stmt_rem->execute();
                                $pay_stmt_rem->close();
                            }
                        }
                    }

                    // --- NOTIFICATIONS ---
                    // 1. Notify User
                    $msg_user = "✅ <strong>Reservation Received!</strong><br>Your booking for <strong>$troom</strong> is now <strong>Pending</strong>. Please wait for admin approval.";
                    send_notification($conn, $user_id, $msg_user, "Booking Status");

                    // Log Activity
                    $log_action = $is_extension ? "Reservation Extended" : "Reservation Submitted";
                    log_activity($conn, $user_id, $log_action, "Room: $troom | Status: Pending");
                    trigger_update($conn); // Auto-refresh admin view

                    $_SESSION['swal'] = ['title' => 'Success!', 'text' => 'Reservation successful! You can pay your bills in the My Reservations page.', 'icon' => 'success'];
                    header("Location: my_reservations.php");
                    exit;
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            } else {
            $error = "Sorry, $troom is currently fully booked for those dates.";
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Reservation | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        /* Premium Night Mode / Light Mode Integration */
        :root {
            --surface-color: #ffffff;
            --surface-hover: #f8f9fa;
            --border-color: #eaecee;
            --text-color: #333333;
            --text-muted: #6c757d;
            --primary: #34B875;
        }
        body.night-mode {
            --surface-color: #1e1e1e;
            --surface-hover: #2c2c2c;
            --border-color: #333333;
            --text-color: #eaeaea;
            --text-muted: #a0a0a0;
            background-color: #121212 !important;
            color: var(--text-color) !important;
        }
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode .bg-white { background-color: var(--surface-color) !important; color: var(--text-color) !important; }
        
        .card-custom {
            background-color: var(--surface-color) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-custom:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
        .card-header { background-color: transparent !important; border-bottom: 1px solid var(--border-color) !important; padding: 1.25rem 1.5rem; }
        
        .form-control, .form-select {
            background-color: var(--surface-hover) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-color) !important;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(52, 184, 117, 0.15) !important;
            background-color: var(--surface-color) !important;
        }
        .form-control[readonly] { background-color: var(--surface-hover) !important; opacity: 0.8; cursor: not-allowed; }
        
        .utility-block {
            background-color: var(--surface-hover) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 16px;
            padding: 24px;
            color: var(--text-color) !important;
            margin-top: 2rem;
        }
        .text-dark { color: var(--text-color) !important; }
        .text-muted { color: var(--text-muted) !important; }
        .bg-light { background-color: var(--surface-hover) !important; }
        .border { border-color: var(--border-color) !important; }
        .form-label { color: var(--text-color); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; }
        hr { border-color: var(--border-color) !important; }
        
        .btn-custom {
            background-color: var(--primary);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 184, 117, 0.2);
        }
        .btn-custom:hover { background-color: #2A9A60; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(52, 184, 117, 0.3); color: white; }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .card-custom { padding: 15px !important; }
            .card-header { padding: 1rem 1rem !important; }
            .card-body { padding: 1rem 0.5rem !important; }
            .utility-block { padding: 15px !important; }
            .btn-custom { padding: 12px !important; }
        }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
    })();
</script>
<div class="container py-5 animate-fade-in">
    <div class="d-flex justify-content-end mb-3">
        <a href="javascript:void(0)" onclick="goBackOrHome()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if(!empty($error)): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="reservationForm">
        <input type="hidden" name="confirm_booking" value="1">
                    <input type="hidden" name="term_type" id="term_type" value="<?= ($pre_duration == '6') ? 'Long' : (($pre_duration == 'Daily') ? 'Daily' : 'Short') ?>">
        <?php if($is_extension): ?>
            <input type="hidden" name="extend_id" value="<?= $eid ?>">
        <?php endif; ?>
        <div class="row g-4">
            <!-- Personal Info -->
            <div class="col-md-5 anim-trigger delay-1 d-flex flex-column gap-4">
                <div class="card card-custom">
                    <div class="card-header fw-bold text-success"><i class="fas fa-user me-2"></i>Personal Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sex <span class="text-danger">*</span></label>
                            <?php if(!empty($user_gender)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_gender) ?>" readonly>
                                <input type="hidden" name="gender" value="<?= htmlspecialchars($user_gender) ?>">
                            <?php else: ?>
                                <select name="gender" class="form-select" required onchange="checkRealTimeAvailability()">
                                    <option value="" disabled selected>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Occupation Status <span class="text-danger">*</span></label>
                            <?php if(!empty($user_occupation)): ?>
                                <input type="text" name="occupation" class="form-control" id="occupation" value="<?= htmlspecialchars($user_occupation) ?>" readonly>
                                <input type="hidden" name="occupation" value="<?= htmlspecialchars($user_occupation) ?>" id="occupation_hidden">
                            <?php else: ?>
                                <select name="occupation" id="occupation" class="form-select" required onchange="toggleCompanyField()">
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="Student">Student</option>
                                    <option value="Employed">Employed</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3" id="id_type_div" style="display: none;">
                            <label class="form-label">ID Type <span class="text-danger">*</span></label>
                            <?php if(!empty($user_id_type)): ?>
                                <input type="text" name="id_type" class="form-control" id="id_type_readonly" value="<?= htmlspecialchars($user_id_type) ?>" readonly>
                                <input type="hidden" id="id_type" value="<?= htmlspecialchars($user_id_type) ?>">
                            <?php else: ?>
                                <select name="id_type" id="id_type" class="form-select">
                                    <option value="" disabled selected>Select ID Type</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3" id="company_div" style="display: none;">
                            <label class="form-label" id="company_label">Company / School Name <span class="text-danger">*</span></label>
                            <?php if(!empty($user_company)): ?>
                                <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($user_company) ?>" readonly>
                                <input type="hidden" name="company" value="<?= htmlspecialchars($user_company) ?>">
                            <?php else: ?>
                                <input type="text" name="company" id="company" class="form-control" placeholder="Enter your company or school name" required>
                            <?php endif; ?>
                        </div>
                        <!-- ID Upload -->
                        <div class="mb-3" id="school_id_div" style="display: none;">
                            <label class="form-label" id="id_image_label">Valid ID (Image) <span class="text-danger">*</span></label>
                            <?php if(!empty($user_school_id_image)): ?>
                                <div class="mb-2">
                                    <?php if(file_exists('../uploads/proofs/' . $user_school_id_image)): ?>
                                        <img src="../uploads/proofs/<?= htmlspecialchars($user_school_id_image) ?>" alt="Valid ID" style="max-width: 200px; max-height: 150px;" class="border rounded">
                                        <div class="small text-success mt-1"><i class="fas fa-check-circle"></i> ID already uploaded</div>
                                    <?php else: ?>
                                        <div class="small text-danger"><i class="fas fa-exclamation-triangle"></i> Previous ID file missing. Please re-upload.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="school_id_image" id="school_id_image" class="form-control" accept="image/*">
                            <small class="text-muted" id="id_image_help">Upload a clear photo of your ID</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                            <?php if(!empty($user_address)): ?>
                                <textarea class="form-control" readonly><?= htmlspecialchars($user_address) ?></textarea>
                                <input type="hidden" name="address" value="<?= htmlspecialchars($user_address) ?>">
                            <?php else: ?>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select id="region" class="form-select form-select-sm" required>
                                            <option value="" disabled selected>Select Region</option>
                                            <option value="130000000">National Capital Region (NCR)</option>
                                            <option value="140000000">Cordillera Administrative Region (CAR)</option>
                                            <option value="010000000">Region I (Ilocos Region)</option>
                                            <option value="020000000">Region II (Cagayan Valley)</option>
                                            <option value="030000000">Region III (Central Luzon)</option>
                                            <option value="040000000">Region IV-A (CALABARZON)</option>
                                            <option value="170000000">MIMAROPA Region</option>
                                            <option value="050000000">Region V (Bicol Region)</option>
                                            <option value="060000000">Region VI (Western Visayas)</option>
                                            <option value="070000000">Region VII (Central Visayas)</option>
                                            <option value="080000000">Region VIII (Eastern Visayas)</option>
                                            <option value="090000000">Region IX (Zamboanga Peninsula)</option>
                                            <option value="100000000">Region X (Northern Mindanao)</option>
                                            <option value="110000000">Region XI (Davao Region)</option>
                                            <option value="120000000">Region XII (SOCCSKSARGEN)</option>
                                            <option value="160000000">Region XIII (Caraga)</option>
                                            <option value="190000000">Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="province" class="form-select form-select-sm" required disabled><option value="">Select Province</option></select>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="city" class="form-select form-select-sm" required disabled><option value="">Select City</option></select>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="barangay" class="form-select form-select-sm" required disabled><option value="">Select Barangay</option></select>
                                    </div>
                                    <div class="col-12">
                                        <input type="text" id="street" class="form-control form-control-sm" placeholder="Street / Unit #">
                                    </div>
                                </div>
                                <input type="hidden" name="address" id="full_address_input">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label_emergency_name">Emergency Contact Name <span class="text-danger">*</span></label>
                            <?php if(!empty($user_emergency_contact_name)): ?>
                                <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($user_emergency_contact_name) ?>" readonly>
                                <input type="hidden" name="emergency_contact_name" value="<?= htmlspecialchars($user_emergency_contact_name) ?>">
                            <?php else: ?>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label_emergency_number">Emergency Contact Number <span class="text-danger">*</span></label>
                            <?php if(!empty($user_emergency_contact_number)): ?>
                                <input type="text" name="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user_emergency_contact_number) ?>" readonly>
                                <input type="hidden" name="emergency_contact_number" value="<?= htmlspecialchars($user_emergency_contact_number) ?>">
                            <?php else: ?>
                                <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" placeholder="e.g. 09123456789" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" required value="<?= htmlspecialchars($user_emergency_contact_number) ?>" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user_name) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user_email) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user_phone) ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Companions Section -->
                <div id="companion_forms_container" style="display:none;"></div>
            </div>

            <!-- Reservation Info -->
            <div class="col-md-7 anim-trigger delay-2 d-flex flex-column gap-4">
                <div class="card card-custom">
                    <div class="card-header fw-bold text-success"><i class="fas fa-bed me-2"></i>Booking Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Room Type <span class="text-danger">*</span></label>
                            <select name="troom" id="troom" class="form-select" required onchange="calculateTotal(); updateRoomOptions(); checkRealTimeAvailability()">
                                <option value="" disabled selected>Select Room Type</option>
                                <option value="6-Bed" <?= ($pre_room == '6-Bed') ? 'selected' : '' ?>>6 Beds Room</option>
                                <option value="4-Bed" <?= ($pre_room == '4-Bed') ? 'selected' : '' ?>>4 Beds Room</option>
                                <option value="Single" <?= ($pre_room == 'Single') ? 'selected' : '' ?>>Single Room</option>
                            </select>
                            <small id="availability_status" class="fw-bold mt-1 d-block"></small>
                        </div>

                        <div class="mb-3" id="bed_pref_div" style="display:none;">
                            <label class="form-label">Bed Preference</label>
                            <?php if(isset($_GET['bed_preference']) && in_array($_GET['bed_preference'], ['Lower Bunk', 'Upper Bunk'])): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($_GET['bed_preference']) ?>" readonly>
                                <input type="hidden" name="bed_preference" value="<?= htmlspecialchars($_GET['bed_preference']) ?>">
                                <small class="text-success"><i class="fas fa-lock me-1"></i> Preference locked from selection</small>
                            <?php else: ?>
                                <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkRealTimeAvailability(); updateCompanionForms();">
                                    <option value="Any">Any</option>
                                    <option value="Lower Bunk">Lower Bunk</option>
                                    <option value="Upper Bunk">Upper Bunk</option>
                                    <option value="Whole Room">Whole Room</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3" id="occupant_count_div" style="display:none;">
                            <label class="form-label">Number of Occupants</label>
                            <select name="occupant_count" id="occupant_count" class="form-select" onchange="updateCompanionForms()">
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Duration <span class="text-danger">*</span></label>
                            <select id="duration_select" class="form-select" onchange="updateCheckoutDate()">
                                <option value="" disabled <?= empty($pre_duration) ? 'selected' : '' ?>>Select Duration</option>
                                <option value="1" <?= ($pre_duration == '1') ? 'selected' : '' ?>>Short Term (1 Month)</option>
                                <option value="6" <?= ($pre_duration == '6') ? 'selected' : '' ?>>Long Term (6 Months Contract)</option>
                                <option value="Daily" <?= ($pre_duration == 'Daily') ? 'selected' : '' ?>>Daily</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check-in Date <span class="text-danger">*</span></label>
                            <?php if($is_extension): ?>
                                <div class="small text-muted mb-1">Current stay ends: <strong><?= $ext_data['end_date'] ?></strong></div>
                            <?php endif; ?>
                            <input type="date" name="cin" id="cin" class="form-control" min="<?= ($is_extension ? $pre_cin : date('Y-m-d')) ?>" value="<?= $pre_cin ?>" required onchange="updateMinCheckout(); updateCheckoutDate(); calculateTotal(); checkRealTimeAvailability()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check-out Date <span class="text-danger">*</span></label>
                            <input type="date" name="cout" id="cout" class="form-control" min="<?= date('Y-m-d', strtotime($pre_cin . ' +1 day')) ?>" required onchange="updateDurationFromDates()">
                        </div>

                        <div class="utility-block">
                            <div class="d-flex justify-content-between mb-2" style="cursor: pointer;" onclick="document.getElementById('cin').showPicker()" title="Click to change date">
                                <span>Check-in:</span> <strong id="cin_display"><?= date('Y-m-d') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="cursor: pointer;" onclick="document.getElementById('cout').showPicker()" title="Click to change date">
                                <span>Check-out:</span> <strong id="cout_display">-</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Duration:</span> <strong id="duration_display">-</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Utilities Policy:</span> <strong id="utility_display">-</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Security Deposit:</span> <strong class="text-dark" id="sd_display">₱3,000.00 (Refundable)</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">Initial Payment:</span>
                                <span class="h4 text-success fw-bold mb-0">₱<span id="totalAmount">0</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                        <!-- Agreement Section -->
                <div class="card card-custom">
                    <div class="card-header fw-bold text-success"><i class="fas fa-file-contract me-2"></i>Agreement & Policies</div>
                    <div class="card-body">
                        <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="agree_rules" id="agree_rules" required>
    <label class="form-check-label small" for="agree_rules">
        <a href="javascript:void(0)" <?= !empty($house_rules_file) ? 'data-bs-toggle="modal" data-bs-target="#rulesModal"' : 'onclick="alert(\'House rules file not uploaded yet.\'); return false;"' ?> class="text-success text-decoration-none fw-bold" onclick="event.stopPropagation();">I agree to the house rules and regulations</a>
    </label>
</div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="agree_fees" id="agree_fees" required>
                            <label class="form-check-label small" for="agree_fees">
                                I agree to pay utilities, fees, and possible damages
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Electronic Signature (Type Full Name)</label>
                            <input type="text" name="typed_signature" class="form-control" value="<?= htmlspecialchars($user_name) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Date Submitted</label>
                            <input type="text" class="form-control" value="<?= date('F d, Y') ?>" readonly>
                        </div>

                        <button type="button" onclick="confirmReservation()" class="btn btn-custom w-100 py-3 mt-2"><i class="fas fa-check-circle"></i> Confirm Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Rules Modal -->
<div class="modal fade" id="rulesModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center">
                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: cover;" class="me-3 rounded-circle border border-2 border-warning">
                    <h5 class="modal-title fw-bold text-success mb-0">House Rules & Regulations</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0" style="min-height: 400px; background-color: #f8f9fa;">
                <?php if(!empty($house_rules_file)): ?>
                    <?php 
                        $ext = strtolower(pathinfo($house_rules_file, PATHINFO_EXTENSION)); 
                        $file_path = "../uploads/settings/" . htmlspecialchars($house_rules_file);
                    ?>
                    <?php if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                        <img src="<?= $file_path ?>" class="img-fluid w-100 h-auto" alt="House Rules">
                    <?php elseif($ext == 'pdf'): ?>
                        <iframe src="<?= $file_path ?>" width="100%" height="600px" style="border: none;"></iframe>
                    <?php else: ?>
                        <div class="p-5 mt-5">
                            <i class="fas fa-file-word fa-4x text-primary mb-3"></i>
                            <h5 class="text-dark fw-bold">Document Uploaded</h5>
                            <p class="text-muted mb-4">This document type cannot be previewed directly. Please download it to read.</p>
                            <a href="<?= $file_path ?>" class="btn btn-custom px-4 rounded-pill" download><i class="fas fa-download me-2"></i>Download to Read</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top-0 bg-white">
    <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal" onclick="document.getElementById('agree_rules').checked = true;">Close & Agree</button>
</div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    <?php if(isset($_SESSION['swal'])): ?>
    const swalData = <?= json_encode($_SESSION['swal']) ?>;
    Swal.fire({
        title: swalData.title,
        text: swalData.text,
        icon: swalData.icon
    });
    <?php unset($_SESSION['swal']); endif; ?>

    const roomPrices = <?= json_encode($room_prices_js) ?>;
    const isExtension = <?= $is_extension ? 'true' : 'false' ?>;

function toggleCompanyField() {
    var occupation = document.getElementById('occupation');
    var companyDiv = document.getElementById('company_div');
    var schoolIdDiv = document.getElementById('school_id_div');
    var idTypeDiv = document.getElementById('id_type_div');
    var idType = document.getElementById('id_type');
    var companyInput = document.getElementById('company');
    var schoolIdInput = document.getElementById('school_id_image');
    var idImageLabel = document.getElementById('id_image_label');
    
    // Labels
    var labelName = document.getElementById('label_emergency_name');
    var labelNumber = document.getElementById('label_emergency_number');
    
    // Inputs
    var inputName = document.getElementById('emergency_contact_name');
    var inputNumber = document.getElementById('emergency_contact_number');
    
    var companyLabel = document.getElementById('company_label');
    let currentIdType = "<?= $user_id_type ?? '' ?>";

    if (occupation && occupation.value === 'Employed') {
        if(companyDiv) companyDiv.style.display = 'none';
        if(companyInput) companyInput.required = false;
        if(schoolIdDiv) schoolIdDiv.style.display = 'block';
        if(idTypeDiv) idTypeDiv.style.display = 'block';
        if(schoolIdInput) schoolIdInput.required = <?= empty($user_school_id_image) ? 'true' : 'false' ?>;
            if(idType && idType.tagName === 'SELECT') {
            idType.required = true;
                idType.innerHTML = '<option value="" disabled selected>Select Valid ID</option><option value="Company ID">Company ID</option><option value="National ID">National ID</option><option value="Driver\'s License">Driver\'s License</option><option value="Passport">Passport</option><option value="UMID">UMID</option><option value="Postal ID">Postal ID</option><option value="SSS ID">SSS ID</option>';
                if(currentIdType) idType.value = currentIdType;
            }
            if(idImageLabel) idImageLabel.innerHTML = "Valid ID (Image) <span class='text-danger'>*</span>";
            if(labelName) labelName.innerHTML = "Company Name <span class='text-danger'>*</span>";
            if(labelNumber) labelNumber.innerHTML = "Company Number <span class='text-danger'>*</span>";
            if(inputName) inputName.placeholder = "Enter company name";
            if(inputNumber) inputNumber.placeholder = "Enter company contact number";
    } else if (occupation && occupation.value === 'Student') {
        if(companyDiv) companyDiv.style.display = 'block'; 
            if(schoolIdDiv) schoolIdDiv.style.display = 'block';
            if(idTypeDiv) idTypeDiv.style.display = 'block';
        if(companyInput) companyInput.required = true;
        if(schoolIdInput) schoolIdInput.required = <?= empty($user_school_id_image) ? 'true' : 'false' ?>;
            if(idType && idType.tagName === 'SELECT') {
            idType.required = true;
                idType.innerHTML = '<option value="" disabled selected>Select ID Type</option><option value="School ID">School ID</option><option value="National ID">National ID</option><option value="Driver\'s License">Driver\'s License</option><option value="E-FORM">E-FORM</option><option value="Passport">Passport</option>';
                if(currentIdType) idType.value = currentIdType;
            }
            if(idImageLabel) idImageLabel.innerHTML = "Student ID / Valid ID (Image) <span class='text-danger'>*</span>";
        if(companyLabel) companyLabel.innerHTML = "School Name <span class='text-danger'>*</span>";
        if(companyInput) companyInput.placeholder = "Enter your school name";
        if(labelName) labelName.innerHTML = "Guardian Name <span class='text-danger'>*</span>";
        if(labelNumber) labelNumber.innerHTML = "Guardian Contact Number <span class='text-danger'>*</span>";
        if(inputName) inputName.placeholder = "Enter guardian name";
        if(inputNumber) inputNumber.placeholder = "Enter guardian contact number";
    } else {
        if(companyDiv) companyDiv.style.display = 'none';
            if(schoolIdDiv) schoolIdDiv.style.display = 'none';
            if(idTypeDiv) idTypeDiv.style.display = 'none'; 
        if(companyInput) companyInput.required = false;
        if(schoolIdInput) schoolIdInput.required = false;
        if(idType && idType.tagName === 'SELECT') idType.required = false;
        if(labelName) labelName.innerHTML = "Emergency Contact Name <span class='text-danger'>*</span>";
        if(labelNumber) labelNumber.innerHTML = "Emergency Contact Number <span class='text-danger'>*</span>";
        if(inputName) inputName.placeholder = "e.g. Juan Dela Cruz";
        if(inputNumber) inputNumber.placeholder = "e.g. 09123456789";
    }
}

// Initialize on page load - check if user is already a student
window.addEventListener('DOMContentLoaded', function() {
    toggleCompanyField();
});

    // PH Location Logic
    const apiBase = "https://psgc.gitlab.io/api";
    document.getElementById('region')?.addEventListener('change', function() {
        const code = this.value;
        const provSelect = document.getElementById('province');
        provSelect.disabled = false; provSelect.innerHTML = '<option value="" selected disabled>Select Province/District</option>';
        let endpoint = `${apiBase}/regions/${code}/provinces/`;
        if(code === '130000000') endpoint = `${apiBase}/regions/${code}/districts/`;
        fetch(endpoint).then(res => res.json()).then(data => {
            provSelect.innerHTML = '<option value="" disabled selected>Select Province/District</option>';
            data.sort((a,b)=>a.name.localeCompare(b.name)).forEach(item => {
                provSelect.innerHTML += `<option value="${item.code}">${item.name}</option>`;
            });
            document.getElementById('city').disabled = true; document.getElementById('city').innerHTML = '<option value="">Select City</option>';
            document.getElementById('barangay').disabled = true; document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
            provSelect.focus();
        }).catch(() => { provSelect.innerHTML = '<option value="">N/A</option>'; });
    });
    document.getElementById('province')?.addEventListener('change', function() {
        const code = this.value;
        const citySelect = document.getElementById('city');
        citySelect.disabled = false; citySelect.innerHTML = '<option value="" selected disabled>Select City/Municipality</option>';
        let type = this.options[this.selectedIndex].text.includes('District') ? 'districts' : 'provinces';
        fetch(`${apiBase}/${type}/${code}/cities-municipalities/`).then(res => res.json()).then(data => {
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            data.sort((a,b)=>a.name.localeCompare(b.name)).forEach(item => {
                citySelect.innerHTML += `<option value="${item.code}">${item.name}</option>`;
            });
            document.getElementById('barangay').disabled = true; document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
            citySelect.focus();
        }).catch(() => { citySelect.innerHTML = '<option value="">N/A</option>'; });
    });
    document.getElementById('city')?.addEventListener('change', function() {
        const code = this.value;
        const barSelect = document.getElementById('barangay');
        barSelect.disabled = false; barSelect.innerHTML = '<option value="" selected disabled>Select Barangay</option>';
        fetch(`${apiBase}/cities-municipalities/${code}/barangays/`).then(res => res.json()).then(data => {
            barSelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            data.sort((a,b)=>a.name.localeCompare(b.name)).forEach(item => {
                barSelect.innerHTML += `<option value="${item.code}">${item.name}</option>`;
            });
            barSelect.focus();
        }).catch(() => { barSelect.innerHTML = '<option value="">N/A</option>'; });
    });

function confirmReservation() {
        const form = document.getElementById('reservationForm');

        // Populate combined address if using dropdowns
        const reg = document.getElementById('region');
        if(reg && !reg.disabled && reg.value !== "") {
            const regionText = reg.options[reg.selectedIndex].text;
            const provEl = document.getElementById('province');
            const provinceText = provEl.options[provEl.selectedIndex]?.text || '';
            const cityEl = document.getElementById('city');
            const cityText = cityEl.options[cityEl.selectedIndex]?.text || '';
            const barEl = document.getElementById('barangay');
            const barangayText = barEl.options[barEl.selectedIndex]?.text || '';
            const street = document.getElementById('street').value;
            const fullAddr = (street ? street + ', ' : '') + barangayText + ', ' + cityText + ', ' + provinceText + ', ' + regionText;
            const addrInput = document.getElementById('full_address_input');
            if(addrInput) addrInput.value = fullAddr;
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        Swal.fire({
            title: isExtension ? 'Confirm Extension?' : 'Confirm Reservation?',
            text: isExtension ? 'Are you sure you want to extend your stay?' : 'Are you sure you want to book this room?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2E7D32',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    // Real-time Availability Checker
    function checkRealTimeAvailability() {
        let room = document.getElementById('troom').value;
        let cin = document.getElementById('cin').value;
        let cout = document.getElementById('cout').value;
        let bedPrefEl = document.querySelector('[name="bed_preference"]');
        let bedPref = bedPrefEl ? bedPrefEl.value : 'Any';
        let statusSpan = document.getElementById('availability_status');
        let genderEl = document.querySelector('[name="gender"]');
        let userGender = genderEl ? genderEl.value : '';

        if(room && cin && cout) {
            if(!userGender) {
                statusSpan.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select your Sex/Gender first';
                statusSpan.className = 'fw-bold mt-1 d-block text-warning';
                return;
            }

            statusSpan.innerHTML = '<span class="skeleton-box" style="max-width: 200px;"></span>';
            statusSpan.className = 'mt-1 d-block';

            // Use existing get_rooms.php API
            fetch(`get_rooms.php?checkin=${cin}&checkout=${cout}`)
                .then(response => response.json())
                .then(data => {
                    window.availableRoomsData = data;
                    // Filter rooms of selected type and matching gender
                    let roomsOfType = data.filter(r => r.room_type === room && (room === 'Single' || bedPref === 'Whole Room' || r.gender === 'Any' || r.gender === userGender));
                    
                    // Check specific availability across all rooms of this type
                    let hasLower = roomsOfType.some(r => r.avail_lower > 0);
                    let hasUpper = roomsOfType.some(r => r.avail_upper > 0);
                    let hasWhole = roomsOfType.some(r => r.available_beds == r.total_beds);
                    let hasAny = roomsOfType.length > 0;

                    // Update Dropdown Options
                    if(bedPrefEl) {
                        let lowerOpt = bedPrefEl.querySelector('option[value="Lower Bunk"]');
                        let upperOpt = bedPrefEl.querySelector('option[value="Upper Bunk"]');
                        let wholeOpt = bedPrefEl.querySelector('option[value="Whole Room"]');

                        if(lowerOpt) { lowerOpt.disabled = !hasLower; lowerOpt.text = hasLower ? "Lower Bunk" : "Lower Bunk (Full)"; }
                        if(upperOpt) { upperOpt.disabled = !hasUpper; upperOpt.text = hasUpper ? "Upper Bunk" : "Upper Bunk (Full)"; }
                        if(wholeOpt) { wholeOpt.disabled = !hasWhole; wholeOpt.text = hasWhole ? "Whole Room" : "Whole Room (Unavailable)"; }
                    }

                    // Check if current selection is valid
                    let available = false;
                    if (bedPref === 'Lower Bunk') available = hasLower;
                    else if (bedPref === 'Upper Bunk') available = hasUpper;
                    else if (bedPref === 'Whole Room') available = hasWhole;
                    else available = hasAny;
                    
                    if(available) {
                        statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Available';
                        statusSpan.className = 'fw-bold mt-1 d-block text-success';
                    } else {
                        statusSpan.innerHTML = '<i class="fas fa-times-circle"></i> Fully Booked';
                        statusSpan.className = 'fw-bold mt-1 d-block text-danger';
                    }
                });
        }
    }

    function updateCompanionForms() {
        let room = document.getElementById('troom').value;
        let bedPrefEl = document.querySelector('[name="bed_preference"]');
        let bedPref = bedPrefEl ? bedPrefEl.value : 'Any';
        let occCountDiv = document.getElementById('occupant_count_div');
        let occCountSelect = document.getElementById('occupant_count');
        let container = document.getElementById('companion_forms_container');

        if (bedPref === 'Whole Room' && (room === '4-Bed' || room === '6-Bed')) {
            if (occCountDiv) occCountDiv.style.display = 'block';
            let maxOcc = room === '4-Bed' ? 4 : 6;
            
            if (occCountSelect && occCountSelect.options.length !== maxOcc) {
                let currentVal = occCountSelect.value;
                occCountSelect.innerHTML = '';
                for(let i=1; i<=maxOcc; i++) {
                    let selected = (currentVal == i) ? 'selected' : (i == maxOcc ? 'selected' : '');
                    occCountSelect.innerHTML += `<option value="${i}" ${selected}>${i} Person${i>1?'s':''}</option>`;
                }
            }

            let count = occCountSelect ? (parseInt(occCountSelect.value) - 1) : (maxOcc - 1);

            if (count > 0) {
                let html = '<div class="card card-custom"><div class="card-header fw-bold text-success"><i class="fas fa-users me-2"></i>Companions Information</div><div class="card-body">';
                html += '<p class="text-muted small mb-4">Please provide the details and valid IDs for your companions to complete this whole room reservation.</p>';
                for(let i=1; i<=count; i++) {
                html += `
                <div class="p-3 mb-4 bg-light border rounded">
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                        <span class="badge bg-success rounded-pill px-3 py-2 fw-bold"><i class="fas fa-user-plus me-1"></i> Companion ${i}</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="comp_lname[]" class="form-control" placeholder="Last Name" required oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="comp_fname[]" class="form-control" placeholder="First Name" required oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="comp_mname[]" class="form-control" placeholder="Middle Name" oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="comp_gender[]" class="form-select" required>
                                <option value="" disabled selected>Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="comp_phone[]" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\\d{9}$" maxlength="11" oninput="let v=this.value.replace(/[^0-9]/g,''); if(v.length>0&&v[0]!=='0')v='0'+v; if(v.length>1&&v[1]!=='9')v='09'+v.substring(2); this.value=v;" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="comp_email[]" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Valid ID (Image) <span class="text-danger">*</span></label>
                            <input type="file" name="comp_id_image[]" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                </div>`;
                }
                html += '</div></div>';
                container.innerHTML = html;
                container.style.display = 'block';
            } else {
                container.innerHTML = '';
                container.style.display = 'none';
            }
        } else {
            if (occCountDiv) occCountDiv.style.display = 'none';
            container.innerHTML = '';
            container.style.display = 'none';
        }
    }

    function updateRoomOptions() {
        let room = document.getElementById('troom').value;
        let prefDiv = document.getElementById('bed_pref_div');
        let bedSelect = document.querySelector('select[name="bed_preference"]');
        let prefLabel = prefDiv.querySelector('label');
        
        if (room === 'Single') {
            prefDiv.style.display = 'block';
            if(prefLabel) prefLabel.innerText = 'Occupants';
            if(bedSelect) {
                let currentVal = bedSelect.value;
                bedSelect.innerHTML = '<option value="Solo">Solo (1 Person)</option><option value="2 Persons">2 Persons</option>';
                if(currentVal === 'Solo' || currentVal === '2 Persons') bedSelect.value = currentVal;
            }
        } else if (room && room.includes('Bed')) {
            prefDiv.style.display = 'block';
            if(prefLabel) prefLabel.innerText = 'Bed Preference';
            if(bedSelect) {
                let currentVal = bedSelect.value;
                bedSelect.innerHTML = '<option value="Any">Any</option><option value="Lower Bunk">Lower Bunk</option><option value="Upper Bunk">Upper Bunk</option><option value="Whole Room">Whole Room</option>';
                if(['Any', 'Lower Bunk', 'Upper Bunk', 'Whole Room'].includes(currentVal)) bedSelect.value = currentVal;
            }
        } else {
            prefDiv.style.display = 'none';
            if(bedSelect) bedSelect.innerHTML = '<option value="Any">Any</option>';
        }
        updateCompanionForms();
    }

    function updateDurationFromDates() {
        let cin = document.getElementById('cin').value;
        let cout = document.getElementById('cout').value;
        let durationSelect = document.getElementById('duration_select');
        let termInput = document.getElementById('term_type');

        if (cin && cout) {
            let d1 = new Date(cin);
            let d2 = new Date(cout);
            let diffTime = d2 - d1;
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

            if (diffDays >= 28) {
                durationSelect.value = '1';
                termInput.value = 'Short';
            } else {
                durationSelect.value = 'Daily';
                termInput.value = 'Daily';
            }
            calculateTotal();
            checkRealTimeAvailability();
        }
    }

    function updateCheckoutDate() {
        let duration = document.getElementById('duration_select').value;
        let cinInput = document.getElementById('cin');
        let coutInput = document.getElementById('cout');
        let termInput = document.getElementById('term_type');
        
        if(cinInput.value) {
            let d = new Date(cinInput.value);
            
            if (duration === '1') {
                // Short Term: 30 Days (Start Date + 29 days)
                d.setDate(d.getDate() + 29);
                coutInput.value = d.toISOString().split('T')[0];
                coutInput.readOnly = false;
                termInput.value = 'Short';
            } else if (duration === '6') {
                // Long Term Rules
                let startDay = d.getDate();
                let targetMonth = d.getMonth(); // 0-11
                
                if (startDay <= 15) {
                    targetMonth += 5; // End of 6th month inclusive of current
                } else {
                    targetMonth += 6; // End of 6th month after current
                }
                
                // Set to last day of target month
                let endDate = new Date(d.getFullYear(), targetMonth + 1, 0); 
                coutInput.value = endDate.toISOString().split('T')[0];
                coutInput.readOnly = false;
                termInput.value = 'Long';
            } else {
                // Daily
                d.setDate(d.getDate() + 1);
                coutInput.value = d.toISOString().split('T')[0];
                coutInput.readOnly = false;
                termInput.value = 'Daily';
            }
            
            calculateTotal();
            checkRealTimeAvailability();
        }
    }

    function updateMinCheckout() {
        let cin = document.getElementById('cin').value;
        if(cin) {
            document.getElementById('cin_display').innerText = cin;
            let d = new Date(cin);
            d.setDate(d.getDate() + 1);
            let minCout = d.toISOString().split('T')[0];
            let coutInput = document.getElementById('cout');
            coutInput.min = minCout;
            
            if(coutInput.value && coutInput.value < minCout) {
                coutInput.value = '';
                calculateTotal();
            }
        }
    }

    function calculateTotal() {
        let room = document.getElementById('troom').value;
        let cinVal = document.getElementById('cin').value;
        let coutVal = document.getElementById('cout').value;
        let bedPrefEl = document.querySelector('[name="bed_preference"]');
        let bedPref = bedPrefEl ? bedPrefEl.value : 'Any';
        let durationType = document.getElementById('duration_select').value;

        // Update displays regardless of room selection
        if (cinVal) document.getElementById('cin_display').innerText = cinVal;
        if (coutVal) document.getElementById('cout_display').innerText = coutVal;

        if (cinVal && coutVal) {
            let d1 = new Date(cinVal);
            let d2 = new Date(coutVal);
            
            // Calculate exact months and remaining days for pricing
            let months = (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth());
            // Adjust if day of month is smaller (e.g. Feb 10 vs Jan 15)
            if (d2.getDate() < d1.getDate()) {
                months--;
            }
            
            let tempDate = new Date(d1);
            tempDate.setMonth(tempDate.getMonth() + months);
            let daysDiff = Math.ceil((d2 - tempDate) / (1000 * 3600 * 24));

            let durText = (months > 0 ? months + " month(s)" : "") + (months > 0 && daysDiff > 0 ? ", " : "") + (daysDiff > 0 || months === 0 ? daysDiff + " day(s)" : "");
            document.getElementById('duration_display').innerText = durText;

            let totalDays = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
            if (totalDays < 1) totalDays = 1;

            if (room) {
                let priceData = roomPrices[room] || {};
                let total = 0;
                let sd = 0;
                if (!isExtension) {
                    if (room === 'Single') {
                        sd = 8000;
                    } else if (durationType === '6') {
                        sd = 3000;
                    } else if (durationType === '1') {
                        sd = 1000;
                    }
                }

                if (durationType === 'Daily') {
                    // Daily Calculation
                    document.getElementById('utility_display').innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Included</span>';
                    document.getElementById('sd_display').innerText = '₱0.00';
                    
                    let dailyRate = 0;
                    if(room === 'Single' || bedPref === 'Whole Room') dailyRate = parseFloat(priceData.daily_room || 0);
                    else dailyRate = parseFloat(priceData.daily_bed || 0);

                    // Fallback to Monthly / 30 if daily rate is 0
                    if(dailyRate === 0) {
                        let monthlyBase = 0;
                        if(room === 'Single') monthlyBase = parseFloat(priceData.short_base || 0);
                        else if(bedPref === 'Whole Room') {
                            monthlyBase = parseFloat(priceData.short_whole || 0);
                            if(monthlyBase === 0) {
                                let beds = (room === '4-Bed') ? 4 : 6;
                                monthlyBase = parseFloat(priceData.short_base || 0) * beds;
                            }
                        } else if(bedPref === 'Upper Bunk') monthlyBase = parseFloat(priceData.short_upper || 0);
                        else monthlyBase = parseFloat(priceData.short_lower || 0);

                        if(monthlyBase === 0) monthlyBase = parseFloat(priceData.short_base || 0);
                        dailyRate = monthlyBase / 30;
                    }
                    
                    total = totalDays * dailyRate;

                } else if (durationType === '6') {
                    // Long Term Calculation
                    document.getElementById('utility_display').innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Excludes Utility Charges</span>';
                    document.getElementById('sd_display').innerText = isExtension ? '₱0.00 (Existing)' : '₱' + sd.toLocaleString() + '.00 (Refundable)';
                    
                    let monthlyRate = 0;
                    if (room === 'Single') {
                        monthlyRate = parseFloat(priceData.long_whole || 0);
                        if(monthlyRate === 0) monthlyRate = parseFloat(priceData.short_base || 0);
                    } else {
                        let upper = parseFloat(priceData.long_upper || 0);
                        let lower = parseFloat(priceData.long_lower || 0);
                        if(bedPref === 'Whole Room') {
                            monthlyRate = parseFloat(priceData.long_whole || 0);
                            if(monthlyRate === 0) monthlyRate = parseFloat(priceData.short_whole || 0);
                            if(monthlyRate === 0) {
                                let beds = (room === '4-Bed') ? 4 : 6;
                                monthlyRate = parseFloat(priceData.short_base || 0) * beds;
                            }
                        } else {
                            monthlyRate = (bedPref === 'Upper Bunk') ? upper : lower;
                            if(monthlyRate === 0) monthlyRate = parseFloat(priceData.short_base || 0);
                        }
                    }

                    // Long Term Base (1 Month) + SD
                    total = monthlyRate + sd;

                } else {
                    // Short Term (1 Month) Calculation
                    document.getElementById('utility_display').innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Included in Rent</span>';
                    document.getElementById('sd_display').innerText = isExtension ? '₱0.00 (Existing)' : '₱' + sd.toLocaleString() + '.00 (Refundable)';
                    
                    let monthlyRate = 0;
                    if (room === 'Single') {
                        monthlyRate = parseFloat(priceData.short_base || 0);
                    } else {
                        let upper = parseFloat(priceData.short_upper || 0);
                        let lower = parseFloat(priceData.short_lower || 0);
                        if(bedPref === 'Whole Room') {
                            monthlyRate = parseFloat(priceData.short_whole || 0);
                            if(monthlyRate === 0) {
                                let beds = (room === '4-Bed') ? 4 : 6;
                                monthlyRate = parseFloat(priceData.short_base || 0) * beds;
                            }
                        } else {
                            monthlyRate = (bedPref === 'Upper Bunk') ? upper : lower;
                            if(monthlyRate === 0) monthlyRate = parseFloat(priceData.short_base || 0);
                        }
                    }
                    total = monthlyRate + sd;
                }

                let formattedTotal = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('totalAmount').innerText = formattedTotal;
            }
        } else {
            document.getElementById('totalAmount').innerText = "0";
            document.getElementById('duration_display').innerText = "-";
            document.getElementById('utility_display').innerText = "-";
            if(!coutVal) document.getElementById('cout_display').innerText = "-";
        }
    }

    // Initialize on page load if values exist
    window.addEventListener('DOMContentLoaded', (event) => {
        if(document.getElementById('troom').value) {
            updateRoomOptions();
        }
        
        // Initialize logic based on pre-selected values
        if(document.getElementById('duration_select').value) {
            updateCheckoutDate();
        } else if(!document.getElementById('cin').value) {
            let today = new Date();
            let yyyy = today.getFullYear();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('cin').value = `${yyyy}-${mm}-${dd}`;
            updateCheckoutDate();
        } else {
            calculateTotal(); // Calculate immediately if dates exist
            checkRealTimeAvailability();
        }
        updateCompanionForms();
    });

    // Notification Logic
    let lastUnreadCount = <?= (int)$unread_count ?>;
    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => {});
                }
                lastUnreadCount = data.unread_count;
            });
    }
    
    setInterval(fetchNotifications, 5000);
    fetchNotifications(); // Initial load

    // Auto Refresh & Sound Logic (Does not reload to preserve form data)
    let lastUpdate = 0;
    function checkUpdates() {
        fetch('../check_updates.php')
        .then(r => r.text())
        .then(t => {
            if(lastUpdate == 0) {
                lastUpdate = t;
            } else if (t > lastUpdate) {
                lastUpdate = t;
                checkRealTimeAvailability(); // Quietly update availability instead of destroying user's form inputs
            }
        });
    }
    setInterval(checkUpdates, 3000);

    // Night Mode Logic
    const currentUserId = "<?= $user_id ?>";
    if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
        document.body.classList.add('night-mode');
    }

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode_' + currentUserId) {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });

    // Custom back button logic to prevent returning to auth pages
    function goBackOrHome() {
        const ref = document.referrer.toLowerCase();
        if (ref.includes('register.php') || ref.includes('login.php') || !ref) {
            window.location.href = 'index.php';
        } else {
            history.back();
        }
    }
</script>

</body>
</html>
