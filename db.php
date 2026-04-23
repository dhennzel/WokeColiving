<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = mysqli_connect("localhost", "root", "");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Create database if not exists
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS woke_coliving");

// Select the database
mysqli_select_db($conn, "woke_coliving");

// Enable error reporting for mysqli to catch exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ensure site_settings table exists globally
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(50) UNIQUE NOT NULL, setting_value TEXT)");

// Ensure Incomplete status exists in reservations ENUM
mysqli_query($conn, "ALTER TABLE reservations MODIFY COLUMN status ENUM('Pending','Verifying','Approved','Cancelled','Completed','Incomplete') DEFAULT 'Pending'");

// Auto-migrate new schema changes (Companions Feature)
$mig_comp = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='migration_companions_v2'");
if(mysqli_num_rows($mig_comp) == 0) {
    $check_res_tbl2 = mysqli_query($conn, "SHOW TABLES LIKE 'reservations'");
    if(mysqli_num_rows($check_res_tbl2) > 0) {
        $check_comp = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'companions'");
        if(mysqli_num_rows($check_comp) == 0) {
            mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN companions TEXT DEFAULT NULL");
        }
    }
    $check_res_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'residents'");
    if(mysqli_num_rows($check_res_tbl) > 0) {
        $check_res_comp = mysqli_query($conn, "SHOW COLUMNS FROM residents LIKE 'is_companion'");
        if(mysqli_num_rows($check_res_comp) == 0) {
            mysqli_query($conn, "ALTER TABLE residents MODIFY user_id INT NULL");
            mysqli_query($conn, "ALTER TABLE residents ADD COLUMN is_companion TINYINT(1) DEFAULT 0");
            mysqli_query($conn, "ALTER TABLE residents ADD COLUMN primary_res_id INT DEFAULT NULL");
        }
    }
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('migration_companions_v2', '1')");
}

if (!function_exists('get_theme_colors')) {
function get_theme_colors($conn) {
    $theme = [
        'primary' => '#34B875', 
        'dark' => '#1B5E20', 
        'accent' => '#F0B429',
        'danger' => '#e53935',
        'info' => '#0288d1',
        'bg_body' => '#F8F9FA',
        'bg_surface' => '#FFFFFF',
        'text_main' => '#333333',
        'sidebar_bg' => '#FFFFFF',
        'sidebar_text' => '#6c757d'
    ];

    $q = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'theme_%'");
    if($q){
        while($row = mysqli_fetch_assoc($q)){
            $key = str_replace('theme_', '', $row['setting_key']);
            if(array_key_exists($key, $theme)) {
                $theme[$key] = $row['setting_value'];
            }
        }
    }
    return $theme;
}
}

if (!function_exists('is_super_admin')) {
function is_super_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin';
}
}

