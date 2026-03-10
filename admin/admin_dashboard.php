<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';

$current_page = basename($_SERVER['PHP_SELF']);

// AJAX: Fetch Recent Activity
if(isset($_GET['fetch_activity'])){
    $logs_q = mysqli_query($conn, "SELECT l.*, CONCAT(u.last_name, ', ', u.first_name) as full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 5");
    if(mysqli_num_rows($logs_q) > 0){
        while($log = mysqli_fetch_assoc($logs_q)){
            echo '<div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <small class="fw-bold text-truncate" style="max-width: 150px;">'.htmlspecialchars($log['action']).'</small>
                    <small class="text-muted" style="font-size: 0.7rem;">'.date('M d, H:i', strtotime($log['created_at'])).'</small>
                </div>
                <p class="mb-1 small text-muted text-truncate">'.htmlspecialchars($log['details']).'</p>
                <small class="text-primary" style="font-size: 0.7rem;"><i class="fas fa-user-circle me-1"></i> '.($log['full_name'] ? htmlspecialchars($log['full_name']) : 'System').'</small>
            </div>';
        }
    } else {
        echo '<div class="list-group-item text-center text-muted small py-4">No recent activity.</div>';
    }
    exit;
}

// Ensure do_not_renew column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'do_not_renew'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN do_not_renew TINYINT(1) DEFAULT 0");
}

// Stats: Earnings
$total_earnings_query = mysqli_query($conn, "SELECT SUM(amount) AS total FROM payments WHERE payment_status='Paid'");
$total_earnings = mysqli_fetch_assoc($total_earnings_query)['total'] ?? 0;

// Stats: Counts
$pending_count_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM reservations WHERE status='Pending'");
$pending_count = mysqli_fetch_assoc($pending_count_query)['count'];

$approved_count_query = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS count FROM reservations WHERE status='Approved'");
$approved_count = mysqli_fetch_assoc($approved_count_query)['count'];

$total_rooms_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM rooms");
$total_rooms = mysqli_fetch_assoc($total_rooms_query)['count'];

// Stats: Occupancy Rate (Active Beds / Total Capacity)
$today = date('Y-m-d');
// 1. Total Capacity (Beds in non-maintenance rooms)
$cap_q = mysqli_query($conn, "SELECT SUM(total_beds) as total FROM rooms WHERE availability != 'Maintenance' AND is_archived=0");
$total_capacity = mysqli_fetch_assoc($cap_q)['total'] ?? 0;

// 2. Total Occupied (Active Approved and Pending Reservations)
$occ_q = mysqli_query($conn, "SELECT COUNT(*) as occupied FROM reservations WHERE status IN ('Approved', 'Pending') AND start_date <= '$today' AND end_date > '$today'");
$total_occupied = mysqli_fetch_assoc($occ_q)['occupied'] ?? 0;

$occupancy_rate = ($total_capacity > 0) ? round(($total_occupied / $total_capacity) * 100) : 0;

