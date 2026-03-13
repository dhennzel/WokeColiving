<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Ensure password history table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure site_settings table exists for colors
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT
)");

$message = "";
$error = "";
$admin_user = $_SESSION['admin_username'];
$current_page = basename($_SERVER['PHP_SELF']);
$theme = get_theme_colors($conn);

// Fetch current admin info
$admin_info_q = mysqli_query($conn, "SELECT * FROM admin WHERE username='$admin_user'");
$admin_info = mysqli_fetch_assoc($admin_info_q);

// Fetch current login bg
$current_login_bg = 'hero.jpg';
$q_bg = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='login_bg'");
if($row_bg = mysqli_fetch_assoc($q_bg)){
    if(!empty($row_bg['setting_value']) && file_exists("../assets/images/" . $row_bg['setting_value'])){
        $current_login_bg = $row_bg['setting_value'];
    }
}

// Fetch current living area img
$current_living_area_img = 'hero.jpg';
$q_la = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='living_area_image'");
if($row_la = mysqli_fetch_assoc($q_la)){
    if(!empty($row_la['setting_value']) && file_exists("../assets/images/" . $row_la['setting_value'])){
        $current_living_area_img = $row_la['setting_value'];
    }
}

// Handle Reset Defaults
if(isset($_POST['reset_defaults'])){
    if(is_super_admin()){
    mysqli_query($conn, "DELETE FROM site_settings WHERE setting_key IN ('theme_primary', 'theme_dark', 'theme_accent')");
    $message = "Theme colors reset to defaults.";
    $theme = get_theme_colors($conn); // Refresh
    }
}

// Handle Logo Revert
if(isset($_POST['revert_logo'])){
    if(is_super_admin()){
    $target_file = "../Images/WokeLogo.jpg";
    $backup_file = "../Images/WokeLogo_backup.jpg";
    
    if(file_exists($backup_file)){
        if(copy($backup_file, $target_file)){
            $message = "Logo reverted to previous version successfully.";
        } else {
            $error = "Failed to revert logo.";
        }
    } else {
        $error = "No backup logo found.";
    }
    }
}

