<?php
session_start();
include("../db.php");

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
    $lname = trim($_POST['lname']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = mysqli_real_escape_string($conn, $lname);
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $u_email = mysqli_real_escape_string($conn, $_POST['email']);
    $u_phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $u_occ = mysqli_real_escape_string($conn, $_POST['occupation']);
    $u_addr = mysqli_real_escape_string($conn, $_POST['address']);
    $u_comp = mysqli_real_escape_string($conn, $_POST['company']);
    $u_em_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
    $u_em_num = mysqli_real_escape_string($conn, $_POST['emergency_contact_number']);
    $u_gender = mysqli_real_escape_string($conn, $_POST['gender']);

    // Build query dynamically based on existing columns to prevent errors
    $cols_check = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_cols = [];
    while($c = mysqli_fetch_assoc($cols_check)) $existing_cols[] = $c['Field'];

    $set_clause = "last_name='$lname', first_name='$fname', middle_name='$mname', email='$u_email', phone_number='$u_phone'";
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

// Fetch User Details
$user_query = mysqli_query($conn, "
    SELECT u.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name,
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days
    FROM users u WHERE u.user_id=$uid
");
$user = mysqli_fetch_assoc($user_query);

// Fetch Total Outstanding Balance
$balance_q = mysqli_query($conn, "SELECT SUM(p.amount) as balance FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid'");
$total_balance = mysqli_fetch_assoc($balance_q)['balance'] ?? 0;

$financial_q = mysqli_query($conn, "SELECT IFNULL(SUM(p.amount), 0) as total_billed, IFNULL(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END), 0) as total_paid FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status != 'Cancelled'");
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
    SELECT r.*, rm.room_name, rm.room_number, rm.room_type
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id=$uid 
    ORDER BY r.created_at DESC
");

// Filter Logic for Payments
$pay_status_filter = isset($_GET['pay_status']) ? $_GET['pay_status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$pay_where = "r.user_id=$uid";
if($pay_status_filter){
    $pay_where .= " AND p.payment_status = '" . mysqli_real_escape_string($conn, $pay_status_filter) . "'";
}
if($start_date){
    $pay_where .= " AND p.payment_date >= '" . mysqli_real_escape_string($conn, $start_date) . " 00:00:00'";
}
if($end_date){
    $pay_where .= " AND p.payment_date <= '" . mysqli_real_escape_string($conn, $end_date) . " 23:59:59'";
}

// Fetch Payment History
$pay_query = mysqli_query($conn, "
    SELECT p.*, r.reservation_id, rm.room_name, rm.room_number, rm.room_type 
    FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE $pay_where 
    ORDER BY p.payment_date DESC
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
if(isset($_GET['pay_status']) || isset($_GET['start_date']) || isset($_GET['end_date']) || (isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid')){
    $active_tab = 'payments';
}

// Fetch User-Specific Pending Counts for Tabs
$user_pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE user_id=$uid AND status IN ('Pending', 'Verifying')"))['c'];
$user_pending_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid' AND p.proof_image IS NOT NULL"))['c'];
$user_unpaid_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid' AND p.proof_image IS NULL"))['c'];

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
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
                            <a href="javascript:void(0)" onclick="showProfilePicture('../uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>', '<?= htmlspecialchars($user['full_name']) ?>', '<?= htmlspecialchars($user['email']) ?>', '<?= htmlspecialchars($user['phone_number']) ?>')" title="View Profile Picture">
                                <img src="../uploads/profiles/<?= $user['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </a>
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
                        <span><i class="fas fa-id-badge me-1"></i> ID: #<?= $user['user_id'] ?></span>
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
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="showSchoolId('../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>')">
                                <i class="fas fa-id-card me-1"></i> View School ID
                            </button>
                        </div>
                    <?php elseif($user['occupation'] == 'Student' && empty($user['school_id_image'])): ?>
                        <div class="mt-2">
                            <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Missing School ID</span>
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
                    <div class="card p-3 bg-white border-0 shadow-sm rounded-4 h-100 border-start border-danger border-4">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Remaining Balance</small>
                        <h4 class="fw-bold text-danger mb-0">₱<?= number_format($total_billed - $total_paid, 2) ?></h4>
                    </div>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid'): ?>
                <div class="alert alert-success">Selected payments marked as paid successfully.</div>
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
                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="showSchoolId('../uploads/proofs/<?= $pending_update['school_id_image'] ?>')">
                                <i class="fas fa-image me-1"></i> View New ID
                            </button>
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
                                        <a href="booking_management.php?action=terminate&id=<?= $exp['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'End this contract? This will mark it as Completed.')"><i class="fas fa-file-contract me-1"></i> End Contract</a>
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
                                <?php if($user_unpaid_count > 0): ?>
                                    <span class="badge bg-warning text-dark rounded-pill ms-1" title="Unpaid Bills"><?= $user_unpaid_count ?></span>
                                <?php endif; ?>
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
                                        <th>ID</th>
                                        <th>Room</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($res_query)): ?>
                                    <tr>
                                        <td>#<?= $row['reservation_id'] ?></td>
                                        <td class="fw-bold">
                                            <?= !empty($row['room_number']) ? 'Room ' . htmlspecialchars($row['room_number']) : htmlspecialchars($row['room_name']) ?> <small class="text-muted fw-normal">(<?= $row['room_type'] ?>)</small>
                                            <?php if(!empty($row['bed_preference']) && $row['bed_preference'] != 'Any'): ?>
                                                <div class="badge bg-light text-dark border mt-1"><i class="fas fa-bed me-1"></i> <?= $row['bed_preference'] ?></div>
                                            <?php endif; ?>
                                            <?php if(isset($row['auto_assigned']) && $row['auto_assigned'] == 0): ?>
                                                <div class="badge bg-primary mt-1"><i class="fas fa-hand-pointer me-1"></i> Chosen by Guest</div>
                                            <?php endif; ?>
                                            <?php if(!empty($row['extended_from'])): ?>
                                                <div class="badge bg-info text-dark mt-1"><i class="fas fa-history me-1"></i> Extension Request (for #<?= $row['extended_from'] ?>)</div>
                                            <?php endif; ?>
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
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td>₱<?= number_format($row['total_price'], 2) ?></td>
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
                                                <a href="booking_management.php?action=terminate&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'End this contract?')"><i class="fas fa-ban"></i></a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
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
                        <select name="pay_status" class="form-select form-select-sm" style="width: 110px;">
                            <option value="">All Status</option>
                            <option value="Paid" <?= $pay_status_filter == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Unpaid" <?= $pay_status_filter == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
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
                                $is_overdue = ($pay['payment_status'] == 'Unpaid' && strtotime($pay['payment_date']) < strtotime('-5 days'));
                                $row_class = $is_overdue ? 'table-danger' : '';
                                $desc = !empty($pay['description']) ? $pay['description'] : 'Room Payment';
                                $room_info = !empty($pay['room_number']) ? 'Room ' . htmlspecialchars($pay['room_number']) : ($pay['room_name'] ? htmlspecialchars($pay['room_name']) : '<span class="text-muted">Unknown Room</span>');
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td>
                                    <?php if($pay['payment_status'] == 'Unpaid'): ?>
                                        <input type="checkbox" name="payment_ids[]" value="<?= $pay['payment_id'] ?>" class="pay-checkbox">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= date('M d, Y', strtotime($pay['payment_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($pay['payment_date'])) ?></small>
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
                                        <?= $pay['payment_status'] ?>
                                        <?php if($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if($is_overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                                    <?php if($pay['payment_status'] == 'Unpaid' && !empty($pay['proof_image'])): ?><br><small class="text-danger fw-bold mt-1 d-block" style="font-size:0.7rem;"><i class="fas fa-exclamation-circle me-1"></i>Review Proof</small><?php endif; ?>
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
                                            <a href="booking_management.php?action=mark_paid&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success" title="Approve Payment" onclick="confirmAction(event, this.href, 'Approve this payment as Paid?')"><i class="fas fa-check"></i></a>
                                            <?php if(!empty($pay['proof_image'])): ?>
                                                <a href="booking_management.php?action=reject_payment&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-warning text-dark" title="Reject Payment Proof (Re-upload)" onclick="confirmAction(event, this.href, 'Reject this payment proof? The guest will have to re-upload.')"><i class="fas fa-undo"></i></a>
                                            <?php endif; ?>
                                            <a href="booking_management.php?action=cancel_payment&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" title="Cancel Payment" onclick="confirmAction(event, this.href, 'Cancel this payment? Use this if the guest no longer wants to continue.')"><i class="fas fa-times"></i></a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
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

                    </div>
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
                    ?>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Last Name</label><input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($lname) ?>" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">First Name</label><input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($fname) ?>" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Middle Name</label><input type="text" name="mname" class="form-control" value="<?= htmlspecialchars($mname) ?>"></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09">
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
                                <img src="../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>" class="img-thumbnail" style="max-height: 100px; cursor: pointer;" onclick="showSchoolId('../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>')" title="Click to enlarge">
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Emergency Contact Number</label>
                            <input type="text" name="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user['emergency_contact_number'] ?? '') ?>" pattern="^09\d{9}$" maxlength="11" title="11-digit PH number starting with 09">
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
        
        if (itemType === currentApproveType && (floor === 'all' || itemFloor === floor) && (!currentUserGender || itemGender === currentUserGender)) {
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
</script>
</body>
</html>