if (!function_exists('send_system_email')) {
function send_system_email($conn, $to, $subject, $message, $recipient_name = '') {
    // Fetch SMTP settings from DB
    $settings_q = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'smtp_%'");
    $smtp_settings = [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => '',
        'from_name' => 'Woke Coliving'
    ];
    while($row = mysqli_fetch_assoc($settings_q)) {
        $key = str_replace('smtp_', '', $row['setting_key']);
        if(array_key_exists($key, $smtp_settings)) {
            $smtp_settings[$key] = $row['setting_value'];
        }
    }

    $phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';
    if (file_exists($phpmailer_path) && !empty($smtp_settings['username']) && !empty($smtp_settings['password'])) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_settings['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_settings['username'];
            $mail->Password   = $smtp_settings['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$smtp_settings['port'];

            $mail->setFrom($smtp_settings['from_email'], $smtp_settings['from_name']);
            $mail->addAddress($to, $recipient_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    return false; // Don't fallback to prevent server issues
}
}

if (!function_exists('send_notification')) {
function send_notification($conn, $user_id, $message, $type = 'System', $custom_subject = null) {
    // 1. Insert into Database (In-App Notification)

    try {
        $msg_safe = mysqli_real_escape_string($conn, $message);
        $type_safe = mysqli_real_escape_string($conn, $type);
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) VALUES ('$user_id', '$msg_safe', '$type_safe', NOW())");
    } catch (mysqli_sql_exception $e) {
        // Handle missing 'message' column if table existed but was outdated
        if (strpos($e->getMessage(), "Unknown column 'message'") !== false) {
            mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN message TEXT NOT NULL");
            // Retry insert
            try {
                mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type_safe')");
            } catch (mysqli_sql_exception $e2) {
                if (strpos($e2->getMessage(), "Unknown column 'type'") !== false) {
                    mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'System'");
                    mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type_safe')");
                }
            }
        } elseif (strpos($e->getMessage(), "Unknown column 'type'") !== false) {
            mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'System'");
            // Retry insert
            mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type_safe')");
        } else {
            // Log other errors but don't crash the page
            error_log("Notification Error: " . $e->getMessage());
        }
    }

    // 2. Send Email (Simulated/Actual)
    // Fetch user email
    $u_res = mysqli_query($conn, "SELECT email, CONCAT(last_name, ', ', first_name) as full_name, phone_number FROM users WHERE user_id='$user_id'");
    if($u_row = mysqli_fetch_assoc($u_res)){
        $to = $u_row['email'];
        $subject = $custom_subject ?? "Woke Coliving Notification: $type";
        send_system_email($conn, $to, $subject, $message, $u_row['full_name']);
    }
}
}

// --- ACTIVITY LOGGING ---
if (!function_exists('log_activity')) {
function log_activity($conn, $user_id, $action, $details = "") {
    // Ensure table exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Ensure new columns exist for tracking performer
    $cols_q = mysqli_query($conn, "SHOW COLUMNS FROM activity_logs LIKE 'performed_by'");
    if(mysqli_num_rows($cols_q) == 0) {
        mysqli_query($conn, "ALTER TABLE activity_logs ADD COLUMN performed_by VARCHAR(100) DEFAULT 'System'");
    }
    $cols_r = mysqli_query($conn, "SHOW COLUMNS FROM activity_logs LIKE 'role'");
    if(mysqli_num_rows($cols_r) == 0) {
        mysqli_query($conn, "ALTER TABLE activity_logs ADD COLUMN role VARCHAR(50) DEFAULT 'System'");
    }

    $act = mysqli_real_escape_string($conn, $action);
    $det = mysqli_real_escape_string($conn, $details);
    
    $performer = 'System';
    $role = 'System';
    
    if(isset($_SESSION['admin_username'])){
        $fname = trim($_SESSION['admin_full_name'] ?? '');
        $performer = !empty($fname) ? $fname . ' (' . $_SESSION['admin_username'] . ')' : $_SESSION['admin_username'];
        $role = $_SESSION['admin_role'] ?? 'Admin';
    } elseif(isset($_SESSION['user_id'])) {
        $performer = 'User';
        $role = 'User';
    }
    
    $performer = mysqli_real_escape_string($conn, $performer);
    $role = mysqli_real_escape_string($conn, $role);

    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, details, performed_by, role) VALUES ('$user_id', '$act', '$det', '$performer', '$role')");
}
}

// --- RESERVATIONS TABLE ---
if (!function_exists('setup_reservations_table')) {
function setup_reservations_table($conn) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'reservations'");
    if(mysqli_num_rows($check) == 0) {
        $sql = "CREATE TABLE reservations (
            reservation_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            room_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            months INT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('Pending','Verifying','Approved','Cancelled','Completed','Incomplete') DEFAULT 'Pending',
            bed_preference VARCHAR(50) DEFAULT 'Any',
            signature_image VARCHAR(255) DEFAULT NULL,
            signature_required TINYINT(1) DEFAULT 0,
            cancellation_reason VARCHAR(255) DEFAULT NULL,
            is_archived TINYINT(1) DEFAULT 0,
            occupation VARCHAR(50) DEFAULT NULL,
            company_or_school VARCHAR(100) DEFAULT NULL,
            contact_person_name VARCHAR(100) DEFAULT NULL,
            contact_person_number VARCHAR(20) DEFAULT NULL,
            companions TEXT DEFAULT NULL,
            extended_from INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        )";
        mysqli_query($conn, $sql);
    } else {
        $check_comp = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'companions'");
        if(mysqli_num_rows($check_comp) == 0) {
            mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN companions TEXT DEFAULT NULL");
        }
    }
}
}

// --- PAYMENTS TABLE ---
if (!function_exists('setup_payments_table')) {
function setup_payments_table($conn) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
    if(mysqli_num_rows($check) == 0) {
        $sql = "CREATE TABLE payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('Paid','Unpaid') DEFAULT 'Unpaid',
            payment_date DATETIME DEFAULT NULL,
            reference_number VARCHAR(100) DEFAULT NULL,
            proof_image VARCHAR(255) DEFAULT NULL,
            description VARCHAR(255) DEFAULT 'Room Payment',
            is_penalized TINYINT(1) DEFAULT 0,
            FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
        )";
        mysqli_query($conn, $sql);
    } else {
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'is_archived'");
        if(mysqli_num_rows($check_col) == 0) {
            mysqli_query($conn, "ALTER TABLE payments ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
        }
        $check_pk = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'parking_reservation_id'");
        if(mysqli_num_rows($check_pk) == 0) {
            mysqli_query($conn, "ALTER TABLE payments ADD COLUMN parking_reservation_id INT DEFAULT NULL AFTER reservation_id");
        }
    }
}
}

// --- WITHDRAWAL REQUESTS TABLE ---
if (!function_exists('setup_withdrawal_requests_table')) {
function setup_withdrawal_requests_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS withdrawal_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        gcash_name VARCHAR(100) NOT NULL,
        gcash_number VARCHAR(20) NOT NULL,
        status ENUM('Pending', 'Processed', 'Rejected') DEFAULT 'Pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at DATETIME NULL,
        admin_notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
}
}

// --- ACCOUNT DELETION REQUESTS TABLE ---
if (!function_exists('setup_deletion_requests_table')) {
function setup_deletion_requests_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS account_deletion_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
}
}

