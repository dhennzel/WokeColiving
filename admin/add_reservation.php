<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$error = "";
$success = "";

// Fetch Users
$users = mysqli_query($conn, "SELECT *, CONCAT(last_name, ', ', first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', middle_name), ''), IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), '')) as full_name FROM users WHERE is_archived=0 ORDER BY last_name ASC");

// Ensure gender column exists in users table
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL");
}

// Ensure role column exists
$check_role = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if(mysqli_num_rows($check_role) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
}

// Ensure is_walkin column exists
$check_walkin = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_walkin'");
if(mysqli_num_rows($check_walkin) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_walkin TINYINT(1) DEFAULT 0");
}

// Ensure occupation column exists
$check_occ = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'occupation'");
if(mysqli_num_rows($check_occ) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN occupation VARCHAR(50) DEFAULT NULL");
}

// Ensure company column exists
$check_company = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'company'");
if(mysqli_num_rows($check_company) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN company VARCHAR(100) DEFAULT NULL");
}

// Ensure emergency contact columns exist
$check_em_name = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'emergency_contact_name'");
if(mysqli_num_rows($check_em_name) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN emergency_contact_name VARCHAR(100) DEFAULT NULL");
}

$check_em_num = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'emergency_contact_number'");
if(mysqli_num_rows($check_em_num) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN emergency_contact_number VARCHAR(20) DEFAULT NULL");
}

// Fetch Room Prices for JS
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

// Fetch All Rooms for Modal Selection
$all_rooms = get_all_rooms_with_occupancy($conn);

$active_maint_q = mysqli_query($conn, "SELECT DISTINCT room_id FROM maintenance_requests WHERE status IN ('Pending', 'Scheduled')");
$active_maint_rooms = [];
while($r = mysqli_fetch_assoc($active_maint_q)) $active_maint_rooms[] = $r['room_id'];

$active_house_q = mysqli_query($conn, "SELECT DISTINCT room_id FROM housekeeping_requests WHERE status IN ('Pending', 'Scheduled')");
$active_house_rooms = [];
while($r = mysqli_fetch_assoc($active_house_q)) $active_house_rooms[] = $r['room_id'];

// Check for pre-selected room type
$pre_room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';

// Sidebar counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Initialize form variables for persistence
$pre_cin = date('Y-m-d');
$f_user_type = $_POST['user_type'] ?? 'existing';
$f_user_id = $_POST['user_id'] ?? '';
$f_new_lname = $_POST['new_lname'] ?? '';
$f_new_fname = $_POST['new_fname'] ?? '';
$f_new_mname = $_POST['new_mname'] ?? '';
$f_new_suffix = $_POST['new_suffix'] ?? '';
$f_new_email = $_POST['new_email'] ?? '';
$f_new_phone = $_POST['new_phone'] ?? '';
$f_new_address = $_POST['new_address'] ?? '';
$f_new_gender = $_POST['new_gender'] ?? 'Male';
$f_new_occupation = $_POST['new_occupation'] ?? '';
$f_new_company = $_POST['new_company'] ?? '';
$f_new_em_name = $_POST['new_em_name'] ?? '';
$f_new_em_num = $_POST['new_em_num'] ?? '';
$f_room_type = $_POST['room_type'] ?? ($pre_room_type ?: 'Single');
$f_bed_preference = $_POST['bed_preference'] ?? 'Any';
$f_duration = $_POST['duration_select'] ?? '';
$f_cin = $_POST['cin'] ?? $pre_cin;
$f_cout = $_POST['cout'] ?? '';
$f_pay_method = $_POST['payment_method'] ?? 'Cash';
$f_pay_status = $_POST['payment_status'] ?? 'Unpaid';
$f_term_type = $_POST['term_type'] ?? 'Short';

