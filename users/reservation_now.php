<?php
session_start();
include('../db.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'reservation_now.php';
    header("Location: login.php");
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
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_lname, $user_fname, $user_mname, $user_email, $user_phone, $user_gender, $user_occupation, $user_address, $user_company, $user_school_id_image, $user_emergency_contact_name, $user_emergency_contact_number);
$stmt->fetch();
$stmt->close();
$user_name = $user_lname . ', ' . $user_fname . (!empty($user_mname) ? ' ' . $user_mname : '');

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

// Handle Waitlist Join
if (isset($_POST['join_waitlist'])) {
    $wl_room = $_POST['wl_room'];
    $check_wl = mysqli_query($conn, "SELECT * FROM waitlist WHERE user_id=$user_id AND room_type='$wl_room'");
    if(mysqli_num_rows($check_wl) == 0){
        mysqli_query($conn, "INSERT INTO waitlist (user_id, room_type) VALUES ($user_id, '$wl_room')");
        $_SESSION['swal'] = ['title' => 'Waitlist Joined', 'text' => "You have been added to the waitlist for $wl_room. We will notify you when it becomes available.", 'icon' => 'success'];
        header("Location: reservation_now.php");
        exit;
    } else {
        $error = "You are already on the waitlist for this room type.";
    }
}

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");

// Handle Extension (Pre-fill data)
$pre_cin = date("Y-m-d");
$pre_room = "";
$pre_bed = "Any";

if ($is_extension) {
    $pre_cin = $ext_data['end_date']; // Start new booking when old one ends
    $pre_room = $ext_data['room_type'];
} elseif(isset($_GET['room_type'])){
    $pre_room = htmlspecialchars($_GET['room_type']);
}

if(isset($_GET['bed_preference'])){
    $pre_bed = htmlspecialchars($_GET['bed_preference']);
}

