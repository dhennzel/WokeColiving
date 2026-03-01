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

// Ensure gender column exists in users table
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL");
}

// Ensure occupation column exists in users table
$check_occ_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'occupation'");
if(mysqli_num_rows($check_occ_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN occupation VARCHAR(50) DEFAULT NULL");
}

// Ensure company column exists in users table (for employed users)
$check_company_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'company'");
if(mysqli_num_rows($check_company_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN company VARCHAR(100) DEFAULT NULL");
}

// Ensure school_id_image column exists in users table (for student verification)
$check_school_id_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'school_id_image'");
if(mysqli_num_rows($check_school_id_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN school_id_image VARCHAR(255) DEFAULT NULL");
}

// Ensure address column exists in users table
$check_addr_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'address'");
if(mysqli_num_rows($check_addr_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
}

// --- Check for Extension Request ---
$is_extension = false;
$ext_data = [];

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
$user_name = '';
$user_email = '';
$user_phone = '';
$user_gender = '';
$user_occupation = '';
$user_address = '';
$user_company = '';
$user_school_id_image = '';
$user_emergency_contact_name = '';
$user_emergency_contact_number = '';

$stmt = $conn->prepare("SELECT full_name, email, phone_number, gender, occupation, address, company, school_id_image, emergency_contact_name, emergency_contact_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_name, $user_email, $user_phone, $user_gender, $user_occupation, $user_address, $user_company, $user_school_id_image, $user_emergency_contact_name, $user_emergency_contact_number);
$stmt->fetch();
$stmt->close();

// Fetch Room Prices dynamically from DB for JS
$room_prices_js = [];
$price_query = mysqli_query($conn, "SELECT room_type, total_price, price_upper, price_lower FROM rooms GROUP BY room_type");
while($row = mysqli_fetch_assoc($price_query)){
    $room_prices_js[$row['room_type']] = [
        'base' => $row['total_price'],
        'upper' => $row['price_upper'],
        'lower' => $row['price_lower']
    ];
}

// Handle Waitlist Join
if (isset($_POST['join_waitlist'])) {
    $wl_room = $_POST['wl_room'];
    try {
        $check_wl = mysqli_query($conn, "SELECT * FROM waitlist WHERE user_id=$user_id AND room_type='$wl_room'");
    } catch (Exception $e) {
        $check_wl = false;
    }
    if($check_wl && mysqli_num_rows($check_wl) == 0){
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
    $error = ""; // Initialize error variable
        $troom = $_POST['troom'];
        $cin = $_POST['cin'];
        $cout = $_POST['cout'];
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $payment_method = $_POST['payment_method'];
        $agree_rules = isset($_POST['agree_rules']);
        $agree_fees = isset($_POST['agree_fees']);
        $typed_signature = trim($_POST['typed_signature']);

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
        if ($payment_method == 'GCash') {
            $ref_number = $_POST['ref_number'];
            if (empty($ref_number)) {
                $error = "GCash Reference Number is required for GCash payments.";
            }
            if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
                $target_dir = "../uploads/proofs/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $proof_filename = time() . '_' . basename($_FILES["proof_image"]["name"]);
                $target_file = $target_dir . $proof_filename;
                if (!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_file)) {
                    $error = "Sorry, there was an error uploading your proof of payment.";
                }
            } else {
                $error = "Proof of payment is required for GCash payments.";
            }
        }
        
        // Find an available room of the selected type
        $found_room = null;
        $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower FROM rooms WHERE room_type = ? AND availability = 'Available' AND is_archived=0";
        $r_stmt = $conn->prepare($r_sql);
        $r_stmt->bind_param("s", $troom);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();

        while($room = $r_res->fetch_assoc()) {
            $rid = $room['room_id'];
            $capacity = $room['total_beds'];
            
            // Get detailed occupancy
            $q_occ = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin' GROUP BY bed_preference";
            $res_occ = mysqli_query($conn, $q_occ);
            
            $occ_lower = 0; $occ_upper = 0; $occ_any = 0; $total_booked = 0;
            while($row_o = mysqli_fetch_assoc($res_occ)){
                $total_booked += $row_o['cnt'];
                if($row_o['bed_preference'] == 'Lower Bunk') $occ_lower += $row_o['cnt'];
                elseif($row_o['bed_preference'] == 'Upper Bunk') $occ_upper += $row_o['cnt'];
                else $occ_any += $row_o['cnt'];
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
                }

                // Accurate Calculation: (Months * Price) + (Remaining Days * Daily Rate)
                $totalAmount = ($calc_months * $monthly_price) + ($calc_days * ($monthly_price / 30));
                $status = "Pending";
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
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference, signature_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iissidsss", $user_id, $room_id, $cin, $cout, $months, $totalAmount, $status, $bed_preference, $sig_img);
                    } catch (Exception $e) { $stmt = false; }
                } else {
                    // Standard insert
                    try {
                        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if($stmt) $stmt->bind_param("iissidss", $user_id, $room_id, $cin, $cout, $months, $totalAmount, $status, $bed_preference);
                    } catch (Exception $e) { $stmt = false; }
                }

                if ($stmt) {
                    $exec_result = $stmt->execute();
                    $stmt->close();
                    $reservation_id = $conn->insert_id;
                    
                    // Link to original reservation if extension
                    if($is_extension && $reservation_id){
                        mysqli_query($conn, "UPDATE reservations SET extended_from=$eid WHERE reservation_id=$reservation_id");
                    }
                } else {
                    // Fallback logic for older DB versions omitted for brevity, assuming DB is up to date
                    $error = "Database Error: Could not prepare statement.";
                }

                if ($exec_result) {
                    // Insert Payment Record
                    $pay_status = ($payment_method == 'GCash' || $payment_method == 'PayPal') ? 'Paid' : 'Unpaid';
                    
                    try {
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, reference_number, proof_image) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
                    } catch (Exception $e) {
                        $pay_stmt = false;
                    }
                    
                    if ($pay_stmt) {
                        $pay_stmt->bind_param("idssss", $reservation_id, $totalAmount, $payment_method, $pay_status, $ref_number, $proof_filename);
                        $pay_stmt->execute();
                        $pay_stmt->close();
                    } else {
                        // Fallback if reference_number/proof_image columns missing
                        $pay_stmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, ?, NOW())");
                        if ($pay_stmt) {
                            $pay_stmt->bind_param("idss", $reservation_id, $totalAmount, $payment_method, $pay_status);
                            $pay_stmt->execute();
                            $pay_stmt->close();
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

                    // 2. Notify Admin (Simulated by sending to ID 1 or specific email)
                    // send_notification($conn, 1, "New Reservation from User #$user_id for $troom", "Admin Alert");

                    $_SESSION['swal'] = ['title' => 'Success!', 'text' => 'Reservation successful!', 'icon' => 'success'];
                    header("Location: my_reservations.php");
                    exit;
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            } else {
                // Room is FULL: Logic for Suggestions and Waitlist
                $error = "Sorry, <strong>$troom</strong> is fully booked for these dates.";
                
                // 1. Auto-suggest available rooms
                $suggest_sql = "SELECT DISTINCT room_type FROM rooms WHERE status='Available' AND room_type != '$troom'";
                $suggest_res = mysqli_query($conn, $suggest_sql);
                $suggestions = [];
                while($s_row = mysqli_fetch_assoc($suggest_res)){
                    $suggestions[] = $s_row['room_type'];
                }
                if(!empty($suggestions)){
                    $error .= "<br>💡 <strong>Suggestion:</strong> Try booking: " . implode(", ", $suggestions);
                }
                
                // 2. Enable Waitlist Button
                $show_waitlist = true;
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
    <style>
        :root {
            --primary-green: #2E7D32;
            --dark-green: #1B5E20;
            --accent-yellow: #FBC02D;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .navbar { background: var(--dark-green); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; transition: transform 0.3s; }
        .card-custom:hover { transform: translateY(-5px); }
        
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; padding: 10px 30px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card, body.night-mode .card-custom { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .card-header { background-color: #252525 !important; color: #e0e0e0 !important; border-bottom-color: #333; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .dropdown-item { color: #e0e0e0; }
        body.night-mode .dropdown-item:hover { background-color: #333; }
        body.night-mode .form-control, body.night-mode .form-select, body.night-mode textarea { background-color: #2c2c2c; color: #e0e0e0; border-color: #444; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333; color: #fff; }
        body.night-mode .alert-light { background-color: #2c2c2c; border-color: #333; color: #e0e0e0; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="profile.php" class="text-white text-decoration-none fw-bold position-relative">
                My Profile
                <?php if($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                <?php endif; ?>
            </a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
            <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container mb-5 reveal" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php if($is_extension): ?>
            <h2 class="fw-bold text-warning"><i class="fas fa-history me-2"></i>Extend Your Stay</h2>
        <?php else: ?>
            <h2 class="fw-bold text-success"><i class="fas fa-calendar-plus me-2"></i>Make a Reservation</h2>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-secondary rounded-pill">&larr; Back</a>
    </div>

    <?php if(isset($error)) { ?>
        <div class="alert alert-danger">
            <?= $error ?>
            <?php if(isset($show_waitlist) && $show_waitlist): ?>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="wl_room" value="<?= $_POST['troom'] ?>">
                    <button type="submit" name="join_waitlist" class="btn btn-sm btn-outline-danger fw-bold">Join Waitlist for <?= $_POST['troom'] ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php } ?>

    <form method="post" enctype="multipart/form-data" id="reservationForm">
        <input type="hidden" name="confirm_booking" value="1">
        <?php if($is_extension): ?>
            <input type="hidden" name="extend_id" value="<?= $eid ?>">
        <?php endif; ?>
        <div class="row g-4">
            <!-- Personal Info -->
            <div class="col-md-5">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold text-success"><i class="fas fa-user me-2"></i>Personal Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sex*</label>
                            <?php if(!empty($user_gender)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_gender) ?>" readonly>
                                <input type="hidden" name="gender" value="<?= htmlspecialchars($user_gender) ?>">
                            <?php else: ?>
                                <select name="gender" class="form-select" required>
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
                                <input type="hidden" name="occupation" value="<?= htmlspecialchars($user_occupation) ?>">
                            <?php else: ?>
                                <select name="occupation" id="occupation" class="form-select" required onchange="toggleCompanyField()">
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="Student">Student</option>
                                    <option value="Employed">Employed</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3" id="company_div" style="display: none;">
                            <label class="form-label">Company Name*</label>
                            <?php if(!empty($user_company)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_company) ?>" readonly>
                                <input type="hidden" name="company" value="<?= htmlspecialchars($user_company) ?>">
                            <?php else: ?>
                                <input type="text" name="company" id="company" class="form-control" placeholder="Enter your company name" required>
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
                                <input type="text" name="emergency_contact_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="label_emergency_number">Emergency Contact Number*</label>
                            <?php if(!empty($user_emergency_contact_number)): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_emergency_contact_number) ?>" readonly>
                                <input type="hidden" name="emergency_contact_number" value="<?= htmlspecialchars($user_emergency_contact_number) ?>">
                            <?php else: ?>
                                <input type="text" name="emergency_contact_number" class="form-control" placeholder="e.g. 09123456789" required>
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
            <div class="col-md-7">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold text-success"><i class="fas fa-bed me-2"></i>Booking Details</div>
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
                                <input type="text" class="form-control bg-white" value="<?= htmlspecialchars($_GET['bed_preference']) ?>" readonly>
                                <input type="hidden" name="bed_preference" value="<?= htmlspecialchars($_GET['bed_preference']) ?>">
                                <small class="text-success"><i class="fas fa-lock me-1"></i> Preference locked from selection</small>
                            <?php else: ?>
                                <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkRealTimeAvailability()">
                                    <option value="Any">Any</option>
                                    <option value="Lower Bunk">Lower Bunk</option>
                                    <option value="Upper Bunk">Upper Bunk</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Duration</label>
                            <select id="duration_select" class="form-select" onchange="updateCheckoutDate()">
                                <option value="custom">Custom Dates</option>
                                <option value="1">1 Month</option>
                                <option value="6">6 Months</option>
                                <option value="12">1 Year</option>
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
                            <input type="date" name="cout" id="cout" class="form-control" min="<?= date('Y-m-d', strtotime($pre_cin . ' +1 day')) ?>" required onchange="calculateTotal(); checkRealTimeAvailability()">
                        </div>

                        <div class="alert alert-light border">
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
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">Total Amount:</span>
                                <span class="h4 text-success fw-bold mb-0">₱<span id="totalAmount">0</span></span>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentDetails()">
                                <option value="Cash">Cash (Pay at Property)</option>
                                <option value="GCash">GCash</option>
                                <option value="PayPal">PayPal</option>
                            </select>
                        </div>

                        <!-- GCash Details -->
                        <div id="gcash_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                            <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash</h6>
                            <p class="small text-muted mb-2">Scan the QR code or send to the number below:</p>
                            <div class="text-center mb-3">
                                <div class="bg-white p-2 d-inline-block border rounded"><img src="../Images/gcash_qr.png" alt="GCash QR Code" style="width: 120px; height: 120px;"></div>
                                <p class="fw-bold mt-1">0967-310-3156 (Woke Coliving)</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Amount to Pay</label>
                                <input type="text" class="form-control" id="gcash_amount_display" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">GCash Reference Number</label>
                                <input type="text" name="ref_number" class="form-control" placeholder="Enter GCash Ref No.">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Upload Proof of Payment (Screenshot)</label>
                                <input type="file" name="proof_image" class="form-control" accept="image/*">
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
                                <input type="text" class="form-control bg-white" value="<?= date('F d, Y') ?>" readonly>
                            </div>
                        </div>

                        <button type="button" onclick="confirmReservation()" class="btn btn-custom w-100 py-2">Confirm Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

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

function toggleCompanyField() {
    var occupation = document.getElementById('occupation');
    var companyDiv = document.getElementById('company_div');
    var schoolIdDiv = document.getElementById('school_id_div');
    var companyInput = document.getElementById('company');
    var schoolIdInput = document.getElementById('school_id_image');
    
    // Labels
    var labelName = document.getElementById('label_emergency_name');
    var labelNumber = document.getElementById('label_emergency_number');
    
    if (occupation && occupation.value === 'Employed') {
        companyDiv.style.display = 'block';
        if(companyInput) companyInput.required = true;
        if(labelName) labelName.innerText = "Emergency Contact/Boss Name*";
        if(labelNumber) labelNumber.innerText = "Emergency Contact/Boss Contact Number*";
    } else {
        companyDiv.style.display = 'none';
        if(companyInput) companyInput.required = false;
        if (occupation && occupation.value === 'Student') {
            if(labelName) labelName.innerText = "Guardian Name*";
            if(labelNumber) labelNumber.innerText = "Guardian Contact Number*";
        } else {
            if(labelName) labelName.innerText = "Emergency Contact Name*";
            if(labelNumber) labelNumber.innerText = "Emergency Contact Number*";
        }
    }
    
    // Toggle School ID field for students
    if (occupation && occupation.value === 'Student') {
        schoolIdDiv.style.display = 'block';
        if(schoolIdInput) schoolIdInput.required = true;
    } else {
        schoolIdDiv.style.display = 'none';
        if(schoolIdInput) schoolIdInput.required = false;
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

        const isExtension = <?= $is_extension ? 'true' : 'false' ?>;
        
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

        if(room && cin && cout) {
            statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
            statusSpan.className = 'fw-bold mt-1 d-block text-muted';

            // Use existing get_rooms.php API
            fetch(`get_rooms.php?checkin=${cin}&checkout=${cout}`)
                .then(response => response.json())
                .then(data => {
                    // Filter data for selected room type
                    let available = data.some(r => {
                        if (r.room_type !== room) return false;
                        if (bedPref === 'Lower Bunk') return r.avail_lower > 0;
                        if (bedPref === 'Upper Bunk') return r.avail_upper > 0;
                        return r.available_beds > 0;
                    });
                    
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
        if (room && room.includes('Bed')) {
            prefDiv.style.display = 'block';
        } else {
            prefDiv.style.display = 'none';
            let bedSelect = document.querySelector('select[name="bed_preference"]');
            if(bedSelect) bedSelect.value = 'Any';
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

    function updateCheckoutDate() {
        let duration = document.getElementById('duration_select').value;
        let cinInput = document.getElementById('cin');
        let coutInput = document.getElementById('cout');
        
        if(duration !== 'custom' && cinInput.value) {
            let d = new Date(cinInput.value);
            d.setMonth(d.getMonth() + parseInt(duration));
            coutInput.value = d.toISOString().split('T')[0];
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
            
            if(days < 1) days = 0;
            document.getElementById('duration_display').innerText = days + " days";

            // Utility Policy Logic (Match PHP rounding: 6 months+ pays utilities)
            let estMonths = Math.round(days / 30);
            if (estMonths >= 6) {
                document.getElementById('utility_display').innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Tenant pays Water & Electric</span>';
            } else {
                document.getElementById('utility_display').innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Included in Rent</span>';
            }

            if (room) {
                let priceData = roomPrices[room] || {};
                let pricePerMonth = 0;

                if (room === 'Single') {
                    pricePerMonth = parseFloat(priceData.base || 0);
                } else {
                    let upper = parseFloat(priceData.upper || 0);
                    let lower = parseFloat(priceData.lower || 0);
                    let base = parseFloat(priceData.base || 0);

                    if (upper === 0) upper = base;
                    if (lower === 0) lower = base;

                    pricePerMonth = (bedPref === 'Upper Bunk') ? upper : lower;
                }

                let total = (months * pricePerMonth) + (daysDiff * (pricePerMonth / 30));
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
            checkRealTimeAvailability();
            calculateTotal();
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

    // Auto Refresh Logic
    let lastUpdate = 0;
    function checkUpdates() {
        fetch('../check_updates.php')
        .then(r => r.text())
        .then(t => {
            if(lastUpdate == 0) lastUpdate = t;
            else if (t > lastUpdate) location.reload();
        });
    }
    setInterval(checkUpdates, 3000); // Check every 3 seconds

    // Night Mode Logic
    if(localStorage.getItem('nightMode') === 'enabled') {
        document.body.classList.add('night-mode');
    }

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode') {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });
</script>

</body>
</html>
