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

// Fetch Rooms for Approval Modal
$rooms_for_modal = [];
$rfm_q = mysqli_query($conn, "SELECT room_id, room_number, room_name, room_type, floor FROM rooms WHERE availability != 'Maintenance' ORDER BY floor, room_number");
while($r = mysqli_fetch_assoc($rfm_q)){
    $rooms_for_modal[] = $r;
}

// Determine active tab
$active_tab = 'reservations';
if(isset($_GET['pay_status']) || isset($_GET['start_date']) || isset($_GET['end_date']) || (isset($_GET['msg']) && $_GET['msg'] == 'bulk_paid')){
    $active_tab = 'payments';
}

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
        .avatar-circle { width: 80px; height: 80px; font-size: 2rem; background-color: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
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
            <a href="booking_management.php" class="sidebar-link active"><i class="fas fa-calendar-check me-2"></i>Bookings</a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><i class="fas fa-wrench me-2"></i>Maintenance</a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><i class="fas fa-broom me-2"></i>Housekeeping</a>
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
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
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
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="booking_management.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
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
                            <button class="nav-link <?= $active_tab == 'reservations' ? 'active' : '' ?>" id="res-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button">Reservations</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $active_tab == 'payments' ? 'active' : '' ?>" id="pay-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">Payments</button>
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
                                                if($row['status'] == 'Cancelled') $badge = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td>₱<?= number_format($row['total_price'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success" onclick="openApproveModal(<?= $row['reservation_id'] ?>, <?= $row['room_id'] ?>, '<?= $row['room_type'] ?>')"><i class="fas fa-check"></i></button>
                                                <a href="booking_management.php?action=reject&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" onclick="confirmAction(event, this.href, 'Reject this reservation?')"><i class="fas fa-times"></i></a>
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
            </div>

        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i>Approve Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="booking_management.php">
                <div class="modal-body">
                    <input type="hidden" name="reservation_id" id="approveResId">
                    <input type="hidden" name="redirect_url" value="view_user.php?uid=<?= $uid ?>">
                    <p>Please confirm the room assignment before approving.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign Room / Floor</label>
                        <select name="room_id" id="approveRoomSelect" class="form-select" required></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="confirm_approve" class="btn btn-success fw-bold">Confirm & Approve</button>
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
document.getElementById("menu-toggle").addEventListener("click", toggleMenu);
document.getElementById("sidebar-toggle").addEventListener("click", toggleMenu);

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

const allRooms = <?= json_encode($rooms_for_modal) ?>;

function openApproveModal(resId, currentRoomId, roomType) {
    document.getElementById('approveResId').value = resId;
    const select = document.getElementById('approveRoomSelect');
    select.innerHTML = '';

    allRooms.forEach(room => {
        if(room.room_type === roomType) {
            let option = document.createElement('option');
            option.value = room.room_id;
            option.text = `Room ${room.room_number || ''} (${room.room_name}) - ${room.floor}th Floor`;
            if(room.room_id == currentRoomId) option.selected = true;
            select.appendChild(option);
        }
    });
    new bootstrap.Modal(document.getElementById('approveModal')).show();
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