if(isset($_POST['add_reservation'])){
    $user_type = $_POST['user_type'] ?? 'existing';
    $user_id = 0;
    $account_msg = "";
    $occupation = "";
    $company = "";
    $em_name = "";
    $em_num = "";

    if($user_type == 'new'){
        // Create New User
        $lname = mb_convert_case(trim($_POST['new_lname'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $fname = mb_convert_case(trim($_POST['new_fname'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $mname = mb_convert_case(trim($_POST['new_mname'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $suffix = trim($_POST['new_suffix'] ?? '');
        $name = $lname . ', ' . $fname . ' ' . $mname . ' ' . $suffix;
        $email = trim($_POST['new_email'] ?? '');
        $phone = trim($_POST['new_phone'] ?? '');
        $gender = $_POST['new_gender'] ?? 'Male';
        $address = trim($_POST['new_address'] ?? '');
        $occupation = $_POST['new_occupation'] ?? '';
        $company = trim($_POST['new_company'] ?? '');

        // Validate required fields for new user
        if(empty($lname) || empty($fname) || empty($email) || empty($phone) || empty($occupation)){
            $error = "Please fill in all required guest details (Name, Email, Phone, Occupation).";
        }

        if(empty($error) && !preg_match('/^09\d{9}$/', $phone)){
            $error = "Invalid phone number. Must be 11 digits starting with 09 and no letters allowed.";
        }

        $em_name = trim($_POST['new_em_name'] ?? '');
        $em_num = trim($_POST['new_em_num'] ?? '');
        $raw_pass = !empty($_POST['new_password']) ? $_POST['new_password'] : 'Wokecoliving101';
        $name_regex = "/^[a-zA-Z\sñÑ]+$/";
        
        if (empty($error) && (!preg_match($name_regex, $fname) || !preg_match($name_regex, $lname) || (!empty($mname) && !preg_match($name_regex, $mname)) || (!empty($suffix) && !preg_match($name_regex, $suffix)))) {
            $error = "First, Middle, Last names, and Suffixes should only contain letters and spaces. Signs and numbers are not allowed.";
        } elseif(empty($error) && !empty($_POST['new_password'])){
            $letter_count = preg_match_all('/[a-zA-Z]/', $raw_pass);
            $digit_count = preg_match_all('/[0-9]/', $raw_pass);
            if(strlen($raw_pass) < 6 || strlen($raw_pass) > 8 || $digit_count < 1){
                $error = "Password must be between 6 to 8 characters and contain at least one number.";
            }
        }
        
        $password = password_hash($raw_pass, PASSWORD_DEFAULT);

        // Handle School ID Image Upload (Similar to guest registration)
        $school_id_image = null;
        if ($occupation == 'Student' && isset($_FILES['school_id']) && $_FILES['school_id']['error'] == 0) {
            $ext = pathinfo($_FILES['school_id']['name'], PATHINFO_EXTENSION);
            $school_id_image = time() . "_school_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['school_id']['name']);
            move_uploaded_file($_FILES['school_id']['tmp_name'], "../uploads/proofs/" . $school_id_image);
        } elseif ($occupation == 'Student' && empty($company)) {
            $error = "School name is required for students.";
        }

        if(empty($error)){
            $email_safe = mysqli_real_escape_string($conn, $email);
            $fname_safe = mysqli_real_escape_string($conn, $fname);
            $lname_safe = mysqli_real_escape_string($conn, $lname);
            $mname_safe = mysqli_real_escape_string($conn, $mname);
            $suffix_safe = mysqli_real_escape_string($conn, $suffix);
            $phone_safe = mysqli_real_escape_string($conn, $phone);

            $check_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email_safe'");
            $check_name = mysqli_query($conn, "SELECT user_id FROM users WHERE first_name='$fname_safe' AND last_name='$lname_safe' AND middle_name='$mname_safe' AND suffix='$suffix_safe'");
            $check_phone = mysqli_query($conn, "SELECT user_id FROM users WHERE phone_number='$phone_safe'");

            if(mysqli_num_rows($check_email) > 0){
                $error = "Email address already registered.";
            } elseif(mysqli_num_rows($check_name) > 0) {
                $error = "A guest with this full name is already registered.";
            } elseif(mysqli_num_rows($check_phone) > 0) {
                $error = "Phone number is already registered.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO users (last_name, first_name, middle_name, suffix, email, phone_number, gender, occupation, company, address, password, role, is_walkin, emergency_contact_name, emergency_contact_number, school_id_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssssssssss", $lname, $fname, $mname, $suffix, $email, $phone, $gender, $occupation, $company, $address, $password, $em_name, $em_num, $school_id_image);
                if(mysqli_stmt_execute($stmt)){
                    $user_id = mysqli_insert_id($conn);
                    $account_msg = "Account created for $name (Pass: $raw_pass). ";
                    log_activity($conn, $user_id, "Account Created", "Walk-in account created by $admin_username");
                } else {
                    $error = "Failed to create user account.";
                }
            }
        }
    } else {
        $user_id = (int)$_POST['user_id'];
        $u_q = mysqli_query($conn, "SELECT email, gender, occupation, company, emergency_contact_name, emergency_contact_number FROM users WHERE user_id=$user_id");
        $u_row = mysqli_fetch_assoc($u_q);
        $gender = $u_row['gender'] ?? 'Any';
        $email = $u_row['email'] ?? '';
        $occupation = $u_row['occupation'] ?? '';
        $company = $u_row['company'] ?? '';
        $em_name = $u_row['emergency_contact_name'] ?? '';
        $em_num = $u_row['emergency_contact_number'] ?? '';
    }

    if(!$error && $user_id > 0){
        $room_type = $_POST['room_type'] ?? 'Single';
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $cin = $_POST['cin'] ?? date('Y-m-d');
        $cout = $_POST['cout'] ?? date('Y-m-d', strtotime('+1 day'));
        
        // Calculate accurate duration components
        $d1 = new DateTime($cin);
        $d2 = new DateTime($cout);
        $interval = $d1->diff($d2);
        
        $months = ($interval->y * 12) + $interval->m;
        if ($months == 0 && $interval->d > 0) $months = 1;

        // Find available room
        $specific_room_id = isset($_POST['specific_room_id']) ? (int)$_POST['specific_room_id'] : 0;
        $found_room = null;
        
        if ($specific_room_id > 0) {
            $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, daily_price_bed, daily_price_room, gender FROM rooms WHERE room_id = ? AND is_archived=0";
            $r_stmt = $conn->prepare($r_sql);
            $r_stmt->bind_param("i", $specific_room_id);
        } else {
            $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, daily_price_bed, daily_price_room, gender FROM rooms WHERE room_type = ? AND availability = 'Available' AND is_archived=0";
            $r_stmt = $conn->prepare($r_sql);
            $r_stmt->bind_param("s", $room_type);
        }
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();

        while($room = $r_res->fetch_assoc()) {
            if ($room_type != 'Single' && $bed_preference != 'Whole Room' && $room['gender'] != 'Any' && $gender != 'Any' && $room['gender'] != $gender) {
                continue; // Skip if room gender restriction does not match user's gender
            }
            $rid = $room['room_id'];
            $total_capacity = $room['total_beds'];
            
            // Get counts for specific dates
            $cin_safe = mysqli_real_escape_string($conn, $cin);
            $cout_safe = mysqli_real_escape_string($conn, $cout);
            $q_counts = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout_safe' AND end_date > '$cin_safe' GROUP BY bed_preference";
            $res_counts = mysqli_query($conn, $q_counts);
            
            $occ_upper = 0; $occ_lower = 0; $occ_any = 0; $total_taken = 0;
            
            while($row_c = mysqli_fetch_assoc($res_counts)){
                $cnt = $row_c['cnt'];
                if($row_c['bed_preference'] == 'Whole Room') {
                    $total_taken += $total_capacity;
                    $occ_any += $total_capacity;
                } else {
                    $total_taken += $cnt;
                    if($row_c['bed_preference'] == 'Upper Bunk') $occ_upper += $cnt;
                    elseif($row_c['bed_preference'] == 'Lower Bunk') $occ_lower += $cnt;
                    else $occ_any += $cnt;
                }
            }
            
            // If totally full, skip
            if($total_taken >= $total_capacity) continue;

            // Check specific bed availability
            if(($room_type == '4-Bed' || $room_type == '6-Bed')){
                $cap_upper = floor($total_capacity / 2);
                $cap_lower = ceil($total_capacity / 2);
                
                $avail_upper = max(0, $cap_upper - $occ_upper);
                $avail_lower = max(0, $cap_lower - $occ_lower);

                if($occ_any > 0) {
                    $fill_lower = min($avail_lower, $occ_any);
                    $avail_lower -= $fill_lower;
                    $occ_any -= $fill_lower;
                    
                    $avail_upper -= $occ_any;
                    $avail_upper = max(0, $avail_upper);
                }
                
                if($bed_preference == 'Upper Bunk'){
                    if($avail_upper > 0) { $found_room = $room; break; }
                } elseif($bed_preference == 'Lower Bunk'){
                    if($avail_lower > 0) { $found_room = $room; break; }
                } elseif($bed_preference == 'Whole Room'){
                    if($total_taken == 0) { $found_room = $room; break; }
                } else {
                    if($avail_upper > 0 || $avail_lower > 0) { $found_room = $room; break; }
                }
            } else {
                $found_room = $room; break;
            }
        }

        if($found_room){
            $room_id = $found_room['room_id'];
            
            // Calculate Price
            $monthly_price = $found_room['total_price']; // Default base
            if ($room_type != 'Single') {
                if ($bed_preference == 'Upper Bunk') $monthly_price = ($found_room['price_upper'] > 0) ? $found_room['price_upper'] : $found_room['total_price'];
                elseif ($bed_preference == 'Lower Bunk') $monthly_price = ($found_room['price_lower'] > 0) ? $found_room['price_lower'] : $found_room['total_price'];
                elseif ($bed_preference == 'Whole Room') $monthly_price = ($found_room['price_whole'] > 0) ? $found_room['price_whole'] : ($found_room['total_price'] * $found_room['total_beds']);
            }

            // --- NEW CALCULATION LOGIC ---
            $term_type = $_POST['term_type'] ?? 'Short';
            $totalAmount = 0;
            $security_deposit = 0;
            if ($room_type === 'Single') {
                $security_deposit = 8000;
            } elseif ($term_type === 'Long') {
                $security_deposit = 3000;
            } elseif ($term_type === 'Short') {
                $security_deposit = 1000;
            }

            if ($term_type === 'Daily') {
                $nights = $d1->diff($d2)->days;
                if($nights < 1) $nights = 1;
                
                // Determine Daily Rate (Fallback to Monthly/30 if not set)
                $daily_rate = 0;
                if($room_type == 'Single' || $bed_preference == 'Whole Room') {
                    $daily_rate = $found_room['daily_price_room'];
                } else {
                    $daily_rate = $found_room['daily_price_bed'];
                }

                if($daily_rate <= 0) {
                    $daily_rate = $monthly_price / 30;
                }
                $totalAmount = $nights * $daily_rate;
            } elseif ($term_type === 'Long') {
                // 6 Month Term Logic
                $lt_price = $monthly_price;
                if ($room_type == 'Single') {
                    if ($found_room['long_term_price_whole'] > 0) $lt_price = $found_room['long_term_price_whole'];
                } else {
                    if ($bed_preference == 'Upper Bunk' && $found_room['long_term_price_upper'] > 0) $lt_price = $found_room['long_term_price_upper'];
                    elseif ($bed_preference == 'Lower Bunk' && $found_room['long_term_price_lower'] > 0) $lt_price = $found_room['long_term_price_lower'];
                    elseif ($bed_preference == 'Whole Room') {
                        if($found_room['long_term_price_whole'] > 0) $lt_price = $found_room['long_term_price_whole'];
                        else $lt_price = $monthly_price; // Fallback to short term whole room price
                    }
                }

                $start_day = (int)$d1->format('j');
                $days_in_month = (int)$d1->format('t');
                $daily_rate = $lt_price / $days_in_month;
                $remaining_days = $days_in_month - $start_day + 1;
                $prorated = $daily_rate * $remaining_days;

                $totalAmount = $prorated + $security_deposit;
                
                if ($start_day >= 20) {
                    $totalAmount += $lt_price; // Add 1 Month Advance
                }
            } else {
                // Short Term
                $st_price = $monthly_price;
                $totalAmount = $st_price + $security_deposit;
            }

            // Check for carried over unpaid balances
            $prev_bal_q = mysqli_query($conn, "SELECT SUM(p.amount) as balance FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id = $user_id AND p.payment_status = 'Unpaid'");
            $prev_bal_row = mysqli_fetch_assoc($prev_bal_q);
            $prev_balance = (float)($prev_bal_row['balance'] ?? 0);
            
            $totalAmount += $prev_balance;
            $pay_desc = "Walk-in Booking Payment" . ($prev_balance > 0 ? " (Includes carried over balance: ₱" . number_format($prev_balance, 2) . ")" : "");

            // --- EXTRACT COMPANIONS IF WHOLE ROOM ---
            $companions = [];
            if ($bed_preference == 'Whole Room' && in_array($room_type, ['4-Bed', '6-Bed'])) {
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
                            $c_id_image = time() . '_comp_admin_' . $i . '_' . basename($_FILES["comp_id_image"]["name"][$i]);
                            move_uploaded_file($_FILES["comp_id_image"]["tmp_name"][$i], $target_dir . $c_id_image);
                        }

                        if (!empty($c_lname) && !empty($c_fname)) {
                            $companions[] = ['name' => mysqli_real_escape_string($conn, $c_name), 'first_name' => mysqli_real_escape_string($conn, $c_fname), 'last_name' => mysqli_real_escape_string($conn, $c_lname), 'middle_name' => mysqli_real_escape_string($conn, $c_mname), 'gender' => mysqli_real_escape_string($conn, $c_gender), 'email' => mysqli_real_escape_string($conn, $c_email), 'phone' => mysqli_real_escape_string($conn, $c_phone), 'id_image' => $c_id_image ? mysqli_real_escape_string($conn, $c_id_image) : null];
                        }
                    }
                }
            }
            $companions_json = !empty($companions) ? json_encode($companions) : null;

            // Insert with security deposit
            $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, occupation, company_or_school, contact_person_name, contact_person_number, security_deposit, companions) VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissidsssssds", $user_id, $room_id, $cin, $cout, $months, $totalAmount, $bed_preference, $occupation, $company, $em_name, $em_num, $security_deposit, $companions_json);
            
            if($stmt->execute()){
                $res_id = $conn->insert_id;
                
                sync_resident_profile($conn, $res_id); // Auto-sync newly created resident + companions

                // Mark old unpaid payments as Carried Over
                if($prev_balance > 0) {
                    mysqli_query($conn, "UPDATE payments p JOIN reservations r ON p.reservation_id = r.reservation_id SET p.payment_status='Cancelled', p.description = CONCAT(p.description, ' (Carried over to Reservation #$res_id)') WHERE r.user_id=$user_id AND p.payment_status='Unpaid' AND r.reservation_id != $res_id");
                }
                $pay_method = $_POST['payment_method'] ?? 'Cash';
                $pay_status = $_POST['payment_status'] ?? 'Unpaid';
                
                if($security_deposit > 0) {
                    $rent_part = $totalAmount - $security_deposit;
                    // 1. Security Deposit
                    $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, ?, NOW(), 'Security Deposit')");
                    $pay_stmt->bind_param("idss", $res_id, $security_deposit, $pay_method, $pay_status);
                    $pay_stmt->execute();
                    // 2. Rent Part
                    $rent_desc = ($term_type == 'Daily') ? "Daily Stay Payment" : "First Month Rent";
                    $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, ?, NOW(), ?)");
                    $pay_stmt->bind_param("idsss", $res_id, $rent_part, $pay_method, $pay_status, $rent_desc);
                    $pay_stmt->execute();
                } else {
                    $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, ?, NOW(), ?)");
                    $pay_stmt->bind_param("idsss", $res_id, $totalAmount, $pay_method, $pay_status, $pay_desc);
                    $pay_stmt->execute();
                }

                // Generate the monthly payment schedule for Long Term (6 Months) contracts
                if ($term_type === 'Long') {
                    for ($month_num = 2; $month_num <= 6; $month_num++) {
                        $rem_desc = "Month $month_num Rent";
                        $rem_status = "Unpaid";
                        $rem_date = date('Y-m-d H:i:s', strtotime($cin . " + " . ($month_num - 1) . " months"));
                        
                        $pay_stmt_rem = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'System', ?, ?, ?)");
                        if ($pay_stmt_rem) {
                            $pay_stmt_rem->bind_param("idsss", $res_id, $lt_price, $rem_status, $rem_date, $rem_desc);
                            $pay_stmt_rem->execute();
                            $pay_stmt_rem->close();
                        }
                    }
                }
                
                log_activity($conn, $user_id, "Walk-in Booking", "Reservation #$res_id created by $admin_username");
                
                
                $success = $account_msg . "Reservation created successfully!";
                $new_reservation_id = $res_id; // For JS Print Popup
            } else {
                $error = "Database Error: " . $conn->error;
            }
        } else {
            $error = "No available rooms of type $room_type for these dates.";
        }
    }
}
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Reservation | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="Admin_JS/add_reservation.js"></script>
    <link rel="stylesheet" href="admin.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .card-form { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 40px; background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { filter: brightness(90%); }
        .room-card-option { cursor: pointer; transition: all 0.2s; border: 2px solid transparent; overflow: hidden; height: 100%; }
        .room-card-option:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .room-card-option.selected { border-color: var(--primary-green); background-color: #e8f5e9; }
        .room-card-option.disabled { opacity: 0.6; pointer-events: none; filter: grayscale(1); }
        .room-card-option img { height: 140px; object-fit: cover; width: 100%; }
        #user_preview_card { transition: all 0.2s; cursor: pointer; }
        #user_preview_card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; border-color: var(--primary-green) !important; }
    </style>
</head>
<body>
<script>
    const currentAdminUser = "<?= htmlspecialchars($admin_username ?? 'admin', ENT_QUOTES, 'UTF-8') ?>";
    window.currentAdminUser = currentAdminUser; // Supply var to admin.js
    if(localStorage.getItem('adminNightMode_' + currentAdminUser) === 'enabled') {
        document.body.classList.add('night-mode');
    }
</script>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-form">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0 text-success">Create Manual Reservation</h3>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill">&larr; Back</a>
                </div>

                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

                <form method="POST" id="reservationForm" enctype="multipart/form-data">
                    <input type="hidden" name="term_type" id="term_type" value="Short">
                    <input type="hidden" name="add_reservation" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Guest Type</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" id="type_existing" value="existing" <?= $f_user_type == 'existing' ? 'checked' : '' ?> onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_existing">Existing User</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" id="type_new" value="new" <?= $f_user_type == 'new' ? 'checked' : '' ?> onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_new">New Walk-in Guest</label>
                            </div>
                        </div>
                    </div>

                    <div id="existing_user_section" class="mb-3">
                        <label class="form-label fw-bold">Select User</label>
                        <select name="user_id" id="existing_user_id" class="form-select" onchange="updateUserPreview(); updateGenderConstraint(); checkAvailability()">
                            <option value="" data-gender="">-- Choose User --</option>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $u['user_id'] ?>" 
                                    data-gender="<?= $u['gender'] ?>" 
                                    data-phone="<?= $u['phone_number'] ?>"
                                    data-occ="<?= $u['occupation'] ?>"
                                    data-comp="<?= $u['company'] ?>"
                                    data-email="<?= $u['email'] ?>"
                                    <?= $f_user_id == $u['user_id'] ? 'selected' : '' ?>><?= $u['full_name'] ?> (<?= $u['email'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <div id="user_preview_card" class="mt-3 p-4 border border-success rounded-4 bg-white shadow-sm" style="display:none; border-left-width: 5px !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-success mb-0"><i class="fas fa-user-check me-2"></i>Resident Profile Summary</h6>
                                <span class="badge bg-success rounded-pill px-3">Registered User</span>
                            </div>
                            <div class="row g-3 small text-muted" id="preview_content">
                                <!-- JS populated -->
                            </div>
                        </div>
                    </div>

                    <div id="new_user_section" class="mb-3 p-3 border rounded bg-light" style="display: <?= $f_user_type == 'new' ? 'block' : 'none' ?>;">
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-user-plus me-2"></i>Guest Details</h6>
                        <div class="row g-2">
                            <div class="col-md-4"><label class="small fw-bold">Last Name <span class="text-danger">*</span></label><input type="text" name="new_lname" class="form-control" value="<?= htmlspecialchars($f_new_lname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                            <div class="col-md-4"><label class="small fw-bold">First Name <span class="text-danger">*</span></label><input type="text" name="new_fname" class="form-control" value="<?= htmlspecialchars($f_new_fname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                            <div class="col-md-4"><label class="small fw-bold">Middle Name</label><input type="text" name="new_mname" class="form-control" value="<?= htmlspecialchars($f_new_mname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Suffix</label>
                                <select name="new_suffix" class="form-select">
                                    <option value="">None</option>
                                    <option value="Jr" <?= $f_new_suffix == 'Jr' ? 'selected' : '' ?>>Jr</option>
                                    <option value="Sr" <?= $f_new_suffix == 'Sr' ? 'selected' : '' ?>>Sr</option>
                                    <option value="II" <?= $f_new_suffix == 'II' ? 'selected' : '' ?>>II</option>
                                    <option value="III" <?= $f_new_suffix == 'III' ? 'selected' : '' ?>>III</option>
                                    <option value="IV" <?= $f_new_suffix == 'IV' ? 'selected' : '' ?>>IV</option>
                                    <option value="V" <?= $f_new_suffix == 'V' ? 'selected' : '' ?>>V</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">Email <span class="text-danger">*</span></label><input type="email" name="new_email" class="form-control" value="<?= htmlspecialchars($f_new_email) ?>"></div>
                            <div class="col-md-6"><label class="small fw-bold">Phone <span class="text-danger">*</span></label><input type="text" name="new_phone" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09" value="<?= htmlspecialchars($f_new_phone) ?>" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;"></div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Gender <span class="text-danger">*</span></label>
                                <select name="new_gender" id="new_gender" class="form-select" onchange="updateGenderConstraint(); checkAvailability()">
                                    <option value="" disabled>Select Gender</option>
                                    <option value="Male" <?= $f_new_gender == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $f_new_gender == 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Occupation Status <span class="text-danger">*</span></label>
                                <select name="new_occupation" id="new_occupation" class="form-select" onchange="toggleNewGuestCompany()">
                                    <option value="" disabled <?= empty($f_new_occupation) ? 'selected' : '' ?>>Select Status</option>
                                    <option value="Student" <?= $f_new_occupation == 'Student' ? 'selected' : '' ?>>Student</option>
                                    <option value="Employed" <?= $f_new_occupation == 'Employed' ? 'selected' : '' ?>>Employed</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="new_company_div" style="display: <?= in_array($f_new_occupation, ['Student', 'Employed']) ? 'block' : 'none' ?>;">
                                <label class="small fw-bold" id="new_company_label">Company/School Name <span class="text-danger">*</span></label>
                                <input type="text" name="new_company" id="new_company" class="form-control" value="<?= htmlspecialchars($f_new_company) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold">Permanent Address <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select id="region" class="form-select form-select-sm">
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
                                <input type="hidden" name="new_address" id="full_address">
                            </div>
                            <div class="col-md-6"><label class="small fw-bold" id="new_em_name_label">Emergency Name <span class="text-danger">*</span></label><input type="text" name="new_em_name" class="form-control" value="<?= htmlspecialchars($f_new_em_name) ?>"></div>
                            <div class="col-md-6"><label class="small fw-bold" id="new_em_num_label">Emergency Contact <span class="text-danger">*</span></label><input type="text" name="new_em_num" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09" value="<?= htmlspecialchars($f_new_em_num) ?>" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;"></div>
                            <div class="col-md-6"><label class="small fw-bold">Password</label><input type="password" name="new_password" class="form-control" placeholder="Default: Wokecoliving101" minlength="6" maxlength="8"></div>
                        </div>
                        <small class="text-muted d-block mt-2">A new account will be created. If password is left blank, it will be <strong>Wokecoliving101</strong> (7 letters, 1 number).</small>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Room Type <span class="text-danger">*</span></label>
                            <select name="room_type" id="room_type" class="form-select" required onchange="updateRoomOptions(); calculateTotal(); checkAvailability(); updateCompanionForms();">
                                <option value="Single" <?= $f_room_type == 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="4-Bed" <?= $f_room_type == '4-Bed' ? 'selected' : '' ?>>4-Bed</option>
                                <option value="6-Bed" <?= $f_room_type == '6-Bed' ? 'selected' : '' ?>>6-Bed</option>
                            </select>
                        <small id="availability_status" class="fw-bold mt-1 d-block" style="cursor: pointer;" onclick="openRoomModal()" title="Click to select room"></small>
                        </div>
                        <div class="col-md-4 mb-3" id="bed_pref_div" style="display: <?= $f_room_type != 'Single' ? 'block' : 'none' ?>;">
                            <label class="form-label fw-bold">Bed Preference</label>
                            <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkAvailability(); updateCompanionForms();">
                                <option value="Any" <?= $f_bed_preference == 'Any' ? 'selected' : '' ?>>Any</option>
                                <option value="Lower Bunk" <?= $f_bed_preference == 'Lower Bunk' ? 'selected' : '' ?>>Lower Bunk</option>
                                <option value="Upper Bunk" <?= $f_bed_preference == 'Upper Bunk' ? 'selected' : '' ?>>Upper Bunk</option>
                                <option value="Whole Room" <?= $f_bed_preference == 'Whole Room' ? 'selected' : '' ?>>Whole Room</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3" id="occupant_count_div" style="display:none;">
                            <label class="form-label fw-bold">Number of Occupants</label>
                            <select name="occupant_count" id="occupant_count" class="form-select" onchange="updateCompanionForms()">
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Duration <span class="text-danger">*</span></label>
                        <select id="duration_select" name="duration_select" class="form-select" required onchange="updateCheckoutDate()">
                            <option value="" disabled <?= empty($f_duration) ? 'selected' : '' ?>>Select Duration</option>
                            <option value="1" <?= $f_duration == '1' ? 'selected' : '' ?>>Short Term (1 Month)</option>
                            <option value="6" <?= $f_duration == '6' ? 'selected' : '' ?>>Long Term (6 Months Contract)</option>
                            <option value="Daily" <?= $f_duration == 'Daily' ? 'selected' : '' ?>>Daily</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-in <span class="text-danger">*</span></label>
                            <input type="date" name="cin" id="cin" class="form-control" required value="<?= htmlspecialchars($f_cin) ?>" onchange="updateCheckoutDate(); calculateTotal(); checkAvailability()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-out <span class="text-danger">*</span></label>
                            <input type="date" name="cout" id="cout" class="form-control" required value="<?= htmlspecialchars($f_cout) ?>" onchange="updateDurationFromDates()">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Cash" <?= $f_pay_method == 'Cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="GCash" <?= $f_pay_method == 'GCash' ? 'selected' : '' ?>>GCash (Online via Dragonpay)</option>
                                <option value="Bank Transfer" <?= $f_pay_method == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="Unpaid" <?= $f_pay_status == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="Paid" <?= $f_pay_status == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Companions Section -->
                    <div id="companion_forms_container" style="display:none;" class="mb-4"></div>

                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Utilities Policy:</span> <strong id="utility_display">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Security Deposit:</span> <strong class="text-dark" id="sd_display">₱3,000.00 (Refundable)</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2"><span class="h5 mb-0">Total: </span><span class="h4 text-success fw-bold">₱<span id="totalAmount">0.00</span></span></div>
                    </div>

                    <input type="hidden" name="specific_room_id" id="specific_room_id" value="<?= htmlspecialchars($_POST['specific_room_id'] ?? '') ?>">
                    
                    <button type="button" class="btn btn-custom w-100 py-2 mt-4" onclick="openRoomModal()">Select Room</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="roomSelectionModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-door-open me-2"></i>Select Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="mb-0">Showing rooms for: <strong id="modalRoomTypeDisplay">All</strong></p>
                    <div class="d-flex align-items-center">
                        <label class="small fw-bold me-2 text-muted">Filter Floor:</label>
                        <select id="roomModalFloorFilter" class="form-select form-select-sm" style="width: 120px;" onchange="filterRoomModal()">
                            <option value="all">All Floors</option>
                            <?php for($i=2; $i<=7; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3" id="roomSelectionGrid">
                    <?php foreach($all_rooms as $room): 
                        $room_display = $room['room_name'];
                        if (!empty($room['room_number'])) {
                            $room_display = "Room " . $room['room_number'];
                        } elseif (is_numeric($room['room_name'])) {
                            $room_display = "Room " . $room['room_name'];
                        }
                        $is_maintenance = ($room['availability'] == 'Maintenance');
                        $click_action = $is_maintenance ? "Swal.fire('Unavailable', 'This room is currently blocked for maintenance.', 'error')" : "selectSpecificRoom({$room['room_id']}, '" . addslashes($room_display) . "')";
                    ?>
                        <div class="col-md-6 col-lg-4 room-select-item" data-type="<?= $room['room_type'] ?>" data-floor="<?= $room['floor'] ?>" data-gender="<?= $room['gender'] ?? 'Male' ?>" data-id="<?= $room['room_id'] ?>">
                            <div class="card room-card-option shadow-sm <?= $is_maintenance ? 'disabled' : '' ?>" onclick="<?= $click_action ?>" style="<?= $is_maintenance ? 'opacity: 0.75;' : '' ?>">
                                <img src="../assets/images/<?= $room['image'] ?>" class="card-img-top" alt="<?= $room['room_name'] ?>">
                                <div class="card-body d-flex flex-column p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><?= $room_display ?></h6>
                                        <div>
                                            <?php if(in_array($room['room_id'], $active_maint_rooms) || $is_maintenance): ?>
                                                <span class="badge bg-danger border" title="Under Maintenance"><i class="fas fa-tools"></i></span>
                                            <?php endif; ?>
                                            <?php if(in_array($room['room_id'], $active_house_rooms)): ?>
                                                <span class="badge bg-info text-dark border" title="Pending Housekeeping"><i class="fas fa-broom"></i></span>
                                            <?php endif; ?>
                                            <span class="badge bg-light text-dark border me-1"><i class="fas fa-venus-mars"></i> <?= $room['gender'] ?? 'Male' ?></span>
                                            <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                        </div>
                                    </div>
                                    <div class="mb-2 small text-muted">
                                        <div class="d-flex justify-content-between"><span>Total Beds:</span> <strong><?= $room['total_beds'] ?></strong></div>
                                        <div class="availability-details mt-1" id="details_<?= $room['room_id'] ?>"></div>
                                    </div>
                                    <div class="mt-auto text-center">
                                        <?php if($is_maintenance): ?>
                                            <span class="badge bg-danger w-100 py-2"><i class="fas fa-ban me-1"></i> Maintenance Block</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary w-100 py-2 room-status-badge" id="status_badge_<?= $room['room_id'] ?>">Check Dates</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="text-muted small">Selected: </span><strong id="modalSelectedRoom" class="text-success">Auto Assign</strong>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="clearRoomSelection()">(Clear)</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" onclick="submitReservation()">Create Reservation</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="admin.js"></script>
<script>
const roomPrices = <?= json_encode($room_prices_js) ?>;

// Initialize Select2 for the user dropdown
$(document).ready(function() {
    $('#existing_user_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Choose User --',
        width: '100%',
        allowClear: true
    });
    
    // Ensure Select2 triggers the onchange event properly
    $('#existing_user_id').on('select2:select', function (e) {
        checkAvailability();
    });
});

function updateUserPreview() {
    const select = document.getElementById('existing_user_id');
    const preview = document.getElementById('user_preview_card');
    const content = document.getElementById('preview_content');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const gender = option.dataset.gender || 'Not set';
        const phone = option.dataset.phone || 'N/A';
        const email = option.dataset.email || 'N/A';
        const occupation = option.dataset.occ || 'N/A';
        const company = option.dataset.comp || 'N/A';
        const userId = select.value;

        content.innerHTML = `
            <div class="col-md-6"><span class="fw-bold text-dark d-block">Email Address</span><i class="fas fa-envelope me-1"></i> ${email}</div>
            <div class="col-md-6"><span class="fw-bold text-dark d-block">Contact Number</span><i class="fas fa-phone me-1"></i> ${phone}</div>
            <div class="col-md-6"><span class="fw-bold text-dark d-block">Gender</span><i class="fas fa-venus-mars me-1"></i> ${gender}</div>
            <div class="col-md-6"><span class="fw-bold text-dark d-block">Occupation</span><i class="fas fa-briefcase me-1"></i> ${occupation}</div>
            <div class="col-md-12"><span class="fw-bold text-dark d-block">Work / School</span><i class="fas fa-building me-1"></i> ${company}</div>
        `;
        preview.style.display = 'block';
        preview.onclick = () => window.open('view_user.php?uid=' + userId, '_blank');
        preview.title = "Click to view full resident profile";
    } else {
        preview.style.display = 'none';
    }
}

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

function toggleUserSection() {
    if(document.getElementById('type_new').checked) {
        document.getElementById('existing_user_section').style.display = 'none';
        document.getElementById('new_user_section').style.display = 'block';
        document.querySelector('select[name="user_id"]').required = false;
        document.querySelector('input[name="new_lname"]').required = true;
        document.querySelector('input[name="new_fname"]').required = true;
        document.querySelector('input[name="new_email"]').required = true;
    } else {
        document.getElementById('existing_user_section').style.display = 'block';
        document.getElementById('new_user_section').style.display = 'none';
        document.querySelector('select[name="user_id"]').required = true;
        document.querySelector('input[name="new_lname"]').required = false;
        document.querySelector('input[name="new_fname"]').required = false;
        document.querySelector('input[name="new_email"]').required = false;
    }
    updateRoomOptions();
}

function toggleNewGuestCompany() {
    const occ = document.getElementById('new_occupation').value;
    const div = document.getElementById('new_company_div');
    const label = document.getElementById('new_company_label');
    const input = document.getElementById('new_company');
    const emNameLabel = document.getElementById('new_em_name_label');
    const emNumLabel = document.getElementById('new_em_num_label');
    const sidDiv = document.getElementById('new_school_id_div');

    if(occ === 'Student') {
        div.style.display = 'block';
        sidDiv.style.display = 'block';
        label.innerHTML = 'School Name <span class="text-danger">*</span>';
        input.required = true;
        emNameLabel.innerHTML = 'Guardian Name <span class="text-danger">*</span>';
        emNumLabel.innerHTML = 'Guardian Contact Number <span class="text-danger">*</span>';
    } else if(occ === 'Employed') {
        div.style.display = 'none'; // Hide for employed
        sidDiv.style.display = 'none';
        label.innerHTML = 'Company Name'; // Label is irrelevant if hidden
        input.required = false; // Not required if hidden
        emNameLabel.innerHTML = 'Company Name <span class="text-danger">*</span>';
        emNumLabel.innerHTML = 'Company Number <span class="text-danger">*</span>';
    } else {
        div.style.display = 'none';
        sidDiv.style.display = 'none';
        input.required = false;
        emNameLabel.innerHTML = 'Emergency Contact Name <span class="text-danger">*</span>';
        emNumLabel.innerHTML = 'Emergency Contact Number <span class="text-danger">*</span>';
    }
}

function updateGenderConstraint() {
    // I-filter ang available rooms base sa bagong piniling kasarian ng walk-in
    if(document.getElementById('roomSelectionModal').classList.contains('show')) filterRoomModal();
}

function getUserGender() {
    if(document.getElementById('type_new').checked) {
        return document.getElementById('new_gender').value;
    } else {
        const select = document.getElementById('existing_user_id');
        if(select.selectedIndex > -1) {
            return select.options[select.selectedIndex].getAttribute('data-gender') || '';
        }
    }
    return '';
}

function updateRoomOptions() {
    let room = document.getElementById('room_type').value;
    let prefDiv = document.getElementById('bed_pref_div');
    let bedSelect = document.querySelector('select[name="bed_preference"]');
    let prefLabel = prefDiv.querySelector('label');
    let isNewUser = document.getElementById('type_new').checked;
    
    if (room === 'Single' && isNewUser) {
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

function updateCompanionForms() {
    let room = document.getElementById('room_type').value;
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
            let html = '<div class="p-4 mb-4 border rounded bg-light"><h6 class="fw-bold text-success mb-3"><i class="fas fa-users me-2"></i>Companions Information</h6>';
            html += '<p class="text-muted small mb-4">Please provide the details and valid IDs for your companions to complete this whole room reservation.</p>';
            for(let i=1; i<=count; i++) {
            html += `
            <div class="p-3 mb-3 bg-white border rounded shadow-sm">
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <span class="badge bg-success rounded-pill px-3 py-2 fw-bold"><i class="fas fa-user-plus me-1"></i> Companion ${i}</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-bold">Last Name <span class="text-danger">*</span></label><input type="text" name="comp_lname[]" class="form-control form-control-sm" required oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">First Name <span class="text-danger">*</span></label><input type="text" name="comp_fname[]" class="form-control form-control-sm" required oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Middle Name</label><input type="text" name="comp_mname[]" class="form-control form-control-sm" oninput="this.value = this.value.replace(/[^a-zA-Z\\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Gender <span class="text-danger">*</span></label><select name="comp_gender[]" class="form-select form-select-sm" required><option value="" disabled selected>Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Contact Number <span class="text-danger">*</span></label><input type="text" name="comp_phone[]" class="form-control form-control-sm" pattern="^09\\d{9}$" maxlength="11" oninput="let v=this.value.replace(/[^0-9]/g,''); if(v.length>0&&v[0]!=='0')v='0'+v; if(v.length>1&&v[1]!=='9')v='09'+v.substring(2); this.value=v;" required></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Email Address</label><input type="email" name="comp_email[]" class="form-control form-control-sm" placeholder="Optional"></div>
                    <div class="col-md-12"><label class="form-label small fw-bold">Valid ID (Image) <span class="text-danger">*</span></label><input type="file" name="comp_id_image[]" class="form-control form-control-sm" accept="image/*" required></div>
                </div>
            </div>`;
            }
            html += '</div>';
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

function updateCheckoutDate() {
    let duration = document.getElementById('duration_select').value;
    let cinInput = document.getElementById('cin');
    let coutInput = document.getElementById('cout');
    let termInput = document.getElementById('term_type');

    // Auto-set Check-in to today if empty when selecting duration
    if (!cinInput.value) {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        cinInput.value = `${yyyy}-${mm}-${dd}`;
    }

        if(cinInput.value && duration) {
        let d = new Date(cinInput.value);

        if (duration === '1') {
            d.setMonth(d.getMonth() + 1);
            coutInput.value = d.toISOString().split('T')[0];
            termInput.value = 'Short';
        } else if (duration === '6') {
            d.setMonth(d.getMonth() + 6);
            coutInput.value = d.toISOString().split('T')[0];
            termInput.value = 'Long';
            } else if (duration === 'Daily') {
            d.setDate(d.getDate() + 1);
            coutInput.value = d.toISOString().split('T')[0];
            termInput.value = 'Daily';
        }
    }
    calculateTotal();
    checkAvailability();
}

function updateDurationFromDates() {
    let cin = document.getElementById('cin').value;
    let cout = document.getElementById('cout').value;
    let durationSelect = document.getElementById('duration_select');
    let termInput = document.getElementById('term_type');

    if (cin && cout) {
        let p1 = cin.split('-');
        let d1 = new Date(parseInt(p1[0]), parseInt(p1[1]) - 1, parseInt(p1[2]));
        let p2 = cout.split('-');
        let d2 = new Date(parseInt(p2[0]), parseInt(p2[1]) - 1, parseInt(p2[2]));
        
        let diffTime = d2.getTime() - d1.getTime();
        let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        // Calculate month difference
        let months = (d2.getFullYear() - d1.getFullYear()) * 12;
        months -= d1.getMonth();
        months += d2.getMonth();
        months = months <= 0 ? 0 : months;

        if (months >= 5 && diffDays > 150) {
             durationSelect.value = '6';
             termInput.value = 'Long';
        } else if (diffDays >= 28) {
             durationSelect.value = '1';
             termInput.value = 'Short';
        } else {
             durationSelect.value = 'Daily';
             termInput.value = 'Daily';
        }
        calculateTotal();
        checkAvailability();
    }
}

function calculateTotal() {
    let room = document.getElementById('room_type').value;
    let cin = document.getElementById('cin').value;
    let cout = document.getElementById('cout').value;
    let bedPref = document.querySelector('select[name="bed_preference"]').value;
    let durationType = document.getElementById('duration_select').value;

        if(room && cin && cout && durationType) {
        let priceData = roomPrices[room] || {};
        let total = 0;
        let sd = 0;
        if (room === 'Single') {
            sd = 8000;
        } else if (durationType === '6') {
            sd = 3000;
        } else if (durationType === '1') {
            sd = 1000;
        }

        if (durationType === 'Daily') {
            document.getElementById('utility_display').innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Included</span>';
            document.getElementById('sd_display').innerText = '₱0.00';
            
            let d1 = new Date(cin);
            let d2 = new Date(cout);
            let days = Math.ceil((d2 - d1) / (1000 * 3600 * 24));
            if(days < 1) days = 1;

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
            document.getElementById('utility_display').innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Excludes Utility Charges</span>';
            document.getElementById('sd_display').innerText = '₱' + sd.toLocaleString() + '.00 (Refundable)';
            
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

            let start = new Date(cin);
            let startDay = start.getDate();
            let daysInMonth = new Date(start.getFullYear(), start.getMonth() + 1, 0).getDate();
            let dailyRate = monthlyRate / daysInMonth;
            let remainingDays = daysInMonth - startDay + 1;
            total = (dailyRate * remainingDays) + sd;
            if (startDay >= 20) total += monthlyRate;

        } else {
            document.getElementById('utility_display').innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Included in Rent</span>';
            document.getElementById('sd_display').innerText = '₱' + sd.toLocaleString() + '.00 (Refundable)';
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

        document.getElementById('totalAmount').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        document.getElementById('totalAmount').innerText = "0.00";
    }
}

let availableRoomsData = []; // Store fetched availability

function checkAvailability() {
    let room = document.getElementById('room_type').value;
    let cin = document.getElementById('cin').value;
    let cout = document.getElementById('cout').value;
    let bedPrefEl = document.querySelector('select[name="bed_preference"]');
    let bedPref = bedPrefEl ? bedPrefEl.value : 'Any';
    let statusSpan = document.getElementById('availability_status');
    let userGender = getUserGender();

    if(room && cin && cout) {
        statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
        statusSpan.className = 'fw-bold mt-1 d-block text-muted';

        fetch(`../users/get_rooms.php?checkin=${cin}&checkout=${cout}`)
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    if (text.toLowerCase().includes('<html')) {
                        window.location.reload();
                    }
                    throw new Error("Invalid response from server");
                }
            })
            .then(data => {
                availableRoomsData = data; // Store for modal
                updateModalAvailability(); // Update modal badges
                
                // Check specific availability across all rooms of this type
                let roomsOfType = data.filter(r => r.room_type === room && (room === 'Single' || bedPref === 'Whole Room' || r.gender === 'Any' || !userGender || userGender === 'Any' || r.gender === userGender));
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
    } else {
        statusSpan.innerHTML = '';
    }
}

function openRoomModal() {
    const form = document.getElementById('reservationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const type = document.getElementById('room_type').value;
    if(!type) { Swal.fire('Select Room Type', 'Please select a room type first.', 'warning'); return; }
    
    document.getElementById('modalRoomTypeDisplay').innerText = type;
    filterRoomModal(); // Apply filters
    updateModalAvailability(); // Ensure badges are correct
    let modalObj = bootstrap.Modal.getOrCreateInstance(document.getElementById('roomSelectionModal'));
    modalObj.show();
}

function filterRoomModal() {
    const floor = document.getElementById('roomModalFloorFilter').value;
    const type = document.getElementById('room_type').value;
    const items = document.querySelectorAll('.room-select-item');
    const userGender = getUserGender();
    const bedPref = document.querySelector('select[name="bed_preference"]').value;
    
    items.forEach(item => {
        const itemType = item.dataset.type;
        const itemFloor = item.dataset.floor;
        const itemGender = item.dataset.gender || 'Male';
        
        if (itemType === type && (floor === 'all' || itemFloor === floor) && 
           (itemType === 'Single' || bedPref === 'Whole Room' || itemGender === 'Any' || !userGender || userGender === 'Any' || itemGender === userGender)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function updateModalAvailability() {
    const bedPref = document.querySelector('select[name="bed_preference"]').value;
    const items = document.querySelectorAll('.room-select-item');
    
    items.forEach(item => {
        const roomId = item.dataset.id;
        const badge = document.getElementById('status_badge_' + roomId);
        const details = document.getElementById('details_' + roomId);
        const card = item.querySelector('.room-card-option');
        
        const roomData = availableRoomsData.find(r => r.room_id == roomId);
        let isAvailable = false;
        let statusText = "Full/Unavailable";
        let badgeClass = "bg-secondary";
        let detailsHtml = "";
        
        if(roomData) {
            if (bedPref === 'Lower Bunk') isAvailable = roomData.avail_lower > 0;
            else if (bedPref === 'Upper Bunk') isAvailable = roomData.avail_upper > 0;
            else if (bedPref === 'Whole Room') isAvailable = (roomData.available_beds == roomData.total_beds);
            else isAvailable = (roomData.available_beds > 0);
            
            if(isAvailable) {
                statusText = `${roomData.available_beds} Beds Free`;
                badgeClass = "bg-success text-white";
            } else {
                badgeClass = "bg-danger text-white";
            }

            if(roomData.room_type !== 'Single') {
                detailsHtml += `<div class="d-flex justify-content-between"><span class="text-muted">Upper:</span> <span class="${roomData.avail_upper > 0 ? 'text-success fw-bold' : 'text-danger'}">${roomData.avail_upper} left</span></div>`;
                detailsHtml += `<div class="d-flex justify-content-between"><span class="text-muted">Lower:</span> <span class="${roomData.avail_lower > 0 ? 'text-success fw-bold' : 'text-danger'}">${roomData.avail_lower} left</span></div>`;
            }
        }
        
        badge.innerText = statusText;
        badge.className = `badge w-100 py-2 room-status-badge ${badgeClass}`;
        if(details) details.innerHTML = detailsHtml;
        
        if(isAvailable) card.classList.remove('disabled');
        else card.classList.add('disabled');
    });
}

function selectSpecificRoom(id, name) {
    document.getElementById('specific_room_id').value = id;
    document.getElementById('modalSelectedRoom').innerText = name;
    
    document.querySelectorAll('.room-card-option').forEach(c => c.classList.remove('selected'));
    const selectedItem = document.querySelector(`.room-select-item[data-id="${id}"] .room-card-option`);
    if(selectedItem) selectedItem.classList.add('selected');
}

function clearRoomSelection() {
    document.getElementById('specific_room_id').value = "";
    document.getElementById('modalSelectedRoom').innerText = "Auto Assign";
    document.querySelectorAll('.room-card-option').forEach(c => c.classList.remove('selected'));
}

function submitReservation() {
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
        document.getElementById('full_address').value = (street ? street + ', ' : '') + barangayText + ', ' + cityText + ', ' + provinceText + ', ' + regionText;
    }
    document.getElementById('reservationForm').submit();
}

<?php if(isset($new_reservation_id)): ?>
Swal.fire({
    title: 'Success!',
    html: '<?= $account_msg ?>Reservation created successfully.<br><br>Do you want to print the receipt?',
    icon: 'success',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-print"></i> Print Receipt',
    cancelButtonText: 'Close'
}).then((result) => {
    if (result.isConfirmed) {
        window.open('view_receipt.php?id=<?= $new_reservation_id ?>', '_blank');
    }
});
<?php endif; ?>

// Initialize on load if room type is pre-selected
window.addEventListener('DOMContentLoaded', (event) => {
    // Form Auto-Save & Restore Logic
    const form = document.getElementById('reservationForm');
    if(form) {
        <?php if(!empty($success)): ?>
        // Clear saved state if reservation was successful
        sessionStorage.removeItem('add_reservation_form_state');
        <?php else: ?>
        // Restore state
        const savedStateStr = sessionStorage.getItem('add_reservation_form_state');
        if (savedStateStr) {
            try {
                const state = JSON.parse(savedStateStr);
                for (const [key, value] of Object.entries(state)) {
                    if (key === 'new_password' || key.startsWith('_')) continue;
                    
                    const elements = form.querySelectorAll(`[name="${key}"]`);
                    if (elements.length > 1 && elements[0].type === 'radio') {
                        elements.forEach(el => { if(el.value === value) el.checked = true; });
                    } else if (elements.length === 1) {
                        elements[0].value = value;
                        if(key === 'user_id' && window.jQuery) {
                            $('#existing_user_id').val(value).trigger('change');
                        }
                    }
                }

                // Restore street manually
                if(state['_street']) {
                    const streetEl = document.getElementById('street');
                    if(streetEl) streetEl.value = state['_street'];
                }

                // Restore Address Dropdowns (Async cascade)
                if (state['_region']) {
                    const reg = document.getElementById('region');
                    if(reg) {
                        reg.value = state['_region'];
                        reg.dispatchEvent(new Event('change'));
                        setTimeout(() => {
                            const prov = document.getElementById('province');
                            if(prov && state['_province']) {
                                prov.value = state['_province'];
                                prov.dispatchEvent(new Event('change'));
                                setTimeout(() => {
                                    const city = document.getElementById('city');
                                    if(city && state['_city']) {
                                        city.value = state['_city'];
                                        city.dispatchEvent(new Event('change'));
                                        setTimeout(() => {
                                            const brgy = document.getElementById('barangay');
                                            if(brgy && state['_barangay']) {
                                                brgy.value = state['_barangay'];
                                            }
                                        }, 600);
                                    }
                                }, 600);
                            }
                        }, 600);
                    }
                }
            } catch (e) { console.error('Error restoring form state:', e); }
        }

        // Save state function
        function saveFormState() {
            const formData = new FormData(form);
            const state = {};
            for (const [key, value] of formData.entries()) {
                if (key !== 'new_password') state[key] = value;
            }
            ['region', 'province', 'city', 'barangay', 'street'].forEach(id => {
                const el = document.getElementById(id);
                if(el) state['_' + id] = el.value;
            });
            sessionStorage.setItem('add_reservation_form_state', JSON.stringify(state));
        }

        form.addEventListener('input', saveFormState);
        form.addEventListener('change', saveFormState);
        if (window.jQuery) { $('#existing_user_id').on('change', saveFormState); }
        <?php endif; ?>
    }

    if(document.getElementById('room_type').value) {
        updateRoomOptions();
    }

    // Re-trigger new guest field logic if values were persisted
    if(document.getElementById('type_new').checked) {
        toggleUserSection();
        toggleNewGuestCompany();
    }
    
    // Re-trigger preview if existing user was persisted
    if(document.getElementById('type_existing').checked && document.getElementById('existing_user_id').value) updateUserPreview();

    // Initialize Dates if empty to trigger calculation
    if(!document.getElementById('cin').value) {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        document.getElementById('cin').value = `${yyyy}-${mm}-${dd}`;
        updateCheckoutDate(); // This sets cout and calls calculateTotal
    } else {
        calculateTotal();
        checkAvailability();
    }
    updateCompanionForms();
});

// Parent Sidebar Badges
document.addEventListener('DOMContentLoaded', function() {
    ['frontDeskSubmenu', 'operationsSubmenu'].forEach(menuId => {
        let menu = document.getElementById(menuId);
        if (menu) {
            let badges = menu.querySelectorAll('.badge');
            let total = 0;
            badges.forEach(b => total += parseInt(b.innerText) || 0);
            if (total > 0) {
                let link = document.querySelector(`[href="#${menuId}"]`);
                if(link) {
                    let icon = link.querySelector('.fa-chevron-down');
                    if(icon) icon.insertAdjacentHTML('beforebegin', `<span class="badge bg-danger rounded-pill me-2 parent-badge">${total}</span>`);
                    link.addEventListener('click', function() { let b = this.querySelector('.parent-badge'); if(b) b.style.setProperty('display', 'none', 'important'); });
                }
            }
        }
    });
});
</script>
</body>
</html>