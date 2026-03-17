<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Profile Picture Deletion
if (isset($_POST['delete_profile_image'])) {
    header('Content-Type: application/json');

    $old_q = mysqli_query($conn, "SELECT profile_image FROM users WHERE user_id=$user_id");
    $old_row = mysqli_fetch_assoc($old_q);
    
    if (!empty($old_row['profile_image'])) {
        $target_dir = "../uploads/profiles/";
        $file_to_delete = $target_dir . $old_row['profile_image'];
        
        if (file_exists($file_to_delete)) {
            @unlink($file_to_delete);
        }
        
        mysqli_query($conn, "UPDATE users SET profile_image=NULL WHERE user_id=$user_id");
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'No image to delete.']);
    exit;
}
// Handle Profile Picture Upload
if (isset($_POST['cropped_image_data'])) {
    header('Content-Type: application/json'); // Set header early

    $data = $_POST['cropped_image_data'];

    if (strpos($data, 'data:image/png;base64,') === 0) {
        $data = str_replace('data:image/png;base64,', '', $data);
        $data = str_replace(' ', '+', $data);
        $img_data = base64_decode($data);

        if ($img_data) {
            $target_dir = "../uploads/profiles/";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
                    exit;
                }
            }

            $new_filename = "user_" . $user_id . "_" . time() . ".png";
            $target_file = $target_dir . $new_filename;

            if (file_put_contents($target_file, $img_data)) {
                // Delete old image if exists
                $old_q = mysqli_query($conn, "SELECT profile_image FROM users WHERE user_id=$user_id");
                $old_row = mysqli_fetch_assoc($old_q);
                if (!empty($old_row['profile_image']) && file_exists($target_dir . $old_row['profile_image'])) {
                    @unlink($target_dir . $old_row['profile_image']);
                }

                // Update database
                $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image=? WHERE user_id=?");
                mysqli_stmt_bind_param($stmt, "si", $new_filename, $user_id);
                mysqli_stmt_execute($stmt);
                
                echo json_encode(['success' => true, 'file' => $new_filename]);
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid image data.']);
    exit;
}

// Handle Password Change
if(isset($_POST['change_password'])){
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $u_q = mysqli_query($conn, "SELECT password FROM users WHERE user_id=$user_id");
    $user_data = mysqli_fetch_assoc($u_q);

    if(!password_verify($current_pass, $user_data['password'])){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Incorrect current password.', 'icon' => 'error'];
    } elseif($new_pass !== $confirm_pass){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'New passwords do not match.', 'icon' => 'error'];
    } elseif(strlen($new_pass) < 6){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Password must be at least 6 characters.', 'icon' => 'error'];
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE user_id=$user_id");
        $_SESSION['swal'] = ['title' => 'Success', 'text' => 'Password updated successfully.', 'icon' => 'success'];
    }
    header("Location: profile.php");
    exit;
}