// --- PARKING MODULE TABLES ---
if (!function_exists('setup_parking_tables')) {
function setup_parking_tables($conn) {
    // 1. Parking Slots Table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS parking_slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slot_name VARCHAR(50) NOT NULL,
        slot_type ENUM('Car', 'Motorcycle') NOT NULL,
        status ENUM('Available', 'Occupied') DEFAULT 'Available',
        monthly_rate DECIMAL(10,2) NOT NULL,
        daily_rate DECIMAL(10,2) NOT NULL,
        is_archived TINYINT(1) DEFAULT 0
    )");

    // 2. Parking Reservations Table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS parking_reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        slot_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE,
        total_cost DECIMAL(10,2) NOT NULL,
        billing_type ENUM('Monthly', 'Daily') NOT NULL,
        status ENUM('Active', 'Completed') DEFAULT 'Active',
        vehicle_plate VARCHAR(50) DEFAULT NULL,
        vehicle_details VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (slot_id) REFERENCES parking_slots(id)
    )");

    // Ensure vehicle columns exist for existing tables
    $check_pr_plate = mysqli_query($conn, "SHOW COLUMNS FROM parking_reservations LIKE 'vehicle_plate'");
    if(mysqli_num_rows($check_pr_plate) == 0) {
        mysqli_query($conn, "ALTER TABLE parking_reservations ADD COLUMN vehicle_plate VARCHAR(50) DEFAULT NULL AFTER status");
        mysqli_query($conn, "ALTER TABLE parking_reservations ADD COLUMN vehicle_details VARCHAR(100) DEFAULT NULL AFTER vehicle_plate");
    }

    // 3. Pre-populate slots if table is empty
    // 3. Pre-populate slots if table is empty
    $check_slots = mysqli_query($conn, "SELECT id FROM parking_slots LIMIT 1");
    if(mysqli_num_rows($check_slots) == 0){
        for($i = 1; $i <= 5; $i++) mysqli_query($conn, "INSERT INTO parking_slots (slot_name, slot_type, monthly_rate, daily_rate) VALUES ('Car Slot $i', 'Car', 600.00, 50.00)");
        for($i = 1; $i <= 5; $i++) mysqli_query($conn, "INSERT INTO parking_slots (slot_name, slot_type, monthly_rate, daily_rate) VALUES ('Motorcycle Slot $i', 'Motorcycle', 600.00, 50.00)");
    }

    // Migration: Update existing slots to new rates (600 Monthly, 50 Daily)
    $migration_check_rates = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='migration_parking_rates_v1'");
    if(mysqli_num_rows($migration_check_rates) == 0) {
        mysqli_query($conn, "UPDATE parking_slots SET monthly_rate = 600.00, daily_rate = 50.00");
        mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('migration_parking_rates_v1', '1')");
    }
}
}

// --- KEY MONITORING TABLES ---
if (!function_exists('setup_key_tables')) {
function setup_key_tables($conn) {
    // 1. Keys Table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `keys` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(100) NOT NULL,
        type ENUM('Room', 'Parking') NOT NULL,
        reference_id INT NOT NULL,
        status ENUM('Available', 'Released') DEFAULT 'Available'
    )");

    // 2. Key Transactions Table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS key_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_id INT NOT NULL,
        user_id INT NOT NULL,
        released_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        returned_at DATETIME DEFAULT NULL,
        status ENUM('Active', 'Returned') DEFAULT 'Active',
        FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // 3. Sync Keys with Rooms
    $rooms = mysqli_query($conn, "SELECT room_id, room_name, room_number, total_beds FROM rooms WHERE is_archived = 0");
    while($r = mysqli_fetch_assoc($rooms)){
        $rid = $r['room_id'];
        $total_beds = (int)$r['total_beds'];
        
        $chk = mysqli_query($conn, "SELECT id FROM `keys` WHERE type='Room' AND reference_id=$rid");
        $existing_keys_count = mysqli_num_rows($chk);

        if ($existing_keys_count < $total_beds) {
            for ($i = $existing_keys_count + 1; $i <= $total_beds; $i++) {
                $room_identifier = !empty($r['room_number']) ? $r['room_number'] : $r['room_name'];
                $name = "Room " . $room_identifier . " Key #" . $i;
                $name = mysqli_real_escape_string($conn, $name);
                mysqli_query($conn, "INSERT INTO `keys` (key_name, type, reference_id) VALUES ('$name', 'Room', $rid)");
            }
        }
    }
}
}

// --- SYSTEM UPDATES TABLE ---
if (!function_exists('setup_updates_table')) {
function setup_updates_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS system_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(20) NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        release_date DATE DEFAULT CURRENT_DATE
    )");

    // List of all updates to ensure they exist in DB
    $updates = [
        ['1.3.6', 'User Control', 'Added option for users to permanently delete their account (requires no active bookings).', 'NOW()'],
        ['1.3.5', 'Account Management', 'Added ability for users to update their email address securely.', 'NOW()'],
        ['1.3.4', 'Bug Fixes & UI Polish', 'Fixed night mode styling issues. Improved responsive layout for mobile devices.', 'NOW()'],
        ['1.3.3', 'Security Update', 'Added Change Password feature for enhanced account security.', 'DATE_SUB(NOW(), INTERVAL 1 DAY)'],
        ['1.3.2', 'Support Features', 'Added "Other Request" option to contact support directly via Messenger.', 'DATE_SUB(NOW(), INTERVAL 2 DAY)'],
        ['1.3.1', 'System Integrity', 'Implemented strict feature access control based on system version.', 'DATE_SUB(NOW(), INTERVAL 3 DAY)'],
        ['1.3.0', 'Dashboard Customization', 'Added ability to hide and reorder dashboard cards. Preferences are saved automatically.', 'DATE_SUB(NOW(), INTERVAL 4 DAY)'],
        ['1.2.0', 'Profile Enhancements', 'Added Bio, Social Links, and Newsletter subscription options.', 'DATE_SUB(NOW(), INTERVAL 7 DAY)'],
        ['1.1.0', 'Waitlist Feature', 'Introduced room waitlists and automated notifications.', 'DATE_SUB(NOW(), INTERVAL 14 DAY)'],
        ['1.0.0', 'Initial Release', 'Core booking and management system launch.', 'DATE_SUB(NOW(), INTERVAL 30 DAY)']
    ];

    foreach ($updates as $upd) {
        $ver = $upd[0];
        $check = mysqli_query($conn, "SELECT id FROM system_updates WHERE version='$ver'");
        if (mysqli_num_rows($check) == 0) {
            $title = mysqli_real_escape_string($conn, $upd[1]);
            $desc = mysqli_real_escape_string($conn, $upd[2]);
            $date_sql = $upd[3]; 
            mysqli_query($conn, "INSERT INTO system_updates (version, title, description, release_date) VALUES ('$ver', '$title', '$desc', $date_sql)");
        }
    }
}
}