// Handle Submission
if (isset($_POST['confirm_booking'])) {
        $troom = $_POST['troom'] ?? '';
        $pre_room = $troom; // Persist room selection
        $cin = $_POST['cin'] ?? '';
        $cout = $_POST['cout'] ?? '';
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $payment_method = $_POST['payment_method'] ?? 'Cash';
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

        // Update Gender if not set
        if(empty($user_gender) && isset($_POST['gender'])){
            $new_gender = mysqli_real_escape_string($conn, $_POST['gender']);
            mysqli_query($conn, "UPDATE users SET gender='$new_gender' WHERE user_id=$user_id");
            $user_gender = $new_gender; // Update local variable
        }

        // Update Occupation if not set
        if(empty($user_occupation) && isset($_POST['occupation'])){
            $new_occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
            mysqli_query($conn, "UPDATE users SET occupation='$new_occupation' WHERE user_id=$user_id");
            $user_occupation = $new_occupation; // Update local variable
        }

        // Update Company if not set (only if employed)
        if(isset($_POST['company']) && !empty($_POST['company'])){
            $new_company = mysqli_real_escape_string($conn, $_POST['company']);
            mysqli_query($conn, "UPDATE users SET company='$new_company' WHERE user_id=$user_id");
            $user_company = $new_company; // Update local variable
        }

        // Update Address if not set
        if(empty($user_address) && isset($_POST['address'])){
            $new_address = mysqli_real_escape_string($conn, $_POST['address']);
            mysqli_query($conn, "UPDATE users SET address='$new_address' WHERE user_id=$user_id");
            $user_address = $new_address; // Update local variable
        }

        // Update Emergency Contact if not set
        if(empty($user_emergency_contact_name) && isset($_POST['emergency_contact_name']) && !empty($_POST['emergency_contact_name'])){
            $new_emergency_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
            mysqli_query($conn, "UPDATE users SET emergency_contact_name='$new_emergency_name' WHERE user_id=$user_id");
            $user_emergency_contact_name = $new_emergency_name;
        }
        if(empty($user_emergency_contact_number) && isset($_POST['emergency_contact_number']) && !empty($_POST['emergency_contact_number'])){
            $new_emergency_number = mysqli_real_escape_string($conn, $_POST['emergency_contact_number']);
            mysqli_query($conn, "UPDATE users SET emergency_contact_number='$new_emergency_number' WHERE user_id=$user_id");
            $user_emergency_contact_number = $new_emergency_number;
        }

        // Handle School ID upload for students
        $school_id_filename = $user_school_id_image; // Keep existing by default
        $is_student = ($user_occupation == 'Student');
        if(isset($_POST['occupation']) && $_POST['occupation'] == 'Student'){
            $is_student = true;
        }
        
        if($is_student){
            // Check if new upload or keep existing
            if(isset($_FILES['school_id_image']) && $_FILES['school_id_image']['error'] == 0) {
                $target_dir = "../uploads/proofs/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $school_id_filename = time() . '_school_' . basename($_FILES["school_id_image"]["name"]);
                $target_file = $target_dir . $school_id_filename;
                if (!move_uploaded_file($_FILES["school_id_image"]["tmp_name"], $target_file)) {
                    $error = "Sorry, there was an error uploading your school ID.";
                }
            } elseif(empty($user_school_id_image)) {
                // Student must upload school ID
                $error = "School ID image is required for students.";
            }
            
            // Save/update school ID if no error
            if(!$error && $school_id_filename){
                mysqli_query($conn, "UPDATE users SET school_id_image='$school_id_filename' WHERE user_id=$user_id");
            }
        }

        if (!$agree_rules || !$agree_fees || empty($typed_signature)) {
            $error = "You must agree to the policies and provide a signature to proceed.";
        }
        $ref_number = null;
        $proof_filename = null;
        
        // Calculate duration based on dates
        $d1 = new DateTime($cin);
        $d2 = new DateTime($cout);
        $interval = $d1->diff($d2);
        
        // Calculate accurate billing components (Months + Remaining Days)
        $calc_months = ($interval->y * 12) + $interval->m;
        $calc_days = $interval->d;
        
        // Store approximate duration for DB record
        $days_total = $d1->diff($d2)->days;
        $months = max(1, round($days_total / 30)); 

        // Handle GCash Payment Details & Upload
        // Handle GCash via Dragonpay - No manual proof needed
        if ($payment_method == 'GCash') {
            // We set placeholder values because the actual payment happens on Dragonpay
            $ref_number = 'PENDING_DRAGONPAY'; 
            $proof_filename = null; 
        }
        
        $specific_room_id = isset($_POST['specific_room_id']) ? (int)$_POST['specific_room_id'] : 0;
        $auto_assigned = ($specific_room_id > 0) ? 0 : 1;

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
            $q_occ = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin' GROUP BY bed_preference";
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
                $initialPayment = 0;
                $contract_price = 0;
                $security_deposit = $is_extension ? 0 : 3000;

                if ($term_type === 'Daily') {
                    // Daily Rate Calculation
                    $nights = $d1->diff($d2)->days;
                    if($nights < 1) $nights = 1;
                    $daily_rate = $found_room['daily_price_bed'] > 0 ? $found_room['daily_price_bed'] : 700; // Fallback
                    if($troom == 'Single') $daily_rate = $found_room['daily_price_room'] > 0 ? $found_room['daily_price_room'] : 1200;
                    if($bed_preference == 'Whole Room') $daily_rate = $found_room['daily_price_room'] > 0 ? $found_room['daily_price_room'] : ($daily_rate * $found_room['total_beds']);
                    
                    $contract_price = $nights * $daily_rate;
                    $initialPayment = $contract_price;
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
                    $initialPayment = $lt_price + $security_deposit;
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
                    $initialPayment = $contract_price;
                    $status = "Pending";
                }

                // Check for carried over unpaid balances from previous reservations
                $prev_bal_q = mysqli_query($conn, "SELECT SUM(p.amount) as balance FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id = $user_id AND p.payment_status = 'Unpaid'");
                $prev_bal_row = mysqli_fetch_assoc($prev_bal_q);
                $prev_balance = (float)($prev_bal_row['balance'] ?? 0);
                
                $initialPayment += $prev_balance; // Add previous debt to initial payment required

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
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, signature_image, auto_assigned, occupation, company_or_school, contact_person_name, contact_person_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iissidsssissis", $user_id, $room_id, $cin, $cout, $months, $contract_price, $status, $bed_preference, $sig_img, $auto_assigned, $user_occupation, $user_company, $user_emergency_contact_name, $user_emergency_contact_number);
                    } catch (Exception $e) { $stmt = false; }
                } else {
                    // Standard insert
                    try {
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, auto_assigned, occupation, company_or_school, contact_person_name, contact_person_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if($stmt) $stmt->bind_param("iissidssissss", $user_id, $room_id, $cin, $cout, $months, $contract_price, $status, $bed_preference, $auto_assigned, $user_occupation, $user_company, $user_emergency_contact_name, $user_emergency_contact_number);
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
                    // Insert first part (Paid or Unpaid depending on method)
                    $p_status = ($payment_method == 'Cash') ? 'Unpaid' : 'Unpaid'; // Dragonpay will update this
                    $p_desc = ($remaining > 0) ? "Reservation Fee / Downpayment" : "Full Payment";
                    
                    $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, ?, NOW(), ?)");
                    $pay_stmt->bind_param("idsss", $reservation_id, $initial_payment, $payment_method, $p_status, $p_desc);
                    $pay_stmt->execute();

<<<<<<< HEAD
                    if ($remaining > 0) {
                        // Insert balance part
                        $bal_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, 'Unpaid', NOW(), ?)");
                        $b_desc = "Remaining Balance for Reservation #$reservation_id";
                        $bal_stmt->bind_param("idss", $reservation_id, $remaining, $payment_method, $b_desc);
                        $bal_stmt->execute();
                    }

                    // Update Dragonpay amount if needed
                    $totalAmount = $initial_payment; 
