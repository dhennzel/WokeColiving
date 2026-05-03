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

// Fetch current house rules
$current_house_rules = "";
$q_rules = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='house_rules'");
if($row_rules = mysqli_fetch_assoc($q_rules)){
    $current_house_rules = $row_rules['setting_value'];
}

// Fetch current GCash QR
$current_gcash_qr = "";
$q_gcash = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='gcash_qr'");
if($row_gcash = mysqli_fetch_assoc($q_gcash)){
    $current_gcash_qr = $row_gcash['setting_value'];
}

// Fetch current Clearance Form
$current_clearance_file = "";
$q_clearance = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='clearance_file'");
if($row_clearance = mysqli_fetch_assoc($q_clearance)){
    $current_clearance_file = $row_clearance['setting_value'];
}

// Fetch current maintenance mode
$current_maint_mode = '0';
$current_maint_end = 0;
$q_maint = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='maintenance_mode'");
if($row_maint = mysqli_fetch_assoc($q_maint)){
    $current_maint_mode = $row_maint['setting_value'];
}
if ($current_maint_mode == '1') {
    $q_end = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='maintenance_end_time'");
    if($row_end = mysqli_fetch_assoc($q_end)) {
        $current_maint_end = (int)$row_end['setting_value'];
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

// Handle Reset Defaults
if(isset($_POST['reset_defaults'])){
    if(is_super_admin()){
        $type = $_POST['reset_defaults'];
        if($type == 'theme'){
            mysqli_query($conn, "DELETE FROM site_settings WHERE setting_key LIKE 'theme_%'");
            $message = "Theme colors reset to defaults.";
            $theme = get_theme_colors($conn); // Refresh
        } elseif($type == 'smtp'){
            mysqli_query($conn, "DELETE FROM site_settings WHERE setting_key LIKE 'smtp_%'");
            $message = "SMTP settings cleared.";
        }
    }
}

// Handle SMTP Settings
if(isset($_POST['update_smtp']) && is_super_admin()){
    foreach($_POST as $key => $val){
        if(strpos($key, 'smtp_') === 0) mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '".mysqli_real_escape_string($conn, $val)."') ON DUPLICATE KEY UPDATE setting_value='".mysqli_real_escape_string($conn, $val)."'");
    }
    $message = "SMTP settings updated successfully.";
}
// --- Credentials Update ---
if(isset($_POST['update_credentials'])){
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
            
            // Handle Admin Profile Image Upload (Personal)
            if(isset($_POST['cropped_profile_data']) && !empty($_POST['cropped_profile_data'])){
                $data = $_POST['cropped_profile_data'];
                list($type, $data) = explode(';', $data);
                list(, $data)      = explode(',', $data);
                $data = base64_decode($data);
                
                $target_dir = "../uploads/profiles/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $new_filename = "admin_" . $admin_info['id'] . "_" . time() . ".png";
                $target_file = $target_dir . $new_filename;
                
                if(file_put_contents($target_file, $data)){
                    $old_img = $admin_info['profile_image'] ?? '';
                    if(!empty($old_img) && file_exists("../uploads/profiles/" . $old_img)){
                        @unlink("../uploads/profiles/" . $old_img);
                    }
                    
                    $val = mysqli_real_escape_string($conn, $new_filename);
                    mysqli_query($conn, "UPDATE admin SET profile_image='$val' WHERE username='$new_username'");
                    $admin_info['profile_image'] = $val;
                } else {
                    $error = "Failed to upload personal profile picture.";
                }
            } elseif(isset($_FILES['admin_profile_image']) && $_FILES['admin_profile_image']['error'] == 0){
                $target_dir = "../uploads/profiles/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES["admin_profile_image"]["name"], PATHINFO_EXTENSION));
                $new_filename = "admin_" . $admin_info['id'] . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if(move_uploaded_file($_FILES["admin_profile_image"]["tmp_name"], $target_file)){
                    $old_img = $admin_info['profile_image'] ?? '';
                    if(!empty($old_img) && file_exists("../uploads/profiles/" . $old_img)){
                        @unlink("../uploads/profiles/" . $old_img);
                    }
                    
                    $val = mysqli_real_escape_string($conn, $new_filename);
                    mysqli_query($conn, "UPDATE admin SET profile_image='$val' WHERE username='$new_username'");
                    $admin_info['profile_image'] = $val;
                } else {
                    $error = "Failed to upload personal profile picture.";
                }
            }

            $_SESSION['admin_username'] = $new_username;
            $_SESSION['admin_full_name'] = trim($first_name . ' ' . $last_name);
            $admin_user = $new_username;
            $admin_info['first_name'] = $first_name;
            $admin_info['last_name'] = $last_name;
            $admin_info['email'] = $email;
            $admin_info['phone_number'] = $phone_number;
            $message = "Account credentials updated successfully.";
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

 // --- Branding Update ---
 if(isset($_POST['update_branding']) && is_super_admin()) {
    // Handle Cropped Logo Upload
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
                    @unlink("../assets/images/" . $old_bg);
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
                    @unlink("../assets/images/" . $old_img);
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

    if(empty($message) && empty($error)) $message = "Branding saved.";
}

// --- Maintenance Mode Update ---
if(isset($_POST['update_maintenance_mode']) && is_super_admin()) {
    $status = $_POST['maintenance_status'];
    $val = (int)$_POST['maintenance_duration_value'];
    $unit = $_POST['maintenance_duration_unit'];
    
    $multiplier = 3600;
    if($unit == 'seconds') $multiplier = 1;
    elseif($unit == 'minutes') $multiplier = 60;
    elseif($unit == 'hours') $multiplier = 3600;
    elseif($unit == 'days') $multiplier = 86400;
    elseif($unit == 'weeks') $multiplier = 604800;
    elseif($unit == 'months') $multiplier = 2592000;

    $end_time = time() + ($val * $multiplier);

    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('maintenance_mode', '$status') ON DUPLICATE KEY UPDATE setting_value='$status'");
    if($status == '1') {
        mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('maintenance_end_time', '$end_time') ON DUPLICATE KEY UPDATE setting_value='$end_time'");
        $message = "Maintenance mode enabled for $val $unit.";
    } else {
        $message = "Maintenance mode disabled.";
    }
    $current_maint_mode = $status;
}

// --- Theme Update ---
if(isset($_POST['update_theme']) && is_super_admin()) {
    if(isset($_POST['primary_color'])){
        $colors = [
            'theme_primary' => $_POST['primary_color'],
            'theme_dark' => $_POST['dark_color'],
            'theme_accent' => $_POST['accent_color'],
            'theme_danger' => $_POST['danger_color'],
            'theme_info' => $_POST['info_color'],
            'theme_bg_body' => $_POST['bg_body_color'],
            'theme_bg_surface' => $_POST['bg_surface_color'],
            'theme_text_main' => $_POST['text_main_color'],
            'theme_sidebar_bg' => $_POST['sidebar_bg_color'],
            'theme_sidebar_text' => $_POST['sidebar_text_color']
        ];
        foreach($colors as $key => $val){
            $val = mysqli_real_escape_string($conn, $val);
            mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
        }
        $theme = get_theme_colors($conn); // Refresh
        $message = "Theme colors updated successfully.";
    }
}

// --- Policies Update ---
if(isset($_POST['update_policies']) && is_super_admin()) {
    // Handle House Rules File Upload
    if(isset($_FILES['house_rules_file']) && $_FILES['house_rules_file']['error'] == 0){
        $target_dir = "../uploads/settings/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["house_rules_file"]["name"], PATHINFO_EXTENSION));
        $new_filename = "house_rules_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["house_rules_file"]["tmp_name"], $target_file)){
            $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='house_rules'");
            if($row = mysqli_fetch_assoc($q)){
                $old_file = $row['setting_value'];
                if(!empty($old_file) && file_exists("../uploads/settings/" . $old_file)){
                    @unlink("../uploads/settings/" . $old_file);
                }
            }
            
            $val = mysqli_real_escape_string($conn, $new_filename);
            mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('house_rules', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
            $message .= " House rules file updated.";
            $current_house_rules = $new_filename;
        } else {
            $error = "Failed to upload house rules file.";
        }
    }

    // Handle GCash QR Image Upload
    if(isset($_FILES['gcash_qr_image']) && $_FILES['gcash_qr_image']['error'] == 0){
        $target_dir = "../uploads/settings/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["gcash_qr_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "gcash_qr_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["gcash_qr_image"]["tmp_name"], $target_file)){
            $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='gcash_qr'");
            if($row = mysqli_fetch_assoc($q)){
                $old_file = $row['setting_value'];
                if(!empty($old_file) && file_exists("../uploads/settings/" . $old_file)){
                    @unlink("../uploads/settings/" . $old_file);
                }
            }
            
            $val = mysqli_real_escape_string($conn, $new_filename);
            mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('gcash_qr', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
            $message .= " GCash QR updated.";
            $current_gcash_qr = $new_filename;
        } else {
            $error = "Failed to upload GCash QR.";
        }
    }

    // Handle Clearance Form File Upload
    if(isset($_FILES['clearance_file']) && $_FILES['clearance_file']['error'] == 0){
        $target_dir = "../uploads/settings/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["clearance_file"]["name"], PATHINFO_EXTENSION));
        $new_filename = "clearance_form_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["clearance_file"]["tmp_name"], $target_file)){
            $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='clearance_file'");
            if($row = mysqli_fetch_assoc($q)){
                $old_file = $row['setting_value'];
                if(!empty($old_file) && file_exists("../uploads/settings/" . $old_file)){
                    @unlink("../uploads/settings/" . $old_file);
                }
            }
            
            $val = mysqli_real_escape_string($conn, $new_filename);
            mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('clearance_file', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
            $message .= " Clearance form updated.";
            $current_clearance_file = $new_filename;
        } else {
            $error = "Failed to upload clearance form.";
        }
    }
    if(empty($message) && empty($error)) $message = "System policies saved.";
    }


// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];
?>
<?php
// Fetch SMTP settings for the form
$smtp_settings = [
    'host' => '', 'port' => '', 'username' => '', 'password' => '', 
    'from_email' => '', 'from_name' => ''
];
$smtp_q = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'smtp_%'");
while($row = mysqli_fetch_assoc($smtp_q)){
    $key = str_replace('smtp_', '', $row['setting_key']);
    if(array_key_exists($key, $smtp_settings)){
        $smtp_settings[$key] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        .img-container { max-height: 400px; overflow: hidden; }
        .profile-setting-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #ffffff;
        }
        .profile-setting-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1></h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <h4 class="fw-bold mb-4 text-center" style="color: var(--dark-green);">Admin Profile</h4>
                    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <div class="row g-4">
                        <!-- Account Credentials Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#credentialsModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-user-shield fa-3x text-success mb-3"></i>
                                <h5 class="fw-bold text-dark">Account Credentials</h5>
                                <p class="text-muted small mb-0">Update your personal info, login details, and profile picture.</p>
                            </div>
                        </div>
                        
                        <?php if(is_super_admin()): ?>
                        <!-- System Branding Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#brandingModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-image fa-3x text-primary mb-3"></i>
                                <h5 class="fw-bold text-dark">System Branding</h5>
                                <p class="text-muted small mb-0">Update the system logo, login background, and living area images.</p>
                            </div>
                        </div>

                        <!-- Theme Customization Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#themeModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-palette fa-3x text-warning mb-3"></i>
                                <h5 class="fw-bold text-dark">Theme Customization</h5>
                                <p class="text-muted small mb-0">Change the primary, dark, and accent colors of the system.</p>
                            </div>
                        </div>

                        <!-- System Policies Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#policiesModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-file-contract fa-3x text-info mb-3"></i>
                                <h5 class="fw-bold text-dark">System Policies</h5>
                                <p class="text-muted small mb-0">Upload house rules, GCash QR, and printable clearance forms.</p>
                            </div>
                        </div>
                        <!-- SMTP Settings Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#smtpModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-paper-plane fa-3x text-secondary mb-3"></i>
                                <h5 class="fw-bold text-dark">Email (SMTP) Settings</h5>
                                <p class="text-muted small mb-0">Configure the system to send emails for notifications and password resets.</p>
                            </div>
                        </div>

                        <!-- System Maintenance Card -->
                        <div class="col-md-4">
                            <div class="card card-custom h-100 text-center p-4 profile-setting-card" data-bs-toggle="modal" data-bs-target="#maintenanceModeModal" style="cursor:pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-tools fa-3x text-danger mb-3"></i>
                                <h5 class="fw-bold text-dark">System Maintenance</h5>
                                <?php if($current_maint_mode == '1' && $current_maint_end > time()): ?>
                                    <p class="text-danger small fw-bold mb-0" id="cardMaintCountdown"><span class="spinner-grow spinner-grow-sm me-1" style="width: 0.7rem; height: 0.7rem; vertical-align: middle;"></span> Active (Calculating...)</p>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">Enable or disable maintenance mode for the public website.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<!-- Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-shield me-2"></i>Account Credentials</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body bg-light p-4" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden;">
                    <input type="hidden" name="cropped_profile_data" id="cropped_profile_data">
                    <div class="mb-4 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">Personal Profile Picture</label>
                        <div class="d-flex align-items-center gap-3">
                            <?php
                                $my_avatar = "https://ui-avatars.com/api/?name=" . urlencode($admin_info['username']) . "&background=2DC08F&color=fff";
                                if (!empty($admin_info['profile_image']) && file_exists("../uploads/profiles/" . $admin_info['profile_image'])) {
                                    $my_avatar = "../uploads/profiles/" . $admin_info['profile_image'] . "?v=" . time();
                                }
                            ?>
                            <img src="<?= htmlspecialchars($my_avatar) ?>" id="admin_profile_preview" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="admin_profile_image" id="admin_profile_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 bg-white p-3 rounded shadow-sm mx-0">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($admin_info['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($admin_info['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Email</label>
                            <div class="input-group input-group-sm"><span class="input-group-text"><i class="fas fa-envelope"></i></span><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin_info['email'] ?? '') ?>"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Phone Number</label>
                            <div class="input-group input-group-sm"><span class="input-group-text"><i class="fas fa-phone"></i></span><input type="text" name="phone_number" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" value="<?= htmlspecialchars($admin_info['phone_number'] ?? '') ?>" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;"></div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Login Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin_user) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">New Password</label>
                            <input type="password" name="password" id="newPass" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Confirm new password">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="showPassToggle">
                            <label class="form-check-label small text-muted" for="showPassToggle">Show Password</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_credentials" class="btn btn-success fw-bold">Save Credentials</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if(is_super_admin()): ?>
<!-- Branding Modal -->
<div class="modal fade" id="brandingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-image me-2"></i>System Branding</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body bg-light p-4" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden;">
                    <input type="hidden" name="cropped_logo_data" id="cropped_logo_data">
                    
                    <!-- Logo -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-bold small text-muted mb-0">System Logo (Recommended: Square)</label>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="file" name="site_logo" id="logo_input" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- Login Background -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">Login Background</label>
                        <input type="file" name="login_bg" id="login_bg_input" class="form-control mb-2" accept="image/*">
                        <div id="login_bg_preview_container" style="display:none;">
                            <img id="login_bg_preview" src="" class="rounded w-100 border shadow-sm" style="height: 120px; object-fit: cover;">
                        </div>
                        <div class="text-end mt-1">
                            <small class="text-muted" style="font-size: 0.7rem;">Current: <?= htmlspecialchars($current_login_bg) ?></small>
                        </div>
                    </div>

                    <!-- Living Area Image -->
                    <div class="mb-3 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">Living Area Image</label>
                        <input type="file" name="living_area_img" class="form-control mb-2" accept="image/*">
                        <div class="text-end mt-1">
                            <small class="text-muted" style="font-size: 0.7rem;">Current: <?= htmlspecialchars($current_living_area_img) ?></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_branding" class="btn btn-primary fw-bold">Save Branding</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Theme Modal -->
<div class="modal fade" id="themeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="fas fa-palette me-2"></i>Theme Customization</h5>
                    <h6 class="fst-italic mb-0 mt-1 opacity-75" style="font-size: 0.85rem;">(Under Maintenance)</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light p-4" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden;">
                    <div class="bg-white p-3 rounded shadow-sm mb-3">
                        <div class="row g-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold text-muted mb-1">Primary</label>
                                <input type="color" name="primary_color" id="primary_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['primary'] ?>" title="Primary Color">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-muted mb-1">Dark/Sidebar</label>
                                <input type="color" name="dark_color" id="dark_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['dark'] ?>" title="Dark Color">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-muted mb-1">Accent</label>
                                <input type="color" name="accent_color" id="accent_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['accent'] ?>" title="Accent Color">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Danger/Error</label>
                                <input type="color" name="danger_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['danger'] ?>" title="Danger Color">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted mb-1">Info/Blue</label>
                                <input type="color" name="info_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['info'] ?>" title="Info Color">
                            </div>
                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-layer-group me-2"></i> Layout & Backgrounds (Light Mode)</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Body Background</label>
                                <input type="color" name="bg_body_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['bg_body'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Card Surface</label>
                                <input type="color" name="bg_surface_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['bg_surface'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Main Text</label>
                                <input type="color" name="text_main_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['text_main'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Sidebar Background</label>
                                <input type="color" name="sidebar_bg_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['sidebar_bg'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Sidebar Text / Icons</label>
                                <input type="color" name="sidebar_text_color" class="form-control form-control-color w-100 border shadow-sm" value="<?= $theme['sidebar_text'] ?>">
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none fw-bold" onclick="confirmReset('theme')">
                                <i class="fas fa-undo me-1"></i> Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_theme" class="btn btn-warning fw-bold">Save Theme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Policies Modal -->
<div class="modal fade" id="policiesModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-contract me-2"></i>System Policies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body bg-light p-4" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden;">
                    <!-- House Rules -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">House Rules & Regulations File</label>
                        <input type="file" name="house_rules_file" class="form-control mb-2" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <?php if(!empty($current_house_rules)): ?>
                            <div class="text-end">
                                <a href="../uploads/settings/<?= htmlspecialchars($current_house_rules) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-download me-1"></i> View Current File</a>
                            </div>
                        <?php endif; ?>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Upload a PDF, DOC, or Image file containing the house rules.</small>
                    </div>

                    <!-- GCash QR -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">GCash QR Code</label>
                        <input type="file" name="gcash_qr_image" class="form-control mb-2" accept="image/*">
                        <?php if(!empty($current_gcash_qr)): ?>
                            <div class="text-end">
                                <a href="../uploads/settings/<?= htmlspecialchars($current_gcash_qr) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-qrcode me-1"></i> View Current QR</a>
                            </div>
                        <?php endif; ?>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Upload the GCash QR Code displayed to guests on the payment page.</small>
                    </div>

                    <!-- Clearance Form -->
                    <div class="mb-3 bg-white p-3 rounded shadow-sm">
                        <label class="form-label fw-bold small text-muted">Printable Clearance Form</label>
                        <input type="file" name="clearance_file" class="form-control mb-2" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <?php if(!empty($current_clearance_file)): ?>
                            <div class="text-end">
                                <a href="../uploads/settings/<?= htmlspecialchars($current_clearance_file) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print me-1"></i> View / Print Form</a>
                            </div>
                        <?php endif; ?>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Upload a blank clearance form to print for tenants completing their contracts.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_policies" class="btn btn-info fw-bold text-white">Save Policies</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SMTP Modal -->
<div class="modal fade" id="smtpModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-paper-plane me-2"></i>Email (SMTP) Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="alert alert-info small"><i class="fas fa-info-circle me-2"></i>Configure your Gmail or other SMTP service to send system emails. For Gmail, you'll need to generate an "App Password".</div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtp_settings['host']) ?>" placeholder="e.g., smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($smtp_settings['port']) ?>" min="0" placeholder="e.g., 587">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">SMTP Username (Your Email)</label>
                            <input type="email" name="smtp_username" class="form-control" value="<?= htmlspecialchars($smtp_settings['username']) ?>" placeholder="your-email@gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">SMTP Password (App Password)</label>
                            <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($smtp_settings['password']) ?>" placeholder="Enter App Password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">"From" Email</label>
                            <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($smtp_settings['from_email']) ?>" placeholder="no-reply@yourdomain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">"From" Name</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($smtp_settings['from_name']) ?>" placeholder="Dormitory">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none fw-bold" onclick="confirmReset('smtp')"><i class="fas fa-undo me-1"></i> Clear Settings</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_smtp" class="btn btn-secondary fw-bold">Save SMTP Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Mode Modal -->
<div class="modal fade" id="maintenanceModeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-tools me-2"></i>System Maintenance Mode</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light p-4">
                    <div class="alert alert-warning small"><i class="fas fa-exclamation-triangle me-2"></i>Enabling maintenance mode will block all users and guests from accessing the website. Only admins will be able to log in.</div>
                    <?php if($current_maint_mode == '1' && $current_maint_end > time()): ?>
                        <div class="alert alert-danger small border-danger shadow-sm" id="adminMaintCountdownContainer">
                            <i class="fas fa-clock me-2"></i> Active Maintenance Ends In: <strong id="adminMaintCountdown" class="fs-6">Calculating...</strong>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Maintenance Status</label>
                        <select name="maintenance_status" class="form-select" id="maintStatus" onchange="toggleMaintDuration()">
                            <option value="0" <?= $current_maint_mode == '0' ? 'selected' : '' ?>>Disabled</option>
                            <option value="1" <?= $current_maint_mode == '1' ? 'selected' : '' ?>>Enabled</option>
                        </select>
                    </div>
                    <div class="mb-3" id="maintDurationDiv" style="display: <?= $current_maint_mode == '1' ? 'block' : 'none' ?>;">
                        <label class="form-label fw-bold">Duration</label>
                        <div class="input-group">
                            <input type="number" name="maintenance_duration_value" class="form-control" value="1" min="1">
                            <select name="maintenance_duration_unit" class="form-select">
                                <option value="seconds">Seconds</option>
                                <option value="minutes">Minutes</option>
                                <option value="hours" selected>Hours</option>
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                            </select>
                        </div>
                        <small class="text-muted">How long will the maintenance last?</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_maintenance_mode" class="btn btn-danger fw-bold">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--primary-green);">
                <h5 class="modal-title fw-bold"><i class="fas fa-crop me-2"></i>Crop Image</h5>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="admin.js"></script>
<script>
    // Fix accessibility warning (Blocked aria-hidden) when closing modals
    document.addEventListener('hide.bs.modal', function () {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });

    <?php if($current_maint_mode == '1' && $current_maint_end > time()): ?>
        var adminMaintEndTime = <?= $current_maint_end * 1000 ?>;
        function updateAdminMaintTimer() {
            var now = new Date().getTime();
            var distance = adminMaintEndTime - now;
            var countdownEl = document.getElementById('adminMaintCountdown');
            var cardCountdownEl = document.getElementById('cardMaintCountdown');
            var containerEl = document.getElementById('adminMaintCountdownContainer');
            
            if (distance <= 0) {
                if(countdownEl) countdownEl.innerHTML = 'Maintenance period has ended.';
                if(cardCountdownEl) cardCountdownEl.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Maintenance ended';
                if(containerEl) {
                    containerEl.classList.remove('alert-danger', 'border-danger');
                    containerEl.classList.add('alert-success', 'border-success');
                }
                return;
            }
            
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            var display = '';
            if (days > 0) display += days + 'd ';
            if (hours > 0 || days > 0) display += hours + 'h ';
            display += minutes + 'm ' + seconds + 's';
            
            if(countdownEl) countdownEl.innerHTML = display;
            if(cardCountdownEl) cardCountdownEl.innerHTML = '<span class="spinner-grow spinner-grow-sm text-danger me-1" style="width: 0.7rem; height: 0.7rem; vertical-align: middle;"></span> Active (' + display + ')';
        }
        setInterval(updateAdminMaintTimer, 1000);
        updateAdminMaintTimer();
    <?php endif; ?>

    function toggleMaintDuration() {
        document.getElementById('maintDurationDiv').style.display = document.getElementById('maintStatus').value == '1' ? 'block' : 'none';
    }

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
    const profileInput = document.getElementById('admin_profile_image');
    const modal = new bootstrap.Modal(document.getElementById('cropModal'));
    let currentCropTarget = '';

    if (input) {
        input.addEventListener('change', function(e) { handleCropChange(e, 'logo'); });
    }
    
    if (profileInput) {
        profileInput.addEventListener('change', function(e) { handleCropChange(e, 'profile'); });
    }

    function handleCropChange(e, target) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(e) {
                image.src = e.target.result;
                currentCropTarget = target;
                modal.show();
                if(cropper) cropper.destroy();
                setTimeout(() => { cropper = new Cropper(image, { aspectRatio: 1, viewMode: 1 }); }, 200);
            };
            reader.readAsDataURL(files[0]);
        }
    }

    document.getElementById('crop-btn').addEventListener('click', function() {
        const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
        const dataUrl = canvas.toDataURL('image/png');
        if (currentCropTarget === 'logo') {
            document.getElementById('cropped_logo_data').value = dataUrl;
            document.querySelectorAll('img[src*="WokeLogo.jpg"]').forEach(img => {
                img.src = dataUrl;
            });
        } else if (currentCropTarget === 'profile') {
            document.getElementById('cropped_profile_data').value = dataUrl;
            document.getElementById('admin_profile_preview').src = dataUrl;
        }
        modal.hide();
    });

    // Theme Preview
    const root = document.documentElement;
    const pInput = document.getElementById('primary_color');
    const dInput = document.getElementById('dark_color');
    const aInput = document.getElementById('accent_color');
    const dangInput = document.querySelector('input[name="danger_color"]');
    const infoInput = document.querySelector('input[name="info_color"]');
    const bgbInput = document.querySelector('input[name="bg_body_color"]');
    const bgsInput = document.querySelector('input[name="bg_surface_color"]');
    const txtInput = document.querySelector('input[name="text_main_color"]');
    const sbgInput = document.querySelector('input[name="sidebar_bg_color"]');
    const stxtInput = document.querySelector('input[name="sidebar_text_color"]');

    function updateTheme() {
        root.style.setProperty('--primary-green', pInput.value);
        root.style.setProperty('--dark-green', dInput.value);
        root.style.setProperty('--accent-yellow', aInput.value);
        root.style.setProperty('--danger-color', dangInput.value);
        root.style.setProperty('--info-color', infoInput.value);
        
        if(!document.body.classList.contains('night-mode')) {
            root.style.setProperty('--bg-body', bgbInput.value);
            root.style.setProperty('--bg-surface', bgsInput.value);
            root.style.setProperty('--text-main', txtInput.value);
            root.style.setProperty('--sidebar-bg', sbgInput.value);
            root.style.setProperty('--sidebar-text', stxtInput.value);
        }
    }

    [pInput, dInput, aInput, dangInput, infoInput, bgbInput, bgsInput, txtInput, sbgInput, stxtInput].forEach(input => {
        if (input) input.addEventListener('input', updateTheme);
    });

    function confirmReset(type) {
        let title = type === 'theme' ? 'Reset Theme?' : 'Clear SMTP Settings?';
        let text = type === 'theme' 
            ? "Are you sure you want to reset all theme colors to their default values?"
            : "Are you sure you want to clear all saved SMTP email settings?";

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector(`form button[name="reset_defaults"][value="${type}"]`)?.form.submit();
            }
        });
    }

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

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) { lastUpdate = t; } 
        else if (t > lastUpdate) { sessionStorage.setItem('playNotifSound', 'true'); location.reload(); }
    });
}
setInterval(checkUpdates, 3000);

if(sessionStorage.getItem('playNotifSound') === 'true') {
    let audio = new Audio('../assets/sounds/notification.mp3');
    audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
    audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
    sessionStorage.removeItem('playNotifSound');
}
</script>
</body>
</html>