// --- SYSTEM COMPLIANCE CHECK ---
if (!function_exists('check_system_compliance')) {
function check_system_compliance($conn, $user_id) {
    // Centralized Schema Definition
    $user_schema = [
        'is_walkin'  => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 0, 'reason' => 'Account needs walk-in status synchronization.'],
        'role'       => ['type' => "VARCHAR(20) DEFAULT 'user'", 'default' => "'user'", 'reason' => 'User role needs to be defined for system access.'],
        'night_mode' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 0, 'reason' => 'Night mode preference needs to be initialized.'],
        'gender'     => ['type' => 'VARCHAR(20) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Gender information is missing and required for booking.'],
        'occupation' => ['type' => 'VARCHAR(50) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Occupation status is missing.'],
        'company'    => ['type' => 'VARCHAR(100) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Company or school information needs to be updated.'],
        'address'    => ['type' => 'TEXT DEFAULT NULL', 'default' => "NULL", 'reason' => 'Address information is missing.'],
        'school_id_image' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Student verification data needs to be updated.'],
        'emergency_contact_name' => ['type' => 'VARCHAR(100) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Emergency contact details are missing.'],
        'emergency_contact_number' => ['type' => 'VARCHAR(20) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Emergency contact details are missing.'],
        'reset_token' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'Password reset feature needs to be enabled.'],
        'reset_expiry' => ['type' => 'DATETIME DEFAULT NULL', 'default' => "NULL", 'reason' => 'Password reset feature needs to be enabled.'],
        'do_not_renew' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 0, 'reason' => 'Account renewal status needs to be initialized.'],
        'newsletter' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 1, 'reason' => 'New Feature: Community Newsletter subscription.'],
        'bio' => ['type' => 'TEXT DEFAULT NULL', 'default' => "NULL", 'reason' => 'New Feature: User Bio for community profile.'],
        'social_link' => ['type' => 'VARCHAR(255) DEFAULT NULL', 'default' => "NULL", 'reason' => 'New Feature: Social Media link field.'],
        'other_request_feature' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 1, 'reason' => 'New Feature: Other Request (Contact Support) option.'],
        'change_password_feature' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 1, 'reason' => 'New Feature: Change Password option.'],
        'change_email_feature' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 1, 'reason' => 'New Feature: Change Email option.'],
        'delete_account_feature' => ['type' => 'TINYINT(1) DEFAULT NULL', 'default' => 1, 'reason' => 'New Feature: Delete Account option.']
    ];

    $update_reasons = [];
    $u_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
    if(!$u_query) return ['is_outdated' => false, 'reasons' => [], 'schema' => $user_schema];
    $user_info = mysqli_fetch_assoc($u_query);
    if(!$user_info) {
        return ['is_outdated' => false, 'reasons' => [], 'schema' => $user_schema];
    }

    $user_columns_q = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_user_columns = [];
    while($col = mysqli_fetch_assoc($user_columns_q)) $existing_user_columns[] = $col['Field'];

    foreach ($user_schema as $column => $details) {
        if (!in_array($column, $existing_user_columns)) {
            $update_reasons[] = $details['reason'];
        } elseif (array_key_exists($column, $user_info) && is_null($user_info[$column]) && $details['default'] !== "NULL") {
            if (!in_array($details['reason'], $update_reasons)) $update_reasons[] = $details['reason'];
        }
    }

    return ['is_outdated' => !empty($update_reasons), 'reasons' => $update_reasons, 'schema' => $user_schema];
}
}

// --- AUTO REFRESH TRIGGER ---
if (!function_exists('trigger_update')) {
function trigger_update($conn) {
    $t = time();
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('last_update', '$t') ON DUPLICATE KEY UPDATE setting_value='$t'");
}
}

// --- AUTOMATED TASKS (Runs on page load) ---

