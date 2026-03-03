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

// Handle Night Mode Toggle
if(isset($_POST['toggle_night_mode'])){
    $mode = $_POST['mode'] === 'true' ? 1 : 0;
    mysqli_query($conn, "UPDATE users SET night_mode=$mode WHERE user_id=$user_id");
    $_SESSION['night_mode'] = $mode;
    exit;
}

// Fetch ALL user info to check against schema
$u_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['last_name'] . ', ' . $user_info['first_name'] . (!empty($user_info['middle_name']) ? ' ' . $user_info['middle_name'] : '');

// Check for Outdated System Data
$update_reasons = [];
// 1. Check data integrity and format
if(!filter_var($user_info['email'], FILTER_VALIDATE_EMAIL)) {
    $update_reasons[] = "Invalid email format needs to be fixed.";
}
if(!preg_match('/^(09|\+639)\d{9}$/', $user_info['phone_number'])) {
    $update_reasons[] = "Phone number requires re-formatting for system compatibility.";
}

// 2. Check for uninitialized columns based on a developer-defined schema
// Developer: Add new columns here to have the system check and initialize them for existing users.
// The 'type' is the full SQL column definition. The 'default' is the value for the UPDATE statement.
$user_schema = [
    'is_walkin'  => ['type' => 'TINYINT(1) DEFAULT 0', 'default' => 0, 'reason' => 'Account needs walk-in status synchronization.'],
    'role'       => ['type' => "VARCHAR(20) DEFAULT 'user'", 'default' => "'user'", 'reason' => 'User role needs to be defined for system access.'],
    'night_mode' => ['type' => 'TINYINT(1) DEFAULT 0', 'default' => 0, 'reason' => 'Night mode preference needs to be initialized.'],
    'gender'     => ['type' => 'VARCHAR(20) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Gender information is missing and required for booking.'],
    'occupation' => ['type' => 'VARCHAR(50) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Occupation status is missing.'],
    'company'    => ['type' => 'VARCHAR(100) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Company or school information needs to be updated.'],
    'address'    => ['type' => 'TEXT DEFAULT NULL', 'default' => "NULL", 'reason' => 'Address information is missing.'],
    'school_id_image' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Student verification data needs to be updated.'],
    'emergency_contact_name' => ['type' => 'VARCHAR(100) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Emergency contact details are missing.'],
    'emergency_contact_number' => ['type' => 'VARCHAR(20) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Emergency contact details are missing.'],
    'reset_token' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Password reset feature needs to be enabled.'],
    'reset_expiry' => ['type' => 'DATETIME DEFAULT NULL', 'default' => "NULL", 'reason' => 'Password reset feature needs to be enabled.'],
    'do_not_renew' => ['type' => 'TINYINT(1) DEFAULT 0', 'default' => 0, 'reason' => 'Account renewal status needs to be initialized.'],
    'newsletter' => ['type' => 'TINYINT(1) DEFAULT 1', 'default' => 1, 'reason' => 'New Feature: Community Newsletter subscription.'],
    'bio' => ['type' => 'TEXT DEFAULT NULL', 'default' => "NULL", 'reason' => 'New Feature: User Bio for community profile.'],
    'social_link' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'New Feature: Social Media link field.']
];

// Get the actual columns from the users table to avoid errors if a column doesn't exist yet
$user_columns_q = mysqli_query($conn, "SHOW COLUMNS FROM users");
$existing_user_columns = [];
while($col = mysqli_fetch_assoc($user_columns_q)) {
    $existing_user_columns[] = $col['Field'];
}

foreach ($user_schema as $column => $details) {
    // Check if the column exists in the DB and if the user's value for it is NULL
    if (!in_array($column, $existing_user_columns)) {
        // If column doesn't even exist, it's definitely outdated.
        $update_reasons[] = $details['reason'];
    } elseif (is_null($user_info[$column]) && $details['default'] !== "NULL") {
        // Add the reason if it's not already in the list (to avoid duplicates)
        if (!in_array($details['reason'], $update_reasons)) {
            $update_reasons[] = $details['reason'];
        }
    }
}

// 3. Check for broken profile picture link
$curr_img = $user_info['profile_image'];
if((!empty($curr_img) && !file_exists("../uploads/profiles/" . $curr_img)) || $curr_img === ''){
    $update_reasons[] = "Profile picture link is broken and needs to be reset.";
}
$is_outdated = !empty($update_reasons);