// Handle Email Change
if(isset($_POST['change_email'])){
    $new_email = mysqli_real_escape_string($conn, trim($_POST['new_email']));
    $current_pass = $_POST['current_password'];

    $u_q = mysqli_query($conn, "SELECT password FROM users WHERE user_id=$user_id");
    $user_data = mysqli_fetch_assoc($u_q);

    if(!password_verify($current_pass, $user_data['password'])){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Incorrect password.', 'icon' => 'error'];
    } elseif(!filter_var($new_email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Invalid email format.', 'icon' => 'error'];
    } else {
        $check_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$new_email' AND user_id != $user_id");
        if(mysqli_num_rows($check_email) > 0){
            $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Email address is already in use.', 'icon' => 'error'];
        } else {
            mysqli_query($conn, "UPDATE users SET email='$new_email' WHERE user_id=$user_id");
            $_SESSION['swal'] = ['title' => 'Success', 'text' => 'Email updated successfully.', 'icon' => 'success'];
        }
    }
    header("Location: profile.php");
    exit;
}

// Handle Delete Account
if(isset($_POST['delete_account'])){
    $current_pass = $_POST['current_password'];
    
    $u_q = mysqli_query($conn, "SELECT password FROM users WHERE user_id=$user_id");
    $user_data = mysqli_fetch_assoc($u_q);

    if(!password_verify($current_pass, $user_data['password'])){
        $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Incorrect password.', 'icon' => 'error'];
    } else {
        // Check active reservations
        $chk_res = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$user_id AND status IN ('Pending', 'Approved', 'Verifying')");
        // Check unpaid payments
        $chk_pay = mysqli_query($conn, "SELECT payment_id FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$user_id AND p.payment_status='Unpaid'");

        if(mysqli_num_rows($chk_res) > 0){
            $_SESSION['swal'] = ['title' => 'Cannot Delete', 'text' => 'You have active reservations. Please complete or cancel them first.', 'icon' => 'warning'];
        } elseif(mysqli_num_rows($chk_pay) > 0){
            $_SESSION['swal'] = ['title' => 'Cannot Delete', 'text' => 'You have unpaid bills. Please settle them first.', 'icon' => 'warning'];
        } else {
            // Check if request already exists
            $chk_req = mysqli_query($conn, "SELECT request_id FROM account_deletion_requests WHERE user_id=$user_id AND status='Pending'");
            if(mysqli_num_rows($chk_req) > 0){
                $_SESSION['swal'] = ['title' => 'Request Pending', 'text' => 'You already have a pending deletion request.', 'icon' => 'info'];
            } else {
                mysqli_query($conn, "INSERT INTO account_deletion_requests (user_id) VALUES ($user_id)");
                $_SESSION['swal'] = ['title' => 'Request Submitted', 'text' => 'Your account deletion request has been submitted for admin approval.', 'icon' => 'success'];
            }
        }
    }
    header("Location: profile.php");
    exit;
}

// Handle Night Mode Toggle
if(isset($_POST['toggle_night_mode'])){
    $mode = $_POST['mode'] === 'true' ? 1 : 0;
    mysqli_query($conn, "UPDATE users SET night_mode=$mode WHERE user_id=$user_id");
    $_SESSION['night_mode'] = $mode;
    exit;
}

// Fetch ALL user info to check against schema
$u_query = mysqli_query($conn, "
    SELECT u.*, 
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days
    FROM users u WHERE u.user_id=$user_id
");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['last_name'] . ', ' . $user_info['first_name'] . (!empty($user_info['middle_name']) ? ' ' . $user_info['middle_name'] : '');

// Handle Mark as Read
if(isset($_GET['read_all'])){
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
    header("Location: profile.php");
    exit;
}

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];

// Fetch Notifications
try {
    $notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
} catch (Exception $e) {
    $notif_query = false;
}

// Fetch Counts for Dashboard Cards
$c_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE user_id=$user_id AND is_archived=0 AND status IN ('Pending', 'Approved')"))['c'];
$c_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE user_id=$user_id AND status IN ('Pending', 'Scheduled')"))['c'];
$c_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE user_id=$user_id AND status IN ('Pending', 'Scheduled')"))['c'];
$c_arch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE user_id=$user_id AND is_archived=1"))['c'];

$c_park = 0;
try {
    $p_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_reservations WHERE user_id=$user_id AND status = 'Active'");
    if($p_q) $c_park = mysqli_fetch_assoc($p_q)['c'];
} catch(Exception $e){}

$c_wait = 0;
try {
    $w_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE user_id=$user_id");
    if($w_q) $c_wait = mysqli_fetch_assoc($w_q)['c'];
} catch(Exception $e){}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        /* Night Mode Styles */
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar-user { background: #1f1f1f !important; border-bottom: 1px solid #333 !important; }
        body.night-mode .card, body.night-mode .card-custom, body.night-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .modal-header { border-bottom-color: #333 !important; }
        body.night-mode .modal-footer { background-color: #1e1e1e !important; border-top-color: #333 !important; }
        body.night-mode .dropdown-menu { background-color: #1e1e1e !important; border-color: #333 !important; }
        body.night-mode .dropdown-item { color: #e0e0e0 !important; }
        body.night-mode .dropdown-item:hover { background-color: #333 !important; }
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333 !important; color: #fff !important; border-color: var(--primary-green) !important; }
        body.night-mode .bg-light, body.night-mode .bg-white { background-color: #2c2c2c !important; color: #e0e0e0 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .border, body.night-mode .border-bottom, body.night-mode .border-top { border-color: #444 !important; }
        body.night-mode .table { color: #e0e0e0 !important; }
        body.night-mode .table th, body.night-mode .table td { border-color: #444 !important; background-color: transparent !important; color: #e0e0e0 !important; }
        body.night-mode .list-group-item { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .btn-secondary-custom { background-color: #2c2c2c !important; color: #e0e0e0 !important; border: 1px solid #444 !important; }
        body.night-mode .btn-secondary-custom:hover { background-color: #444 !important; color: #fff !important; }
        body.night-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.night-mode .form-control[type="file"] { color: #34B875 !important; }
        body.night-mode .form-control::file-selector-button { background-color: #1e1e1e !important; color: #34B875 !important; border-color: #444 !important; }
        body.night-mode .form-control:hover::file-selector-button { background-color: #333 !important; }
        body.night-mode .navbar-user .nav-link, body.night-mode .navbar-user .navbar-brand, body.night-mode .navbar-user .text-muted { color: #34B875 !important; }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
        body.night-mode .form-label { color: #34B875 !important; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-user fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
        <!-- Notification Dropdown -->
        <div class="dropdown anim-trigger">
            <a href="#" class="text-white text-decoration-none position-relative me-3" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-lg"></i>
                <?php if($unread_count > 0): ?>
                    <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                        <?= $unread_count ?>
                        <span class="visually-hidden">unread messages</span>
                    </span>
                <?php endif; ?>
            </a>
            <ul id="notifList" class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                    <span class="fw-bold small text-uppercase text-muted">Notifications</span>
                    <?php if($unread_count > 0): ?>
                        <a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>
                    <?php endif; ?>
                </li>
                <?php if($notif_query && mysqli_num_rows($notif_query) > 0): ?>
                    <?php while($notif = mysqli_fetch_assoc($notif_query)): ?>
                        <li>
                            <div class="dropdown-item p-3 border-bottom <?= $notif['is_read'] == 0 ? 'bg-white' : 'bg-light text-muted' ?>" style="white-space: normal;">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="small <?= $notif['is_read'] == 0 ? 'text-success' : '' ?>"><?= htmlspecialchars($notif['type']) ?></strong>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></small>
                                </div>
                                <p class="mb-0 small"><?= $notif['message'] ?></p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="p-3 text-center text-muted small">No notifications found.</li>
                <?php endif; ?>
            </ul>
        </div>

        <span class="text-muted fw-bold d-none d-md-block">Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
        <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="text-center mb-5 anim-trigger anim-zoom">
        <!-- Profile Pic -->
        <div class="position-relative d-inline-block mb-3">
            <?php if(!empty($user_info['profile_image'])): ?>
                <img src="../uploads/profiles/<?= $user_info['profile_image'] ?>" class="rounded-circle shadow-sm" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid var(--app-surface);">
            <?php else: ?>
                <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center bg-success text-white" style="width: 120px; height: 120px; font-size: 3rem; border: 4px solid var(--app-surface);">
                    <?= strtoupper(substr($user_info['first_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <h2 class="display-5 fw-bold text-success">Hello, <?= htmlspecialchars($user_info['full_name']) ?>!</h2>
        <div class="mb-2">
            <?php 
                $m = $user_info['res_months']; $d = $user_info['res_days'];
                $lbl = 'Registered'; $cls = 'bg-secondary';
                if($m >= 6) { $lbl = 'Long-Term Resident'; $cls = 'bg-primary'; }
                elseif($d !== null && $d < 28) { $lbl = 'Daily Guest'; $cls = 'bg-warning text-dark'; }
                elseif($d !== null) { $lbl = 'Short-Term Resident'; $cls = 'bg-success'; }
                if($user_info['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in Guest'; $cls = 'bg-info text-dark'; } else { $lbl .= ' (Walk-in)'; } }
                echo "<span class='badge $cls'>$lbl</span>";
            ?>
        </div>
        <p class="text-muted lead">Manage your stay, bookings, and account details.</p>
    </div>

    <div class="row g-4 justify-content-center" id="dashboard-cards">
        <!-- Book a Room -->
        <div class="col-md-3 anim-trigger anim-zoom delay-1" data-card-id="book">
            <a href="reservation_now.php" class="text-decoration-none">
                <div class="card card-custom profile-card h-100">
                    <div class="icon-box"><i class="fas fa-calendar-plus"></i></div>
                    <h5 class="fw-bold text-dark">Book a Room</h5>
                    <p class="small">Find and book your next stay.</p>
                </div>
            </a>
        </div>

        <!-- My Waitlist -->
        <div class="col-md-3 anim-trigger anim-zoom delay-2" data-card-id="waitlist">
            <a href="my_waitlist.php" class="text-decoration-none" onclick="markAsRead('waitlist', <?= $c_wait ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_wait > 0): ?><div class="card-badge" id="badge-waitlist" data-count="<?= $c_wait ?>" title="Waitlisted Rooms"><?= $c_wait ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-list-ol"></i></div>
                    <h5 class="fw-bold text-dark">My Waitlist</h5>
                    <p class="small">View rooms you are waiting for.</p>
                </div>
            </a>
        </div>

        <!-- My Reservations -->
        <div class="col-md-3 anim-trigger anim-zoom delay-3" data-card-id="reservations">
            <a href="my_reservations.php" class="text-decoration-none" onclick="markAsRead('reservations', <?= $c_res ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_res > 0): ?><div class="card-badge" id="badge-reservations" data-count="<?= $c_res ?>" title="Active Reservations"><?= $c_res ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-suitcase"></i></div>
                    <h5 class="fw-bold text-dark">My Reservations</h5>
                    <p class="small">View your booking history and status.</p>
                </div>
            </a>
        </div>

        <!-- My Parking -->
        <div class="col-md-3 anim-trigger anim-zoom delay-4" data-card-id="parking">
            <a href="my_parking.php" class="text-decoration-none" onclick="markAsRead('parking', <?= $c_park ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_park > 0): ?><div class="card-badge" id="badge-parking" data-count="<?= $c_park ?>" title="Active Parking"><?= $c_park ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-parking"></i></div>
                    <h5 class="fw-bold text-dark">My Parking</h5>
                    <p class="small">View your assigned parking slots.</p>
                </div>
            </a>
        </div>

        <!-- Maintenance -->
        <div class="col-md-3 anim-trigger anim-zoom delay-5" data-card-id="maintenance">
            <a href="maintenance.php" class="text-decoration-none" onclick="markAsRead('maintenance', <?= $c_maint ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_maint > 0): ?><div class="card-badge" id="badge-maintenance" data-count="<?= $c_maint ?>" title="Active Requests"><?= $c_maint ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-tools"></i></div>
                    <h5 class="fw-bold text-dark">Maintenance</h5>
                    <p class="small">Report issues and track repairs.</p>
                </div>
            </a>
        </div>

        <!-- Housekeeping -->
        <div class="col-md-3 anim-trigger anim-zoom delay-6" data-card-id="housekeeping">
            <a href="housekeeping.php" class="text-decoration-none" onclick="markAsRead('housekeeping', <?= $c_house ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_house > 0): ?><div class="card-badge" id="badge-housekeeping" data-count="<?= $c_house ?>" title="Active Requests"><?= $c_house ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-broom"></i></div>
                    <h5 class="fw-bold text-dark">Housekeeping</h5>
                    <p class="small">Request cleaning services.</p>
                </div>
            </a>
        </div>

        <!-- Archived History -->
        <div class="col-md-3 anim-trigger anim-zoom delay-7" data-card-id="archives">
            <a href="my_archives.php" class="text-decoration-none" onclick="markAsRead('archives', <?= $c_arch ?>)">
                <div class="card card-custom profile-card h-100">
                    <?php if($c_arch > 0): ?><div class="card-badge" id="badge-archives" data-count="<?= $c_arch ?>" title="Archived Items"><?= $c_arch ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-archive"></i></div>
                    <h5 class="fw-bold text-dark">Archives</h5>
                    <p class="small">View removed and old contracts.</p>
                </div>
            </a>
        </div>

        <!-- Other Request -->
        <div class="col-md-3 anim-trigger anim-zoom delay-8" data-card-id="other_request">
            <a href="https://www.facebook.com/messages/t/109786470426283" target="_blank" class="text-decoration-none">
                <div class="card card-custom profile-card h-100">
                    <div class="icon-box"><i class="fab fa-facebook-messenger"></i></div>
                    <h5 class="fw-bold text-dark">Other Request</h5>
                    <p class="small">Contact us via Messenger for other concerns.</p>
                </div>
            </a>
        </div>

        <!-- User Customization -->
        <div class="col-md-3 anim-trigger anim-zoom delay-9" data-card-id="customization">
            <a href="javascript:void(0)" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#customizationModal">
                <div class="card card-custom profile-card h-100">
                    <div class="icon-box"><i class="fas fa-sliders-h"></i></div>
                    <h5 class="fw-bold text-dark">Customization</h5>
                    <p class="small">Personalize your profile experience.</p>
                </div>
            </a>
        </div>

    </div>

</div>
<br><br>

<!-- Customization Modal -->
<div class="modal fade" id="customizationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-sliders-h me-2"></i>Customization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Personalize your profile experience and account settings.</p>
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#uploadPicModal">
                        <span><i class="fas fa-camera fa-fw me-3 text-success"></i>Change Profile Picture</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </button>
                    <?php if(!empty($user_info['profile_image'])): ?>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="deleteProfilePicture()">
                        <span><i class="fas fa-trash-alt fa-fw me-3 text-danger"></i>Delete Profile Picture</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <span><i class="fas fa-key fa-fw me-3 text-warning"></i>Change Password</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#changeEmailModal">
                        <span><i class="fas fa-envelope fa-fw me-3 text-info"></i>Change Email</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <span><i class="fas fa-user-times fa-fw me-3 text-danger"></i>Delete Account</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </button>
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-moon fa-fw me-3 text-primary"></i>Night Mode</span>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="nightModeSwitch" <?= ($user_info['night_mode'] == 1) ? 'checked' : '' ?> onchange="toggleNightMode()"></div>
                    </div>
                    
                    <div class="list-group-item bg-light mt-2">
                        <h6 class="fw-bold small mb-2 text-muted text-uppercase">Dashboard Layout</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-book" checked onchange="toggleCard('book')">
                            <label class="form-check-label small" for="show-book">Book a Room</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-waitlist" checked onchange="toggleCard('waitlist')">
                            <label class="form-check-label small" for="show-waitlist">My Waitlist</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-reservations" checked onchange="toggleCard('reservations')">
                            <label class="form-check-label small" for="show-reservations">My Reservations</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-parking" checked onchange="toggleCard('parking')">
                            <label class="form-check-label small" for="show-parking">My Parking</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-maintenance" checked onchange="toggleCard('maintenance')">
                            <label class="form-check-label small" for="show-maintenance">Maintenance</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-housekeeping" checked onchange="toggleCard('housekeeping')">
                            <label class="form-check-label small" for="show-housekeeping">Housekeeping</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-archives" checked onchange="toggleCard('archives')">
                            <label class="form-check-label small" for="show-archives">Archives</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show-other_request" checked onchange="toggleCard('other_request')">
                            <label class="form-check-label small" for="show-other_request">Other Request</label>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="resetDashboardLayout()">Reset Layout</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-target="#customizationModal" data-bs-toggle="modal">Back</button>
                    <button type="submit" class="btn btn-custom">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Email Modal -->
<div class="modal fade" id="changeEmailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-envelope me-2"></i>Change Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="change_email" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Email Address</label>
                        <input type="email" name="new_email" class="form-control" required value="<?= htmlspecialchars($user_info['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Verify it's you">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-target="#customizationModal" data-bs-toggle="modal">Back</button>
                    <button type="submit" class="btn btn-custom">Update Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="text-danger fw-bold">Warning: This action is permanent and cannot be undone.</p>
                    <p class="small">All your data, including reservation history and logs, will be permanently removed. You cannot delete your account if you have active bookings.</p>
                    <input type="hidden" name="delete_account" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Enter Password to Confirm</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-target="#customizationModal" data-bs-toggle="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadPicModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <input type="file" id="profile_image_input" class="d-none" accept="image/png, image/jpeg, image/gif, image/webp">
                    <button type="button" class="btn btn-custom" onclick="document.getElementById('profile_image_input').click();">
                        <i class="fas fa-folder-open me-2"></i>Choose Image
                    </button>
                </div>
                <div class="img-container">
                    <img id="image_to_crop" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" id="crop_and_upload_btn" disabled>Crop & Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<a href="#" class="scroll-top-btn" id="scrollTopBtn"><i class="fas fa-chevron-up"></i></a>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="users_JS/app.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    const currentUserId = "<?= $user_id ?>";
    let lastUnreadCount = <?= (int)$unread_count ?>;

    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // If the response is an HTML page (like a login redirect), force a reload
                    if (text.toLowerCase().includes('<html')) {
                        window.location.reload();
                    }
                    throw e;
                }
            })
            .then(data => {
                if (!data) return;
                // Update Badge
                const bell = document.getElementById('notifDropdown');
                let badge = document.getElementById('notifBadge');
                
                if(data.unread_count > 0) {
                    if(!badge) {
                        badge = document.createElement('span');
                        badge.id = 'notifBadge';
                        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        badge.style.fontSize = '0.6rem';
                        bell.appendChild(badge);
                    }
                    badge.innerHTML = `${data.unread_count} <span class="visually-hidden">unread messages</span>`;
                } else {
                    if(badge) badge.remove();
                }

                // Play Sound if count increased
                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => console.log('Audio play failed:', e));
                    
                    const bellIcon = document.querySelector('#notifDropdown i');
                    if(bellIcon) {
                        bellIcon.classList.add('shake-animation');
                        setTimeout(() => bellIcon.classList.remove('shake-animation'), 500);
                    }
                }
                lastUnreadCount = data.unread_count;

                // Update List
                const list = document.getElementById('notifList');
                let html = `<li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                        <span class="fw-bold small text-uppercase text-muted">Notifications</span>
                        ${data.unread_count > 0 ? '<a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>' : ''}
                    </li>`;
                
                if(data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const bgClass = notif.is_read == 0 ? 'bg-white' : 'bg-light text-muted';
                        const textClass = notif.is_read == 0 ? 'text-success' : '';
                        html += `<li>
                                <div class="dropdown-item p-3 border-bottom ${bgClass}" style="white-space: normal;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="small ${textClass}">${notif.type}</strong>
                                        <small class="text-muted" style="font-size: 0.7rem;">${notif.created_at}</small>
                                    </div>
                                    <p class="mb-0 small">${notif.message}</p>
                                </div>
                            </li>`;
                    });
                } else {
                    html += '<li class="p-3 text-center text-muted small">No notifications found.</li>';
                }
                list.innerHTML = html;
            })
            .catch(err => console.error('Notification fetch error:', err));
    }

    // Mark as read on click
    document.getElementById('notifDropdown').addEventListener('click', function() {
        const badge = document.getElementById('notifBadge');
        if(badge) badge.remove();
        
        fetch('get_notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_read=1'
        });
    });

    // Poll every 5 seconds
    setInterval(fetchNotifications, 5000);

    // Card Badge Logic (Hide if seen)
    function checkCardBadges() {
        const types = ['waitlist', 'reservations', 'maintenance', 'housekeeping', 'archives', 'parking'];
        types.forEach(type => {
            const badge = document.getElementById('badge-' + type);
            if(badge) {
                const currentCount = parseInt(badge.getAttribute('data-count'));
                const seenCount = parseInt(localStorage.getItem('seen_count_' + type + '_' + currentUserId) || 0);
                
                if(seenCount >= currentCount) {
                    badge.style.display = 'none';
                }
            }
        });
    }

    function markAsRead(type, count) {
        localStorage.setItem('seen_count_' + type + '_' + currentUserId, count);
    }

    // Auto Refresh Logic (Global)
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
    document.addEventListener('DOMContentLoaded', checkCardBadges);

    // Night Mode Logic
    function toggleNightMode() {
        document.body.classList.toggle('night-mode');
        const isNight = document.body.classList.contains('night-mode');
        
        // Sync switch if it exists
        const nightModeSwitch = document.getElementById('nightModeSwitch');
        if (nightModeSwitch) {
            nightModeSwitch.checked = isNight;
        }

        // Update Local Storage
        localStorage.setItem('nightMode_' + currentUserId, isNight ? 'enabled' : 'disabled');
        
        // Update Database
        fetch('profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'toggle_night_mode=1&mode=' + isNight
        });
    }

    function deleteProfilePicture() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will remove your profile picture and revert to the default avatar.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('profile.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'delete_profile_image=1'
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        if (text.toLowerCase().includes('<html')) {
                            window.location.reload();
                        }
                        throw new Error("Invalid response");
                    }
                })
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        Swal.fire('Error', 'Could not delete profile picture.', 'error');
                    }
                }).catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred.', 'error');
                });
            }
        });
    }

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode_' + currentUserId) {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });

    // CropperJS Logic
    document.addEventListener('DOMContentLoaded', function () {
        const uploadModalEl = document.getElementById('uploadPicModal');
        if (!uploadModalEl) return;

        const uploadModal = new bootstrap.Modal(uploadModalEl);
        const image = document.getElementById('image_to_crop');
        const fileInput = document.getElementById('profile_image_input');
        const cropBtn = document.getElementById('crop_and_upload_btn');
        let cropper;

        fileInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    image.src = event.target.result;
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(image, {
                        aspectRatio: 1,
                        viewMode: 1,
                        background: false,
                        autoCropArea: 1,
                        ready: function () {
                            // This makes the crop box appear circular
                            document.querySelector('.cropper-view-box').style.borderRadius = '50%';
                            document.querySelector('.cropper-face').style.borderRadius = '50%';
                        }
                    });
                    cropBtn.disabled = false;
                };
                reader.readAsDataURL(files[0]);
            }
        });

        cropBtn.addEventListener('click', function () {
            if (!cropper) {
                return;
            }

            const canvas = cropper.getCroppedCanvas({
                width: 500,
                height: 500,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            cropBtn.disabled = true;
            cropBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            const base64data = canvas.toDataURL('image/png');
            
            fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'cropped_image_data=' + encodeURIComponent(base64data)
            })
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
                if (data.success) {
                    window.location.reload();
                } else {
                    Swal.fire('Upload Failed', data.message || 'Unknown error', 'error');
                    cropBtn.disabled = false;
                    cropBtn.innerHTML = 'Crop & Upload';
                }
            }).catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred during upload.', 'error');
                cropBtn.disabled = false;
                cropBtn.innerHTML = 'Crop & Upload';
            });
        });

        uploadModalEl.addEventListener('hidden.bs.modal', function () {
            if (cropper) cropper.destroy();
            image.src = '';
            fileInput.value = '';
            cropBtn.disabled = true;
            cropBtn.innerHTML = 'Crop & Upload';
        });
    });

    // Dashboard Customization Logic
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('dashboard-cards');
        
        // Load State
        loadDashboardState();

        // Init Sortable
        new Sortable(container, {
            animation: 150,
            ghostClass: 'opacity-50',
            handle: '.card', // Drag by card
            onEnd: function() {
                saveDashboardState();
            }
        });
    });

    function saveDashboardState() {
        const order = [];
        const hidden = [];
        
        document.querySelectorAll('#dashboard-cards > div').forEach(el => {
            const id = el.getAttribute('data-card-id');
            if(id) {
                order.push(id);
                if(el.classList.contains('d-none')) hidden.push(id);
            }
        });
        
        localStorage.setItem('dashboard_order_' + currentUserId, JSON.stringify(order));
        localStorage.setItem('dashboard_hidden_' + currentUserId, JSON.stringify(hidden));
    }

    function loadDashboardState() {
        const order = JSON.parse(localStorage.getItem('dashboard_order_' + currentUserId));
        const hidden = JSON.parse(localStorage.getItem('dashboard_hidden_' + currentUserId)) || [];
        const container = document.getElementById('dashboard-cards');
        
        // Apply Visibility
        document.querySelectorAll('[data-card-id]').forEach(el => {
            const id = el.getAttribute('data-card-id');
            const switchEl = document.getElementById('show-' + id);
            
            if(hidden.includes(id)) {
                el.classList.add('d-none');
                if(switchEl) switchEl.checked = false;
            } else {
                el.classList.remove('d-none');
                if(switchEl) switchEl.checked = true;
            }
        });

        // Apply Order
        if(order && order.length > 0) {
            const currentCards = Array.from(container.children);
            const cardMap = {};
            currentCards.forEach(el => {
                const id = el.getAttribute('data-card-id');
                if(id) cardMap[id] = el;
            });
            
            order.forEach(id => {
                if(cardMap[id]) {
                    container.appendChild(cardMap[id]);
                }
            });
            
            // Append any remaining (new) cards
            currentCards.forEach(el => {
                const id = el.getAttribute('data-card-id');
                if(id && !order.includes(id)) {
                    container.appendChild(el);
                }
            });
        }
    }

    function toggleCard(id) {
        const el = document.querySelector(`[data-card-id="${id}"]`);
        if(el) {
            el.classList.toggle('d-none');
            saveDashboardState();
        }
    }

    function resetDashboardLayout() {
        localStorage.removeItem('dashboard_order_' + currentUserId);
        localStorage.removeItem('dashboard_hidden_' + currentUserId);
        location.reload();
    }
</script>
</body>
</html>