// Skip automated tasks on login/public pages to prevent lag (e.g. SMTP connections)
$current_script = basename($_SERVER['PHP_SELF']);
if (!in_array($current_script, ['admin_login.php', 'login.php', 'register.php', 'index.php'])) {

try {

// 1. Auto-expire Pending reservations older than 24 hours (With Logging)
$expire_query = mysqli_query($conn, "SELECT reservation_id, user_id FROM reservations WHERE status='Pending' AND created_at < (NOW() - INTERVAL 24 HOUR)");
if($expire_query){
    while($row = mysqli_fetch_assoc($expire_query)){
        $rid = $row['reservation_id'];
        $uid = $row['user_id'];
        mysqli_query($conn, "UPDATE reservations SET status='Cancelled', cancellation_reason='Auto-expired due to non-payment' WHERE reservation_id=$rid");
        log_activity($conn, $uid, "Reservation Cancelled", "Reservation #$rid auto-expired due to non-payment.");
    }
}

// 4. Auto-complete Parking reservations where the end date has been reached
$complete_park_q = mysqli_query($conn, "SELECT id, user_id, slot_id FROM parking_reservations WHERE status='Active' AND end_date IS NOT NULL AND end_date < CURDATE()");
if($complete_park_q){
    while($row = mysqli_fetch_assoc($complete_park_q)){
        $pr_id = $row['id'];
        $uid = $row['user_id'];
        $sid = $row['slot_id'];
        mysqli_query($conn, "UPDATE parking_reservations SET status='Completed' WHERE id=$pr_id");
        mysqli_query($conn, "UPDATE parking_slots SET status='Available' WHERE id=$sid");
        log_activity($conn, $uid, "Parking Completed", "Parking reservation #$pr_id automatically marked as Completed.");
        send_notification($conn, $uid, "🅿️ <strong>Parking Completed</strong><br>Your parking reservation has reached its end date.", "Parking");
    }
}

// 5. Sync parking slot status column with active reservations (Fix for ghost occupancy)
mysqli_query($conn, "UPDATE parking_slots SET status = 'Available' WHERE id NOT IN (SELECT slot_id FROM parking_reservations WHERE status = 'Active')");

// 2. Permanently delete archived reservations older than 90 days (Cleanup)
mysqli_query($conn, "DELETE FROM reservations WHERE is_archived=1 AND end_date < (NOW() - INTERVAL 90 DAY)");

// 3. Auto-complete Approved reservations where the end date has been reached
$complete_query = mysqli_query($conn, "SELECT reservation_id, user_id FROM reservations WHERE status='Approved' AND end_date < CURDATE()");
if($complete_query){
    while($row = mysqli_fetch_assoc($complete_query)){
        $rid = $row['reservation_id'];
        $uid = $row['user_id'];
        mysqli_query($conn, "UPDATE reservations SET status='Completed' WHERE reservation_id=$rid");
        log_activity($conn, $uid, "Contract Completed", "Reservation #$rid automatically marked as Completed (End date reached).");
        send_notification($conn, $uid, "🏁 <strong>Stay Completed</strong><br>Your stay for reservation #$rid has reached its scheduled end date and is now marked as Completed. Thank you for staying with us!", "Contract Ended");
    }
}

// 3. Automated Reminders
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$rem_query = mysqli_query($conn, "SELECT r.reservation_id, r.user_id, r.start_date FROM reservations r WHERE r.start_date = '$tomorrow' AND r.status = 'Approved'");
if($rem_query) {
    while($row = mysqli_fetch_assoc($rem_query)) {
        $uid = $row['user_id'];
        $chk_rem = mysqli_query($conn, "SELECT user_id FROM notifications WHERE user_id='$uid' AND type = 'Reminder' AND created_at > DATE_SUB(NOW(), INTERVAL 18 HOUR)");
        if(mysqli_num_rows($chk_rem) == 0){
            send_notification($conn, $uid, "📅 Reminder: Your stay starts tomorrow (" . $row['start_date'] . "). We look forward to welcoming you!", "Reminder");
        }
    }
}
} catch (Exception $e) {
    // Prevent crash if tables don't exist yet
}

// 4. Contract Expiration Reminders (7 days before)
$expire_soon_query = mysqli_query($conn, "SELECT r.reservation_id, r.user_id, r.end_date, rm.room_name FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.status = 'Approved' AND r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");

if($expire_soon_query) {
    while($row = mysqli_fetch_assoc($expire_soon_query)) {
        $uid = $row['user_id'];
        // Check if notification sent in last 24 hours to avoid spam
        $chk_exp = mysqli_query($conn, "SELECT user_id FROM notifications WHERE user_id='$uid' AND type = 'Expiration Alert' AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)");
        if(mysqli_num_rows($chk_exp) == 0){
            $days_left = ceil((strtotime($row['end_date']) - time()) / (60 * 60 * 24));
            $msg = "⚠️ <strong>Contract Expiring Soon</strong><br>Your stay in <strong>{$row['room_name']}</strong> ends on <strong>{$row['end_date']}</strong> ($days_left days left). Please contact admin to renew.";
            send_notification($conn, $uid, $msg, "Expiration Alert");
        }
    }
}

// 5. Auto-apply Late Penalties & Warnings (Accurate to Contract Date)
$grace_period = 5; // days
$penalty_rate = 0.05; // 5%

$pay_query = mysqli_query($conn, "
    SELECT p.payment_id, p.reservation_id, p.amount, p.description, p.payment_date, r.start_date, r.user_id
    FROM payments p
    JOIN reservations r ON p.reservation_id = r.reservation_id
    WHERE p.payment_status = 'Unpaid' 
    AND p.is_penalized = 0 
    AND p.description NOT LIKE 'Late Penalty%' 
");

if($pay_query){
    while($row = mysqli_fetch_assoc($pay_query)){
        $pid = $row['payment_id'];
        $rid = $row['reservation_id'];
        $uid = $row['user_id'];
        $amount = $row['amount'];

        // Determine Due Date (Contract Start Date for Room Payments, Bill Date for others)
        $is_room_payment = (stripos($row['description'] ?? '', 'Room Payment') !== false);
        $due_timestamp = ($is_room_payment && !empty($row['start_date'])) ? strtotime($row['start_date']) : strtotime($row['payment_date']);

        $current_time = time();
        $penalty_timestamp = $due_timestamp + ($grace_period * 86400);

        // A. Apply Penalty if grace period passed
        if($current_time > $penalty_timestamp){
            $penalty_amount = $amount * $penalty_rate;
            $desc = "Late Penalty (5%) for Payment #$pid";

            $ins = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'System', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($ins, "ids", $rid, $penalty_amount, $desc);
            mysqli_stmt_execute($ins);

            mysqli_query($conn, "UPDATE payments SET is_penalized = 1 WHERE payment_id = $pid");

            log_activity($conn, $uid, "Penalty Applied", "Late fee of ".number_format($penalty_amount,2)." applied for Payment #$pid");
            send_notification($conn, $uid, "⚠️ <strong>Late Payment Penalty</strong><br>A penalty of ₱".number_format($penalty_amount,2)." has been applied to your account due to overdue payment.", "Billing Alert");
        }
        // B. Send Warning if Overdue (every 4 hours)
        elseif($current_time > $due_timestamp){
             $chk_warn = mysqli_query($conn, "SELECT user_id FROM notifications WHERE user_id='$uid' AND type = 'Payment Warning' AND created_at > DATE_SUB(NOW(), INTERVAL 4 HOUR)");
             if(mysqli_num_rows($chk_warn) == 0){
                 $msg = "⚠️ <strong>Payment Overdue</strong><br>Your payment of ₱".number_format($amount,2)." was due on ".date('M d, Y', $due_timestamp).". Please pay immediately to avoid penalties.";
                 send_notification($conn, $uid, $msg, "Payment Warning");
             }
        }
    }
}
} // End of automated tasks check


// --- ROOM OCCUPANCY FUNCTIONS ---

// Get room occupancy status (Fully Occupied / Partially Occupied / Vacant)
if (!function_exists('get_room_occupancy_status')) {
function get_room_occupancy_status($conn, $room_id) {
    // Get room details
    $room_q = mysqli_query($conn, "SELECT total_beds, availability FROM rooms WHERE room_id=$room_id");
    $room = mysqli_fetch_assoc($room_q);
    
    if (!$room) return 'Unknown';
    
    // Check if room is under maintenance
    if ($room['availability'] == 'Maintenance') {
        return 'Maintenance';
    }
    
    $total_beds = $room['total_beds'];
    
    // Count current occupants (Approved or Pending reservations that overlap with current date)
    $occ_q = mysqli_query($conn, "SELECT bed_preference, COUNT(*) as cnt FROM reservations 
        WHERE room_id=$room_id AND status IN ('Approved', 'Pending') 
        AND start_date <= CURDATE() AND end_date > CURDATE() GROUP BY bed_preference");
        
    $occupied = 0;
    while($row = mysqli_fetch_assoc($occ_q)) {
        if ($row['bed_preference'] == 'Whole Room') {
            $occupied += $total_beds;
        } else {
            $occupied += $row['cnt'];
        }
    }
    
    if ($occupied == 0) {
        return 'Vacant';
    } elseif ($occupied >= $total_beds) {
        return 'Fully Occupied';
    } else {
        return 'Partially Occupied';
    }
}
}

// Get current occupants for a room
if (!function_exists('get_room_occupants')) {
function get_room_occupants($conn, $room_id) {
    $query = mysqli_query($conn, "
        SELECT r.reservation_id, r.start_date, r.end_date, r.bed_preference, r.status, u.gender,
               u.user_id, u.first_name, u.last_name, u.middle_name, u.profile_image,
               CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name,
               r.companions
        FROM reservations r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.room_id = $room_id 
        AND r.status IN ('Approved', 'Pending')
        AND r.start_date <= CURDATE() 
        AND r.end_date > CURDATE()
        ORDER BY r.bed_preference, u.last_name
    ");
    
    $occupants = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $occupants[] = $row;
        if (!empty($row['companions'])) {
            $comps = json_decode($row['companions'], true);
            if (is_array($comps)) {
                foreach ($comps as $idx => $comp) {
                    $comp_row = $row; 
                    $comp_row['full_name'] = $comp['name'] . ' (Companion)';
                    $comp_row['first_name'] = $comp['name'];
                    $comp_row['last_name'] = '(Companion)';
                    $comp_row['middle_name'] = '';
                    $comp_row['gender'] = $comp['gender'] ?? 'Any';
                    $comp_row['user_id'] = null; 
                    $comp_row['profile_image'] = null;
                    $occupants[] = $comp_row;
                }
            }
        }
    }
    return $occupants;
}
}

// Get room key information
if (!function_exists('get_room_key_info')) {
function get_room_key_info($conn, $room_id) {
    $query = mysqli_query($conn, "
        SELECT k.id as key_id, k.key_name, k.status as key_status,
               kt.id as trans_id, kt.user_id as key_holder_id, kt.released_at,
               CONCAT(u.last_name, ', ', u.first_name) as key_holder_name
        FROM `keys` k
        LEFT JOIN key_transactions kt ON k.id = kt.key_id AND kt.status = 'Active'
        LEFT JOIN users u ON kt.user_id = u.user_id
        WHERE k.type = 'Room' AND k.reference_id = $room_id
    ");
    
    $keys = [];
    if ($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $keys[] = $row;
        }
    }
    return $keys;
}
}

// Get all rooms with occupancy information
if (!function_exists('get_all_rooms_with_occupancy')) {
function get_all_rooms_with_occupancy($conn, $show_hidden = false) {
    $hidden_clause = $show_hidden ? "" : "AND r.is_hidden = 0";
    $query = mysqli_query($conn, "
        SELECT r.*, 
               r.room_number, r.room_type, r.total_beds, r.floor, r.room_name, r.availability
        FROM rooms r
        WHERE r.is_archived = 0
        $hidden_clause
        ORDER BY r.display_order ASC, r.room_type, r.floor, CAST(COALESCE(r.room_number, r.room_name) AS UNSIGNED), COALESCE(r.room_number, r.room_name) ASC
    ");
    
    $rooms = [];
    $seen_room_names = []; // This tracks display names to catch hidden database duplicates
    
    while ($row = mysqli_fetch_assoc($query)) {
        $room_id = $row['room_id'];
        
        // 1. Determine the actual display name
        $room_display = $row['room_name'];
        if (!empty($row['room_number'])) {
            $room_display = trim($row['room_number']);
        } elseif (is_numeric($row['room_name'])) {
            $room_display = trim($row['room_name']);
        }
        // Create a universal key for checking duplicates (e.g., "205")
        $display_key = strtolower($room_display);

        // 2. Fetch occupancy and keys
        $row['occupancy_status'] = get_room_occupancy_status($conn, $room_id);
        $row['occupants'] = get_room_occupants($conn, $room_id);
        $keys = get_room_key_info($conn, $room_id);
        $row['key_info'] = !empty($keys) ? $keys[0] : null; 
        $row['all_keys'] = $keys; 
        
        // 3. Calculate beds
        $occupied_count = 0;
        foreach($row['occupants'] as $occ) {
            if($occ['bed_preference'] == 'Whole Room') {
                $occupied_count += $row['total_beds'];
            } else {
                $occupied_count += 1;
            }
        }
        $row['occupied_count'] = $occupied_count;
        $row['available_beds'] = max(0, $row['total_beds'] - $row['occupied_count']);
        
        // 4. THE FIX: Strict Deduplication by Room Name
        if (isset($seen_room_names[$display_key])) {
            $existing_idx = $seen_room_names[$display_key];
            
            // If this duplicate has the actual tenants, swap it in so it shows "Partially Occupied"
            if ($row['occupied_count'] > $rooms[$existing_idx]['occupied_count']) {
                $rooms[$existing_idx] = $row; 
            }
            
            // We deliberately DO NOT merge the keys here. 
            // This throws away the fake room's keys and fixes the "31 instead of 30" issue.
            continue; 
        }

        // If it's a new room, save it and remember the name
        $seen_room_names[$display_key] = count($rooms);
        $rooms[] = $row;
    }
    return $rooms;
}
}

// Handle key release from room occupancy page
if (!function_exists('release_room_key')) {
function release_room_key($conn, $key_id, $user_id) {
    $chk = mysqli_query($conn, "SELECT status FROM `keys` WHERE id=$key_id");
    $k = mysqli_fetch_assoc($chk);
    
    if ($k['status'] == 'Available') {
        mysqli_query($conn, "INSERT INTO key_transactions (key_id, user_id) VALUES ($key_id, $user_id)");
        mysqli_query($conn, "UPDATE `keys` SET status='Released' WHERE id=$key_id");
        
        send_notification($conn, $user_id, "🔑 <strong>Key Assigned</strong><br>You have been assigned a key. Please keep it safe.", "Key System");
        trigger_update($conn);
        return true;
    }
    return false;
}
}

// Handle key return from room occupancy page
if (!function_exists('return_room_key')) {
function return_room_key($conn, $trans_id) {
    $t_q = mysqli_query($conn, "SELECT key_id, user_id FROM key_transactions WHERE id=$trans_id");
    if ($t = mysqli_fetch_assoc($t_q)) {
        $key_id = $t['key_id'];
        mysqli_query($conn, "UPDATE key_transactions SET status='Returned', returned_at=NOW() WHERE id=$trans_id");
        mysqli_query($conn, "UPDATE `keys` SET status='Available' WHERE id=$key_id");
        
        send_notification($conn, $t['user_id'], "🔑 <strong>Key Returned</strong><br>Your key has been marked as returned.", "Key System");
        trigger_update($conn);
        return true;
    }
    return false;
}
}

// --- INVENTORY MANAGEMENT TABLE ---
if (!function_exists('setup_inventory_table')) {
function setup_inventory_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS inventory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) DEFAULT 'General',
        room_id INT NULL,
        quantity INT DEFAULT 1,
        status ENUM('Good', 'Damaged', 'Repair', 'Lost') DEFAULT 'Good',
        purchase_date DATE NULL,
        cost DECIMAL(10,2) DEFAULT 0.00,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE SET NULL
    )");
}
}