// Handle System Update Action
if(isset($_GET['action']) && $_GET['action'] == 'system_update'){
    // 0. Add missing columns to the database if they don't exist
    // This ensures that new features defined in the schema are added to the DB.
    // Note: The database user needs ALTER privileges for this to work.
    foreach ($user_schema as $column => $details) {
        if (!in_array($column, $existing_user_columns)) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN `$column` " . $details['type']);
        }
    }
    // Re-fetch user info and existing columns after potential schema changes
    $u_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
    $user_info = mysqli_fetch_assoc($u_query);
    
    $user_columns_q = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_user_columns = [];
    while($col = mysqli_fetch_assoc($user_columns_q)) {
        $existing_user_columns[] = $col['Field'];
    }


    // 1. Fix Data Integrity (Email, Phone)
    $sanitized_email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
    if($sanitized_email !== $user_info['email'] && filter_var($sanitized_email, FILTER_VALIDATE_EMAIL)){
        mysqli_query($conn, "UPDATE users SET email='" . mysqli_real_escape_string($conn, $sanitized_email) . "' WHERE user_id=$user_id");
    }

    // 2. Fix Phone Number (Remove non-numeric/plus characters)
    $clean_phone = preg_replace('/[^0-9+]/', '', $user_info['phone_number']);
    if($clean_phone !== $user_info['phone_number']){
        mysqli_query($conn, "UPDATE users SET phone_number='$clean_phone' WHERE user_id=$user_id");
    }

    // 2. Initialize new/NULL columns based on schema
    foreach ($user_schema as $column => $details) {
        if (in_array($column, $existing_user_columns) && is_null($user_info[$column])) {
            mysqli_query($conn, "UPDATE users SET `$column` = " . $details['default'] . " WHERE user_id=$user_id AND `$column` IS NULL");
        }
    }
    
    // 3. Fix Profile Picture (Broken Link or Empty -> NULL to show default placeholder)
    $curr_img = $user_info['profile_image'];
    if((!empty($curr_img) && !file_exists("../uploads/profiles/" . $curr_img)) || $curr_img === ''){
        mysqli_query($conn, "UPDATE users SET profile_image=NULL WHERE user_id=$user_id");
    }

    $_SESSION['swal'] = ['title' => 'System Updated', 'text' => 'Account data synchronized successfully.', 'icon' => 'success'];
    header("Location: profile.php");
    exit;
}

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

$c_wait = 0;
try {
    $w_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE user_id=$user_id");
    if($w_q) $c_wait = mysqli_fetch_assoc($w_q)['c'];
} catch(Exception $e){}

