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

$stmt = $conn->prepare("SELECT full_name, email, phone_number, gender FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_name, $user_email, $user_phone, $user_gender);
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
if (isset($_POST['submit'])) {
    $error = ""; // Initialize error variable
    if ($_POST['code1'] != $_POST['code']) {
        $error = "Invalid verification code.";
    } else {
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
        $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower FROM rooms WHERE room_type = ? AND status = 'Available'";
        $r_stmt = $conn->prepare($r_sql);
        $r_stmt->bind_param("s", $troom);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();

        while($room = $r_res->fetch_assoc()) {
            $rid = $room['room_id'];
            // Check availability for dates
            $q = "SELECT COUNT(*) as booked FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin'";
            $c_res = mysqli_query($conn, $q);
            $c_row = mysqli_fetch_assoc($c_res);
            
            $total_booked = $c_row['booked'];
            $capacity = $room['total_beds'];

            if(($capacity - $total_booked) > 0){
                // Room has space. Now check specific bed preference if applicable.
                if (($troom == '4-Bed' || $troom == '6-Bed') && ($bed_preference == 'Lower Bunk' || $bed_preference == 'Upper Bunk')) {
                    // Calculate specific capacity (Assume 50/50 split, odd number goes to Lower)
                    $cap_lower = ceil($capacity / 2);
                    $cap_upper = floor($capacity / 2);
                    $target_cap = ($bed_preference == 'Lower Bunk') ? $cap_lower : $cap_upper;

                    // Count existing bookings for this specific preference
                    $q_pref = "SELECT COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin' AND bed_preference = '$bed_preference'";
                    $pref_res = mysqli_query($conn, $q_pref);
                    $pref_row = mysqli_fetch_assoc($pref_res);
                    $taken_specific = $pref_row['cnt'];

                    if ($taken_specific < $target_cap) {
                        $found_room = $room;
                        break;
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
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
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
            <a href="profile.php" class="text-white text-decoration-none fw-bold">My Profile</a>
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

    <form method="post" enctype="multipart/form-data">
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
                                <select name="bed_preference" class="form-select" onchange="calculateTotal()">
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
                                <input type="text" name="typed_signature" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
                            </div>
                            
                            <div class="mb-1">
                                <label class="form-label small fw-bold">Date Submitted</label>
                                <input type="text" class="form-control bg-white" value="<?= date('F d, Y') ?>" readonly>
                            </div>
                        </div>

                        <!-- Verification -->
                        <div class="bg-light p-3 rounded mb-3">
                            <label class="form-label small fw-bold text-muted">Human Verification</label>
                            <div class="d-flex align-items-center gap-3">
                                <?php $Random_code = rand(); ?>
                                <div class="bg-white border px-3 py-2 rounded fw-bold letter-spacing-2"><?= $Random_code ?></div>
                                <input type="text" name="code1" class="form-control" placeholder="Enter code" required>
                                <input type="hidden" name="code" value="<?= $Random_code ?>">
                            </div>
                        </div>

                        <button type="submit" name="submit" class="btn btn-custom w-100 py-2">Confirm Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    const roomPrices = <?= json_encode($room_prices_js) ?>;

    // Real-time Availability Checker
    function checkRealTimeAvailability() {
        let room = document.getElementById('troom').value;
        let cin = document.getElementById('cin').value;
        let cout = document.getElementById('cout').value;
        let statusSpan = document.getElementById('availability_status');

        if(room && cin && cout) {
            statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
            statusSpan.className = 'fw-bold mt-1 d-block text-muted';

            // Use existing get_rooms.php API
            fetch(`get_rooms.php?checkin=${cin}&checkout=${cout}`)
                .then(response => response.json())
                .then(data => {
                    // Filter data for selected room type
                    let available = data.some(r => r.room_type === room && r.available_beds > 0);
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
</script>

</body>
</html>
