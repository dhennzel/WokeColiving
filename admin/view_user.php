<?php
session_start();
include("../db.php");
date_default_timezone_set('Asia/Manila');

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['uid'])){
    header("Location: admin_dashboard.php");
    exit;
}

$uid = (int)$_GET['uid'];

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Ensure room_transfers table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS room_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    old_room_id INT NOT NULL,
    new_room_id INT NOT NULL,
    transfer_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Moved', 'Returned') DEFAULT 'Moved'
)");

// Ensure return_date column exists
$check_col_rd = mysqli_query($conn, "SHOW COLUMNS FROM room_transfers LIKE 'return_date'");
if(mysqli_num_rows($check_col_rd) == 0) {
    mysqli_query($conn, "ALTER TABLE room_transfers ADD COLUMN return_date DATETIME NULL DEFAULT NULL");
}

// Clean up any erroneous system wallet credits from previous refunds to fix negative balances
mysqli_query($conn, "DELETE FROM payments WHERE description = 'Security Deposit Refund Credit'");

// Handle Delete User
if(isset($_POST['delete_user'])){
    if(($_SESSION['admin_role'] ?? 'Admin') !== 'Super Admin'){
        $swal_error = "Access Denied: Only Super Admins can delete residents.";
    } else {
        
    $del_uid = (int)$_POST['user_id'];
    // Check for active reservations
    $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid AND status IN ('Pending', 'Approved')");
    if(mysqli_num_rows($check_active) > 0){
        $swal_error = "Cannot delete user: They have active or pending reservations. Please cancel/complete them first.";
    } else {
        // Soft delete: Mark user as archived instead of permanent deletion
        mysqli_query($conn, "UPDATE users SET is_archived=1 WHERE user_id=$del_uid");
        // Also mark any pending deletion request as Approved
        mysqli_query($conn, "UPDATE account_deletion_requests SET status='Approved' WHERE user_id=$del_uid AND status='Pending'");
        trigger_update($conn);
        echo "<script>window.location='booking_management.php?msg=user_archived';</script>";
        exit;
    }
    } // Closing brace for the 'else' block of the admin role check
}

// Handle Update User Info
if(isset($_POST['update_user_info'])){
    $lname = mb_convert_case(trim($_POST['lname']), MB_CASE_TITLE, "UTF-8");
    $fname = mb_convert_case(trim($_POST['fname']), MB_CASE_TITLE, "UTF-8");
    $mname = mb_convert_case(trim($_POST['mname']), MB_CASE_TITLE, "UTF-8");
    $suffix = trim($_POST['suffix'] ?? '');
    $lname = mysqli_real_escape_string($conn, $lname);
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $suffix = mysqli_real_escape_string($conn, $suffix);
    $u_email = mysqli_real_escape_string($conn, $_POST['email']);
    $u_phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $u_occ = mysqli_real_escape_string($conn, $_POST['occupation']);
    $u_addr = mysqli_real_escape_string($conn, $_POST['address']);
    $u_comp = mysqli_real_escape_string($conn, $_POST['company']);
    $u_em_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
    $u_em_num = mysqli_real_escape_string($conn, $_POST['emergency_contact_number']);
    $u_gender = mysqli_real_escape_string($conn, $_POST['gender']);

    $name_regex = "/^[a-zA-Z\sñÑ]+$/";
    if (!preg_match($name_regex, $fname) || !preg_match($name_regex, $lname) || (!empty($mname) && !preg_match($name_regex, $mname)) || (!empty($suffix) && !preg_match($name_regex, $suffix))) {
        $swal_error = "Names should only contain letters and spaces. Signs and numbers are not allowed.";
    } else {
        $check_phone = mysqli_query($conn, "SELECT user_id FROM users WHERE phone_number='$u_phone' AND user_id != $uid");
        if (mysqli_num_rows($check_phone) > 0) {
            $swal_error = "Phone number is already registered to another account.";
        } else {
            // Build query dynamically based on existing columns to prevent errors
            $cols_check = mysqli_query($conn, "SHOW COLUMNS FROM users");
            $existing_cols = [];
            while($c = mysqli_fetch_assoc($cols_check)) $existing_cols[] = $c['Field'];

            $set_clause = "last_name='$lname', first_name='$fname', middle_name='$mname', suffix='$suffix', email='$u_email', phone_number='$u_phone'";
            if(in_array('occupation', $existing_cols)) $set_clause .= ", occupation='$u_occ'";
            if(in_array('address', $existing_cols)) $set_clause .= ", address='$u_addr'";
            if(in_array('company', $existing_cols)) $set_clause .= ", company='$u_comp'";
            if(in_array('emergency_contact_name', $existing_cols)) $set_clause .= ", emergency_contact_name='$u_em_name'";
            if(in_array('emergency_contact_number', $existing_cols)) $set_clause .= ", emergency_contact_number='$u_em_num'";
            if(in_array('gender', $existing_cols)) $set_clause .= ", gender='$u_gender'";

            if(mysqli_query($conn, "UPDATE users SET $set_clause WHERE user_id=$uid")){
                log_activity($conn, $uid, "Profile Updated", "User details updated by $admin_username.");
                echo "<script>window.location.href='view_user.php?uid=$uid&msg=user_updated';</script>";
                exit;
            } else {
                $swal_error = "Failed to update user: " . mysqli_error($conn);
            }
        }
    }
}

// Handle Update Request Approval/Rejection
if(isset($_POST['handle_update_request'])){
    $req_id = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if($action == 'approve'){
        $req_q = mysqli_query($conn, "SELECT * FROM user_update_requests WHERE request_id=$req_id");
        if($req = mysqli_fetch_assoc($req_q)){
            $u_gender = mysqli_real_escape_string($conn, $req['gender']);
            $u_occ = mysqli_real_escape_string($conn, $req['occupation']);
            $u_comp = mysqli_real_escape_string($conn, $req['company']);
            $u_addr = mysqli_real_escape_string($conn, $req['address']);
            $u_ec_name = mysqli_real_escape_string($conn, $req['emergency_contact_name']);
            $u_ec_num = mysqli_real_escape_string($conn, $req['emergency_contact_number']);
            $u_sid = $req['school_id_image'];
            
            $sid_sql = "";
            if(!empty($u_sid)) $sid_sql = ", school_id_image='" . mysqli_real_escape_string($conn, $u_sid) . "'";
            
            $upd_sql = "UPDATE users SET gender='$u_gender', occupation='$u_occ', company='$u_comp', address='$u_addr', emergency_contact_name='$u_ec_name', emergency_contact_number='$u_ec_num' $sid_sql WHERE user_id=" . $req['user_id'];
            
            if(mysqli_query($conn, $upd_sql)){
                mysqli_query($conn, "UPDATE user_update_requests SET status='Approved' WHERE request_id=$req_id");
                log_activity($conn, $req['user_id'], "Profile Update Approved", "Profile changes approved by $admin_username.");
                send_notification($conn, $req['user_id'], "✅ <strong>Profile Update Approved</strong><br>Your profile information has been updated.", "System");
                echo "<script>window.location.href='view_user.php?uid=$uid&msg=update_approved';</script>";
                exit;
            }
        }
    } elseif($action == 'reject'){
        mysqli_query($conn, "UPDATE user_update_requests SET status='Rejected' WHERE request_id=$req_id");
        send_notification($conn, $uid, "❌ <strong>Profile Update Rejected</strong><br>Your profile update request was rejected by admin.", "System");
        echo "<script>window.location.href='view_user.php?uid=$uid&msg=update_rejected';</script>";
        exit;
    }
}

// Handle Deletion Request Rejection
if(isset($_POST['reject_deletion_request'])){
    $req_id = (int)$_POST['request_id'];
    mysqli_query($conn, "UPDATE account_deletion_requests SET status='Rejected' WHERE request_id=$req_id");
    send_notification($conn, $uid, "❌ <strong>Deletion Request Rejected</strong><br>Your request to delete your account has been rejected by the admin.", "System");
    log_activity($conn, $uid, "Deletion Request Rejected", "Account deletion request rejected by $admin_username.");
    
    echo "<script>window.location.href='view_user.php?uid=$uid&msg=del_req_rejected';</script>";
    exit;
}

// Handle Request Signature
if(isset($_POST['request_signature'])){
    $res_id = (int)$_POST['reservation_id'];
    
    // Ensure column exists
    $check_sig = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'signature_required'");
    if(mysqli_num_rows($check_sig) == 0) {
        mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN signature_required TINYINT(1) DEFAULT 0");
    }

    mysqli_query($conn, "UPDATE reservations SET signature_required=1 WHERE reservation_id=$res_id");
    send_notification($conn, $uid, "✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #$res_id. Please go to My Reservations to sign.", "Action Required");
    log_activity($conn, $uid, "Signature Requested", "Signature requested for Reservation #$res_id by $admin_username");
    
    echo "<script>window.location.href='view_user.php?uid=$uid&msg=sig_requested';</script>";
    exit;
}

// Handle School ID Reminder
if(isset($_POST['remind_school_id'])){
    send_notification($conn, $uid, "⚠️ <strong>School ID Required</strong><br>Please upload your valid School ID to complete your profile verification. You can do this in your profile settings.", "Action Required");
    log_activity($conn, $uid, "ID Reminder Sent", "Admin reminded user to upload their School ID.");
    
    echo "<script>window.location.href='view_user.php?uid=$uid&msg=id_reminded';</script>";
    exit;
}

