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

if (!function_exists('get_theme_colors')) {
function get_theme_colors($conn) {
    $theme = ['primary' => '#2E7D32', 'dark' => '#1B5E20', 'accent' => '#FBC02D'];
    
    $q = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key IN ('theme_primary', 'theme_dark', 'theme_accent')");
    if($q){
        while($row = mysqli_fetch_assoc($q)){
            if($row['setting_key'] == 'theme_primary') $theme['primary'] = $row['setting_value'];
            if($row['setting_key'] == 'theme_dark') $theme['dark'] = $row['setting_value'];
            if($row['setting_key'] == 'theme_accent') $theme['accent'] = $row['setting_value'];
        }
    }
    return $theme;
}
}

if (!function_exists('send_notification')) {
function send_notification($conn, $user_id, $message, $type = 'System') {
    // 1. Insert into Database (In-App Notification)

    try {
        $msg_safe = mysqli_real_escape_string($conn, $message);
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) VALUES ('$user_id', '$msg_safe', '$type', NOW())");
    } catch (mysqli_sql_exception $e) {
        // Handle missing 'message' column if table existed but was outdated
        if (strpos($e->getMessage(), "Unknown column 'message'") !== false) {
            mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN message TEXT NOT NULL");
            // Retry insert
            try {
                mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type')");
            } catch (mysqli_sql_exception $e2) {
                if (strpos($e2->getMessage(), "Unknown column 'type'") !== false) {
                    mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'System'");
                    mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type')");
                }
            }
        } elseif (strpos($e->getMessage(), "Unknown column 'type'") !== false) {
            mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'System'");
            // Retry insert
            mysqli_query($conn, "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$msg_safe', '$type')");
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
        $subject = "Woke Coliving Notification: $type";
        
        // --- GMAIL SMTP CONFIGURATION (Requires PHPMailer) ---
        // 1. Download PHPMailer and extract to: c:\xampp\htdocs\WokeColiving\PHPMailer
        // 2. Enable "2-Step Verification" in Google Account -> Security
        // 3. Generate an "App Password" and paste it below
        $phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';
        
        if (file_exists($phpmailer_path)) {
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YOUR_GMAIL@gmail.com'; // <--- PUT YOUR GMAIL HERE
                $mail->Password   = 'YOUR_APP_PASSWORD';    // <--- PUT YOUR APP PASSWORD HERE
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('YOUR_GMAIL@gmail.com', 'Woke Coliving');
                $mail->addAddress($to, $u_row['full_name']);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;

                $mail->send();
            } catch (Exception $e) {
                // Fallback to standard mail if SMTP fails
                $headers = "From: no-reply@wokecoliving.com\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                @mail($to, $subject, $message, $headers);
            }
        } else {
            // Fallback if PHPMailer not installed
            $headers = "From: no-reply@wokecoliving.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            @mail($to, $subject, $message, $headers);
        }
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

    $act = mysqli_real_escape_string($conn, $action);
    $det = mysqli_real_escape_string($conn, $details);
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, details) VALUES ('$user_id', '$act', '$det')");
}
}

// --- WAITLIST TABLE ---
if (!function_exists('setup_waitlist_table')) {
function setup_waitlist_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS waitlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        room_type VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notified_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY `user_room` (`user_id`,`room_type`)
    )");
}
setup_waitlist_table($conn);
}

// --- AUTO REFRESH TRIGGER ---
if (!function_exists('trigger_update')) {
function trigger_update($conn) {
    $t = time();
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('last_update', '$t') ON DUPLICATE KEY UPDATE setting_value='$t'");
}
}

// --- AUTOMATED TASKS (Runs on page load) ---

try {
// Ensure required columns exist to prevent errors in auto-tasks
$cols_check = mysqli_query($conn, "SHOW COLUMNS FROM reservations");
$cols = [];
while($c = mysqli_fetch_assoc($cols_check)) $cols[] = $c['Field'];

if(!in_array('cancellation_reason', $cols)) mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN cancellation_reason VARCHAR(255) DEFAULT NULL");
if(!in_array('is_archived', $cols)) mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
if(!in_array('created_at', $cols)) mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
if(!in_array('bed_preference', $cols)) mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN bed_preference VARCHAR(50) DEFAULT 'Any'");

// Ensure status ENUM is up to date and fix any broken statuses
mysqli_query($conn, "ALTER TABLE reservations MODIFY COLUMN status ENUM('Pending', 'Verifying', 'Approved', 'Cancelled', 'Completed') DEFAULT 'Pending'");
mysqli_query($conn, "UPDATE reservations SET status='Pending' WHERE status = '' OR status IS NULL");

// Ensure payments table has is_penalized column
$pay_cols_check = mysqli_query($conn, "SHOW COLUMNS FROM payments");
$pay_cols = [];
while($c = mysqli_fetch_assoc($pay_cols_check)) $pay_cols[] = $c['Field'];
if(!in_array('is_penalized', $pay_cols)) mysqli_query($conn, "ALTER TABLE payments ADD COLUMN is_penalized TINYINT(1) DEFAULT 0");
if(!in_array('reference_number', $pay_cols)) mysqli_query($conn, "ALTER TABLE payments ADD COLUMN reference_number VARCHAR(100) DEFAULT NULL");
if(!in_array('proof_image', $pay_cols)) mysqli_query($conn, "ALTER TABLE payments ADD COLUMN proof_image VARCHAR(255) DEFAULT NULL");
if(!in_array('description', $pay_cols)) mysqli_query($conn, "ALTER TABLE payments ADD COLUMN description VARCHAR(255) DEFAULT 'Room Payment'");

// Ensure floor column exists in rooms table
$check_col_floor = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'floor'");
if(mysqli_num_rows($check_col_floor) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN floor INT DEFAULT 2");
}

// Ensure room_number column exists in rooms table
$check_col_rn = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'room_number'");
if(mysqli_num_rows($check_col_rn) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN room_number VARCHAR(50) DEFAULT NULL AFTER room_name");
}

// Ensure notifications table exists and is correct before use in automated tasks
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'System',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$check_col_notif = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'id'");
if(mysqli_num_rows($check_col_notif) == 0) mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");

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

// 2. Permanently delete archived reservations older than 90 days (Cleanup)
mysqli_query($conn, "DELETE FROM reservations WHERE is_archived=1 AND end_date < (NOW() - INTERVAL 90 DAY)");

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