// --- PROPERTY INVENTORY TABLE ---
if (!function_exists('setup_property_inventory_table')) {
function setup_property_inventory_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS property_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) DEFAULT 'General',
        total_quantity INT DEFAULT 0,
        in_use INT DEFAULT 0,
        in_maintenance INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Pre-populate default items if empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as c FROM property_inventory");
    if($check && mysqli_fetch_assoc($check)['c'] == 0) {
        $defaults = [
            ['Beds', 'Furniture', 0],
            ['Bed Sheets', 'Linens', 0],
            ['Pillows', 'Linens', 0],
            ['Pillow Cases', 'Linens', 0]
        ];
        $stmt = mysqli_prepare($conn, "INSERT INTO property_inventory (item_name, category, total_quantity) VALUES (?, ?, ?)");
        foreach($defaults as $item) {
            mysqli_stmt_bind_param($stmt, "ssi", $item[0], $item[1], $item[2]);
            mysqli_stmt_execute($stmt);
        }
    }
}
}

// --- RESIDENTS TABLE ---
if (!function_exists('setup_residents_table')) {
function setup_residents_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS residents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        middle_name VARCHAR(50),
        suffix VARCHAR(10),
        email VARCHAR(100),
        phone_number VARCHAR(20),
        gender VARCHAR(20),
        occupation VARCHAR(50),
        company VARCHAR(100),
        address TEXT,
        emergency_contact_name VARCHAR(100),
        emergency_contact_number VARCHAR(20),
        profile_image VARCHAR(255),
        school_id_image VARCHAR(255),
        role VARCHAR(20) DEFAULT 'user',
        is_walkin TINYINT(1) DEFAULT 0,
        do_not_renew TINYINT(1) DEFAULT 0,
        is_archived TINYINT(1) DEFAULT 0,
        is_companion TINYINT(1) DEFAULT 0,
        primary_res_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // Ensure suffix column exists in residents table for older database instances
    $check_suffix = mysqli_query($conn, "SHOW COLUMNS FROM residents LIKE 'suffix'");
    if(mysqli_num_rows($check_suffix) == 0) {
        mysqli_query($conn, "ALTER TABLE residents ADD COLUMN suffix VARCHAR(10) DEFAULT NULL AFTER middle_name");
    }
    $check_comp = mysqli_query($conn, "SHOW COLUMNS FROM residents LIKE 'is_companion'");
    if(mysqli_num_rows($check_comp) == 0) {
        mysqli_query($conn, "ALTER TABLE residents MODIFY user_id INT NULL");
        mysqli_query($conn, "ALTER TABLE residents ADD COLUMN is_companion TINYINT(1) DEFAULT 0");
        mysqli_query($conn, "ALTER TABLE residents ADD COLUMN primary_res_id INT DEFAULT NULL");
    }
}
}