// Fetch Expiring Contracts (Approved and ending within 7 days or already ended)
$expiring_query = mysqli_query($conn, "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, u.do_not_renew, rm.room_name, rm.room_number 
    FROM reservations r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.status = 'Approved' 
    AND r.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY r.end_date ASC
");

// Stats: Pending Maintenance
$pending_maint_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM maintenance_requests WHERE status='Pending'");
$pending_maint = ($pending_maint_query) ? mysqli_fetch_assoc($pending_maint_query)['count'] : 0;

// Stats: Pending Housekeeping
$pending_house_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM housekeeping_requests WHERE status='Pending'");
$pending_house = ($pending_house_query) ? mysqli_fetch_assoc($pending_house_query)['count'] : 0;

// Stats: Waitlist Count
$waitlist_count_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM waitlist WHERE notified_at IS NULL");
$waitlist_count = ($waitlist_count_query) ? mysqli_fetch_assoc($waitlist_count_query)['count'] : 0;

// Stats: Deletion Requests Count
$del_req_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM account_deletion_requests WHERE status='Pending'");
$del_req_count = ($del_req_query) ? mysqli_fetch_assoc($del_req_query)['count'] : 0;

// Ensure is_hidden column exists
$check_col_hidden = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'is_hidden'");
if(mysqli_num_rows($check_col_hidden) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
}

// Detailed Occupancy by Floor
    $floors_data = [];
    $all_rooms_unified = []; // For "All Floors" view - sorted by room_number
    
    $floors_q = mysqli_query($conn, "SELECT DISTINCT floor FROM rooms WHERE is_hidden=0 ORDER BY floor ASC");

    while($f = mysqli_fetch_assoc($floors_q)){
        $floor = $f['floor'];
        $rooms_on_floor = [];
        
        $r_q = mysqli_query($conn, "SELECT * FROM rooms WHERE floor = $floor AND is_hidden=0 ORDER BY CAST(room_number AS UNSIGNED) ASC");
        while($room = mysqli_fetch_assoc($r_q)){
            $rid = $room['room_id'];
            $rtype = $room['room_type'];
            $total_beds = $room['total_beds'];
            $room_number = $room['room_number'] ?? '';
            
            // Get Occupancy for this room
            $ro_q = mysqli_query($conn, "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id=$rid AND status IN ('Pending', 'Approved') AND start_date <= '$today' AND end_date > '$today' GROUP BY bed_preference");
            
            $occ_upper = 0; $occ_lower = 0; $occ_any = 0;
            $total_room_occ = 0;
            while($ro = mysqli_fetch_assoc($ro_q)){
                $total_room_occ += $ro['cnt'];
                if($ro['bed_preference'] == 'Upper Bunk') $occ_upper += $ro['cnt'];
                elseif($ro['bed_preference'] == 'Lower Bunk') $occ_lower += $ro['cnt'];
                else $occ_any += $ro['cnt'];
            }
            
            $cap_lower = ceil($total_beds / 2);
            $cap_upper = floor($total_beds / 2);
            if($rtype == 'Single') { $cap_lower = $total_beds; $cap_upper = 0; }

            // Calculate Availability (Inventory Logic)
            $avail_upper = max(0, $cap_upper - $occ_upper);
            $avail_lower = max(0, $cap_lower - $occ_lower);

            if($occ_any > 0) {
                $fill_lower = min($avail_lower, $occ_any);
                $avail_lower -= $fill_lower;
                $occ_any -= $fill_lower;
                
                $avail_upper -= $occ_any;
                $avail_upper = max(0, $avail_upper);
            }
            
            // Make room display name consistent with admin_rooms.php
            $room_display = $room['room_name'];
            if (!empty($room_number)) {
                $room_display = "Room " . $room_number;
            } elseif (is_numeric($room['room_name'])) {
                $room_display = "Room " . $room['room_name'];
            }
            
            $room_data = [
                'id' => $rid, 'name' => $room['room_name'], 'room_number' => $room_number, 'display_name' => $room_display, 'type' => $rtype, 'total' => $total_beds,
                'occupied' => $total_room_occ, 'avail_lower' => $avail_lower, 'avail_upper' => $avail_upper,
                'cap_lower' => $cap_lower, 'cap_upper' => $cap_upper, 'status' => $room['status'], 'floor' => $floor
            ];
            
            $rooms_on_floor[] = $room_data;
            $all_rooms_unified[] = $room_data; // Add to unified list for "All Floors" view
        }
        $floors_data[$floor] = $rooms_on_floor;
    }
    
    // Sort unified list by room_number (ascending) for "All Floors" view
    usort($all_rooms_unified, function($a, $b) {
        $a_num = is_numeric($a['room_number']) ? intval($a['room_number']) : 0;
        $b_num = is_numeric($b['room_number']) ? intval($b['room_number']) : 0;
        return $a_num - $b_num;
    });
    $floors_data['all'] = $all_rooms_unified;

// Stats: Monthly Earnings for Chart
$current_year = date('Y');
$monthly_earnings = array_fill(0, 12, 0); // 0-11 index
$chart_q = mysqli_query($conn, "SELECT MONTH(payment_date) as m, SUM(amount) as total FROM payments WHERE payment_status='Paid' AND YEAR(payment_date)='$current_year' GROUP BY m");
while($row = mysqli_fetch_assoc($chart_q)){
    $monthly_earnings[$row['m'] - 1] = (float)$row['total'];
}

// Stats: Monthly Bookings (Unique Users) for Chart
$monthly_bookings = array_fill(0, 12, 0);
$book_q = mysqli_query($conn, "SELECT MONTH(created_at) as m, COUNT(DISTINCT user_id) as total FROM reservations WHERE YEAR(created_at)='$current_year' GROUP BY m");
while($row = mysqli_fetch_assoc($book_q)){
    $monthly_bookings[$row['m'] - 1] = (int)$row['total'];
}

// Stats: Recent Activity
$logs_q = mysqli_query($conn, "SELECT l.*, CONCAT(u.last_name, ', ', u.first_name) as full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Woke Coliving INC</title>
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php $theme = get_theme_colors($conn); ?>
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
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
            <a href="admin_dashboard.php" class="sidebar-link active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? 'show' : '' ?>" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Residents</span>
                </a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                    <?php if($pending_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_count ?></span><?php endif; ?>
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
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? 'show' : '' ?>" id="financeSubmenu">
                <?php if($is_super): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? 'show' : '' ?>" id="operationsSubmenu">
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
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'show' : '' ?>" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if($is_super): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                </a>
                <h2 class="mb-0 fw-bold text-success">Admin Dashboard</h2>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Room deleted permanently.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <?php if($is_super): ?>
        <div class="col-md-3">
            <a href="profit_report.php" class="text-decoration-none">
                <div class="card stat-card h-100 p-3">
                    <div class="stat-label">Total Earnings</div>
                    <div class="stat-value">₱<?= number_format($total_earnings, 2) ?></div>
                    <i class="fas fa-coins stat-icon text-warning"></i>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <div class="<?= $is_super ? 'col-md-3' : 'col-md-4' ?>">
            <a href="admin_rooms.php" class="text-decoration-none">
                <div class="card stat-card h-100 p-3">
                    <div class="stat-label">Occupancy Rate</div>
                    <div class="stat-value"><?= $occupancy_rate ?>% <small class="text-muted fs-6">(<?= $total_occupied ?>/<?= $total_capacity ?>)</small></div>
                    <i class="fas fa-chart-pie stat-icon text-primary"></i>
                </div>
            </a>
        </div>
        <div class="<?= $is_super ? 'col-md-3' : 'col-md-4' ?>">
            <a href="booking_management.php?status=Pending" class="text-decoration-none">
                <div class="card stat-card h-100 p-3">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value"><?= $pending_count ?></div>
                    <i class="fas fa-clock stat-icon text-info"></i>
                </div>
            </a>
        </div>
        <div class="<?= $is_super ? 'col-md-3' : 'col-md-4' ?>">
            <a href="booking_management.php?status=Approved" class="text-decoration-none">
                <div class="card stat-card h-100 p-3">
                    <div class="stat-label">Confirmed Guests</div>
                    <div class="stat-value"><?= $approved_count ?></div>
                    <i class="fas fa-users stat-icon text-success"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="add_reservation.php" class="btn btn-outline-success rounded-pill px-4 fw-bold"><i class="fas fa-plus-circle me-2"></i>New Booking</a>
                    <a href="add_room.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold"><i class="fas fa-bed me-2"></i>Add Room</a>
                    <a href="longterm_billing.php" class="btn btn-outline-warning text-dark rounded-pill px-4 fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Utility Billing</a>
                    <a href="backup.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold"><i class="fas fa-database me-2"></i>Backup Data</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Actionable Insights Row -->
    <div class="row g-4 mb-4">
        <!-- Pending Tasks List -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-tasks me-2 text-warning"></i> Pending Tasks Overview
                </div>
                <div class="list-group list-group-flush">
                    <a href="booking_management.php?status=Pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-check me-2 text-muted"></i> Reservation Requests</span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $pending_count ?></span>
                    </a>
                    <a href="admin_maintenance.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-tools me-2 text-muted"></i> Maintenance Requests</span>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    </a>
                    <a href="admin_housekeeping.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-broom me-2 text-muted"></i> Housekeeping Requests</span>
                        <span class="badge bg-info text-dark rounded-pill"><?= $pending_house ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Detailed Occupancy Breakdown -->
        <div class="col-md-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-building me-2 text-success"></i> 
                        <span class="me-2">Occupancy</span>
                        <select id="floorFilter" class="form-select form-select-sm w-auto py-0 me-2" style="font-size: 0.8rem;" onchange="filterOccupancy()">
                            <option value="all" selected>All Floors</option>
                            <option value="2">2nd Floor</option>
                            <option value="3">3rd Floor</option>
                            <option value="4">4th Floor</option>
                            <option value="5">5th Floor</option>
                            <option value="6">6th Floor</option>
                            <option value="7">7th Floor</option>
                        </select>
                        <select id="occupancyFilter" class="form-select form-select-sm w-auto py-0 me-2" style="font-size: 0.8rem;" onchange="filterOccupancy()">
                            <option value="all">All</option>
                            <option value="available">Available</option>
                            <option value="full">Full</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <select id="roomTypeFilter" class="form-select form-select-sm w-auto py-0 me-2" style="font-size: 0.8rem;" onchange="filterOccupancy()">
                            <option value="all">All Types</option>
                            <option value="Single">Single</option>
                            <option value="4-Bed">4-Bed</option>
                            <option value="6-Bed">6-Bed</option>
                        </select>
                        <input type="text" id="roomSearch" class="form-control form-control-sm w-auto py-0" style="font-size: 0.8rem; width: 120px;" placeholder="Search..." onkeyup="filterOccupancy()">
                    </div>
                    <span class="badge bg-success"><?= $total_occupied ?> / <?= $total_capacity ?> Beds</span>
                </div>
                <div class="card-body p-3" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach($floors_data as $floor => $rooms): ?>
                    <?php if($floor === 'all') continue; // Skip 'all' key, JavaScript handles combining rooms for "All Floors" view ?>
                    <div class="floor-group" data-floor="<?= $floor ?>">
                        <h6 class="fw-bold text-muted mb-2 small text-uppercase border-bottom pb-1"><?= $floor == 2 ? '2nd' : ($floor == 3 ? '3rd' : $floor.'th') ?> Floor</h6>
                        <div class="row g-2 mb-3">
                            <?php foreach($rooms as $room): ?>
                            <?php 
                                $status_tag = 'available';
                                if($room['status'] == 'Maintenance') $status_tag = 'maintenance';
                                elseif($room['occupied'] >= $room['total']) $status_tag = 'full';
                            ?>
                            <div class="col-lg-4 col-md-6 room-item" data-status="<?= $status_tag ?>" data-room-type="<?= $room['type'] ?>">
                                <div class="room-box">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small"><?= $room['display_name'] ?></span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.6rem;"><?= $room['type'] ?></span>
                                    </div>
                                    <?php if($room['status'] == 'Maintenance'): ?>
                                        <div class="text-center text-danger small fw-bold"><i class="fas fa-tools"></i> Maintenance</div>
                                    <?php else: ?>
                                        <?php 
                                            $percent = ($room['total'] > 0 ? ($room['occupied']/$room['total'])*100 : 0);
                                            $bar_color = 'bg-success';
                                            if($percent >= 100) $bar_color = 'bg-danger';
                                            elseif($percent >= 75) $bar_color = 'bg-warning';
                                        ?>
                                        <div class="progress mb-2" style="height: 4px;" title="<?= round($percent) ?>% Occupied">
                                            <div class="progress-bar <?= $bar_color ?>" style="width: <?= $percent ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small align-items-center">
                                            <div>
                                            <?php if($room['type'] == 'Single'): ?>
                                                <span class="text-muted">Occupied:</span>
                                                <span class="fw-bold <?= $room['occupied'] > 0 ? 'text-success' : 'text-muted' ?>"><?= $room['occupied'] ?>/<?= $room['total'] ?></span>
                                            <?php else: ?>
                                                <span class="bed-badge bg-lower" title="Lower Bunks">L: <b><?= $room['avail_lower'] ?></b> left</span>
                                                <span class="bed-badge bg-upper" title="Upper Bunks">U: <b><?= $room['avail_upper'] ?></b> left</span>
                                            <?php endif; ?>
                                            </div>
                                            <div>
                                                <?php if($room['occupied'] < $room['total']): ?>
                                                    <a href="add_reservation.php?room_type=<?= urlencode($room['type']) ?>" class="btn btn-sm btn-outline-primary py-0 px-1 ms-1" style="font-size: 0.7rem;" title="Quick Book"><i class="fas fa-plus"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart & Activity Row -->
    <div class="row g-4 mb-4">
        <!-- Monthly Earnings Chart -->
        <div class="col-md-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="fas fa-chart-line me-2 text-primary"></i> <span id="chartTitle"><?= $is_super ? 'Monthly Earnings' : 'New Bookings' ?></span> (<?= $current_year ?>)</span>
                    <?php if($is_super): ?>
                    <select id="chartFilter" class="form-select form-select-sm w-auto" onchange="updateChart()">
                        <option value="earnings">Earnings</option>
                        <option value="bookings">New Bookings</option>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-history me-2 text-secondary"></i> Recent Activity
                </div>
                <div class="list-group list-group-flush" id="activityFeed">
                    <?php if(mysqli_num_rows($logs_q) > 0): ?>
                        <?php while($log = mysqli_fetch_assoc($logs_q)): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <small class="fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($log['action']) ?></small>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= date('M d, H:i', strtotime($log['created_at'])) ?></small>
                            </div>
                            <p class="mb-1 small text-muted text-truncate"><?= htmlspecialchars($log['details']) ?></p>
                            <small class="text-primary" style="font-size: 0.7rem;"><i class="fas fa-user-circle me-1"></i> <?= $log['full_name'] ? htmlspecialchars($log['full_name']) : 'System' ?></small>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted small py-4">No recent activity.</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center border-0">
                    <a href="system_logs.php" class="small text-decoration-none fw-bold">View All Logs &rarr;</a>
                </div>
            </div>
        </div>
    </div>

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
                            <th>Guest</th>
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
                            $dnr_alert = $exp['do_not_renew'] ? '<span class="badge bg-danger ms-2"><i class="fas fa-ban me-1"></i>Do Not Renew</span>' : '';
                            // Make room display name consistent with admin_rooms.php
                            $room_display = $exp['room_name'];
                            if (!empty($exp['room_number'])) {
                                $room_display = "Room " . $exp['room_number'];
                            } elseif (is_numeric($exp['room_name'])) {
                                $room_display = "Room " . $exp['room_name'];
                            }
                        ?>
                        <tr>
                            <td class="fw-bold">
                                <?= htmlspecialchars($exp['full_name']) ?>
                                <?= $dnr_alert ?>
                            </td>
                            <td><?= $exp['end_date'] ?></td>
                            <td class="<?= $text_class ?>"><?= $status_text ?></td>
                            <td class="text-end">
                                <button onclick="renewContract(<?= $exp['reservation_id'] ?>, <?= $exp['do_not_renew'] ?>)" class="btn btn-sm btn-success me-1"><i class="fas fa-sync-alt me-1"></i> Renew</button>
                                <a href="booking_management.php?action=terminate&id=<?= $exp['reservation_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'End this contract? This will mark it as Completed.')"><i class="fas fa-file-contract me-1"></i> End Contract</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart.js Initialization
const earningsData = <?= json_encode($monthly_earnings) ?>;
const bookingsData = <?= json_encode($monthly_bookings) ?>;

const ctx = document.getElementById('earningsChart').getContext('2d');
const earningsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: '<?= $is_super ? 'Earnings (₱)' : 'Bookings (Count)' ?>',
            data: <?= $is_super ? 'earningsData' : 'bookingsData' ?>,
            borderColor: '<?= $is_super ? $theme['primary'] : $theme['accent'] ?>',
            backgroundColor: '<?= $is_super ? 'rgba(46, 125, 50, 0.1)' : 'rgba(251, 192, 45, 0.1)' ?>',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
            x: { grid: { display: false } }
        }
    }
});

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'user_deleted'): ?>
Swal.fire({
    title: 'Deleted!',
    text: 'User account has been permanently deleted.',
    icon: 'success'
});
<?php endif; ?>