=======
                    try {
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, reference_number, proof_image, description) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
                    } catch (Exception $e) {
                        $pay_stmt = false;
                    }
                    
                    if ($pay_stmt) {
                        $pay_stmt->bind_param("idsssss", $reservation_id, $initialPayment, $payment_method, $pay_status, $ref_number, $proof_filename, $pay_desc);
                        $pay_stmt->execute();
                        $pay_stmt->close();
                    } else {
                        // Fallback if reference_number/proof_image columns missing
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?, NOW())");
                        if ($pay_stmt) {
                            $pay_stmt->bind_param("idss", $reservation_id, $initialPayment, $payment_method, $pay_status);
                            $pay_stmt->execute();
                            $pay_stmt->close();
                        }
                    }

                    // Generate remaining payments for Long Term (Months 2 to 6)
                    if ($term_type === 'Long') {
                        for ($month_num = 2; $month_num <= 6; $month_num++) {
                            $rem_desc = "Month $month_num Rent";
                            $rem_status = "Unpaid";
                            $rem_date = date('Y-m-d H:i:s', strtotime($cin . " + " . ($month_num - 1) . " months"));
                            
                            $pay_stmt_rem = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', ?, ?, ?)");
                            if ($pay_stmt_rem) {
                                $pay_stmt_rem->bind_param("idsss", $reservation_id, $lt_price, $rem_status, $rem_date, $rem_desc);
                                $pay_stmt_rem->execute();
                                $pay_stmt_rem->close();
                            }
                        }
                    }