// Handle Bulk Mark Paid
if(isset($_POST['bulk_mark_paid']) && !empty($_POST['payment_ids'])){
    $ids = array_map('intval', $_POST['payment_ids']);
    if(!empty($ids)){
        $ids_str = implode(',', $ids);
        mysqli_query($conn, "UPDATE payments SET payment_status='Paid', payment_date=NOW() WHERE payment_id IN ($ids_str)");
        trigger_update($conn);
        echo "<script>window.location.href='view_user.php?uid=$uid&msg=bulk_paid';</script>";
        exit;
    }
}

// Handle Refund/Forfeit Actions
if(isset($_POST['process_refund'])){
    $pid = (int)$_POST['refund_pid'];
    $orig_amount = (float)$_POST['refund_amount_original'];
    $deduction = (float)$_POST['refund_deduction'];
    $net_amount = (float)$_POST['refund_net_amount'];
    $remarks = mysqli_real_escape_string($conn, trim($_POST['refund_remarks']));
    $method = mysqli_real_escape_string($conn, $_POST['refund_method']);
    $gcash_ref = isset($_POST['gcash_ref']) ? mysqli_real_escape_string($conn, $_POST['gcash_ref']) : null;
    
    $p_q = mysqli_query($conn, "SELECT * FROM payments WHERE payment_id=$pid");
    if ($p_row = mysqli_fetch_assoc($p_q)) {
        $res_id = $p_row['reservation_id'];
        
        // 1. Update the original payment description to show it's been refunded
        $deduct_str = $deduction > 0 ? " | Deductions: ₱" . number_format($deduction, 2) . " ($remarks)" : "";
        $refund_desc_suffix = " (Refunded ₱".number_format($net_amount, 2)." via $method" . ($method == 'GCash' && $gcash_ref ? " - Ref#$gcash_ref" : "") . "$deduct_str)";
        mysqli_query($conn, "UPDATE payments SET description = CONCAT(description, '$refund_desc_suffix') WHERE payment_id=$pid");

        // 2. Create a withdrawal record for audit trail
        $w_notes = "Security Deposit Refund for Res ID #$res_id. Gross: ₱".number_format($orig_amount,2).". Deductions: ₱".number_format($deduction,2).". Remarks: $remarks. Method: $method.";
        if($gcash_ref) $w_notes .= " Ref: $gcash_ref";
        
        $w_stmt = mysqli_prepare($conn, "INSERT INTO withdrawal_requests (user_id, amount, gcash_name, gcash_number, status, processed_at, admin_notes) VALUES (?, ?, ?, ?, 'Processed', NOW(), ?)");
        $u_q = mysqli_query($conn, "SELECT first_name, last_name, phone_number FROM users WHERE user_id=$uid");
        $u_data = mysqli_fetch_assoc($u_q);
        $gcash_name = trim($u_data['first_name'] . ' ' . $u_data['last_name']);
        $gcash_num = $u_data['phone_number'];
        mysqli_stmt_bind_param($w_stmt, "idsss", $uid, $net_amount, $gcash_name, $gcash_num, $w_notes);
        mysqli_stmt_execute($w_stmt);

        // 3. Log and notify
        log_activity($conn, $uid, "Deposit Refunded", "Security Deposit processed. Net Refund: ₱".number_format($net_amount, 2)." via $method.");
        send_notification($conn, $uid, "💸 <strong>Deposit Refunded</strong><br>Your security deposit has been processed. Net Refund: ₱".number_format($net_amount, 2)." via $method. $deduct_str", "Billing");
        echo "<script>window.location.href='view_user.php?uid=$uid&tab=sd&msg=refunded_external';</script>";
        exit;
    }
}

// Handle Forfeit Action
if(isset($_GET['action']) && $_GET['action'] == 'forfeit_deposit' && isset($_GET['pid'])){
    $pid = (int)$_GET['pid'];
    $p_q = mysqli_query($conn, "SELECT amount FROM payments WHERE payment_id=$pid");
    if($p_row = mysqli_fetch_assoc($p_q)){
        $amt = $p_row['amount'];
        mysqli_query($conn, "UPDATE payments SET description = CONCAT(description, ' (Forfeited)') WHERE payment_id=$pid");
        log_activity($conn, $uid, "Deposit Forfeited", "Security Deposit of ₱".number_format($amt, 2)." was forfeited.");
        send_notification($conn, $uid, "⚠️ <strong>Deposit Forfeited</strong><br>Your security deposit of ₱".number_format($amt, 2)." has been forfeited as per contract terms.", "Billing");
        echo "<script>window.location.href='view_user.php?uid=$uid&tab=sd&msg=forfeited';</script>";
        exit;
    }
}

// Handle Approve with Room Selection (New Bookings)
if(isset($_POST['approve_with_room'])){
    $res_id = (int)$_POST['reservation_id'];
    $room_id = (int)$_POST['room_id'];
    
    // Update the reservation with the selected room
    mysqli_query($conn, "UPDATE reservations SET room_id=$room_id WHERE reservation_id=$res_id");
    
    // Redirect to standard approval logic
    echo "<script>window.location.href='booking_management.php?action=approve&id=$res_id&redirect=view_user&uid=$uid';</script>";
    exit;
}

// Handle Archiving a Reservation
if (isset($_GET['archive_reservation'])) {
    $archive_res_id = (int)$_GET['archive_reservation'];
    mysqli_query($conn, "UPDATE reservations SET is_archived = 1 WHERE reservation_id = $archive_res_id AND user_id = $uid");
    echo "<script>window.location.href='view_user.php?uid=$uid&msg=archived';</script>";
    exit;
}

// Handle Archiving a Payment
if (isset($_GET['archive_payment'])) {
    $archive_pay_id = (int)$_GET['archive_payment'];
    mysqli_query($conn, "UPDATE payments SET is_archived = 1 WHERE payment_id = $archive_pay_id");
    echo "<script>window.location.href='view_user.php?uid=$uid&msg=archived';</script>";
    exit;
}