if (!function_exists('sync_resident_profile')) {
function sync_resident_profile($conn, $reservation_id) {
    $res_q = mysqli_query($conn, "SELECT r.*, r.companions, u.first_name, u.last_name, u.middle_name, u.suffix, u.email, u.phone_number, u.profile_image, u.school_id_image, u.role, u.is_walkin, u.do_not_renew, u.address as user_address, u.gender as user_gender FROM reservations r JOIN users u ON r.user_id = u.user_id WHERE r.reservation_id=$reservation_id");
    if($r = mysqli_fetch_assoc($res_q)){
        $stmt = mysqli_prepare($conn, "INSERT INTO residents (user_id, first_name, last_name, middle_name, suffix, email, phone_number, gender, occupation, company, address, emergency_contact_name, emergency_contact_number, profile_image, school_id_image, role, is_walkin, do_not_renew, is_archived)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            first_name=VALUES(first_name), last_name=VALUES(last_name), middle_name=VALUES(middle_name), suffix=VALUES(suffix), email=VALUES(email), phone_number=VALUES(phone_number),
            gender=VALUES(gender), occupation=VALUES(occupation), company=VALUES(company), address=VALUES(address),
            emergency_contact_name=VALUES(emergency_contact_name), emergency_contact_number=VALUES(emergency_contact_number),
            profile_image=VALUES(profile_image), school_id_image=VALUES(school_id_image), role=VALUES(role), is_walkin=VALUES(is_walkin),
            do_not_renew=VALUES(do_not_renew), is_archived=VALUES(is_archived)");
        mysqli_stmt_bind_param($stmt, "isssssssssssssssiii", $r['user_id'], $r['first_name'], $r['last_name'], $r['middle_name'], $r['suffix'], $r['email'], $r['phone_number'], $r['user_gender'], $r['occupation'], $r['company_or_school'], $r['user_address'], $r['contact_person_name'], $r['contact_person_number'], $r['profile_image'], $r['school_id_image'], $r['role'], $r['is_walkin'], $r['do_not_renew'], $r['is_archived']);
        mysqli_stmt_execute($stmt);
        
        if (!empty($r['companions'])) {
            $comps = json_decode($r['companions'], true);
            if (is_array($comps)) {
                mysqli_query($conn, "DELETE FROM residents WHERE primary_res_id = $reservation_id AND is_companion = 1");
                $comp_stmt = mysqli_prepare($conn, "INSERT INTO residents (first_name, last_name, middle_name, email, phone_number, gender, school_id_image, is_companion, primary_res_id, role) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'companion')");
                foreach ($comps as $comp) {
                    $c_phone = $comp['phone'] ?? '';
                    $c_id_img = $comp['id_image'] ?? '';
                    $c_fname = $comp['first_name'] ?? $comp['name'] ?? '';
                    $c_lname = $comp['last_name'] ?? '';
                    $c_mname = $comp['middle_name'] ?? '';
                    mysqli_stmt_bind_param($comp_stmt, "sssssssi", $c_fname, $c_lname, $c_mname, $comp['email'], $c_phone, $comp['gender'], $c_id_img, $reservation_id);
                    mysqli_stmt_execute($comp_stmt);
                }
            }
        }
    }
}
}