>>>>>>> 2160d63382bf5e340b5fffaadf60d5af883c9f62

                    // --- NOTIFICATIONS ---
                    // 1. Notify User
                    $msg_user = "✅ <strong>Reservation Received!</strong><br>Your booking for <strong>$troom</strong> is now <strong>Pending</strong>. Please wait for admin approval.";
                    send_notification($conn, $user_id, $msg_user, "Booking Status");

                    // Log Activity
                    $log_action = $is_extension ? "Reservation Extended" : "Reservation Submitted";
                    log_activity($conn, $user_id, $log_action, "Room: $troom | Status: Pending");
                    trigger_update($conn); // Auto-refresh admin view

                    // 2. Notify Admin (Simulated by sending to ID 1 or specific email)
                    // send_notification($conn, 1, "New Reservation from User #$user_id for $troom", "Admin Alert");

                    // 🚀 DRAGONPAY REDIRECT LOGIC
                    if ($payment_method == 'GCash') {
                        $merchant_id = 'YOUR_MERCHANT_ID'; // Replace with your Dragonpay Merchant ID
                        $secret_key  = 'YOUR_SECRET_KEY';  // Replace with your Dragonpay Password
                        
                        // Transaction details
                        $txn_id      = 'RES-' . $reservation_id; // e.g., RES-142
                        $amount      = number_format((float)$initialPayment, 2, '.', ''); // Strictly 2 decimal places (e.g., 2500.00)
                        $ccy         = 'PHP';
                        $description = 'Woke Coliving Reservation: ' . $troom;
                        $email       = $user_email;

                        // Create the signature
                        $message = "$merchant_id:$txn_id:$amount:$ccy:$description:$email:$secret_key";
                        $digest = sha1($message);

                        // Build the URL (Using the test environment URL for now)
                        $url = "https://test.dragonpay.ph/Pay.aspx?" . 
                               "merchantid=$merchant_id" .
                               "&txnid=$txn_id" .
                               "&amount=$amount" .
                               "&ccy=$ccy" .
                               "&description=" . urlencode($description) .
                               "&email=" . urlencode($email) .
                               "&digest=$digest" .
                               "&procid=GCSH";

                        // Send them to Dragonpay
                        header("Location: $url");
                        exit;
                    } else {
                        // Standard redirect for Cash or PayPal
                        $_SESSION['swal'] = ['title' => 'Success!', 'text' => 'Reservation successful!', 'icon' => 'success'];
                        header("Location: my_reservations.php");
                        exit;
                    }
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            } else {
                // Room is FULL: Automatically add to waitlist
                $troom_safe = mysqli_real_escape_string($conn, $troom);
                $check_wl = mysqli_query($conn, "SELECT id FROM waitlist WHERE user_id=$user_id AND room_type='$troom_safe'");
                if(mysqli_num_rows($check_wl) == 0){
                    mysqli_query($conn, "INSERT INTO waitlist (user_id, room_type) VALUES ($user_id, '$troom_safe')");
                    $_SESSION['swal'] = [
                        'title' => 'Added to Waitlist', 
                        'text' => "Sorry, $troom is currently fully booked for those dates. You have been automatically added to the waitlist and we will notify you once a spot opens up.", 
                        'icon' => 'info'
                    ];
                } else {
                    $_SESSION['swal'] = [
                        'title' => 'Already on Waitlist', 
                        'text' => "Sorry, $troom is still fully booked. You are already on the waitlist for this room type.", 
                        'icon' => 'warning'
                    ];
                }
                header("Location: my_reservations.php");
                exit;
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
        <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if(!empty($error)): ?>
    <div class="alert alert-danger">
        <?= $error ?>
        <?php if(isset($show_waitlist) && $show_waitlist): ?>
            <form method="POST" class="mt-2">
                <input type="hidden" name="wl_room" value="<?= htmlspecialchars($troom) ?>">
                <button type="submit" name="join_waitlist" class="btn btn-sm btn-accent fw-bold mt-2">Join Waitlist for <?= htmlspecialchars($troom) ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="reservationForm">
        <input type="hidden" name="confirm_booking" value="1">
        <input type="hidden" name="term_type" id="term_type" value="Short">
        <?php if($is_extension): ?>
            <input type="hidden" name="extend_id" value="<?= $eid ?>">
        <?php endif; ?>
        <div class="row g-4">
            <!-- Personal Info -->
            <div class="col-md-5 anim-trigger delay-1">
                <div class="card card-custom h-100">
                    <div class="card-header fw-bold text-success"><i class="fas fa-user me-2"></i>Personal Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sex*</label>
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
                            <label class="form-label">Occupation Status*</label>
                            <?php if(!empty($user_occupation)): ?>
                                <input type="text" class="form-control" id="occupation" value="<?= htmlspecialchars($user_occupation) ?>" readonly>
                                <input type="hidden" name="occupation" value="<?= htmlspecialchars($user_occupation) ?>" id="occupation_hidden">
                            <?php else: ?>
                                <select name="occupation" id="occupation" class="form-select" required onchange="toggleCompanyField()">
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="Student">Student</option>
                                    <option value="Employed">Employed</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3" id="company_div" style="display: none;">
                            <label class="form-label" id="company_label">Company / School Name*</label>
                            <?php if(!empty($user_company)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_company) ?>" readonly>
                                <input type="hidden" name="company" value="<?= htmlspecialchars($user_company) ?>">
                            <?php else: ?>
                                <input type="text" name="company" id="company" class="form-control" placeholder="Enter your company or school name" required>
                            <?php endif; ?>
                        </div>
                        <!-- School ID Upload for Students -->
                        <div class="mb-3" id="school_id_div" style="display: none;">
                            <label class="form-label">School ID*</label>
                            <?php if(!empty($user_school_id_image)): ?>
                                <div class="mb-2">
                                    <img src="../uploads/proofs/<?= htmlspecialchars($user_school_id_image) ?>" alt="School ID" style="max-width: 200px; max-height: 150px;" class="border rounded">
                                    <div class="small text-success mt-1"><i class="fas fa-check-circle"></i> School ID already uploaded</div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="school_id_image" id="school_id_image" class="form-control" accept="image/*">
                            <small class="text-muted">Upload a clear photo of your school ID (Valid ID required)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address*</label>
                            <?php if(!empty($user_address)): ?>
                                <textarea class="form-control" readonly><?= htmlspecialchars($user_address) ?></textarea>
                                <input type="hidden" name="address" value="<?= htmlspecialchars($user_address) ?>">
                            <?php else: ?>
                                <textarea name="address" class="form-control" rows="3" placeholder="Enter your full permanent address" required></textarea>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label_emergency_name">Emergency Contact Name*</label>
                            <?php if(!empty($user_emergency_contact_name)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_emergency_contact_name) ?>" readonly>
                                <input type="hidden" name="emergency_contact_name" value="<?= htmlspecialchars($user_emergency_contact_name) ?>">
                            <?php else: ?>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label_emergency_number">Emergency Contact Number*</label>
                            <?php if(!empty($user_emergency_contact_number)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_emergency_contact_number) ?>" readonly>
                                <input type="hidden" name="emergency_contact_number" value="<?= htmlspecialchars($user_emergency_contact_number) ?>">
                            <?php else: ?>
                                <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" placeholder="e.g. 09123456789" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" required value="<?= htmlspecialchars($user_emergency_contact_number) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
            </div>

            <!-- Reservation Info -->
            <div class="col-md-7 anim-trigger delay-2">
                <div class="card card-custom h-100">
                    <div class="card-header fw-bold text-success"><i class="fas fa-bed me-2"></i>Booking Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
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
                                <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkRealTimeAvailability()">
                                    <option value="Any">Any</option>
                                    <option value="Lower Bunk">Lower Bunk</option>
                                    <option value="Upper Bunk">Upper Bunk</option>
                                    <option value="Whole Room">Whole Room</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Duration</label>
                            <select id="duration_select" class="form-select" onchange="updateCheckoutDate()">
                                <option value="" disabled selected>Select Duration</option>
                                <option value="1">Short Term (1 Month)</option>
                                <option value="6">Long Term (6 Months Contract)</option>
                                <option value="Daily">Daily</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check-in Date</label>
                            <?php if($is_extension): ?>
                                <div class="small text-muted mb-1">Current stay ends: <strong><?= $ext_data['end_date'] ?></strong></div>
                            <?php endif; ?>
                            <input type="date" name="cin" id="cin" class="form-control" min="<?= ($is_extension ? $pre_cin : date('Y-m-d')) ?>" value="<?= $pre_cin ?>" required onchange="updateMinCheckout(); updateCheckoutDate(); calculateTotal(); checkRealTimeAvailability()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Check-out Date</label>
                            <input type="date" name="cout" id="cout" class="form-control" min="<?= date('Y-m-d', strtotime($pre_cin . ' +1 day')) ?>" required onchange="updateDurationFromDates()">
                        </div>

                        <div class="utility-block">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Check-in:</span> <strong id="cin_display"><?= date('Y-m-d') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
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

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <?php $pm = $_POST['payment_method'] ?? 'Cash'; ?>
                            <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentDetails()">
                                <option value="Cash" <?= $pm == 'Cash' ? 'selected' : '' ?>>Cash (Pay at Property)</option>
                                <option value="GCash" <?= $pm == 'GCash' ? 'selected' : '' ?>>GCash</option>
                                <option value="PayPal" <?= $pm == 'PayPal' ? 'selected' : '' ?>>PayPal</option>
                            </select>
                        </div>

                        <!-- GCash Details -->
                        <div id="gcash_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
    <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash</h6>
    <p class="small text-muted mb-2">You will be securely redirected to GCash to complete your payment.</p>
    
    <div class="mb-3">
        <label class="form-label small">Amount to Pay</label>
        <input type="text" class="form-control fw-bold text-success" id="gcash_amount_display" readonly>
    </div>
    
    <div class="alert alert-info py-2 small mb-0">
        <i class="fas fa-info-circle me-1"></i> Please do not close the browser until you are redirected back to our site.
    </div>
</div>

                        <!-- PayPal Details -->
                        <div id="paypal_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                            <h6 class="fw-bold text-primary"><i class="fab fa-paypal me-2"></i>Pay via PayPal</h6>
                            <p class="small text-muted mb-2">Send payment to the email below:</p>
                            <div class="text-center mb-3">
                                <p class="fw-bold mt-1 h5">payments@wokecoliving.com</p>
                            </div>
                            <label class="form-label small">Transaction ID</label>
                            <input type="text" name="ref_number_paypal" class="form-control" placeholder="Enter PayPal Transaction ID">
                        </div>

                        <!-- Agreement Section -->
                        <div class="mb-3 p-3 border rounded bg-light">
                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-file-contract me-2"></i>Agreement & Policies</h6>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="agree_rules" id="agree_rules" required>
                                <label class="form-check-label small" for="agree_rules">
                                    I agree to the house rules and regulations
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
                            
                            <div class="mb-1">
                                <label class="form-label small fw-bold">Date Submitted</label>
                                <input type="text" class="form-control" value="<?= date('F d, Y') ?>" readonly>
                            </div>
                        </div>

                        <button type="button" onclick="confirmReservation()" class="btn btn-custom w-100 py-3 mt-3"><i class="fas fa-check-circle"></i> Confirm Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="users_JS/app.js"></script>

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
    var companyInput = document.getElementById('company');
    var schoolIdInput = document.getElementById('school_id_image');
    
    // Labels
    var labelName = document.getElementById('label_emergency_name');
    var labelNumber = document.getElementById('label_emergency_number');
    
    // Inputs
    var inputName = document.getElementById('emergency_contact_name');
    var inputNumber = document.getElementById('emergency_contact_number');
    
    var companyLabel = document.getElementById('company_label');

    if (occupation && occupation.value === 'Employed') {
        companyDiv.style.display = 'none';
        schoolIdDiv.style.display = 'none';
        if(companyInput) companyInput.required = false;
        if(schoolIdInput) schoolIdInput.required = false;
        if(labelName) labelName.innerText = "Company Name*";
        if(labelNumber) labelNumber.innerText = "Company Number*";
        if(inputName) inputName.placeholder = "Enter company name";
        if(inputNumber) inputNumber.placeholder = "Enter company contact number";
    } else if (occupation && occupation.value === 'Student') {
        companyDiv.style.display = 'block'; // Show for student
        schoolIdDiv.style.display = 'block';
        if(companyInput) companyInput.required = true;
        if(schoolIdInput) schoolIdInput.required = <?= empty($user_school_id_image) ? 'true' : 'false' ?>;
        if(companyLabel) companyLabel.innerText = "School Name*";
        if(companyInput) companyInput.placeholder = "Enter your school name";
        if(labelName) labelName.innerText = "Guardian Name*";
        if(labelNumber) labelNumber.innerText = "Guardian Contact Number*";
        if(inputName) inputName.placeholder = "Enter guardian name";
        if(inputNumber) inputNumber.placeholder = "Enter guardian contact number";
    } else {
        companyDiv.style.display = 'none';
        schoolIdDiv.style.display = 'none';
        if(companyInput) companyInput.required = false;
        if(labelName) labelName.innerText = "Emergency Contact Name*";
        if(labelNumber) labelNumber.innerText = "Emergency Contact Number*";
        if(inputName) inputName.placeholder = "e.g. Juan Dela Cruz";
        if(inputNumber) inputNumber.placeholder = "e.g. 09123456789";
    }
}

