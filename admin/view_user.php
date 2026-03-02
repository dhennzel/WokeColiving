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

// Handle Delete User
if(isset($_POST['delete_user'])){
    $del_uid = (int)$_POST['user_id'];
    // Check for active reservations
    $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid AND status IN ('Pending', 'Approved')");
    if(mysqli_num_rows($check_active) > 0){
        $swal_error = "Cannot delete user: They have active or pending reservations. Please cancel/complete them first.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Get Reservation IDs to clean up child records
            $res_ids = [];
            $r_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid");
            while($row = mysqli_fetch_assoc($r_q)){
                $res_ids[] = $row['reservation_id'];
            }

            if(!empty($res_ids)){
                $ids_str = implode(',', $res_ids);
                // Delete Payments linked to reservations
                mysqli_query($conn, "DELETE FROM payments WHERE reservation_id IN ($ids_str)");
                // Try deleting from optional tables (ignore if not exists)
                try { mysqli_query($conn, "DELETE FROM utility_bills WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM temporary_moves WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
            }

            // 2. Delete records linked directly to user
            // Break self-referencing constraints if any
            try { mysqli_query($conn, "UPDATE reservations SET extended_from = NULL WHERE user_id=$del_uid"); } catch(Exception $e){}
            mysqli_query($conn, "DELETE FROM reservations WHERE user_id=$del_uid");
            
            try { mysqli_query($conn, "DELETE FROM activity_logs WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM maintenance_requests WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM housekeeping_requests WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM waitlist WHERE user_id=$del_uid"); } catch(Exception $e){}

            mysqli_query($conn, "DELETE FROM users WHERE user_id=$del_uid");
            trigger_update($conn);
            mysqli_commit($conn);
            echo "<script>window.location='booking_management.php?msg=user_deleted';</script>";
            exit;
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);
            $swal_error = "Cannot delete user. Database error: " . addslashes($e->getMessage());
        }
    }
}

