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
$users = mysqli_query($conn, "SELECT user_id, CONCAT(last_name, ', ', first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', middle_name), '')) as full_name, email, gender FROM users ORDER BY last_name ASC");

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

// Ensure school_id_image column exists
$check_sid = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'school_id_image'");
if(mysqli_num_rows($check_sid) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN school_id_image VARCHAR(255) DEFAULT NULL");
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

// Check for pre-selected room type
$pre_room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';

// Sidebar counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

if(isset($_POST['add_reservation'])){
    $user_type = $_POST['user_type'];
    $user_id = 0;
    $account_msg = "";

    if($user_type == 'new'){
        // Create New User
        $lname = trim($_POST['new_lname']);
        $fname = trim($_POST['new_fname']);
        $mname = trim($_POST['new_mname']);
        $name = $lname . ', ' . $fname . ' ' . $mname;
        $email = trim($_POST['new_email']);
        $phone = trim($_POST['new_phone']);
        $gender = $_POST['new_gender'];
        $occupation = $_POST['new_occupation'];
        $company = trim($_POST['new_company'] ?? '');

        // Validation for company/school name
        if($occupation == 'Student' && empty($company)){
            $error = "Company/School name is required.";
        }

        // Handle School ID Upload
        $school_id_img = null;
        if(!$error && $occupation == 'Student'){
            if(isset($_FILES['new_school_id_image']) && $_FILES['new_school_id_image']['error'] == 0){
                $target_dir = "../uploads/proofs/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $school_id_img = time() . '_sid_' . basename($_FILES["new_school_id_image"]["name"]);
                move_uploaded_file($_FILES["new_school_id_image"]["tmp_name"], $target_dir . $school_id_img);
            } else {
                $error = "School ID is required for students.";
            }
        }

        $em_name = trim($_POST['new_em_name']);
        $em_num = trim($_POST['new_em_num']);
        $raw_pass = !empty($_POST['new_password']) ? $_POST['new_password'] : '12345678';
        
        if(!empty($_POST['new_password'])){
            if(strlen($raw_pass) > 8){
                $error = "Password must be maximum 8 characters.";
            } elseif(!preg_match('/[a-zA-Z]/', $raw_pass) || !preg_match('/[0-9]/', $raw_pass)){
                $error = "Password must contain at least one letter and one number.";
            }
        }
        
        $password = password_hash($raw_pass, PASSWORD_DEFAULT);

        if(empty($error)){
            $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
            if(mysqli_num_rows($check) > 0){
                $error = "Email address already registered.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO users (last_name, first_name, middle_name, email, phone_number, gender, occupation, company, school_id_image, password, role, is_walkin, emergency_contact_name, emergency_contact_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssssssss", $lname, $fname, $mname, $email, $phone, $gender, $occupation, $company, $school_id_img, $password, $em_name, $em_num);
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
        $u_q = mysqli_query($conn, "SELECT email, gender FROM users WHERE user_id=$user_id");
        $u_row = mysqli_fetch_assoc($u_q);
        $gender = $u_row['gender'] ?? 'Any';
        $email = $u_row['email'] ?? '';
    }

    if(!$error && $user_id > 0){
        $room_type = $_POST['room_type'];
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $cin = $_POST['cin'];
        $cout = $_POST['cout'];
        
        // Calculate duration
        $d1 = new DateTime($cin);
        $d2 = new DateTime($cout);
        $interval = $d1->diff($d2);
        
        // Calculate accurate billing components (Months + Remaining Days)
        $calc_months = ($interval->y * 12) + $interval->m;
        $calc_days = $interval->d;
        
        $days_total = $d1->diff($d2)->days;
        $months = max(1, round($days_total / 30));

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
            if ($bed_preference != 'Whole Room' && $gender != 'Any' && $room['gender'] != $gender) {
                continue; // Skip if room gender restriction does not match user's gender
            }
            $rid = $room['room_id'];
            $total_capacity = $room['total_beds'];
            
            // Get counts for specific dates
            $q_counts = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin' GROUP BY bed_preference";
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
            $security_deposit = 3000;

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

            // Insert
            $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference) VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?)");
            $stmt->bind_param("iissids", $user_id, $room_id, $cin, $cout, $months, $totalAmount, $bed_preference);
            
            if($stmt->execute()){
                $res_id = $conn->insert_id;
                
                $pay_method = $_POST['payment_method'] ?? 'Cash';
                $pay_status = $_POST['payment_status'] ?? 'Unpaid';
                
                if ($pay_method == 'GCash') {
                    $pay_status = 'Unpaid'; // Force unpaid until Dragonpay verifies
                }
                
                $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?, NOW())");
                $pay_stmt->bind_param("idss", $res_id, $totalAmount, $pay_method, $pay_status);
                $pay_stmt->execute();
                
                log_activity($conn, $user_id, "Walk-in Booking", "Reservation #$res_id created by $admin_username");
                
                // 🚀 DRAGONPAY REDIRECT LOGIC
                if ($pay_method == 'GCash') {
                    $merchant_id = 'YOUR_MERCHANT_ID'; // Replace with your Dragonpay Merchant ID
                    $secret_key  = 'YOUR_SECRET_KEY';  // Replace with your Dragonpay Password
                    
                    $txn_id      = 'RES-' . $res_id;
                    $amount      = number_format((float)$totalAmount, 2, '.', '');
                    $ccy         = 'PHP';
                    $description = 'Woke Coliving Reservation: ' . $room_type;
                    
                    $message = "$merchant_id:$txn_id:$amount:$ccy:$description:$email:$secret_key";
                    $digest = sha1($message);

                    $url = "https://test.dragonpay.ph/Pay.aspx?" . 
                           "merchantid=$merchant_id&txnid=$txn_id&amount=$amount&ccy=$ccy&description=" . urlencode($description) . "&email=" . urlencode($email) . "&digest=$digest&procid=GCSH";

                    header("Location: $url");
                    exit;
                }
                
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
                                <input class="form-check-input" type="radio" name="user_type" id="type_existing" value="existing" checked onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_existing">Existing User</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" id="type_new" value="new" onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_new">New Walk-in Guest</label>
                            </div>
                        </div>
                    </div>

                    <div id="existing_user_section" class="mb-3">
                        <label class="form-label fw-bold">Select User</label>
                        <select name="user_id" id="existing_user_id" class="form-select" onchange="checkAvailability()">
                            <option value="" data-gender="">-- Choose User --</option>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $u['user_id'] ?>" data-gender="<?= $u['gender'] ?>"><?= $u['full_name'] ?> (<?= $u['email'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="new_user_section" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-user-plus me-2"></i>Guest Details</h6>
                        <div class="row g-2">
                            <div class="col-md-4"><label class="small fw-bold">Last Name</label><input type="text" name="new_lname" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">First Name</label><input type="text" name="new_fname" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">Middle Name</label><input type="text" name="new_mname" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">Email</label><input type="email" name="new_email" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">Phone</label><input type="text" name="new_phone" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09"></div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Gender</label>
                                <select name="new_gender" id="new_gender" class="form-select" onchange="checkAvailability()">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Occupation Status</label>
                                <select name="new_occupation" id="new_occupation" class="form-select" onchange="toggleNewGuestCompany()">
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="Student">Student</option>
                                    <option value="Employed">Employed</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="new_company_div" style="display:none;">
                                <label class="small fw-bold" id="new_company_label">Company/School Name</label>
                                <input type="text" name="new_company" id="new_company" class="form-control">
                            </div>
                            <div class="col-md-12" id="new_school_id_div" style="display:none;">
                                <label class="small fw-bold">School ID Image</label>
                                <input type="file" name="new_school_id_image" id="new_school_id_image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6"><label class="small fw-bold" id="new_em_name_label">Emergency Contact Name</label><input type="text" name="new_em_name" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold" id="new_em_num_label">Emergency Contact Number</label><input type="text" name="new_em_num" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09"></div>
                            <div class="col-md-6"><label class="small fw-bold">Password</label><input type="password" name="new_password" class="form-control" placeholder="Default: 12345678"></div>
                        </div>
                        <small class="text-muted d-block mt-2">A new account will be created. If password is left blank, it will be <strong>12345678</strong>.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Room Type</label>
                            <select name="room_type" id="room_type" class="form-select" required onchange="updateRoomOptions(); calculateTotal(); checkAvailability()">
                                <option value="Single" <?= $pre_room_type == 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="4-Bed" <?= $pre_room_type == '4-Bed' ? 'selected' : '' ?>>4-Bed</option>
                                <option value="6-Bed" <?= $pre_room_type == '6-Bed' ? 'selected' : '' ?>>6-Bed</option>
                            </select>
                            <small id="availability_status" class="fw-bold mt-1 d-block"></small>
                        </div>
                        <div class="col-md-6 mb-3" id="bed_pref_div" style="display:none;">
                            <label class="form-label fw-bold">Bed Preference</label>
                            <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkAvailability()">
                                <option value="Any">Any</option>
                                <option value="Lower Bunk">Lower Bunk</option>
                                <option value="Upper Bunk">Upper Bunk</option>
                            <option value="Whole Room">Whole Room</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Duration</label>
                        <select id="duration_select" class="form-select" onchange="updateCheckoutDate()">
                            <option value="1">Short Term (1 Month)</option>
                            <option value="6">Long Term (6 Months Contract)</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-in</label>
                            <input type="date" name="cin" id="cin" class="form-control" required onchange="updateCheckoutDate(); calculateTotal(); checkAvailability()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-out</label>
                            <input type="date" name="cout" id="cout" class="form-control" required onchange="updateDurationFromDates()">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash (Online via Dragonpay)</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Utilities Policy:</span> <strong id="utility_display">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Security Deposit:</span> <strong class="text-dark" id="sd_display">₱3,000.00 (Refundable)</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2"><span class="h5 mb-0">Total: </span><span class="h4 text-success fw-bold">₱<span id="totalAmount">0.00</span></span></div>
                    </div>

                    <input type="hidden" name="specific_room_id" id="specific_room_id" value="">
                    
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
                    ?>
                        <div class="col-md-6 col-lg-4 room-select-item" data-type="<?= $room['room_type'] ?>" data-floor="<?= $room['floor'] ?>" data-gender="<?= $room['gender'] ?? 'Male' ?>" data-id="<?= $room['room_id'] ?>">
                            <div class="card room-card-option shadow-sm" onclick="selectSpecificRoom(<?= $room['room_id'] ?>, '<?= addslashes($room_display) ?>')">
                                <img src="../assets/images/<?= $room['image'] ?>" class="card-img-top" alt="<?= $room['room_name'] ?>">
                                <div class="card-body d-flex flex-column p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><?= $room_display ?></h6>
                                        <div>
                                            <span class="badge bg-light text-dark border me-1"><i class="fas fa-venus-mars"></i> <?= $room['gender'] ?? 'Male' ?></span>
                                            <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                        </div>
                                    </div>
                                    <div class="mb-2 small text-muted">
                                        <div class="d-flex justify-content-between"><span>Total Beds:</span> <strong><?= $room['total_beds'] ?></strong></div>
                                        <div class="availability-details mt-1" id="details_<?= $room['room_id'] ?>"></div>
                                    </div>
                                    <div class="mt-auto text-center">
                                        <span class="badge bg-secondary w-100 py-2 room-status-badge" id="status_badge_<?= $room['room_id'] ?>">Check Dates</span>
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
}

function toggleNewGuestCompany() {
    const occ = document.getElementById('new_occupation').value;
    const div = document.getElementById('new_company_div');
    const label = document.getElementById('new_company_label');
    const input = document.getElementById('new_company');
    const sidDiv = document.getElementById('new_school_id_div');
    const sidInput = document.getElementById('new_school_id_image');
    const emNameLabel = document.getElementById('new_em_name_label');
    const emNumLabel = document.getElementById('new_em_num_label');

    if(occ === 'Student') {
        div.style.display = 'block';
        label.innerText = 'School Name';
        input.required = true;
        sidDiv.style.display = 'block';
        sidInput.required = true;
        emNameLabel.innerText = 'Guardian Name';
        emNumLabel.innerText = 'Guardian Contact Number';
    } else if(occ === 'Employed') {
        div.style.display = 'none'; // Hide for employed
        label.innerText = 'Company Name'; // Label is irrelevant if hidden
        input.required = false; // Not required if hidden
        sidDiv.style.display = 'none';
        sidInput.required = false;
        emNameLabel.innerText = 'Company Name';
        emNumLabel.innerText = 'Company Number';
    } else {
        div.style.display = 'none';
        input.required = false;
        sidDiv.style.display = 'none';
        sidInput.required = false;
        emNameLabel.innerText = 'Emergency Contact Name';
        emNumLabel.innerText = 'Emergency Contact Number';
    }
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
    if (room && room.includes('Bed')) {
        prefDiv.style.display = 'block';
    } else {
        prefDiv.style.display = 'none';
        document.querySelector('select[name="bed_preference"]').value = 'Any';
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

    if(cinInput.value) {
        let d = new Date(cinInput.value);

        if (duration === '1') {
            d.setMonth(d.getMonth() + 1);
            coutInput.value = d.toISOString().split('T')[0];
            termInput.value = 'Short';
        } else if (duration === '6') {
            d.setMonth(d.getMonth() + 6);
            coutInput.value = d.toISOString().split('T')[0];
            termInput.value = 'Long';
        } else {
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

    if(room && cin && cout) {
        let priceData = roomPrices[room] || {};
        let total = 0;
        let sd = 3000;

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
            document.getElementById('sd_display').innerText = '₱3,000.00 (Refundable)';
            
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
            document.getElementById('sd_display').innerText = '₱3,000.00 (Refundable)';
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
                let roomsOfType = data.filter(r => r.room_type === room && (bedPref === 'Whole Room' || !userGender || r.gender === userGender));
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
        
        if (itemType === type && (floor === 'all' || itemFloor === floor) && (bedPref === 'Whole Room' || !userGender || itemGender === userGender)) {
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
    if(document.getElementById('room_type').value) {
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
        calculateTotal();
        checkAvailability();
    }
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