// Initialize on page load - check if user is already a student
window.addEventListener('DOMContentLoaded', function() {
    toggleCompanyField();
});

function confirmReservation() {
        const form = document.getElementById('reservationForm');
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
                        statusSpan.innerHTML = '<i class="fas fa-times-circle"></i> Fully Booked (Waitlist available on submit)';
                        statusSpan.className = 'fw-bold mt-1 d-block text-danger';
                    }
                });
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
    }

    function togglePaymentDetails() {
        let method = document.getElementById('payment_method').value;
        let gcashDiv = document.getElementById('gcash_div');
        let paypalDiv = document.getElementById('paypal_div');
        
        // Inputs
        let gcashRef = document.querySelector('input[name="ref_number"]');
        let gcashProof = document.querySelector('input[name="proof_image"]');
        let paypalRef = document.querySelector('input[name="ref_number_paypal"]');
        
        gcashDiv.style.display = 'none';
        paypalDiv.style.display = 'none';
        
        if(gcashRef) gcashRef.required = false;
        if(gcashProof) gcashProof.required = false;
        if(paypalRef) paypalRef.required = false;

        if (method === 'GCash') {
            gcashDiv.style.display = 'block';
            if(gcashRef) gcashRef.required = true;
            if(gcashProof) gcashProof.required = true;
        } else if (method === 'PayPal') {
            paypalDiv.style.display = 'block';
            if(paypalRef) paypalRef.required = true;
        }
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
            
            // Calculate remaining days by adding months to start date and finding diff
            let tempDate = new Date(d1);
            tempDate.setMonth(tempDate.getMonth() + months);
            let daysDiff = Math.ceil((d2 - tempDate) / (1000 * 3600 * 24));
            
            let days = Math.ceil((d2 - d1) / (1000 * 3600 * 24)); // Total days for display
            
            if(days < 1) days = 1;
            document.getElementById('duration_display').innerText = days + " days";

            if (room) {
                let priceData = roomPrices[room] || {};
                let total = 0;
                let sd = isExtension ? 0 : 3000;

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
                    
                    total = days * dailyRate;

                } else if (durationType === '6') {
                    // Long Term Calculation
                    document.getElementById('utility_display').innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Excludes Utility Charges</span>';
                    document.getElementById('sd_display').innerText = isExtension ? '₱0.00 (Existing)' : '₱3,000.00 (Refundable)';
                    
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
                    document.getElementById('sd_display').innerText = isExtension ? '₱0.00 (Existing)' : '₱3,000.00 (Refundable)';
                    
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
                document.getElementById('gcash_amount_display').value = '₱ ' + formattedTotal;
            }
        } else {
            document.getElementById('totalAmount').innerText = "0";
            document.getElementById('gcash_amount_display').value = '₱ 0.00';
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
        
        // Initialize Dates if empty to trigger calculation
        if(!document.getElementById('cin').value) {
            let today = new Date();
            let yyyy = today.getFullYear();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('cin').value = `${yyyy}-${mm}-${dd}`;
            updateCheckoutDate(); // This sets cout and calls calculateTotal
        } else {
            calculateTotal(); // Calculate immediately if dates exist
            checkRealTimeAvailability();
        }
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
</script>

</body>
</html>