// Handle Update User Info
if(isset($_POST['update_user_info'])){
    $lname = trim($_POST['lname']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $u_name = mysqli_real_escape_string($conn, $lname . ', ' . $fname . ' ' . $mname);
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

    $set_clause = "full_name='$u_name', email='$u_email', phone_number='$u_phone'";
    if(in_array('occupation', $existing_cols)) $set_clause .= ", occupation='$u_occ'";
    if(in_array('address', $existing_cols)) $set_clause .= ", address='$u_addr'";
    if(in_array('company', $existing_cols)) $set_clause .= ", company='$u_comp'";
    if(in_array('emergency_contact_name', $existing_cols)) $set_clause .= ", emergency_contact_name='$u_em_name'";
    if(in_array('emergency_contact_number', $existing_cols)) $set_clause .= ", emergency_contact_number='$u_em_num'";
    if(in_array('gender', $existing_cols)) $set_clause .= ", gender='$u_gender'";

    if(mysqli_query($conn, "UPDATE users SET $set_clause WHERE user_id=$uid")){
        log_activity($conn, $uid, "Profile Updated", "Admin updated user details.");
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
                log_activity($conn, $req['user_id'], "Profile Update Approved", "Admin approved profile changes.");
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

// Handle Room Assignment (Move Tenant)
if(isset($_POST['assign_room'])){
    $res_id = (int)$_POST['reservation_id'];
    $new_room_id = (int)$_POST['new_room_id'];
    $new_bed_pref = $_POST['new_bed_preference'] ?? 'Any';
    
    // Get dates of the reservation being moved
    $res_dates_q = mysqli_query($conn, "SELECT start_date, end_date FROM reservations WHERE reservation_id=$res_id");
    $res_dates = mysqli_fetch_assoc($res_dates_q);
    $s_date = $res_dates['start_date'];
    $e_date = $res_dates['end_date'];

    // Check capacity
    $chk_cap = mysqli_query($conn, "SELECT total_beds, room_name, room_type, availability FROM rooms WHERE room_id=$new_room_id");
    $room_info = mysqli_fetch_assoc($chk_cap);
    
    // Check occupancy during the specific dates of the reservation
    $chk_occ = mysqli_query($conn, "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id=$new_room_id AND status IN ('Pending', 'Approved') AND reservation_id != $res_id AND start_date < '$e_date' AND end_date > '$s_date' GROUP BY bed_preference");
    
    $total_occ = 0;
    $occ_lower = 0;
    $occ_upper = 0;
    $occ_any = 0;
    
    while($row = mysqli_fetch_assoc($chk_occ)){
        $total_occ += $row['cnt'];
        if($row['bed_preference'] == 'Lower Bunk') $occ_lower += $row['cnt'];
        elseif($row['bed_preference'] == 'Upper Bunk') $occ_upper += $row['cnt'];
        else $occ_any += $row['cnt'];
    }
    
    $can_move = false;
    $error_msg = "Target room is fully booked.";

    if($room_info['availability'] == 'Maintenance') {
        $error_msg = "Target room is under maintenance.";
    } elseif($total_occ < $room_info['total_beds']){
        if($room_info['room_type'] == '4-Bed' || $room_info['room_type'] == '6-Bed'){
             $cap_lower = ceil($room_info['total_beds'] / 2);
             $cap_upper = floor($room_info['total_beds'] / 2);
             
             $avail_upper = max(0, $cap_upper - $occ_upper);
             $avail_lower = max(0, $cap_lower - $occ_lower);
             
             if($occ_any > 0) {
                 $fill_lower = min($avail_lower, $occ_any);
                 $avail_lower -= $fill_lower;
                 $occ_any -= $fill_lower;
                 
                 $avail_upper -= $occ_any;
                 $avail_upper = max(0, $avail_upper);
             }
             
             if($new_bed_pref == 'Lower Bunk'){
                 if($avail_lower > 0) $can_move = true;
                 else $error_msg = "No Lower Bunks available in target room.";
             } elseif($new_bed_pref == 'Upper Bunk'){
                 if($avail_upper > 0) $can_move = true;
                 else $error_msg = "No Upper Bunks available in target room.";
             } else {
                 $can_move = true;
             }
        } else {
            $new_bed_pref = 'Any'; // Force Any for Single
            $can_move = true;
        }
    }
    
    if($can_move){
        mysqli_query($conn, "UPDATE reservations SET room_id=$new_room_id, bed_preference='$new_bed_pref' WHERE reservation_id=$res_id");
        log_activity($conn, $uid, "Room Re-assigned", "Reservation #$res_id moved to " . $room_info['room_name'] . " ($new_bed_pref)");
        echo "<script>window.location.href='view_user.php?uid=$uid&msg=room_updated';</script>";
        exit;
    } else {
        $swal_error = $error_msg;
    }
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

// Fetch User Details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$uid");
$user = mysqli_fetch_assoc($user_query);

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
    SELECT r.*, rm.room_name, rm.room_type
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
    SELECT p.*, r.reservation_id, rm.room_name, rm.room_type 
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
    SELECT r.*, rm.room_name 
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

// Fetch Base Rooms List (Availability calculated dynamically per reservation)
$base_rooms_list = [];
$rfm_q = mysqli_query($conn, "SELECT room_id, room_number, room_name, room_type, floor, total_beds, availability FROM rooms WHERE is_archived=0 ORDER BY floor ASC, room_type ASC, room_number ASC");
if($rfm_q){
    while($r = mysqli_fetch_assoc($rfm_q)){
        $base_rooms_list[$r['room_id']] = $r;
    }
}

// Determine active tab
$active_tab = 'reservations';
if(isset($_GET['pay_status']) || isset($_GET['start_date']) || isset($_GET['end_date']) || (isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid')){
    $active_tab = 'payments';
}

// Fetch User-Specific Pending Counts for Tabs
$user_pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE user_id=$uid AND status='Pending'"))['c'];
$user_pending_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$uid AND p.payment_status='Unpaid'"))['c'];

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];

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
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        
        .user-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
        }
        .nav-tabs .nav-link { color: var(--dark-green); border: none; border-bottom: 3px solid transparent; padding-bottom: 10px; }
        .nav-tabs .nav-link.active { color: var(--primary-green); border-bottom: 3px solid var(--primary-green); background: transparent; font-weight: bold; }
        .nav-tabs .nav-link:hover { border-color: transparent; color: var(--primary-green); }
        .profile-header { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .avatar-circle { width: 80px; height: 80px; font-size: 2rem; background-color: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link active d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                <?php if($pending_res > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $pending_res ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_house ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="settingsSubmenu">
                <span><i class="fas fa-cog me-2"></i>Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            
            <!-- Minimal Profile Header -->
            <div class="profile-header p-4 mb-4 d-flex flex-wrap align-items-center gap-4">
                <div class="position-relative">
                    <div class="avatar-circle">
                        <?php if(!empty($user['profile_image'])): ?>
                            <img src="../uploads/profiles/<?= $user['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <?php if($user['do_not_renew']): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white">DNR</span>
                    <?php endif; ?>
                    <?php if(!empty($user['is_walkin'])): ?>
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-info border border-white text-dark">Walk-in</span>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <h3 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['full_name']) ?></h3>
                    <div class="d-flex flex-wrap gap-3 text-muted small">
                        <span><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?></span>
                        <span><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($user['phone_number']) ?></span>
                        <span><i class="fas fa-calendar me-1"></i> Joined <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        <span><i class="fas fa-id-badge me-1"></i> ID: #<?= $user['user_id'] ?></span>
                        <?php if(!empty($user['occupation'])): ?>
                            <span><i class="fas fa-briefcase me-1"></i> <?= htmlspecialchars($user['occupation']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if($user['occupation'] == 'Student' && !empty($user['school_id_image'])): ?>
                        <div class="mt-2">
                            <span class="badge bg-info text-dark"><i class="fas fa-user-graduate me-1"></i> Student</span>
                            <a href="../uploads/proofs/<?= htmlspecialchars($user['school_id_image']) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-id-card me-1"></i> View School ID
                            </a>
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
                    <form method="POST" id="deleteUserForm" class="d-inline">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <input type="hidden" name="delete_user" value="1">
                        <button type="button" class="btn btn-outline-danger rounded-pill btn-sm" onclick="confirmDeleteUser()"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                    </form>
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
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
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
                            <a href="../uploads/proofs/<?= $pending_update['school_id_image'] ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="fas fa-image me-1"></i> View New ID</a>
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
                                    <td>
                                        <?= htmlspecialchars($exp['room_name']) ?>
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
                                    <span class="badge bg-danger rounded-pill ms-1"><?= $user_pending_pay ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="room-tab" data-bs-toggle="tab" data-bs-target="#room_assign" type="button">Room Assignment</button>
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
                                        <td>
                                            <?= $row['room_name'] ?> <small class="text-muted">(<?= $row['room_type'] ?>)</small>
                                            <?php if(!empty($row['bed_preference']) && $row['bed_preference'] != 'Any'): ?>
                                                <div class="badge bg-light text-dark border mt-1"><i class="fas fa-bed me-1"></i> <?= $row['bed_preference'] ?></div>
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
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="booking_management.php?action=verify&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-info text-white" onclick="confirmAction(event, this.href, 'Move this reservation to Verifying status?')" title="Verify"><i class="fas fa-search"></i></a>
                                                <a href="booking_management.php?action=reject&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" onclick="confirmAction(event, this.href, 'Reject this reservation?')" title="Reject"><i class="fas fa-times"></i></a>
                                            <?php elseif($row['status'] == 'Verifying'): ?>
                                                <?php
                                                    // Check Payment
                                                    $pay_chk = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payments WHERE reservation_id=".$row['reservation_id']." AND payment_status='Paid'");
                                                    $is_paid = mysqli_fetch_assoc($pay_chk)['cnt'] > 0;
                                                    // Check Signature
                                                    $has_sig = !empty($row['signature_image']);
                                                ?>
                                                <div class="d-flex justify-content-end gap-1">
                                                    <a href="booking_management.php?action=approve&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success" onclick="confirmAction(event, this.href, 'Approve this reservation?')" title="Approve"><i class="fas fa-check"></i></a>
                                                    <a href="booking_management.php?action=reject&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" onclick="confirmAction(event, this.href, 'Reject this reservation?')" title="Reject"><i class="fas fa-times"></i></a>
                                                </div>
                                                <?php if(!$is_paid || !$has_sig): ?>
                                                    <div class="small text-danger mt-1 text-end" style="font-size: 0.7rem;">
                                                        <i class="fas fa-exclamation-triangle"></i> <?= !$is_paid ? 'Unpaid' : '' ?> <?= (!$is_paid && !$has_sig) ? '&' : '' ?> <?= !$has_sig ? 'No Sig' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif($row['status'] == 'Approved'): ?>
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
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = 0;
                            while($pay = mysqli_fetch_assoc($pay_query)): 
                                $total_amount += $pay['amount'];
                            ?>
                            <?php
                                $is_overdue = ($pay['payment_status'] == 'Unpaid' && strtotime($pay['payment_date']) < strtotime('-5 days'));
                                $row_class = $is_overdue ? 'table-danger' : '';
                                $desc = !empty($pay['description']) ? $pay['description'] : 'Room Payment';
                                $room_info = $pay['room_name'] ? htmlspecialchars($pay['room_name']) . ' <small class="text-muted">('.$pay['room_type'].')</small>' : '<span class="text-muted">Unknown Room</span>';
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
                                    <span class="badge <?= $pay['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $pay['payment_status'] ?></span>
                                    <?php if($is_overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if($pay['payment_status'] == 'Unpaid'): ?>
                                        <a href="booking_management.php?action=mark_paid&pid=<?= $pay['payment_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success me-1" title="Confirm Payment" onclick="confirmAction(event, this.href, 'Mark this payment as Paid?')"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="payment_details.php?id=<?= $pay['payment_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($pay_query) == 0): ?>
                                <tr><td colspan="8" class="text-center text-muted">No payment history found.</td></tr>
                            <?php else: ?>
                                <tr class="table-light fw-bold border-top">
                                    <td colspan="5" class="text-end text-uppercase text-secondary">Total Amount:</td>
                                    <td class="text-success fs-6">₱<?= number_format($total_amount, 2) ?></td>
                                    <td colspan="2"></td>
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

                        <!-- Room Assignment Tab -->
                        <div class="tab-pane fade" id="room_assign" role="tabpanel">
                            <h6 class="fw-bold text-muted mb-3">Manage Room Assignments</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr><th>Reservation</th><th>Current Room</th><th>New Room</th><th class="text-center">Action</th></tr></thead>
                                    <tbody>
                                        <?php 
                                        $assign_q = mysqli_query($conn, "SELECT r.*, rm.room_name, rm.room_type FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.user_id=$uid AND r.status IN ('Pending', 'Approved') ORDER BY r.created_at DESC");
                                        while($row = mysqli_fetch_assoc($assign_q)): 
                                        ?>
                                        <tr>
                                            <td>#<?= $row['reservation_id'] ?> <span class="badge bg-secondary"><?= $row['status'] ?></span></td>
                                            <td>
                                                <?= $row['room_name'] ?> (<?= $row['room_type'] ?>)
                                                <?php if($row['bed_preference'] != 'Any'): ?>
                                                    <br><small class="text-muted"><i class="fas fa-bed"></i> <?= $row['bed_preference'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                <select name="new_room_id" form="assign_form_<?= $row['reservation_id'] ?>" class="form-select form-select-sm" required style="max-width: 200px;" onchange="updateBedOptions(this)">
                                                    <option value="" disabled>Select Room</option>
                                                    <?php 
                                                    // Dynamic Availability Calculation
                                                    $s_date = $row['start_date'];
                                                    $e_date = $row['end_date'];
                                                    $current_res_id = $row['reservation_id'];
                                                    
                                                    // Fetch occupancy for this specific date range
                                                    $occupancy_map = [];
                                                    $occ_sql = "SELECT room_id, bed_preference, COUNT(*) as cnt 
                                                                FROM reservations 
                                                                WHERE status IN ('Pending', 'Approved') 
                                                                AND reservation_id != $current_res_id 
                                                                AND start_date < '$e_date' AND end_date > '$s_date' 
                                                                GROUP BY room_id, bed_preference";
                                                    $occ_res = mysqli_query($conn, $occ_sql);
                                                    while($occ = mysqli_fetch_assoc($occ_res)){
                                                        $rid = $occ['room_id'];
                                                        if(!isset($occupancy_map[$rid])) $occupancy_map[$rid] = ['lower'=>0, 'upper'=>0, 'any'=>0];
                                                        if($occ['bed_preference'] == 'Lower Bunk') $occupancy_map[$rid]['lower'] += $occ['cnt'];
                                                        elseif($occ['bed_preference'] == 'Upper Bunk') $occupancy_map[$rid]['upper'] += $occ['cnt'];
                                                        else $occupancy_map[$rid]['any'] += $occ['cnt'];
                                                    }

                                                    $current_floor = 0;
                                                    foreach($base_rooms_list as $r): 
                                                        $rid = $r['room_id'];
                                                        $total_beds = $r['total_beds'];
                                                        $occ = $occupancy_map[$rid] ?? ['lower'=>0, 'upper'=>0, 'any'=>0];
                                                        
                                                        $occupied = $occ['lower'] + $occ['upper'] + $occ['any'];
                                                        $avail_total = max(0, $total_beds - $occupied);
                                                        $avail_text = "$avail_total Free";
                                                        
                                                        $avail_lower = 0;
                                                        $avail_upper = 0;

                                                        // Logic from admin_rooms.php
                                                        $cap_upper = floor($total_beds / 2);
                                                        $cap_lower = ceil($total_beds / 2);
                                                        
                                                        $avail_upper = max(0, $cap_upper - $occ['upper']);
                                                        $avail_lower = max(0, $cap_lower - $occ['lower']);
                                                        
                                                        $taken_any = $occ['any'];
                                                        if($taken_any > 0) {
                                                            $fill_lower = min($avail_lower, $taken_any);
                                                            $avail_lower -= $fill_lower;
                                                            $taken_any -= $fill_lower;
                                                            
                                                            $avail_upper -= $taken_any;
                                                            $avail_upper = max(0, $avail_upper);
                                                        }

                                                        // Override if Maintenance
                                                        if($r['availability'] == 'Maintenance') {
                                                            $avail_total = 0;
                                                            $avail_lower = 0;
                                                            $avail_upper = 0;
                                                            $avail_text = "Maintenance";
                                                        }

                                                        if($r['room_type'] == '4-Bed' || $r['room_type'] == '6-Bed'){
                                                            $avail_text .= " (L:$avail_lower, U:$avail_upper)";
                                                        } else {
                                                            if($r['availability'] != 'Maintenance') $avail_lower = $avail_total;
                                                        }

                                                        if($current_floor != $r['floor']){
                                                            if($current_floor != 0) echo '</optgroup>';
                                                            $current_floor = $r['floor'];
                                                            echo '<optgroup label="Floor ' . $current_floor . '">';
                                                        }
                                                        $room_display = "Room " . ($r['room_number'] ? $r['room_number'] : 'N/A') . " (" . $r['room_type'] . ")";
                                                        
                                                        $is_current = ($r['room_id'] == $row['room_id']);
                                                        $is_full = ($avail_total == 0);
                                                        $disabled = (!$is_current && $is_full) ? 'disabled' : '';
                                                        
                                                        if(!$is_current) $room_display .= $is_full ? " - FULL" : " - " . $avail_text;
                                                        else $room_display .= " (Current)";
                                                    ?>
                                                        <option value="<?= $r['room_id'] ?>" data-lower="<?= $avail_lower ?>" data-upper="<?= $avail_upper ?>" data-type="<?= $r['room_type'] ?>" <?= $is_current ? 'selected' : '' ?> <?= $disabled ?> class="<?= $is_full && !$is_current ? 'text-danger' : '' ?>">
                                                            <?= $room_display ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                    <?php if($current_floor != 0) echo '</optgroup>'; ?>
                                                </select>
                                                <select name="new_bed_preference" form="assign_form_<?= $row['reservation_id'] ?>" class="form-select form-select-sm" style="width: 110px;">
                                                    <option value="Any" <?= $row['bed_preference'] == 'Any' ? 'selected' : '' ?>>Any</option>
                                                    <option value="Lower Bunk" <?= $row['bed_preference'] == 'Lower Bunk' ? 'selected' : '' ?>>Lower</option>
                                                    <option value="Upper Bunk" <?= $row['bed_preference'] == 'Upper Bunk' ? 'selected' : '' ?>>Upper</option>
                                                </select>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <form method="POST" id="assign_form_<?= $row['reservation_id'] ?>">
                                                    <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                                                    <button type="submit" name="assign_room" class="btn btn-sm btn-primary px-3">Move</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($assign_q) == 0): ?>
                                            <tr><td colspan="4" class="text-center text-muted">No active reservations to move.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>

        </div>
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
                        $full_name = $user['full_name'];
                        $lname = ''; $fname = ''; $mname = '';
                        if(strpos($full_name, ',') !== false) {
                            $parts = explode(',', $full_name);
                            $lname = trim($parts[0]);
                            $rest = trim($parts[1] ?? '');
                            $parts2 = explode(' ', $rest);
                            $fname = $parts2[0] ?? '';
                            $mname = isset($parts2[1]) ? implode(' ', array_slice($parts2, 1)) : '';
                        } else {
                            $fname = $full_name; // Fallback for old data
                        }
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
                            <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>">
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
                            <input type="text" name="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user['emergency_contact_number'] ?? '') ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        text: "You are about to PERMANENTLY delete this user. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete user!'
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

function updateBedOptions(roomSelect) {
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const lower = parseInt(selectedOption.getAttribute('data-lower') || 0);
    const upper = parseInt(selectedOption.getAttribute('data-upper') || 0);
    const type = selectedOption.getAttribute('data-type');
    
    const bedSelect = roomSelect.parentNode.querySelector('select[name="new_bed_preference"]');
    if(!bedSelect) return;

    const optLower = bedSelect.querySelector('option[value="Lower Bunk"]');
    const optUpper = bedSelect.querySelector('option[value="Upper Bunk"]');
    
    optLower.disabled = false; optLower.text = "Lower";
    optUpper.disabled = false; optUpper.text = "Upper";

    if (type === 'Single') {
        optLower.disabled = true; optUpper.disabled = true;
        bedSelect.value = 'Any';
    } else {
        if (lower <= 0) { optLower.disabled = true; optLower.text = "Lower (Full)"; }
        if (upper <= 0) { optUpper.disabled = true; optUpper.text = "Upper (Full)"; }
        
        if(bedSelect.value === 'Lower Bunk' && lower <= 0) bedSelect.value = 'Any';
        if(bedSelect.value === 'Upper Bunk' && upper <= 0) bedSelect.value = 'Any';
    }
}

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
</script>
</body>
</html>