if(isset($_POST['update_profile'])){
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $new_password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password'] ?? '');
    
    $pass_update_sql = "";
    
    // Password Validation (Only if user typed something)
    if(!empty($new_password)){
        if(!preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)){
            $error = "Password must contain at least one letter and one number.";
        } elseif($new_password !== $confirm_password){
            $error = "New password and confirm password do not match.";
        } else {
            // Check against current password
            $curr_q = mysqli_query($conn, "SELECT password FROM admin WHERE username='$admin_user'");
            $curr_row = mysqli_fetch_assoc($curr_q);
            if($curr_row && $curr_row['password'] === $new_password){
                $error = "New password cannot be the same as your current password.";
            }

            // Check against last 3 passwords in history
            if(!$error){
                $hist_q = mysqli_query($conn, "SELECT password FROM admin_password_history WHERE username='$admin_user' ORDER BY changed_at DESC LIMIT 3");
                while($h_row = mysqli_fetch_assoc($hist_q)){
                    if($h_row['password'] === $new_password){
                        $error = "You cannot reuse any of your last 3 passwords.";
                        break;
                    }
                }
            }

            if(!$error){
                $pass_update_sql = ", password='$new_password'";
            }
        }
    }

    if(!$error) {
        // Update admin credentials
        $sql = "UPDATE admin SET username='$new_username', first_name='$first_name', last_name='$last_name', email='$email', phone_number='$phone_number' $pass_update_sql WHERE username='$admin_user'";
        if(mysqli_query($conn, $sql)){
            // Update history username if changed
            if($admin_user !== $new_username){
                mysqli_query($conn, "UPDATE admin_password_history SET username='$new_username' WHERE username='$admin_user'");
            }
            
            // Log new password to history if changed
            if(!empty($new_password)){
                mysqli_query($conn, "INSERT INTO admin_password_history (username, password) VALUES ('$new_username', '$new_password')");
            }

            $_SESSION['admin_username'] = $new_username;
            $_SESSION['admin_full_name'] = trim($first_name . ' ' . $last_name);
            $admin_user = $new_username;
            $admin_info['first_name'] = $first_name;
            $admin_info['last_name'] = $last_name;
            $admin_info['email'] = $email;
            $admin_info['phone_number'] = $phone_number;
            $message = "Profile updated successfully.";

            // Handle Cropped Logo Upload
            if(is_super_admin()){
            if(isset($_POST['cropped_logo_data']) && !empty($_POST['cropped_logo_data'])){
                $data = $_POST['cropped_logo_data'];
                list($type, $data) = explode(';', $data);
                list(, $data)      = explode(',', $data);
                $data = base64_decode($data);
                
                $target_dir = "../Images/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $target_file = $target_dir . "WokeLogo.jpg";
                
                if(file_exists($target_file)) copy($target_file, $target_dir . "WokeLogo_backup.jpg");
                
                if(file_put_contents($target_file, $data)){
                    $message .= " Logo updated.";
                }
            } elseif(isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0){
                $target_dir = "../Images/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $target_file = $target_dir . "WokeLogo.jpg"; // Overwrite existing logo
                
                // Create backup before overwriting
                if(file_exists($target_file)){
                    copy($target_file, $target_dir . "WokeLogo_backup.jpg");
                }
                
                if(move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)){
                    $message .= " Logo updated.";
                } else {
                    $error = "Failed to upload logo.";
                }
            }

            // Handle Login Background Upload
            if(isset($_FILES['login_bg']) && $_FILES['login_bg']['error'] == 0){
                $target_dir = "../assets/images/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES["login_bg"]["name"], PATHINFO_EXTENSION));
                $new_filename = "login_bg_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if(move_uploaded_file($_FILES["login_bg"]["tmp_name"], $target_file)){
                    // Get old bg to delete
                    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='login_bg'");
                    if($row = mysqli_fetch_assoc($q)){
                        $old_bg = $row['setting_value'];
                        if(!empty($old_bg) && file_exists("../assets/images/" . $old_bg) && $old_bg != 'hero.jpg'){
                            unlink("../assets/images/" . $old_bg);
                        }
                    }
                    
                    $val = mysqli_real_escape_string($conn, $new_filename);
                    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('login_bg', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
                    $message .= " Login background updated.";
                } else {
                    $error = "Failed to upload login background.";
                }
            }

            // Handle Living Area Image Upload
            if(isset($_FILES['living_area_img']) && $_FILES['living_area_img']['error'] == 0){
                $target_dir = "../assets/images/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES["living_area_img"]["name"], PATHINFO_EXTENSION));
                $new_filename = "living_area_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if(move_uploaded_file($_FILES["living_area_img"]["tmp_name"], $target_file)){
                    // Get old img to delete
                    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='living_area_image'");
                    if($row = mysqli_fetch_assoc($q)){
                        $old_img = $row['setting_value'];
                        if(!empty($old_img) && file_exists("../assets/images/" . $old_img) && $old_img != 'hero.jpg'){
                            unlink("../assets/images/" . $old_img);
                        }
                    }
                    
                    $val = mysqli_real_escape_string($conn, $new_filename);
                    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('living_area_image', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
                    $message .= " Living area image updated.";
                    $current_living_area_img = $new_filename;
                } else {
                    $error = "Failed to upload living area image.";
                }
            }

            // Handle Theme Colors
            if(isset($_POST['primary_color'])){
                $colors = [
                    'theme_primary' => $_POST['primary_color'],
                    'theme_dark' => $_POST['dark_color'],
                    'theme_accent' => $_POST['accent_color']
                ];
                foreach($colors as $key => $val){
                    $val = mysqli_real_escape_string($conn, $val);
                    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
                }
                $theme = get_theme_colors($conn); // Refresh
            }
            }
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .img-container { max-height: 400px; overflow: hidden; }
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { #sidebar-wrapper { margin-left: -250px; } #wrapper.toggled #sidebar-wrapper { margin-left: 0; } }
    </style>
</head>
<body>
<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php', 'payment_details.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php', 'payment_details.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php', 'payment_details.php']) ? 'show' : '' ?>" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Residents</span>
                </a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                    <?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?>
                </a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-ol me-2"></i>Waitlist</span>
                    <?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?>
                </a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-times me-2"></i>Deletion Req</span>
                    <?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?>
                </a>
            </div>

            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? 'show' : '' ?>" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'show' : '' ?>" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5 active"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm"></a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Admin Profile</h4>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card card-custom p-4">
                        <h4 class="fw-bold mb-4 text-center" style="color: var(--dark-green);">System Settings</h4>
                        <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
                        <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="cropped_logo_data" id="cropped_logo_data">
                            <div class="row g-5">
                                <!-- Left Column: Credentials -->
                                <div class="<?= is_super_admin() ? 'col-md-6 border-end' : 'col-md-12' ?>">
                                    <h5 class="text-success fw-bold mb-3"><i class="fas fa-user-shield me-2"></i>Account Credentials</h5>
                                    
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">First Name</label>
                                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($admin_info['first_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Last Name</label>
                                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($admin_info['last_name'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Email & Phone Number</label>
                                        <div class="input-group input-group-sm mb-2"><span class="input-group-text"><i class="fas fa-envelope"></i></span><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin_info['email'] ?? '') ?>"></div>
                                        <div class="input-group input-group-sm"><span class="input-group-text"><i class="fas fa-phone"></i></span><input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($admin_info['phone_number'] ?? '') ?>"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Login Username</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin_user) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">New Password</label>
                                        <input type="password" name="password" id="newPass" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Confirm Password</label>
                                        <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Confirm new password">
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="showPassToggle">
                                        <label class="form-check-label small" for="showPassToggle">Show Password</label>
                                    </div>
                                </div>

                                <!-- Right Column: Branding -->
                                <?php if(is_super_admin()): ?>
                                <div class="col-md-6">
                                    <h5 class="text-secondary fw-bold mb-4 small text-uppercase border-bottom pb-2"><i class="fas fa-sliders-h me-2"></i>System Branding</h5>
                                    
                                    <!-- Logo -->
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label class="form-label fw-bold small mb-0">System Logo</label>
                                            <?php if(file_exists("../Images/WokeLogo_backup.jpg")): ?>
                                                <button type="submit" name="revert_logo" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" style="font-size: 0.75rem;" title="Revert to previous logo">
                                                    <i class="fas fa-history me-1"></i> Revert
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="rounded-circle border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <input type="file" name="site_logo" id="logo_input" class="form-control form-control-sm" accept="image/*">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Login Background -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Login Background</label>
                                        <input type="file" name="login_bg" id="login_bg_input" class="form-control form-control-sm" accept="image/*">
                                        <div id="login_bg_preview_container" class="mt-2" style="display:none;">
                                            <img id="login_bg_preview" src="" class="rounded w-100 border" style="height: 120px; object-fit: cover;">
                                        </div>
                                        <div class="text-end mt-1">
                                            <small class="text-muted" style="font-size: 0.7rem;">Current: <?= htmlspecialchars($current_login_bg) ?></small>
                                        </div>
                                    </div>

                                    <!-- Living Area Image -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Living Area Image</label>
                                        <input type="file" name="living_area_img" class="form-control form-control-sm" accept="image/*">
                                        <div class="text-end mt-1">
                                            <small class="text-muted" style="font-size: 0.7rem;">Current: <?= htmlspecialchars($current_living_area_img) ?></small>
                                        </div>
                                    </div>

                                    <!-- Theme Colors -->
                                    <h5 class="text-secondary fw-bold mb-4 small text-uppercase border-bottom pb-2 mt-5"><i class="fas fa-palette me-2"></i>Theme Customization</h5>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <label class="form-label small fw-bold mb-1">Primary</label>
                                            <input type="color" name="primary_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['primary'] ?>" title="Primary Color">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small fw-bold mb-1">Dark/Sidebar</label>
                                            <input type="color" name="dark_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['dark'] ?>" title="Dark Color">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small fw-bold mb-1">Accent</label>
                                            <input type="color" name="accent_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['accent'] ?>" title="Accent Color">
                                        </div>
                                    </div>
                                    <div class="text-end mt-2">
                                        <button type="submit" name="reset_defaults" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" style="font-size: 0.75rem;" formnovalidate>
                                            <i class="fas fa-undo me-1"></i> Reset to Default
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <hr class="my-4">
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-custom px-5 py-2">Save Changes</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--primary-green);">
                <h5 class="modal-title fw-bold"><i class="fas fa-crop me-2"></i>Crop Logo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="img-container">
                    <img id="image-to-crop" src="" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom px-4" id="crop-btn">Crop & Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetThemeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #dc3545;">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Reset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to reset the theme colors to their default values? This will restore the original green and yellow branding.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <form method="POST">
                    <button type="submit" name="reset_defaults" class="btn btn-danger rounded-pill px-4">Yes, Reset to Default</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });
    document.getElementById("sidebar-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });
    
    // Show Password Toggle
    document.getElementById('showPassToggle').addEventListener('change', function() {
        const type = this.checked ? 'text' : 'password';
        document.getElementById('newPass').type = type;
        document.getElementById('confirmPass').type = type;
    });

    // Cropper Logic
    let cropper;
    const image = document.getElementById('image-to-crop');
    const input = document.getElementById('logo_input');
    const modal = new bootstrap.Modal(document.getElementById('cropModal'));

    input.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(e) {
                image.src = e.target.result;
                modal.show();
                if(cropper) cropper.destroy();
                setTimeout(() => { cropper = new Cropper(image, { aspectRatio: 1, viewMode: 1 }); }, 200);
            };
            reader.readAsDataURL(files[0]);
        }
    });

    document.getElementById('crop-btn').addEventListener('click', function() {
        const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
        document.getElementById('cropped_logo_data').value = canvas.toDataURL('image/png');
        modal.hide();
    });

    // Theme Preview
    const root = document.documentElement;
    const pInput = document.getElementById('primary_color');
    const dInput = document.getElementById('dark_color');
    const aInput = document.getElementById('accent_color');

    function updateTheme() {
        root.style.setProperty('--primary-green', pInput.value);
        root.style.setProperty('--dark-green', dInput.value);
        root.style.setProperty('--accent-yellow', aInput.value);
    }

    [pInput, dInput, aInput].forEach(input => input.addEventListener('input', updateTheme));

    // Login Background Preview
    const loginBgInput = document.getElementById('login_bg_input');
    const loginBgPreview = document.getElementById('login_bg_preview');
    const loginBgContainer = document.getElementById('login_bg_preview_container');

    if(loginBgInput) {
        loginBgInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    loginBgPreview.src = e.target.result;
                    loginBgContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                loginBgContainer.style.display = 'none';
            }
        });
    }

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