// Handle Register Companion
if(isset($_POST['register_companion'])){
    $c_name = mysqli_real_escape_string($conn, $_POST['comp_name']);
    $c_email = mysqli_real_escape_string($conn, $_POST['comp_email']);
    $c_phone = mysqli_real_escape_string($conn, $_POST['comp_phone']);
    $c_gender = mysqli_real_escape_string($conn, $_POST['comp_gender']);
    $primary = mysqli_real_escape_string($conn, $_POST['primary_tenant']);
    $res_id = (int)$_POST['res_id'];
    $comp_idx = (int)$_POST['comp_index'];
    
    $parts = explode(' ', trim($_POST['comp_name']));
    $lname = mysqli_real_escape_string($conn, count($parts) > 1 ? array_pop($parts) : '');
    $fname = mysqli_real_escape_string($conn, implode(' ', $parts));

    if(empty($c_email)) $c_email = strtolower(preg_replace('/[^a-zA-Z]/', '', $fname)) . rand(100,999) . '@wokecoliving.com';

    $chk = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$c_email' OR (first_name='$fname' AND last_name='$lname')");
    if(mysqli_num_rows($chk) > 0) {
        $existing_uid = mysqli_fetch_assoc($chk)['user_id'];
        
        // Mark as restored in JSON to avoid duplicate buttons
        $res_q = mysqli_query($conn, "SELECT companions FROM reservations WHERE reservation_id=$res_id");
        if($r_row = mysqli_fetch_assoc($res_q)){
            $comps = json_decode($r_row['companions'], true);
            if(isset($comps[$comp_idx])){
                $comps[$comp_idx]['restored'] = true;
                $comps[$comp_idx]['restored_user_id'] = $existing_uid;
                $new_json = mysqli_real_escape_string($conn, json_encode($comps));
                mysqli_query($conn, "UPDATE reservations SET companions='$new_json' WHERE reservation_id=$res_id");
            }
        }
        
        echo "<script>window.location.href='view_user.php?uid=$existing_uid&msg=companion_linked';</script>";
        exit;
    } else {
        $pass = password_hash('Wokecoliving101', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (first_name, last_name, email, phone_number, gender, password, role, is_walkin) VALUES ('$fname', '$lname', '$c_email', '$c_phone', '$c_gender', '$pass', 'user', 0)");
        $new_uid = mysqli_insert_id($conn);
        log_activity($conn, $new_uid, "Restored from Companion", "User was previously a companion of $primary.");
        
        // Mark as restored in JSON
        $res_q = mysqli_query($conn, "SELECT companions FROM reservations WHERE reservation_id=$res_id");
        if($r_row = mysqli_fetch_assoc($res_q)){
            $comps = json_decode($r_row['companions'], true);
            if(isset($comps[$comp_idx])){
                $comps[$comp_idx]['restored'] = true;
                $comps[$comp_idx]['restored_user_id'] = $new_uid;
                $new_json = mysqli_real_escape_string($conn, json_encode($comps));
                mysqli_query($conn, "UPDATE reservations SET companions='$new_json' WHERE reservation_id=$res_id");
            }
        }
        
        echo "<script>window.location.href='view_user.php?uid=$new_uid&msg=companion_registered';</script>";
        exit;
    }
}

// Fetch User Details
$user_query = mysqli_query($conn, "
    SELECT u.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', middle_name), ''), IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), '')) as full_name,
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days
    FROM users u WHERE u.user_id=$uid
");
$user = mysqli_fetch_assoc($user_query);

// Ensure payments table has is_archived column before querying it
$check_col_arch = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'is_archived'");
if(mysqli_num_rows($check_col_arch) == 0) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

// Fetch Total Outstanding Balance
$balance_q = mysqli_query($conn, "SELECT SUM(p.amount) as balance FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid' AND p.is_archived = 0");
$total_balance = mysqli_fetch_assoc($balance_q)['balance'] ?? 0;

$financial_q = mysqli_query($conn, "SELECT IFNULL(SUM(p.amount), 0) as total_billed, IFNULL(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END), 0) as total_paid FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status != 'Cancelled' AND p.is_archived = 0");
$fin = mysqli_fetch_assoc($financial_q);
$total_billed = $fin['total_billed'] ?? 0;
$total_paid = $fin['total_paid'] ?? 0;

if(!$user){
    header("Location: admin_dashboard.php");
    exit;
}

// Ensure payments table has description column
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'description'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN description VARCHAR(255) DEFAULT 'Room Payment'");
}

// Fetch All Reservations for this User
$res_query = mysqli_query($conn, "
    SELECT r.*, rm.room_name, rm.room_number, rm.room_type,
    (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE reservation_id = r.reservation_id AND payment_status = 'Paid') as actual_paid
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id=$uid AND r.is_archived = 0
    ORDER BY r.created_at DESC
");

// Filter Logic for Payments
$pay_status_filter = isset($_GET['pay_status']) ? $_GET['pay_status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$pay_where = "r.user_id=$uid AND p.is_archived = 0 AND r.is_archived = 0";
if($pay_status_filter && $pay_status_filter != 'All'){
    if($pay_status_filter == 'PendingReview') {
        $pay_where .= " AND p.payment_status = 'Unpaid' AND p.proof_image IS NOT NULL";
    } elseif($pay_status_filter == 'Unsubmitted') {
        $pay_where .= " AND p.payment_status = 'Unpaid' AND p.proof_image IS NULL";
    } else {
        $pay_where .= " AND p.payment_status = '" . mysqli_real_escape_string($conn, $pay_status_filter) . "'";
    }
} elseif(empty($pay_status_filter)) {
    $pay_where .= " AND (p.payment_status != 'Unpaid' OR p.proof_image IS NOT NULL)";
}
if($start_date){
    $pay_where .= " AND p.payment_date >= '" . mysqli_real_escape_string($conn, $start_date) . " 00:00:00'";
}
if($end_date){
    $pay_where .= " AND p.payment_date <= '" . mysqli_real_escape_string($conn, $end_date) . " 23:59:59'";
}

// Fetch Payment History
$pay_query = mysqli_query($conn, "
    SELECT p.*, r.reservation_id, r.start_date, rm.room_name, rm.room_number, rm.room_type 
    FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE $pay_where 
    ORDER BY p.payment_id ASC
");

// Fetch Security Deposit Records Specifically
$sd_filter = isset($_GET['sd_filter']) ? $_GET['sd_filter'] : 'All';
$sd_where = "r.user_id=$uid AND p.is_archived = 0 AND (p.description LIKE '%Security Deposit%' OR p.description LIKE '%Downpayment%' OR p.description LIKE '%Initial%')";
if($sd_filter == 'Active'){
    $sd_where .= " AND r.status IN ('Pending', 'Verifying', 'Approved')";
} elseif($sd_filter == 'Completed'){
    $sd_where .= " AND r.status = 'Completed'";
} elseif($sd_filter == 'Cancelled'){
    $sd_where .= " AND r.status = 'Cancelled'";
}

$sd_query = mysqli_query($conn, "
    SELECT p.*, r.reservation_id, r.months, r.status as res_status, r.start_date, r.created_at as res_created, rm.room_name, rm.room_number 
    FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE $sd_where
    ORDER BY p.payment_id DESC
");

// Fetch Activity Logs (Ensure table exists first)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$logs_query = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id=$uid ORDER BY created_at DESC");

// Check if user was restored from companion
$was_companion = false;
$comp_log_q = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id=$uid AND action IN ('Registered from Companion', 'Restored from Companion') LIMIT 1");
if(mysqli_num_rows($comp_log_q) > 0) {
    $was_companion = true;
    $comp_log = mysqli_fetch_assoc($comp_log_q);
}

// Fetch Expiring Contracts for this User (Approved and ending within 7 days or already ended)
$expiring_query = mysqli_query($conn, "
    SELECT r.*, rm.room_name, rm.room_number 
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id = $uid 
    AND r.status = 'Approved' 
    AND r.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY r.end_date ASC
");

// Fetch Pending Update Request
$pending_update_q = mysqli_query($conn, "SELECT * FROM user_update_requests WHERE user_id=$uid AND status='Pending'");
$pending_update = mysqli_fetch_assoc($pending_update_q);

// Fetch Pending Deletion Request
$pending_del_req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM account_deletion_requests WHERE user_id=$uid AND status='Pending'"));

// Fetch Base Rooms List (Availability calculated dynamically per reservation)
$base_rooms_list = [];
$rfm_q = mysqli_query($conn, "SELECT room_id, room_number, room_name, room_type, floor, total_beds, availability, image, gender FROM rooms WHERE is_archived=0 ORDER BY floor ASC, room_type ASC, room_number ASC");
if($rfm_q){
    while($r = mysqli_fetch_assoc($rfm_q)){
        // Calculate current occupancy for display in modal
        $occ_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE room_id={$r['room_id']} AND status IN ('Pending', 'Approved') AND end_date > CURDATE()");
        $r['occupied'] = mysqli_fetch_assoc($occ_q)['c'];
        $base_rooms_list[$r['room_id']] = $r;
    }
}

// Determine active tab
$active_tab = 'reservations';
if(isset($_GET['pay_status']) || isset($_GET['start_date']) || isset($_GET['end_date']) || (isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid') || (isset($_GET['tab']) && $_GET['tab'] == 'payments')){
    $active_tab = 'payments';
} elseif(isset($_GET['sd_filter']) || (isset($_GET['tab']) && $_GET['tab'] == 'sd')) {
    $active_tab = 'sd';
}

// Fetch User-Specific Pending Counts for Tabs
$user_pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE user_id=$uid AND status IN ('Pending', 'Verifying') AND is_archived = 0"))['c'];
$user_pending_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid' AND p.proof_image IS NOT NULL AND p.is_archived = 0"))['c'];

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .user-card { border: 1px solid var(--border-color); border-radius: var(--radius-lg, 16px); box-shadow: var(--shadow-sm); background: var(--bg-surface); }
        .table th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; border-bottom: 2px solid var(--border-color); background-color: transparent !important; }
        .nav-tabs .nav-link { color: var(--dark-green); border: none; border-bottom: 3px solid transparent; padding-bottom: 10px; }
        .nav-tabs .nav-link.active { color: var(--primary-green); border-bottom: 3px solid var(--primary-green); background: transparent; font-weight: bold; }
        .nav-tabs .nav-link:hover { border-color: transparent; color: var(--primary-green); }
        .profile-header { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 16px); box-shadow: var(--shadow-sm); transition: transform var(--transition-speed); }
        .profile-header:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .avatar-circle { width: 80px; height: 80px; font-size: 2rem; background-color: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        
        /* Room Selection Modal Styles */
        .card-room-select { cursor: pointer; transition: all 0.2s; border: 2px solid var(--border-color); border-radius: var(--radius-md, 12px); overflow: hidden; background: var(--bg-surface); }
        .card-room-select:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-room-select.selected { border-color: var(--primary-green); background-color: #e8f5e9; }
        .card-room-select img { height: 120px; object-fit: cover; width: 100%; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>User Profile</h1>
            </div>
            
            <!-- Minimal Profile Header -->
            <div class="profile-header p-4 mb-4 d-flex flex-wrap align-items-center gap-4 card-custom">
                <div class="position-relative">
                    <div class="avatar-circle">
                        <?php if(!empty($user['profile_image'])): ?>
                            <?php if(file_exists('../uploads/profiles/' . $user['profile_image'])): ?>
                            <a href="javascript:void(0)" onclick="showProfilePicture('../uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>', '<?= htmlspecialchars($user['full_name']) ?>', '<?= htmlspecialchars($user['email']) ?>', '<?= htmlspecialchars($user['phone_number']) ?>')" title="View Profile Picture">
                                <img src="../uploads/profiles/<?= $user['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </a>
                            <?php else: ?>
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <?php if($user['do_not_renew']): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white">DNR</span>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <h3 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['full_name']) ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-muted small">
                        <span><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?></span>
                        <span><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($user['phone_number']) ?></span>
                        <span><i class="fas fa-calendar me-1"></i> Joined <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        <?php if(!empty($user['gender'])): 
                            $gender_icon = 'fa-genderless';
                            $gender_color = 'text-muted';
                            if($user['gender'] == 'Male') { $gender_icon = 'fa-mars'; $gender_color = 'text-primary'; }
                            elseif($user['gender'] == 'Female') { $gender_icon = 'fa-venus'; $gender_color = 'text-danger'; }
                        ?>
                        <span class="fw-bold"><i class="fas <?= $gender_icon ?> me-1 <?= $gender_color ?>"></i> <?= htmlspecialchars($user['gender']) ?></span>
                        <?php endif; ?>
                        <?php if(!empty($user['occupation'])): ?>
                            <span><i class="fas fa-briefcase me-1"></i> <?= htmlspecialchars($user['occupation']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <?php 
                            $m = $user['res_months']; $d = $user['res_days'];
                            $lbl = 'Registered'; $cls = 'bg-secondary';
                            if($m >= 6) { $lbl = 'Long-Term'; $cls = 'bg-primary'; }
                            elseif($d !== null && $d < 28) { $lbl = 'Daily'; $cls = 'bg-warning text-dark'; }
                            elseif($d !== null) { $lbl = 'Short-Term'; $cls = 'bg-success'; }
                            if($user['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in'; $cls = 'bg-info text-dark'; } else { $lbl .= '/Walk-in'; } }
                            echo "<span class='badge $cls'>$lbl</span>";
                        ?>
                    </div>
                    <?php if($total_balance > 0): ?>
                        <div class="mt-2">
                            <span class="badge bg-danger p-2"><i class="fas fa-exclamation-circle me-1"></i> Outstanding Balance: ₱<?= number_format($total_balance, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($user['school_id_image'])): ?>
                        <div class="mt-2">
                            <span class="badge bg-info text-dark"><i class="fas fa-user-graduate me-1"></i> Student</span>
                            <?php if(file_exists('../uploads/proofs/' . $user['school_id_image'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="showSchoolId('../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>')">
                                <i class="fas fa-id-card me-1"></i> View School ID
                            </button>
                            <?php else: ?>
                            <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Send a reminder to upload their School ID?')">
                                <input type="hidden" name="remind_school_id" value="1">
                                <button type="submit" class="btn btn-sm btn-danger ms-2" title="Send Reminder"><i class="fas fa-bell me-1"></i> ID File Missing (Remind)</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif($user['occupation'] == 'Student' && empty($user['school_id_image'])): ?>
                        <div class="mt-2">
                            <span class="badge bg-info text-dark"><i class="fas fa-user-graduate me-1"></i> Student</span>
                            <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Send a reminder to upload their School ID?')">
                                <input type="hidden" name="remind_school_id" value="1">
                                <button type="submit" class="btn btn-sm btn-warning text-dark ms-2" title="Send Reminder"><i class="fas fa-bell me-1"></i> Missing ID (Remind)</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if($was_companion): ?>
                        <div class="mt-2">
                            <span class="badge bg-info text-dark border"><i class="fas fa-history me-1"></i> Former Companion</span>
                            <span class="small text-muted ms-1 fst-italic"><?= htmlspecialchars($comp_log['details'] ?? '') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
                    <a href="booking_management.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
                    <button type="button" class="btn btn-outline-primary rounded-pill btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fas fa-edit me-1"></i> Edit</button>
                    <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                    <form method="POST" id="deleteUserForm" class="d-inline">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <input type="hidden" name="delete_user" value="1">
                        <button type="button" class="btn btn-outline-danger rounded-pill btn-sm" onclick="confirmDeleteUser()"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Financial Overview -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card p-3 bg-white border-0 shadow-sm rounded-4 h-100">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Billed</small>
                        <h4 class="fw-bold mb-0">₱<?= number_format($total_billed, 2) ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 bg-white border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Paid</small>
                        <h4 class="fw-bold text-success mb-0">₱<?= number_format($total_paid, 2) ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php $calc_bal = $total_billed - $total_paid; ?>
                    <div class="card p-3 bg-white border-0 shadow-sm rounded-4 h-100 border-start border-danger border-4">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Remaining Balance</small>
                        <h4 class="fw-bold text-danger mb-0">₱<?= number_format($calc_bal, 2) ?></h4>
                    </div>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid'): ?>
                <div class="alert alert-success">Selected payments marked as paid successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'companion_linked'): ?>
                <div class="alert alert-info">Companion was already registered. Account linked successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'user_updated'): ?>
                <div class="alert alert-success">User information updated successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'extended'): ?>
                <div class="alert alert-success">Extension request approved successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
                <div class="alert alert-success">Reservation has been successfully approved.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'room_updated'): ?>
                <div class="alert alert-success">Room assignment updated successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived'): ?>
                <div class="alert alert-success">Item archived successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'companion_registered'): ?>
                <div class="alert alert-success">Companion registered as an independent resident successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'update_approved'): ?>
                <div class="alert alert-success">User profile update approved successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'update_rejected'): ?>
                <div class="alert alert-warning">User profile update rejected.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'del_req_rejected'): ?>
                <div class="alert alert-warning">Account deletion request rejected.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sig_requested'): ?>
                <div class="alert alert-success">Signature request notification sent to user.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'id_reminded'): ?>
                <div class="alert alert-success">Reminder to upload School ID sent to the user.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'refunded_external'): ?>
                <div class="alert alert-success">Security deposit has been marked as refunded.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'forfeited'): ?>
                <div class="alert alert-warning">Security deposit has been marked as forfeited.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'pending_profile_update'): ?>
                <div class="alert alert-info">Reservation approved. This user has pending profile updates to review.</div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <!-- Pending Deletion Request Alert -->
            <?php if($pending_del_req): ?>
            <div class="card border-danger mb-4 shadow-sm">
                <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-times me-2"></i> Account Deletion Request</span>
                    <div class="d-flex gap-2">
                        <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Approve deletion? This will archive the user account.');">
                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                            <input type="hidden" name="delete_user" value="1">
                            <button type="submit" class="btn btn-light btn-sm fw-bold text-danger"><i class="fas fa-check me-1"></i> Approve & Archive</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="request_id" value="<?= $pending_del_req['request_id'] ?>">
                            <input type="hidden" name="reject_deletion_request" value="1">
                            <button type="submit" class="btn btn-outline-light btn-sm fw-bold"><i class="fas fa-times me-1"></i> Reject</button>
                        </form>
                    </div>
                </div>
                <div class="card-body bg-light text-danger">
                    <p class="mb-0 small"><i class="fas fa-exclamation-circle me-1"></i> This user has requested to permanently delete their account. Please review any outstanding balances or issues before approving.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending Update Request Alert -->
            <?php if($pending_update): ?>
            <div class="card border-warning mb-4 shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-edit me-2"></i> Pending Profile Update Request</span>
                    <div class="d-flex gap-2">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="request_id" value="<?= $pending_update['request_id'] ?>">
                            <input type="hidden" name="handle_update_request" value="1">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm fw-bold"><i class="fas fa-check me-1"></i> Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm fw-bold"><i class="fas fa-times me-1"></i> Reject</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">The user has requested to update their profile information. Review the changes below:</p>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <strong>Gender:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['gender'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['gender'] ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Occupation:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['occupation'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['occupation'] ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Company/School:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['company'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['company'] ?></span>
                        </div>
                        <div class="col-md-12">
                            <strong>Address:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['address'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['address'] ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Emergency Contact:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['emergency_contact_name'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['emergency_contact_name'] ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Emergency Number:</strong><br>
                            <span class="text-muted text-decoration-line-through me-1"><?= $user['emergency_contact_number'] ?? '-' ?></span> 
                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-bold"><?= $pending_update['emergency_contact_number'] ?></span>
                        </div>
                        <?php if(!empty($pending_update['school_id_image'])): ?>
                        <div class="col-md-12 mt-2">
                            <strong>New School ID:</strong><br>
                            <?php if(file_exists('../uploads/proofs/' . $pending_update['school_id_image'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="showSchoolId('../uploads/proofs/<?= $pending_update['school_id_image'] ?>')">
                                <i class="fas fa-image me-1"></i> View New ID
                            </button>
                            <?php else: ?>
                            <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> File missing on server.</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Expiring Contracts Alert -->
            <?php if(mysqli_num_rows($expiring_query) > 0): ?>
            <div class="card border-danger mb-4 shadow-sm card-table overflow-hidden">
                <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-triangle me-2"></i> Expiring & Expired Contracts (Action Required)</span>
                    <span class="badge bg-white text-danger"><?= mysqli_num_rows($expiring_query) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle table-borderless">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th>Room</th>
                                    <th>End Date</th>
                                    <th>Days Left</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($exp = mysqli_fetch_assoc($expiring_query)): 
                                    $days_left = (strtotime($exp['end_date']) - time()) / (60 * 60 * 24);
                                    $days_left = ceil($days_left);
                                    $status_text = $days_left < 0 ? "Expired " . abs($days_left) . " days ago" : ($days_left == 0 ? "Expires Today" : "$days_left days left");
                                    $text_class = $days_left <= 0 ? "text-danger fw-bold" : "text-warning fw-bold";
                                ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?= !empty($exp['room_number']) ? 'Room ' . htmlspecialchars($exp['room_number']) : htmlspecialchars($exp['room_name']) ?>
                                        <?php if(!empty($exp['bed_preference']) && $exp['bed_preference'] != 'Any'): ?>
                                            <div class="small text-muted"><i class="fas fa-bed"></i> <?= $exp['bed_preference'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $exp['end_date'] ?></td>
                                    <td class="<?= $text_class ?>"><?= $status_text ?></td>
                                    <td class="text-end">
                                        <button onclick="renewContract(<?= $exp['reservation_id'] ?>, <?= $user['do_not_renew'] ?>)" class="btn btn-sm btn-success me-1"><i class="fas fa-sync-alt me-1"></i> Renew</button>
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-flag-checkered me-1"></i> End</button>
                                            <ul class="dropdown-menu shadow">
                                                <li><a class="dropdown-item fw-bold text-success" href="booking_management.php?action=complete&id=<?= $exp['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" onclick="confirmAction(event, this.href, 'Mark as Completed?')"><i class="fas fa-check-circle me-2"></i>Complete Contract</a></li>
                                                <li><a class="dropdown-item fw-bold text-danger" href="booking_management.php?action=incomplete&id=<?= $exp['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" onclick="confirmAction(event, this.href, 'End contract early (Incomplete)?')"><i class="fas fa-times-circle me-2"></i>End Early (Incomplete)</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabs Section -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <ul class="nav nav-tabs" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link <?= $active_tab == 'reservations' ? 'active' : '' ?>" id="res-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button">
                                Reservations
                                <?php if($user_pending_res > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-1"><?= $user_pending_res ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $active_tab == 'payments' ? 'active' : '' ?>" id="pay-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">
                                Payments
                                <?php if($user_pending_pay > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-1" title="Proofs to Review"><?= $user_pending_pay ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $active_tab == 'sd' ? 'active' : '' ?>" id="sd-tab" data-bs-toggle="tab" data-bs-target="#security-deposit" type="button">
                                Security Deposit
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#room-transfers" type="button">
                                Transfers
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="userTabsContent">
                        
                        <!-- Reservations Tab -->
                        <div class="tab-pane fade <?= $active_tab == 'reservations' ? 'show active' : '' ?>" id="reservations" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Total Paid</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($res_query)): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?= !empty($row['room_number']) ? 'Room ' . htmlspecialchars($row['room_number']) : htmlspecialchars($row['room_name']) ?> <small class="text-muted fw-normal">(<?= $row['room_type'] ?>)</small>
                                            <?php if(!empty($row['bed_preference']) && $row['bed_preference'] != 'Any'): ?>
                                                <div class="badge bg-light text-dark border mt-1"><i class="fas fa-bed me-1"></i> <?= $row['bed_preference'] ?></div>
                                            <?php endif; ?>
                                            <?php if(isset($row['auto_assigned']) && $row['auto_assigned'] == 0): ?>
                                                <div class="badge bg-primary mt-1"><i class="fas fa-hand-pointer me-1"></i> Chosen by Guest</div>
                                            <?php endif; ?>
                                            <?php if(!empty($row['extended_from'])): ?>
                                                <div class="badge bg-info text-dark mt-1"><i class="fas fa-history me-1"></i> Extension Request</div>
                                            <?php endif; ?>
                                            <?php if(!empty($row['companions'])): 
                                                $comps = json_decode($row['companions'], true);
                                                if(is_array($comps) && count($comps) > 0):
                                            ?>
                                                <div class="mt-2 small text-muted border-top pt-1">
                                                    <strong><i class="fas fa-users me-1"></i> Companions:</strong><br>
                                                    <?php foreach($comps as $idx => $c): 
                                                        $c_name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                                                        if(empty($c_name)) $c_name = $c['name'] ?? 'Unknown';
                                                    ?>
                                                    <div class="ps-2 d-flex justify-content-between align-items-center mb-1">
                                                        <span>- <?= htmlspecialchars($c_name) ?> <?= !empty($c['restored']) ? '<span class="badge bg-secondary ms-1" style="font-size:0.6rem;">Registered</span>' : '' ?></span>
                                                        <?php if(empty($c['restored'])): ?>
                                                        <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Register this companion as an independent resident?')">
                                                            <input type="hidden" name="register_companion" value="1">
                                                            <input type="hidden" name="res_id" value="<?= $row['reservation_id'] ?>">
                                                            <input type="hidden" name="comp_index" value="<?= $idx ?>">
                                                            <input type="hidden" name="comp_name" value="<?= htmlspecialchars($c_name) ?>">
                                                            <input type="hidden" name="comp_email" value="<?= htmlspecialchars($c['email'] ?? '') ?>">
                                                            <input type="hidden" name="comp_phone" value="<?= htmlspecialchars($c['phone'] ?? '') ?>">
                                                            <input type="hidden" name="comp_gender" value="<?= htmlspecialchars($c['gender'] ?? 'Any') ?>">
                                                            <input type="hidden" name="primary_tenant" value="<?= htmlspecialchars($user['full_name']) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-0" style="font-size: 0.65rem;" title="Register as Resident"><i class="fas fa-user-plus"></i> Register</button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; endif; ?>
                                        </td>
                                        <td>
                                            <small>In: <?= $row['start_date'] ?></small><br>
                                            <small>Out: <?= $row['end_date'] ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $badge = 'bg-secondary';
                                                if($row['status'] == 'Approved') $badge = 'bg-success';
                                                if($row['status'] == 'Pending') $badge = 'bg-warning text-dark';
                                                if($row['status'] == 'Verifying') $badge = 'bg-info text-dark';
                                                if($row['status'] == 'Cancelled') $badge = 'bg-danger';
                                                if($row['status'] == 'Completed') $badge = 'bg-primary';
                                                if($row['status'] == 'Incomplete') $badge = 'bg-dark text-white';
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td class="fw-bold text-success">₱<?= number_format($row['actual_paid'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if($row['status'] == 'Pending' || $row['status'] == 'Verifying'): ?>
                                                <?php
                                                    // Check Payment
                                                    $pay_chk = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payments WHERE reservation_id=".$row['reservation_id']." AND payment_status='Paid'");
                                                    $is_paid = mysqli_fetch_assoc($pay_chk)['cnt'] > 0;
                                                    // Check Signature
                                                    $has_sig = !empty($row['signature_image']);
                                                    $is_pending = $row['status'] == 'Pending';
                                                ?>
                                                <div class="d-flex justify-content-end gap-1">
                                                    <?php if(!empty($row['extended_from'])): ?>
                                                        <a href="booking_management.php?action=approve&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success" onclick="confirmAction(event, this.href, 'Approve this extension?')" title="Approve"><i class="fas fa-check"></i></a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="openApproveModal(<?= $row['reservation_id'] ?>, '<?= $row['room_type'] ?>', <?= $row['room_id'] ?>, '<?= htmlspecialchars($user['gender'] ?? '') ?>')" title="Approve & Assign Room"><i class="fas fa-check"></i> Approve</button>
                                                    <?php endif; ?>
                                                    <a href="booking_management.php?action=reject&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" onclick="confirmAction(event, this.href, 'Reject this reservation?')" title="Reject"><i class="fas fa-times"></i></a>
                                                </div>
                                                <?php if($is_pending): ?>
                                                    <div class="small text-warning mt-1 text-end" style="font-size: 0.7rem;"><i class="fas fa-clock"></i> Pending Review</div>
                                                <?php elseif(!$is_paid || !$has_sig): ?>
                                                    <div class="small text-danger mt-1 text-end" style="font-size: 0.7rem;">
                                                        <i class="fas fa-exclamation-triangle"></i> <?= !$is_paid ? 'Unpaid' : '' ?> <?= (!$is_paid && !$has_sig) ? '&' : '' ?> <?= !$has_sig ? 'No Sig' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif($row['status'] == 'Approved'): ?>
                                                <?php if(empty($row['signature_image'])): ?>
                                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Request signature from user?')">
                                                        <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                                                        <input type="hidden" name="request_signature" value="1">
                                                        <button type="submit" class="btn btn-sm btn-warning text-dark me-1" title="Request Signature"><i class="fas fa-file-signature"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <button onclick="renewContract(<?= $row['reservation_id'] ?>, <?= $user['do_not_renew'] ?>)" class="btn btn-sm btn-success me-1"><i class="fas fa-sync-alt"></i></button>
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="End Contract"><i class="fas fa-flag-checkered"></i></button>
                                                    <ul class="dropdown-menu shadow">
                                                        <li><a class="dropdown-item fw-bold text-success" href="booking_management.php?action=complete&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" onclick="confirmAction(event, this.href, 'Mark as Completed?')"><i class="fas fa-check-circle me-2"></i>Complete</a></li>
                                                        <li><a class="dropdown-item fw-bold text-danger" href="booking_management.php?action=incomplete&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" onclick="confirmAction(event, this.href, 'End early (Incomplete)?')"><i class="fas fa-times-circle me-2"></i>Incomplete</a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        <?php if(in_array($row['status'], ['Completed', 'Incomplete', 'Approved', 'Cancelled'])): ?>
                                            <a href="view_user.php?uid=<?= $uid ?>&archive_reservation=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-outline-secondary ms-1" title="Archive Reservation" onclick="confirmAction(event, this.href, 'Archive this reservation?')"><i class="fas fa-archive"></i></a>
                                        <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($res_query) == 0): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No reservations found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        </div>

                        <!-- Payments Tab -->
                        <div class="tab-pane fade <?= $active_tab == 'payments' ? 'show active' : '' ?>" id="payments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-muted">Transaction History</h6>
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="uid" value="<?= $uid ?>">
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>" title="Start Date">
                        <span class="text-muted">-</span>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>" title="End Date">
                        <select name="pay_status" class="form-select form-select-sm" style="width: 140px;">
                            <option value="">Submitted</option>
                            <option value="All" <?= $pay_status_filter == 'All' ? 'selected' : '' ?>>All Bills</option>
                            <option value="Paid" <?= $pay_status_filter == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="PendingReview" <?= $pay_status_filter == 'PendingReview' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="Unsubmitted" <?= $pay_status_filter == 'Unsubmitted' ? 'selected' : '' ?>>Unsubmitted</option>
                            <option value="Unpaid" <?= $pay_status_filter == 'Unpaid' ? 'selected' : '' ?>>All Unpaid</option>
                            <option value="Cancelled" <?= $pay_status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                        <?php if($pay_status_filter || $start_date || $end_date): ?>
                            <a href="view_user.php?uid=<?= $uid ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                            </div>
                <form method="POST" id="bulkForm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                                <th>Date</th>
                                <th>Room</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = 0;
                            while($pay = mysqli_fetch_assoc($pay_query)): 
                                if($pay['payment_status'] != 'Cancelled') $total_amount += $pay['amount'];
                            ?>
                            <?php
                                $display_date = $pay['payment_date'];
                                $actual_time = $pay['payment_date'] ? date('H:i:s', strtotime($pay['payment_date'])) : '00:00:00';
                                $desc_text = !empty($pay['description']) ? $pay['description'] : 'Room Payment';

                                // Logic para itugma ang date sa billing month/period (Parity with billing.php)
                                if (preg_match('/Month (\d+) Rent/i', $desc_text, $matches)) {
                                    $m_idx = (int)$matches[1] - 1;
                                    $calc_dt = new DateTime($pay['start_date']);
                                    $calc_dt->modify("+$m_idx months");
                                    $display_date = $calc_dt->format('Y-m-d') . ' ' . $actual_time;
                                } elseif (preg_match('/Utility Bill \((\d{4}-\d{2}-\d{2})\)/i', $desc_text, $matches)) {
                                    $display_date = $matches[1] . ' ' . $actual_time;
                                } elseif (strpos(strtolower($desc_text), 'initial') !== false || strpos(strtolower($desc_text), 'walk-in') !== false || strpos(strtolower($desc_text), 'full payment') !== false) {
                                    $display_date = date('Y-m-d', strtotime($pay['start_date'])) . ' ' . $actual_time;
                                }

                                $is_overdue = ($pay['payment_status'] == 'Unpaid' && strtotime($pay['payment_date']) < strtotime('-5 days'));
                                $row_class = $is_overdue ? 'table-danger' : '';
                                $desc = preg_replace('/\s*\[FULL\]\s*/i', '', $desc_text);
                                $room_info = !empty($pay['room_number']) ? 'Room ' . htmlspecialchars($pay['room_number']) : ($pay['room_name'] ? htmlspecialchars($pay['room_name']) : '<span class="text-muted">Unknown Room</span>');
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td>
                                    <?php if($pay['payment_status'] == 'Unpaid'): ?>
                                        <input type="checkbox" name="payment_ids[]" value="<?= $pay['payment_id'] ?>" class="pay-checkbox">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= date('M d, Y', strtotime($display_date)) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($display_date)) ?></small>
                                </td>
                                <td><?= $room_info ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($desc) ?></td>
                                <td><?= htmlspecialchars($pay['payment_method']) ?></td>
                                <td class="fw-bold">₱<?= number_format($pay['amount'], 2) ?></td>
                                <td>
                                    <?php 
                                        $p_status_class = 'bg-warning text-dark';
                                        if($pay['payment_status'] == 'Paid') $p_status_class = 'bg-success';
                                        elseif($pay['payment_status'] == 'Cancelled') $p_status_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $p_status_class ?> position-relative">
                                        <?= ($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])) ? 'Pending Review' : $pay['payment_status'] ?>
                                        <?php if($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if($is_overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                                    <?php if($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])): ?><br><small class="text-danger fw-bold mt-1 d-block" style="font-size:0.7rem;"><i class="fas fa-exclamation-circle me-1"></i><?= $pay['proof_image'] === 'Cash' ? 'Review Payment' : 'Review Proof' ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <a href="payment_details.php?id=<?= $pay['payment_id'] ?>" class="btn btn-sm btn-outline-primary position-relative">
                                        <i class="fas fa-eye"></i> View
                                        <?php if($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-end">
                                    <?php if($pay['payment_status'] == 'Unpaid'): ?>
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="booking_management.php?action=mark_paid&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success" title="Approve Payment" onclick="confirmAction(event, this.href, 'Approve this payment?')"><i class="fas fa-check"></i></a>
                                            <?php if(!empty($pay['proof_image'])): ?>
                                                <a href="booking_management.php?action=reject_payment&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-warning text-dark" title="Reject Payment Proof (Re-upload)" onclick="confirmAction(event, this.href, 'Reject this payment proof? The guest will have to re-upload.')"><i class="fas fa-undo"></i></a>
                                            <?php endif; ?>
                                            <a href="booking_management.php?action=cancel_payment&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" title="Cancel Payment" onclick="confirmAction(event, this.href, 'Cancel this payment? Use this if the guest no longer wants to continue.')"><i class="fas fa-times"></i></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($pay['payment_status'] == 'Paid' || $pay['payment_status'] == 'Cancelled'): ?>
                                        <a href="view_user.php?uid=<?= $uid ?>&archive_payment=<?= $pay['payment_id'] ?>" class="btn btn-sm btn-outline-secondary ms-1" title="Archive Payment" onclick="confirmAction(event, this.href, 'Archive this payment?')"><i class="fas fa-archive"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($pay_query) == 0): ?>
                                <tr><td colspan="9" class="text-center text-muted">No payment history found.</td></tr>
                            <?php else: ?>
                                <tr class="table-light fw-bold border-top">
                                    <td colspan="5" class="text-end text-uppercase text-secondary">Total Amount:</td>
                                    <td class="text-success fs-6">₱<?= number_format($total_amount, 2) ?></td>
                                    <td colspan="3"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-success" onclick="confirmBulkPaid()"><i class="fas fa-check-double me-1"></i> Mark Selected as Paid</button>
                </div>
                </form>
                        </div>

                        <!-- Security Deposit Tab -->
                        <div class="tab-pane fade <?= $active_tab == 'sd' ? 'show active' : '' ?>" id="security-deposit" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-muted">Deposit & Initial Payments</h6>
                                <form method="GET" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="uid" value="<?= $uid ?>">
                                    <input type="hidden" name="tab" value="sd">
                                    <select name="sd_filter" class="form-select form-select-sm" style="width: 145px;" onchange="this.form.submit()">
                                        <option value="All" <?= $sd_filter == 'All' ? 'selected' : '' ?>>All Contracts</option>
                                        <option value="Active" <?= $sd_filter == 'Active' ? 'selected' : '' ?>>Active Contracts</option>
                                        <option value="Completed" <?= $sd_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $sd_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </div>
                            <div class="alert alert-info border-0 shadow-sm rounded-4 small mb-4">
                                <i class="fas fa-info-circle me-2"></i> <strong>Refund Policy:</strong> 
                                Security deposits are <strong>always refundable</strong> for short-term stays. 
                                For contracts of 6 months or more, the deposit is only refundable upon <strong>contract completion</strong>; otherwise, it is forfeited.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Room</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Refund Eligibility</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        while($sd = mysqli_fetch_assoc($sd_query)): 
                                            $sd_display_date = (!empty($sd['payment_date']) && $sd['payment_date'] != '0000-00-00 00:00:00') ? $sd['payment_date'] : (!empty($sd['res_created']) ? $sd['res_created'] : $sd['start_date']);
                                        ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($sd_display_date)) ?></td>
                                            <td><?= !empty($sd['room_number']) ? 'Room ' . htmlspecialchars($sd['room_number']) : htmlspecialchars($sd['room_name']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars(preg_replace('/\s*\[FULL\]\s*/i', '', $sd['description'])) ?></td>
                                            <td class="fw-bold">₱<?= number_format($sd['amount'], 2) ?></td>
                                            <td>
                                                <span class="badge <?= $sd['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= $sd['payment_status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if($sd['months'] < 6) {
                                                    echo '<span class="badge bg-info"><i class="fas fa-check-circle me-1"></i> Always Refundable</span>';
                                                } else {
                                                    if($sd['res_status'] == 'Completed') {
                                                        echo '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Refundable (Completed)</span>';
                                                    } elseif($sd['res_status'] == 'Cancelled') {
                                                        echo '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Forfeited (Early Term)</span>';
                                                    } elseif($sd['res_status'] == 'Approved') {
                                                        echo '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Eligible if Completed</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Pending Contract</span>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="payment_details.php?id=<?= $sd['payment_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                                <?php if($sd['payment_status'] == 'Paid' && strpos($sd['description'], 'Refunded') === false && strpos($sd['description'], 'Forfeited') === false): ?>
                                                    <?php if(($sd['months'] < 6) || ($sd['res_status'] == 'Completed')): ?>
                                                        <button type="button" class="btn btn-sm btn-success" title="Release Refund" onclick="openRefundModal(<?= $sd['payment_id'] ?>, <?= $sd['amount'] ?>, <?= $total_balance ?>)"><i class="fas fa-hand-holding-usd"></i></button>
                                                        <?php if($sd['res_status'] == 'Completed'): ?>
                                                            <button type="button" class="btn btn-sm btn-info text-white ms-1" title="Generate Clearance" onclick="openClearanceModal(<?= $uid ?>, <?= $sd['reservation_id'] ?>, '<?= htmlspecialchars(addslashes(!empty($sd['room_number']) ? 'Room ' . $sd['room_number'] : $sd['room_name'])) ?>', <?= $sd['amount'] ?>, <?= $total_balance ?>)"><i class="fas fa-file-signature"></i></button>
                                                        <?php endif; ?>
                                                    <?php elseif($sd['res_status'] == 'Cancelled' || $sd['res_status'] == 'Incomplete'): ?>
                                                        <a href="?uid=<?= $uid ?>&action=forfeit_deposit&pid=<?= $sd['payment_id'] ?>" class="btn btn-sm btn-danger" title="Forfeit Deposit" onclick="confirmAction(event, this.href, 'Mark this deposit as Forfeited due to early termination?')"><i class="fas fa-gavel"></i></a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if($sd['payment_status'] == 'Paid' || $sd['payment_status'] == 'Cancelled'): ?>
                                                    <a href="view_user.php?uid=<?= $uid ?>&archive_payment=<?= $sd['payment_id'] ?>" class="btn btn-sm btn-outline-secondary ms-1" title="Archive Payment" onclick="confirmAction(event, this.href, 'Archive this payment?')"><i class="fas fa-archive"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($sd_query) == 0): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">No security deposit records found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Room Transfers Tab -->
                        <div class="tab-pane fade" id="room-transfers" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-muted">Room Transfer History</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Move Date</th>
                                            <th>Return Date</th>
                                            <th>Moved From</th>
                                            <th>Moved To</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $transfers_q = mysqli_query($conn, "
                                            SELECT t.*, 
                                                   r1.room_name as old_name, r1.room_number as old_num,
                                                   r2.room_name as new_name, r2.room_number as new_num
                                            FROM room_transfers t
                                            JOIN reservations res ON t.reservation_id = res.reservation_id
                                            JOIN rooms r1 ON t.old_room_id = r1.room_id
                                            JOIN rooms r2 ON t.new_room_id = r2.room_id
                                            WHERE res.user_id = $uid
                                            ORDER BY t.transfer_date DESC
                                        ");
                                        if($transfers_q && mysqli_num_rows($transfers_q) > 0):
                                            while($tr = mysqli_fetch_assoc($transfers_q)):
                                                $old = !empty($tr['old_num']) ? "Room ".$tr['old_num'] : $tr['old_name'];
                                                $new = !empty($tr['new_num']) ? "Room ".$tr['new_num'] : $tr['new_name'];
                                        ?>
                                        <tr>
                                            <td><?= date('M d, Y h:i A', strtotime($tr['transfer_date'])) ?></td>
                                            <td><?= $tr['return_date'] ? date('M d, Y h:i A', strtotime($tr['return_date'])) : '<span class="text-muted">-</span>' ?></td>
                                            <td class="text-danger fw-bold"><i class="fas fa-sign-out-alt me-1"></i> <?= $old ?></td>
                                            <td class="text-success fw-bold"><i class="fas fa-sign-in-alt me-1"></i> <?= $new ?></td>
                                            <td><span class="badge <?= $tr['status'] == 'Moved' ? 'bg-primary' : 'bg-secondary' ?>"><?= $tr['status'] ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">No room transfers recorded.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

<!-- Clearance Modal -->
<div class="modal fade" id="clearanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="print_clearance.php" method="POST" target="_blank" class="w-100">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-signature me-2"></i>Generate Clearance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="tenant_id" id="clear_tenant_id">
                    <input type="hidden" name="reservation_id" id="clear_res_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Room</label>
                        <input type="text" name="room_info" id="clear_room" class="form-control bg-light" readonly>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Clearance Date</label>
                            <input type="date" name="clearance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Security Deposit (₱)</label>
                            <input type="number" step="0.01" name="deposit_amount" id="clear_deposit" class="form-control bg-light" readonly>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-danger">Deductions (₱)</label>
                            <input type="number" step="0.01" name="deduction_amount" id="clear_deduction" class="form-control border-danger" required oninput="calcClearanceRefund()">
                            <small class="text-muted" style="font-size:0.65rem;">Auto-filled from unpaid bills</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-success">Net Refundable (₱)</label>
                            <input type="number" step="0.01" name="net_refund" id="clear_net" class="form-control bg-light fw-bold text-success" readonly>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Deduction Remarks</label>
                        <textarea name="deduction_remarks" id="clear_remarks" class="form-control" rows="2" placeholder="Itemized deductions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-bold rounded-pill px-4"><i class="fas fa-print me-2"></i> Print</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Release Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="refund_pid" id="refund_pid">
                    <input type="hidden" name="refund_amount_original" id="refund_amount_original">
                    
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="fw-bold">Original Deposit:</span>
                        <strong id="refundAmountDisplay" class="text-success fs-5"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-danger fw-bold">Deductions (₱)</label>
                        <input type="number" step="0.01" name="refund_deduction" id="refund_deduction" class="form-control border-danger" required oninput="calcRefundNet()">
                        <small class="text-muted" style="font-size:0.75rem;">Auto-filled from unpaid bills/damages.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deduction Remarks</label>
                        <input type="text" name="refund_remarks" id="refund_remarks" class="form-control" placeholder="e.g. Damages, Unpaid rent">
                    </div>
                    <div class="mb-4 p-3 bg-light rounded">
                        <label class="form-label text-success fw-bold mb-1">Net Refundable Amount (₱)</label>
                        <input type="number" step="0.01" name="refund_net_amount" id="refund_net_amount" class="form-control fw-bold text-success fs-5 bg-white" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Refund Method</label>
                        <select name="refund_method" id="refund_method" class="form-select" onchange="toggleGcashDetails()">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>
                    <div id="gcash_refund_details" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">GCash Reference No.</label>
                            <input type="text" name="gcash_ref" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_refund" class="btn btn-success">Process Refund</button>
                </div>
            </div>
        </form>
    </div>
</div>

        </div>
    </div>
        </main>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_user_info" value="1">
                    <?php
                        $lname = $user['last_name'];
                        $fname = $user['first_name'];
                        $mname = $user['middle_name'];
                        $suffix = $user['suffix'] ?? '';
                    ?>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Last Name</label><input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($lname) ?>" required oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">First Name</label><input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($fname) ?>" required oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Middle Name</label><input type="text" name="mname" class="form-control" value="<?= htmlspecialchars($mname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Suffix</label>
                            <select name="suffix" class="form-select">
                                <option value="">None</option>
                                <option value="Jr" <?= $suffix == 'Jr' ? 'selected' : '' ?>>Jr</option>
                                <option value="Sr" <?= $suffix == 'Sr' ? 'selected' : '' ?>>Sr</option>
                                <option value="II" <?= $suffix == 'II' ? 'selected' : '' ?>>II</option>
                                <option value="III" <?= $suffix == 'III' ? 'selected' : '' ?>>III</option>
                                <option value="IV" <?= $suffix == 'IV' ? 'selected' : '' ?>>IV</option>
                                <option value="V" <?= $suffix == 'V' ? 'selected' : '' ?>>V</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male" <?= (isset($user['gender']) && $user['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (isset($user['gender']) && $user['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Occupation</label>
                            <select name="occupation" class="form-select">
                                <option value="Student" <?= (isset($user['occupation']) && $user['occupation'] == 'Student') ? 'selected' : '' ?>>Student</option>
                                <option value="Employed" <?= (isset($user['occupation']) && $user['occupation'] == 'Employed') ? 'selected' : '' ?>>Employed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Company / School</label>
                            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($user['company'] ?? '') ?>">
                        </div>
                        <?php if(!empty($user['school_id_image'])): ?>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Current School ID</label>
                            <div class="mt-1">
                                <?php if(file_exists('../uploads/proofs/' . $user['school_id_image'])): ?>
                                <img src="../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>" class="img-thumbnail" style="max-height: 100px; cursor: pointer;" onclick="showSchoolId('../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>')" title="Click to enlarge">
                                <?php else: ?>
                                <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Image file not found on server.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Emergency Contact Number</label>
                            <input type="text" name="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user['emergency_contact_number'] ?? '') ?>" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve & Assign Room Modal -->
<div class="modal fade" id="approveRoomModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i>Approve & Assign Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="mb-0">Select a room for this reservation (<strong id="modalRoomType"></strong>):</p>
                        <div class="d-flex align-items-center">
                            <label class="small fw-bold me-2 text-muted">Filter Floor:</label>
                            <select id="approveFloorFilter" class="form-select form-select-sm" style="width: 120px;" onchange="filterApproveRooms()">
                                <option value="all">All Floors</option>
                                <?php for($i=2; $i<=7; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="approve_with_room" value="1">
                    <input type="hidden" name="reservation_id" id="approveResId">
                    <input type="hidden" name="room_id" id="approveRoomId" required>

                    <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; padding: 5px;">
                        <div class="row g-3" id="approveRoomGrid">
                            <?php foreach($base_rooms_list as $room): 
                                $avail = $room['total_beds'] - $room['occupied'];
                                $is_full = $avail <= 0;
                            ?>
                            <div class="col-md-4 col-lg-3 approve-room-item" data-type="<?= $room['room_type'] ?>" data-floor="<?= $room['floor'] ?>" data-gender="<?= $room['gender'] ?? 'Male' ?>">
                                <div class="card card-room-select h-100 shadow-sm" id="room_select_<?= $room['room_id'] ?>" onclick="selectApproveRoom(this, <?= $room['room_id'] ?>)">
                                    <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room['room_name'] ?>">
                                    <div class="card-body p-2 text-center">
                                        <div class="fw-bold"><?= !empty($room['room_number']) ? 'Room ' . htmlspecialchars($room['room_number']) : htmlspecialchars($room['room_name']) ?></div>
                                        <div class="small text-muted"><?= $room['room_type'] ?> &bull; <?= $room['floor'] ?>F</div>
                                        <?php if (($room['gender'] ?? 'Any') == 'Male'): ?>
                                            <div class="badge bg-primary mt-1"><i class="fas fa-male me-1"></i> Male Only</div>
                                        <?php elseif (($room['gender'] ?? 'Any') == 'Female'): ?>
                                            <div class="badge bg-danger mt-1"><i class="fas fa-female me-1"></i> Female Only</div>
                                        <?php endif; ?>
                                        <div class="badge <?= $is_full ? 'bg-secondary' : 'bg-success' ?> mt-1"><?= $is_full ? 'Full' : $avail . ' Beds Free' ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold" id="btnApproveConfirm" disabled>Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- School ID Modal -->
<div class="modal fade" id="schoolIdModal" tabindex="-1" aria-labelledby="schoolIdModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="schoolIdModalLabel">School ID</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="schoolIdImage" class="img-fluid rounded" alt="School ID">
      </div>
    </div>
  </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="profilePicModalLabel">Profile Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="profilePicImage" class="img-fluid rounded-circle mb-3 shadow-sm" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover;">
        <h5 class="fw-bold" id="modalProfileName"></h5>
        <p class="text-muted mb-1" id="modalProfileEmail"></p>
        <p class="text-muted mb-0" id="modalProfilePhone"></p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>

function confirmAction(e, url, msg) {
    e.preventDefault();
    const isDestructive = msg.toLowerCase().includes('reject') || msg.toLowerCase().includes('end') || msg.toLowerCase().includes('delete') || msg.toLowerCase().includes('terminate');
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: isDestructive ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: isDestructive ? '#d33' : '#2e7d32',
        cancelButtonColor: isDestructive ? '#3085d6' : '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}

function confirmForm(e, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) e.target.submit();
    });
}

function openClearanceModal(tenantId, resId, roomInfo, depositAmount, unpaidBalance) {
    document.getElementById('clear_tenant_id').value = tenantId;
    document.getElementById('clear_res_id').value = resId;
    document.getElementById('clear_room').value = roomInfo;
    document.getElementById('clear_deposit').value = depositAmount;
    
    document.getElementById('clear_deduction').value = unpaidBalance.toFixed(2);
    document.getElementById('clear_remarks').value = unpaidBalance > 0 ? "Unpaid system balance (₱" + unpaidBalance.toLocaleString('en-US', {minimumFractionDigits: 2}) + ")" : "No damages or unpaid bills.";
    
    calcClearanceRefund();
    new bootstrap.Modal(document.getElementById('clearanceModal')).show();
}

function calcClearanceRefund() {
    let dep = parseFloat(document.getElementById('clear_deposit').value) || 0;
    let ded = parseFloat(document.getElementById('clear_deduction').value) || 0;
    document.getElementById('clear_net').value = (dep - ded).toFixed(2);
}

async function renewContract(id, dnr) {
    if (dnr == 1) {
        const result = await Swal.fire({
            title: 'Do Not Renew Flagged',
            text: "This user is flagged as 'DO NOT RENEW'. Override and renew?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Override'
        });
        if (!result.isConfirmed) return;
    }

    const { value: formValues } = await Swal.fire({
        title: 'Renew Contract',
        html:
            '<div class="text-start">' +
            '<label class="form-label fw-bold small">Months to Extend</label>' +
            '<input id="swal-months" type="number" class="form-control mb-3" value="1" min="1">' +
            '<label class="form-label fw-bold small">Description (Optional)</label>' +
            '<input id="swal-desc" type="text" class="form-control" placeholder="e.g. Renewal for Semester 2">' +
            '</div>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Renew',
        preConfirm: () => {
            const months = document.getElementById('swal-months').value;
            const desc = document.getElementById('swal-desc').value;
            if (!months || months <= 0) {
                Swal.showValidationMessage('Please enter a valid number of months');
                return false;
            }
            return { months: months, desc: desc };
        }
    });

    if (formValues) {
        const descEncoded = encodeURIComponent(formValues.desc);
        window.location.href = `booking_management.php?action=renew&id=${id}&months=${formValues.months}&desc=${descEncoded}&redirect=view_user&uid=<?= $uid ?>`;
    }
}

<?php if(isset($swal_error)): ?>
Swal.fire({ title: 'Error', text: '<?= $swal_error ?>', icon: 'error' });
<?php endif; ?>

function toggleMenu(e) {
    if(e) e.preventDefault();
    document.getElementById("wrapper").classList.toggle("toggled");
}
const menuToggle = document.getElementById("menu-toggle");
if(menuToggle) menuToggle.addEventListener("click", toggleMenu);
const sidebarToggle = document.getElementById("sidebar-toggle");
if(sidebarToggle) sidebarToggle.addEventListener("click", toggleMenu);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar-wrapper');
    var toggle = document.getElementById('menu-toggle');
    var wrapper = document.getElementById('wrapper');
    
    if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            wrapper.classList.remove('toggled');
        }
    }
});

function confirmDeleteUser() {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to archive this user. They will be moved to the archived list.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, archive user!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteUserForm').submit();
        }
    });
}