// Fetch System Updates History
$sys_updates = [];
$su_q = mysqli_query($conn, "SELECT * FROM system_updates ORDER BY release_date DESC LIMIT 10");
if($su_q){
    while($row = mysqli_fetch_assoc($su_q)){
        $sys_updates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .profile-card {
            transition: all 0.4s ease;
            cursor: pointer;
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
        }
        .profile-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(46, 125, 50, 0.15);
        }
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: var(--accent-yellow);
            transform: scaleX(0);
            transition: transform 0.4s;
            transform-origin: left;
        }
        .profile-card:hover::before { transform: scaleX(1); }
        
        .icon-box {
            font-size: 3rem;
            color: var(--primary-green);
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .profile-card:hover .icon-box { transform: scale(1.1) rotate(10deg); color: var(--accent-yellow); }
        
        .notif-item { border-left: 4px solid var(--dark-green); background: #fff; margin-bottom: 10px; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card, body.night-mode .profile-card, body.night-mode .modal-content { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .bg-white { background-color: #1e1e1e !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .dropdown-item { color: #e0e0e0; }
        body.night-mode .dropdown-item:hover { background-color: #333; }
        body.night-mode .btn-outline-dark { color: #e0e0e0; border-color: #e0e0e0; }
        body.night-mode .btn-outline-dark:hover { background-color: #e0e0e0; color: #121212; }
        body.night-mode .modal-header { border-bottom-color: #333; }
        body.night-mode .modal-footer { border-top-color: #333; }
        body.night-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.night-mode .alert-light { background-color: #2c2c2c; border-color: #333; color: #e0e0e0; }
        body.night-mode .form-control { background-color: #2c2c2c; color: #e0e0e0; border-color: #444; }
        body.night-mode .form-control:focus { background-color: #333; color: #fff; }
        body.night-mode .progress { background-color: #333; }
        body.night-mode .list-group-item {
            background-color: #2c2c2c;
            border-color: #444;
            color: #e0e0e0;
        }
        body.night-mode .list-group-item-action:hover, body.night-mode .list-group-item-action:focus {
            background-color: #333;
            color: #fff;
        }

        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
        }
    </style>
    <style>
        /* CropperJS Modal styles */
        .img-container { max-height: 400px; overflow: hidden; background: #f7f7f7; }
    </style>
</head>
<body class="<?= ($user_info['night_mode'] == 1) ? 'night-mode' : '' ?>">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
        <!-- Notification Dropdown -->
        <div class="dropdown">
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

        <span class="text-white fw-bold d-none d-md-block">Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
        <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="text-center mb-5 reveal">
        <!-- Profile Pic -->
        <div class="position-relative d-inline-block mb-3">
            <?php if(!empty($user_info['profile_image'])): ?>
                <img src="../uploads/profiles/<?= $user_info['profile_image'] ?>" class="rounded-circle shadow" style="width: 120px; height: 120px; object-fit: cover;">
            <?php else: ?>
                <div class="rounded-circle shadow d-flex align-items-center justify-content-center bg-success text-white" style="width: 120px; height: 120px; font-size: 3rem;">
                    <?= strtoupper(substr($user_info['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <h2 class="display-5 fw-bold text-success">Hello, <?= htmlspecialchars($user_info['full_name']) ?>!</h2>
        <p class="text-muted lead">Manage your stay, bookings, and account details.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Book a Room -->
        <div class="col-md-3 reveal delay-1">
            <a href="reservation_now.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-calendar-plus"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Book a Room</h4>
                    <p class="text-muted small">Find and book your next stay.</p>
                </div>
            </a>
        </div>

        <!-- My Waitlist -->
        <div class="col-md-3 reveal delay-2">
            <a href="my_waitlist.php" class="text-decoration-none" onclick="markAsRead('waitlist', <?= $c_wait ?>)">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($c_wait > 0): ?><div class="card-badge" id="badge-waitlist" data-count="<?= $c_wait ?>" title="Waitlisted Rooms"><?= $c_wait ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-list-ol"></i></div>
                    <h4 class="fw-bold text-dark mb-3">My Waitlist</h4>
                    <p class="text-muted small">View rooms you are waiting for.</p>
                </div>
            </a>
        </div>

        <!-- My Reservations -->
        <div class="col-md-3 reveal delay-3">
            <a href="my_reservations.php" class="text-decoration-none" onclick="markAsRead('reservations', <?= $c_res ?>)">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($c_res > 0): ?><div class="card-badge" id="badge-reservations" data-count="<?= $c_res ?>" title="Active Reservations"><?= $c_res ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-suitcase"></i></div>
                    <h4 class="fw-bold text-dark mb-3">My Reservations</h4>
                    <p class="text-muted small">View your booking history and status.</p>
                </div>
            </a>
        </div>

        <!-- Maintenance -->
        <div class="col-md-3 reveal delay-4">
            <a href="maintenance.php" class="text-decoration-none" onclick="markAsRead('maintenance', <?= $c_maint ?>)">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($c_maint > 0): ?><div class="card-badge" id="badge-maintenance" data-count="<?= $c_maint ?>" title="Active Requests"><?= $c_maint ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-tools"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Maintenance</h4>
                    <p class="text-muted small">Report issues and track repairs.</p>
                </div>
            </a>
        </div>

        <!-- Housekeeping -->
        <div class="col-md-3 reveal delay-5">
            <a href="housekeeping.php" class="text-decoration-none" onclick="markAsRead('housekeeping', <?= $c_house ?>)">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($c_house > 0): ?><div class="card-badge" id="badge-housekeeping" data-count="<?= $c_house ?>" title="Active Requests"><?= $c_house ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-broom"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Housekeeping</h4>
                    <p class="text-muted small">Request cleaning services.</p>
                </div>
            </a>
        </div>

        <!-- Archived History -->
        <div class="col-md-3 reveal delay-6">
            <a href="my_archives.php" class="text-decoration-none" onclick="markAsRead('archives', <?= $c_arch ?>)">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($c_arch > 0): ?><div class="card-badge" id="badge-archives" data-count="<?= $c_arch ?>" title="Archived Items"><?= $c_arch ?></div><?php endif; ?>
                    <div class="icon-box"><i class="fas fa-archive"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Archives</h4>
                    <p class="text-muted small">View removed and old contracts.</p>
                </div>
            </a>
        </div>

        <!-- User Customization -->
        <div class="col-md-3 reveal delay-7">
            <a href="javascript:void(0)" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#customizationModal">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-sliders-h"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Customization</h4>
                    <p class="text-muted small">Personalize your profile experience.</p>
                </div>
            </a>
        </div>

        <!-- System Update -->
        <div class="col-md-3 reveal delay-8">
            <a href="javascript:void(0)" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#systemUpdateModal">
                <div class="card profile-card h-100 p-5 text-center">
                    <?php if($is_outdated): ?>
                        <div class="position-absolute top-0 end-0 m-3 p-2 bg-danger rounded-circle shadow border border-white" title="Update Required"></div>
                    <?php endif; ?>
                    <div class="icon-box"><i class="fas fa-sync-alt"></i></div>
                    <h4 class="fw-bold text-dark mb-3">System Update</h4>
                    <p class="text-muted small">Update your account to the latest system features.</p>
                </div>
            </a>
        </div>
    </div>

</div>
<br><br>

<!-- System Update Modal -->
<div class="modal fade" id="systemUpdateModal" tabindex="-1" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2 text-primary"></i>System Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <?php if($is_outdated): ?>
                <div id="update-content">
                    <div class="mb-3">
                        <i class="fas fa-cogs text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                    <h5 class="fw-bold">Synchronize Account Data</h5>
                    <p class="text-muted small mb-4">This action will standardize your profile information and ensure all system fields are initialized correctly.</p>
                    
                    <div class="alert alert-light border text-start small">
                        <strong><i class="fas fa-star text-warning me-1"></i> What's New & Updates:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <?php foreach($update_reasons as $reason): ?>
                                <li><?= htmlspecialchars($reason) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div id="update-progress" style="display:none;" class="py-4">
                    <h5 class="fw-bold text-success mb-3">Updating System...</h5>
                    <div class="progress" style="height: 20px;">
                        <div id="sys-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p class="text-muted small mt-2">Please wait while we synchronize your data.</p>
                </div>
                <?php else: ?>
                <div class="py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold">System Up to Date</h5>
                    <p class="text-muted small">Your account data is synchronized with the latest system version.</p>
                    
                    <?php if(!empty($sys_updates)): ?>
                    <div class="text-start border-top pt-3 mt-3">
                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-bullhorn me-2"></i>What's New & Version History</h6>
                        <div class="list-group list-group-flush small" style="max-height: 250px; overflow-y: auto;">
                            <?php foreach($sys_updates as $update): ?>
                            <div class="list-group-item px-0 bg-transparent">
                                <div class="d-flex w-100 justify-content-between">
                                    <strong class="mb-1 text-dark"><?= htmlspecialchars($update['title']) ?></strong>
                                    <span class="badge bg-light text-dark border">v<?= htmlspecialchars($update['version']) ?></span>
                                </div>
                                <p class="mb-1 text-muted"><?= htmlspecialchars($update['description']) ?></p>
                                <small class="text-secondary"><i class="far fa-calendar-alt me-1"></i> <?= date('M d, Y', strtotime($update['release_date'])) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 justify-content-center" id="update-footer">
                <?php if($is_outdated): ?>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="startSystemUpdate()" class="btn btn-primary rounded-pill px-4">Confirm Update</button>
                <?php else: ?>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Customization Modal -->
<div class="modal fade" id="customizationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-moon fa-fw me-3 text-primary"></i>Night Mode</span>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="nightModeSwitch" <?= ($user_info['night_mode'] == 1) ? 'checked' : '' ?> onchange="toggleNightMode()"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadPicModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <input type="file" id="profile_image_input" class="d-none" accept="image/png, image/jpeg, image/gif">
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profile_image_input').click();">
                        <i class="fas fa-folder-open me-2"></i>Choose Image
                    </button>
                </div>
                <div class="img-container">
                    <img id="image_to_crop" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="crop_and_upload_btn" disabled>Crop & Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    let lastUnreadCount = <?= (int)$unread_count ?>;

    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
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
        const types = ['waitlist', 'reservations', 'maintenance', 'housekeeping', 'archives'];
        types.forEach(type => {
            const badge = document.getElementById('badge-' + type);
            if(badge) {
                const currentCount = parseInt(badge.getAttribute('data-count'));
                const seenCount = parseInt(localStorage.getItem('seen_count_' + type) || 0);
                
                if(seenCount >= currentCount) {
                    badge.style.display = 'none';
                }
            }
        });
    }

    function markAsRead(type, count) {
        localStorage.setItem('seen_count_' + type, count);
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
        localStorage.setItem('nightMode', isNight ? 'enabled' : 'disabled');
        
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
                .then(response => response.json())
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

    function startSystemUpdate() {
        document.getElementById('update-content').style.display = 'none';
        document.getElementById('update-footer').style.display = 'none';
        document.getElementById('update-progress').style.display = 'block';
        
        let width = 0;
        const bar = document.getElementById('sys-progress-bar');
        const interval = setInterval(() => {
            width += 2;
            bar.style.width = width + '%';
            if(width >= 100) {
                clearInterval(interval);
                setTimeout(() => {
                    window.location.href = 'profile.php?action=system_update';
                }, 500);
            }
        }, 30);
    }

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode') {
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
            .then(response => response.json())
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
</script>
</body>
</html>