function updateChart() {
    <?php if(!$is_super): ?>return;<?php endif; ?>
    const filter = document.getElementById('chartFilter').value;
    const title = document.getElementById('chartTitle');
    
    if(filter === 'earnings') {
        title.innerText = 'Monthly Earnings';
        earningsChart.data.datasets[0].label = 'Earnings (₱)';
        earningsChart.data.datasets[0].data = earningsData;
        earningsChart.data.datasets[0].borderColor = '<?= $theme['primary'] ?>';
        earningsChart.data.datasets[0].backgroundColor = 'rgba(46, 125, 50, 0.1)';
    } else {
        title.innerText = 'New Bookings';
        earningsChart.data.datasets[0].label = 'Bookings (Count)';
        earningsChart.data.datasets[0].data = bookingsData;
        earningsChart.data.datasets[0].borderColor = '<?= $theme['accent'] ?>';
        earningsChart.data.datasets[0].backgroundColor = 'rgba(251, 192, 45, 0.1)';
    }
    earningsChart.update();
}

function confirmAction(e, url, msg) {
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
        window.location.href = `booking_management.php?action=renew&id=${id}&months=${formValues.months}&desc=${descEncoded}&redirect=dashboard`;
    }
}

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

// Auto-refresh Activity Feed
function refreshActivity() {
    fetch('admin_dashboard.php?fetch_activity=1')
        .then(response => response.text())
        .then(html => {
            document.getElementById('activityFeed').innerHTML = html;
        });
}
setInterval(refreshActivity, 30000); // 30 seconds