function toggleSelectAll(source) {
    checkboxes = document.getElementsByClassName('pay-checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}

function confirmBulkPaid() {
    const checkboxes = document.querySelectorAll('.pay-checkbox:checked');
    if(checkboxes.length === 0) {
        Swal.fire('No Selection', 'Please select at least one payment.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Confirm Payment?',
        text: `Mark ${checkboxes.length} selected payments as Paid?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Mark Paid'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('bulkForm');
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'bulk_mark_paid';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);
            form.submit();
        }
    });
}

let currentApproveType = '';
let currentUserGender = '';

function openApproveModal(resId, type, currentRoomId = null, userGender = '') {
    document.getElementById('approveResId').value = resId;
    document.getElementById('modalRoomType').innerText = type;
    currentApproveType = type;
    currentUserGender = userGender;
    
    filterApproveRooms();
    new bootstrap.Modal(document.getElementById('approveRoomModal')).show();

    // Reset and select current if available
    document.querySelectorAll('.card-room-select').forEach(c => c.classList.remove('selected'));
    document.getElementById('approveRoomId').value = "";
    document.getElementById('btnApproveConfirm').disabled = true;

    // Highlight current room selection
    if(currentRoomId) {
        const card = document.getElementById('room_select_' + currentRoomId);
        if(card) selectApproveRoom(card, currentRoomId);
    }
}

function filterApproveRooms() {
    const floor = document.getElementById('approveFloorFilter').value;
    const items = document.querySelectorAll('.approve-room-item');
    
    items.forEach(item => {
        const itemType = item.getAttribute('data-type');
        const itemFloor = item.getAttribute('data-floor');
        const itemGender = item.getAttribute('data-gender');
        
        if (itemType === currentApproveType && (floor === 'all' || itemFloor === floor) && (!currentUserGender || itemType === 'Single' || itemGender === 'Any' || itemGender === currentUserGender)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function selectApproveRoom(card, id) {
    document.querySelectorAll('.card-room-select').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('approveRoomId').value = id;
    document.getElementById('btnApproveConfirm').disabled = false;
}

function showSchoolId(imageUrl) {
    document.getElementById('schoolIdImage').src = imageUrl;
    var myModal = new bootstrap.Modal(document.getElementById('schoolIdModal'));
    myModal.show();
}

function showProfilePicture(imageUrl, name, email, phone) {
    document.getElementById('profilePicImage').src = imageUrl;
    document.getElementById('modalProfileName').innerText = name;
    document.getElementById('modalProfileEmail').innerText = email;
    document.getElementById('modalProfilePhone').innerText = phone;
    var myModal = new bootstrap.Modal(document.getElementById('profilePicModal'));
    myModal.show();
}

function openRefundModal(pid, amount, unpaidBalance = 0) {
    document.getElementById('refund_pid').value = pid;
    document.getElementById('refund_amount_original').value = amount;
    document.getElementById('refundAmountDisplay').innerText = '₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('refund_deduction').value = unpaidBalance.toFixed(2);
    document.getElementById('refund_remarks').value = unpaidBalance > 0 ? "Unpaid system balance (₱" + unpaidBalance.toLocaleString('en-US', {minimumFractionDigits: 2}) + ")" : "";
    
    calcRefundNet();
    new bootstrap.Modal(document.getElementById('refundModal')).show();
}
function calcRefundNet() {
    let dep = parseFloat(document.getElementById('refund_amount_original').value) || 0;
    let ded = parseFloat(document.getElementById('refund_deduction').value) || 0;
    document.getElementById('refund_net_amount').value = (dep - ded).toFixed(2);
}
function toggleGcashDetails() {
    document.getElementById('gcash_refund_details').style.display = 
        document.getElementById('refund_method').value === 'GCash' ? 'block' : 'none';
}

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) {
            lastUpdate = t;
        } else if (t > lastUpdate) {
            sessionStorage.setItem('playNotifSound', 'true');
            location.reload();
        }
    });
}
setInterval(checkUpdates, 3000);

document.addEventListener('DOMContentLoaded', () => {
    if(sessionStorage.getItem('playNotifSound') === 'true') {
        let audio = new Audio('../assets/sounds/notification.mp3');
        audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
        audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
        sessionStorage.removeItem('playNotifSound');
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

// Keep active tab in URL so it persists on refresh
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', event => {
        const target = event.target.getAttribute('data-bs-target');
        let tabName = 'reservations';
        if(target === '#payments') tabName = 'payments';
        else if(target === '#security-deposit') tabName = 'sd';
        
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    });
});
</script>
</body>
</html>