function filterOccupancy() {
    const filter = document.getElementById('occupancyFilter').value;
    const floorFilter = document.getElementById('floorFilter').value;
    const roomTypeFilter = document.getElementById('roomTypeFilter') ? document.getElementById('roomTypeFilter').value : 'all';
    const search = document.getElementById('roomSearch').value.toLowerCase();
    const groups = document.querySelectorAll('.floor-group');
    
    // If "All Floors" is selected, show unified sorted list
    if (floorFilter === 'all') {
        // Hide floor headers and show all rooms sorted by room number
        groups.forEach((group, index) => {
            const items = group.querySelectorAll('.room-item');
            let visibleCount = 0;
            
            // Hide floor header when showing "All Floors"
            const header = group.querySelector('h6');
            if (header) header.style.display = 'none';
            
            items.forEach(item => {
                const status = item.dataset.status;
                const roomType = item.dataset.roomType || '';
                const name = item.querySelector('.fw-bold').innerText.toLowerCase();
                const type = item.querySelector('.badge').innerText.toLowerCase();
                
                const matchesFilter = (filter === 'all') || (filter === status);
                const matchesRoomType = (roomTypeFilter === 'all') || (roomType === roomTypeFilter);
                const matchesSearch = name.includes(search) || type.includes(search);
                
                const show = matchesFilter && matchesRoomType && matchesSearch;
                
                item.style.display = show ? 'block' : 'none';
                if(show) visibleCount++;
            });
            
            group.style.display = visibleCount > 0 ? 'block' : 'none';
        });
        
        // Re-sort all visible room-items by their room name/number
        groups.forEach(group => {
            const container = group.querySelector('.row');
            const items = Array.from(group.querySelectorAll('.room-item'));
            
            // Sort items by room number
            items.sort((a, b) => {
                const aName = a.querySelector('.fw-bold').innerText;
                const bName = b.querySelector('.fw-bold').innerText;
                // Extract numeric part from room name (e.g., "Room 201" -> 201)
                const aNum = parseInt(aName.replace(/\D/g, '')) || 0;
                const bNum = parseInt(bName.replace(/\D/g, '')) || 0;
                return aNum - bNum;
            });
            
            // Re-append items in sorted order
            items.forEach(item => container.appendChild(item));
        });
    } else {
        // Specific floor selected - show grouped by floor
        groups.forEach(group => {
            const groupFloor = group.getAttribute('data-floor');
            const showFloor = (floorFilter === 'all') || (floorFilter === groupFloor);

            const items = group.querySelectorAll('.room-item');
            let visibleCount = 0;
            
            // Show floor header when specific floor is selected
            const header = group.querySelector('h6');
            if (header) header.style.display = showFloor ? 'block' : 'none';
            
            if (!showFloor) {
                group.style.display = 'none';
                return;
            }
            
            items.forEach(item => {
                const status = item.dataset.status;
                const roomType = item.dataset.roomType || '';
                const name = item.querySelector('.fw-bold').innerText.toLowerCase();
                const type = item.querySelector('.badge').innerText.toLowerCase();
                
                const matchesFilter = (filter === 'all') || (filter === status);
                const matchesRoomType = (roomTypeFilter === 'all') || (roomType === roomTypeFilter);
                const matchesSearch = name.includes(search) || type.includes(search);
                
                const show = matchesFilter && matchesRoomType && matchesSearch;
                
                item.style.display = show ? 'block' : 'none';
                if(show) visibleCount++;
            });
            
            group.style.display = visibleCount > 0 ? 'block' : 'none';
        });
    }
}

// Initialize filter on load
document.addEventListener('DOMContentLoaded', function() {
    filterOccupancy();
});

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
