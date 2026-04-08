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
            echo '<div class="list-group-item border-0 border-bottom px-0 py-3">
                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark text-truncate" style="max-width: 180px; font-size: 0.85rem;">'.htmlspecialchars($log['action']).'</span>
                    <small class="text-muted" style="font-size: 0.7rem;"><i class="far fa-clock me-1"></i>'.date('M d, H:i', strtotime($log['created_at'])).'</small>
                </div>
                <p class="mb-1 text-muted text-truncate" style="font-size: 0.8rem;">'.htmlspecialchars($log['details']).'</p>
                <small class="text-primary fw-semibold" style="font-size: 0.7rem;"><i class="fas fa-user-circle me-1"></i> '.($log['full_name'] ? htmlspecialchars($log['full_name']) : 'System').'</small>
            </div>';
        }
    } else {
        echo '<div class="list-group-item text-center text-muted small py-4 border-0">No recent activity.</div>';
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
$pending_count_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM reservations WHERE status IN ('Pending', 'Verifying')");
$pending_count_res = mysqli_fetch_assoc($pending_count_query)['count'] ?? 0;
$pending_count_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['count'] ?? 0;
$pending_count = $pending_count_res + $pending_count_pay;

$approved_count_query = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS count FROM reservations WHERE status='Approved'");
$approved_count = mysqli_fetch_assoc($approved_count_query)['count'] ?? 0;

$total_rooms_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM rooms");
$total_rooms = mysqli_fetch_assoc($total_rooms_query)['count'] ?? 0;

// Stats: Total Users
$user_filter = $_GET['user_filter'] ?? 'all';
$user_where = "role != 'admin' AND role != 'Super Admin' AND is_archived = 0";
if($user_filter == 'active') {
    $user_where .= " AND EXISTS (SELECT 1 FROM reservations WHERE user_id = users.user_id AND status IN ('Approved', 'Pending', 'Verifying'))";
}
$total_users_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users WHERE $user_where");
$total_users = mysqli_fetch_assoc($total_users_query)['count'] ?? 0;

// Stats: Occupancy Rate (Active Beds / Total Capacity)
$today = date('Y-m-d');
// 1. Total Capacity (Beds in non-maintenance rooms)
$cap_q = mysqli_query($conn, "SELECT SUM(total_beds) as total FROM rooms WHERE availability != 'Maintenance' AND is_archived=0");
$total_capacity = mysqli_fetch_assoc($cap_q)['total'] ?? 0;

// 2. Total Occupied (Active Approved and Pending Reservations)
$occ_q = mysqli_query($conn, "SELECT bed_preference, room_id, COUNT(*) as cnt FROM reservations WHERE status IN ('Approved', 'Pending') AND start_date <= '$today' AND end_date > '$today' GROUP BY room_id, bed_preference");

$total_occupied = 0;
while ($row = mysqli_fetch_assoc($occ_q)) {
    if ($row['bed_preference'] == 'Whole Room') {
        $r_id = $row['room_id'];
        $cap_q2 = mysqli_query($conn, "SELECT total_beds FROM rooms WHERE room_id=$r_id");
        $t_beds = mysqli_fetch_assoc($cap_q2)['total_beds'] ?? 1;
        $total_occupied += $t_beds;
    } else {
        $total_occupied += $row['cnt'];
    }
}

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
    
    $floors_q = mysqli_query($conn, "SELECT DISTINCT floor FROM rooms WHERE is_hidden=0 AND is_archived=0 ORDER BY floor ASC");

    while($f = mysqli_fetch_assoc($floors_q)){
        $floor = $f['floor'];
        $rooms_on_floor = [];
        
        $r_q = mysqli_query($conn, "SELECT * FROM rooms WHERE floor = $floor AND is_hidden=0 AND is_archived=0 ORDER BY CAST(room_number AS UNSIGNED) ASC");
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
                $cnt = $ro['cnt'];
                if($ro['bed_preference'] == 'Whole Room') {
                    $total_room_occ += $total_beds;
                    $occ_any += $total_beds;
                } else {
                    $total_room_occ += $cnt;
                    if($ro['bed_preference'] == 'Upper Bunk') $occ_upper += $cnt;
                    elseif($ro['bed_preference'] == 'Lower Bunk') $occ_lower += $cnt;
                    else $occ_any += $cnt;
                }
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
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$available_years = [];
$y_q = mysqli_query($conn, "SELECT DISTINCT YEAR(payment_date) as y FROM payments WHERE payment_status='Paid' AND payment_date IS NOT NULL UNION SELECT DISTINCT YEAR(created_at) as y FROM reservations WHERE created_at IS NOT NULL");
if ($y_q) {
    while($y_row = mysqli_fetch_assoc($y_q)){
        if($y_row['y']) $available_years[] = $y_row['y'];
    }
}
if(!in_array(date('Y'), $available_years)) $available_years[] = date('Y');
if(!in_array(2026, $available_years)) $available_years[] = 2026;
rsort($available_years);
$available_years = array_unique($available_years);

$monthly_earnings = array_fill(0, 12, 0); // 0-11 index
$chart_q = mysqli_query($conn, "SELECT MONTH(payment_date) as m, SUM(amount) as total FROM payments WHERE payment_status='Paid' AND YEAR(payment_date)='$current_year' GROUP BY m");
if($chart_q) {
    while($row = mysqli_fetch_assoc($chart_q)){
        if(!empty($row['m'])) $monthly_earnings[(int)$row['m'] - 1] = (float)$row['total'];
    }
}

// Stats: Monthly Bookings (Unique Users) for Chart
$monthly_bookings = array_fill(0, 12, 0);
$book_q = mysqli_query($conn, "SELECT MONTH(created_at) as m, COUNT(DISTINCT user_id) as total FROM reservations WHERE YEAR(created_at)='$current_year' GROUP BY m");
if($book_q) {
    while($row = mysqli_fetch_assoc($book_q)){
        if(!empty($row['m'])) $monthly_bookings[(int)$row['m'] - 1] = (int)$row['total'];
    }
}

// Stats: Recent Activity
$logs_q = mysqli_query($conn, "SELECT l.*, CONCAT(u.last_name, ', ', u.first_name) as full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 5");

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Minimalist Dashboard Adjustments */
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; }
        .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; overflow: hidden; position: relative; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .stat-icon { position: absolute; right: -10px; bottom: -15px; font-size: 5rem; opacity: 0.05; }
        .stat-card-accent-warning { border-left: 4px solid var(--bs-warning); }
        .stat-card-accent-primary { border-left: 4px solid var(--bs-primary); }
        .stat-card-accent-info { border-left: 4px solid var(--bs-info); }
        .stat-card-accent-success { border-left: 4px solid var(--bs-success); }
        
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); }
        .card-header-custom { background-color: transparent; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 1.25rem 1.5rem; }
        
        .task-item { transition: background-color 0.2s; border: none; border-bottom: 1px solid rgba(0,0,0,0.03); padding: 1rem 1.5rem; }
        .task-item:hover { background-color: #f8f9fa; }
        .task-item:last-child { border-bottom: none; }
        
        .room-box { border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; transition: border-color 0.2s; }
        .room-box:hover { border-color: rgba(0,0,0,0.2); }
        .progress-slim { height: 6px; border-radius: 10px; background-color: rgba(0,0,0,0.05); }

        /* Floating Bottom Right Navbar Icons on Scroll */
        .navbar-right.fixed-bottom-right {
            position: fixed; bottom: 30px; right: 30px; background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); padding: 8px 20px; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 1050; animation: slideUp 0.3s ease-out;
            border: 1px solid rgba(0,0,0,0.1); display: flex; gap: 15px; align-items: center;
        }
        .navbar-right.fixed-bottom-right .dropdown-menu { bottom: 100% !important; top: auto !important; margin-bottom: 15px !important; transform-origin: bottom right; }
        .navbar-right.fixed-bottom-right .profile-name { display: none !important; }
        .scroll-top-btn { display: none; }
        .navbar-right.fixed-bottom-right .scroll-top-btn { display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; background: #e8f5e9; color: #34B875; border-radius: 50%; transition: all 0.2s; }
        @keyframes slideUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .card-header-custom[aria-expanded="true"] .collapse-icon { transform: rotate(-180deg); }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h2 class="h3 fw-bold mb-1">Dashboard Overview</h2>
                    <p class="text-muted small mb-0">Welcome back, here's what's happening today.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="add_reservation.php" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm"><i class="fas fa-plus me-1"></i>New Booking</a>
                    <a href="add_room.php" class="btn btn-light border btn-sm px-3 fw-semibold shadow-sm text-dark"><i class="fas fa-bed text-primary me-1"></i>Add Room</a>
                    <a href="longterm_billing.php" class="btn btn-light border btn-sm px-3 fw-semibold shadow-sm text-dark"><i class="fas fa-file-invoice-dollar text-warning me-1"></i>Billing</a>
                    <a href="backup.php" class="btn btn-light border btn-sm px-3 fw-semibold shadow-sm text-dark" title="Backup System"><i class="fas fa-database text-secondary"></i>Backup</a>
                </div>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Room deleted permanently.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php if($is_super): ?>
                <div class="col">
                    <a href="profit_report.php" class="text-decoration-none">
                        <div class="card stat-card stat-card-accent-warning h-100 p-3 shadow-sm bg-white">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Total Earnings</div>
                            <div class="h4 fw-bold text-dark mb-0">₱<?= number_format($total_earnings, 2) ?></div>
                            <i class="fas fa-coins stat-icon"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <div class="col">
                    <div class="card stat-card stat-card-accent-primary h-100 p-3 shadow-sm bg-white" style="cursor: default;">
                        <div class="text-muted small fw-semibold text-uppercase mb-1 d-flex justify-content-between align-items-center">
                            Total Users
                            <select class="border-0 bg-transparent text-muted fw-bold position-relative z-3" style="width: auto; outline: none; cursor: pointer; font-size: 0.75rem; padding: 0;" onchange="window.location.href='?user_filter='+this.value">
                                <option value="all" <?= $user_filter == 'all' ? 'selected' : '' ?>>All</option>
                                <option value="active" <?= $user_filter == 'active' ? 'selected' : '' ?>>Active</option>
                            </select>
                        </div>
                        <div class="h4 fw-bold text-dark mb-0"><?= $total_users ?></div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
                <div class="col">
                    <a href="admin_rooms.php" class="text-decoration-none">
                        <div class="card stat-card stat-card-accent-primary h-100 p-3 shadow-sm bg-white">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Occupancy Rate</div>
                            <div class="h4 fw-bold text-dark mb-0"><?= $occupancy_rate ?>% <span class="text-muted fs-6 fw-normal ms-1">(<?= $total_occupied ?>/<?= $total_capacity ?>)</span></div>
                            <i class="fas fa-chart-pie stat-icon"></i>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="booking_management.php?status=Pending" class="text-decoration-none">
                        <div class="card stat-card stat-card-accent-info h-100 p-3 shadow-sm bg-white">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Pending Requests</div>
                            <div class="h4 fw-bold text-dark mb-0"><?= $pending_count ?></div>
                            <i class="fas fa-clock stat-icon"></i>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="booking_management.php?status=Approved" class="text-decoration-none">
                        <div class="card stat-card stat-card-accent-success h-100 p-3 shadow-sm bg-white">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Confirmed Guests</div>
                            <div class="h4 fw-bold text-dark mb-0"><?= $approved_count ?></div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card card-custom h-100">
                        <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-chart-line me-2 text-primary"></i> 
                                <span id="chartTitle"><?= $is_super ? 'Monthly Earnings' : 'New Bookings' ?></span>
                                <select class="form-select form-select-sm border-0 fw-bold p-0 ms-1 shadow-none d-inline-block text-muted" style="width: auto; cursor: pointer; background-color: transparent !important;" onchange="window.location.href='?year='+this.value">
                                    <?php foreach($available_years as $yr): ?>
                                        <option value="<?= $yr ?>" <?= $yr == $current_year ? 'selected' : '' ?> class="text-dark">(<?= $yr ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </h6>
                            <?php if($is_super): ?>
                            <select id="chartFilter" class="form-select form-select-sm w-auto bg-light border-0 fw-semibold text-dark shadow-none" onchange="updateChart()">
                                <option value="earnings">Earnings</option>
                                <option value="bookings">New Bookings</option>
                            </select>
                            <?php endif; ?>
                        </div>
                        <div class="card-body" style="position: relative; height: 320px; padding: 1.5rem;">
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-tasks me-2 text-warning"></i> Action Required</h6>
                        </div>
                        <div class="list-group list-group-flush pt-1">
                            <a href="booking_management.php?status=Pending" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center task-item text-dark">
                                <div><i class="fas fa-calendar-check me-3 text-muted opacity-75"></i> <span class="fw-medium">Reservations</span></div>
                                <?php if($pending_count > 0): ?><span class="badge bg-warning text-dark rounded-pill px-2 py-1"><?= $pending_count ?></span><?php else: ?><span class="text-muted small">0</span><?php endif; ?>
                            </a>
                            <a href="admin_maintenance.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center task-item text-dark">
                                <div><i class="fas fa-tools me-3 text-muted opacity-75"></i> <span class="fw-medium">Maintenance</span></div>
                                <?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill px-2 py-1"><?= $pending_maint ?></span><?php else: ?><span class="text-muted small">0</span><?php endif; ?>
                            </a>
                            <a href="admin_housekeeping.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center task-item text-dark">
                                <div><i class="fas fa-broom me-3 text-muted opacity-75"></i> <span class="fw-medium">Housekeeping</span></div>
                                <?php if($pending_house > 0): ?><span class="badge bg-info text-dark rounded-pill px-2 py-1"><?= $pending_house ?></span><?php else: ?><span class="text-muted small">0</span><?php endif; ?>
                            </a>
                             <a href="admin_utilities.php#deletion" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center task-item text-dark border-bottom-0">
                                <div><i class="fas fa-user-minus me-3 text-muted opacity-75"></i> <span class="fw-medium">Account Deletions</span></div>
                                <?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill px-2 py-1"><?= $del_req_count ?></span><?php else: ?><span class="text-muted small">0</span><?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#roomOccupancyCollapse" aria-expanded="false" aria-controls="roomOccupancyCollapse" style="cursor: pointer;" title="Click to expand/collapse">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-building me-2 text-success"></i> Room Occupancy</h6>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-2 fw-bold" style="font-size: 0.8rem;"><?= $total_occupied ?> / <?= $total_capacity ?> Beds Filled</span>
                        <i class="fas fa-chevron-down text-muted collapse-icon" style="transition: transform 0.3s ease;"></i>
                    </div>
                </div>
                <div class="collapse" id="roomOccupancyCollapse">
                    <div class="card-body bg-light border-bottom p-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-auto col-6">
                            <select id="floorFilter" class="form-select form-select-sm border-0 shadow-sm fw-medium text-dark" onchange="filterOccupancy()">
                                <option value="all" selected>All Floors</option>
                                <option value="2">2nd Floor</option>
                                <option value="3">3rd Floor</option>
                                <option value="4">4th Floor</option>
                                <option value="5">5th Floor</option>
                                <option value="6">6th Floor</option>
                                <option value="7">7th Floor</option>
                            </select>
                        </div>
                        <div class="col-md-auto col-6">
                            <select id="occupancyFilter" class="form-select form-select-sm border-0 shadow-sm fw-medium text-dark" onchange="filterOccupancy()">
                                <option value="all">All Status</option>
                                <option value="available">Available Rooms</option>
                                <option value="full">Fully Booked</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-auto col-6">
                            <select id="roomTypeFilter" class="form-select form-select-sm border-0 shadow-sm fw-medium text-dark" onchange="filterOccupancy()">
                                <option value="all">All Room Types</option>
                                <option value="Single">Single Rooms</option>
                                <option value="4-Bed">4-Bed Dorms</option>
                                <option value="6-Bed">6-Bed Dorms</option>
                            </select>
                        </div>
                        <div class="col-md col-6 ms-md-auto">
                            <div class="input-group input-group-sm shadow-sm rounded">
                                <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-search"></i></span>
                                <input type="text" id="roomSearch" class="form-control border-0 shadow-none ps-0 fw-medium" placeholder="Search room..." onkeyup="filterOccupancy()">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach($floors_data as $floor => $rooms): ?>
                    <?php if($floor === 'all') continue; ?>
                    <div class="floor-group mb-4" data-floor="<?= $floor ?>">
                        <h6 class="fw-bold text-success mb-3 small text-uppercase pb-1 opacity-75 letter-spacing-1">
                            <i class="fas fa-layer-group me-2"></i><?= $floor == 2 ? '2nd' : ($floor == 3 ? '3rd' : $floor.'th') ?> Floor
                        </h6>
                        <div class="row g-3 mb-4">
                            <?php foreach($rooms as $room): ?>
                            <?php 
                                $status_tag = 'available';
                                if($room['status'] == 'Maintenance') $status_tag = 'maintenance';
                                elseif($room['occupied'] >= $room['total']) $status_tag = 'full';
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 room-item" data-status="<?= $status_tag ?>" data-room-type="<?= $room['type'] ?>">
                                <div class="room-box bg-white p-3 h-100 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-dark"><?= $room['display_name'] ?></span>
                                        <span class="badge bg-light text-muted border px-2" style="font-size: 0.65rem; font-weight: 600;"><?= $room['type'] ?></span>
                                    </div>
                                    <?php if($room['status'] == 'Maintenance'): ?>
                                        <div class="text-center text-danger small fw-bold py-3 mt-auto bg-danger bg-opacity-10 rounded"><i class="fas fa-tools mb-1 d-block fs-5"></i> Under Maintenance</div>
                                    <?php else: ?>
                                        <?php 
                                            $percent = ($room['total'] > 0 ? ($room['occupied']/$room['total'])*100 : 0);
                                            $bar_color = 'bg-success';
                                            if($percent >= 100) $bar_color = 'bg-danger';
                                            elseif($percent >= 75) $bar_color = 'bg-warning';
                                        ?>
                                        <div class="progress progress-slim mb-3" title="<?= round($percent) ?>% Occupied">
                                            <div class="progress-bar <?= $bar_color ?>" style="width: <?= $percent ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small align-items-end mt-auto">
                                            <div class="text-muted">
                                            <?php if($room['type'] == 'Single'): ?>
                                                Occupied: <span class="fw-bold <?= $room['occupied'] > 0 ? 'text-dark' : '' ?>"><?= $room['occupied'] ?>/<?= $room['total'] ?></span>
                                            <?php else: ?>
                                                <div class="mb-1" title="Lower Bunks">L: <strong class="text-dark"><?= $room['avail_lower'] ?></strong> left</div>
                                                <div title="Upper Bunks">U: <strong class="text-dark"><?= $room['avail_upper'] ?></strong> left</div>
                                            <?php endif; ?>
                                            </div>
                                            <div>
                                                <?php if($room['occupied'] < $room['total']): ?>
                                                    <a href="add_reservation.php?room_type=<?= urlencode($room['type']) ?>" class="btn btn-sm btn-light border text-primary px-2 shadow-none" style="font-size: 0.75rem; font-weight: 600;" title="Quick Book"><i class="fas fa-plus me-1"></i>Book</a>
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

            <div class="row g-4 mb-4">
                <?php if(mysqli_num_rows($expiring_query) > 0): ?>
                <div class="col-lg-8">
                    <div class="card card-custom border-danger border-opacity-50 h-100">
                        <div class="card-header bg-danger border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-white mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Action Required: Contracts</h6>
                            <span class="badge bg-white text-danger border border-white rounded-pill"><?= mysqli_num_rows($expiring_query) ?> Due</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="bg-light text-muted small fw-semibold text-uppercase letter-spacing-1">
                                        <tr>
                                            <th class="ps-4">Guest</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <?php while($exp = mysqli_fetch_assoc($expiring_query)): 
                                            $days_left = (strtotime($exp['end_date']) - time()) / (60 * 60 * 24);
                                            $days_left = ceil($days_left);
                                            $status_text = $days_left < 0 ? "Expired " . abs($days_left) . "d ago" : ($days_left == 0 ? "Expires Today" : "$days_left days left");
                                            $text_class = $days_left <= 0 ? "text-danger fw-bold" : "text-warning fw-bold text-dark";
                                            $dnr_alert = $exp['do_not_renew'] ? '<span class="badge bg-danger ms-2" style="font-size:0.65rem;"><i class="fas fa-ban me-1"></i>DNR</span>' : '';
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($exp['full_name']) ?> <?= $dnr_alert ?></div>
                                                <div class="small text-muted">Room <?= htmlspecialchars($exp['room_number'] ?? $exp['room_name']) ?></div>
                                            </td>
                                            <td class="text-muted small"><?= date('M d, Y', strtotime($exp['end_date'])) ?></td>
                                            <td class="<?= $text_class ?> small"><?= $status_text ?></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm shadow-sm">
                                                    <button onclick="renewContract(<?= $exp['reservation_id'] ?>, <?= (int)$exp['do_not_renew'] ?>)" class="btn btn-outline-success fw-semibold bg-white"><i class="fas fa-sync-alt me-1"></i> Renew</button>
                                                    <a href="booking_management.php?action=terminate&id=<?= $exp['reservation_id'] ?>" class="btn btn-outline-danger fw-semibold bg-white" onclick="confirmAction(event, this.href, 'End this contract? This will mark it as Completed.')"><i class="fas fa-check me-1"></i> End</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?= mysqli_num_rows($expiring_query) > 0 ? 'col-lg-4' : 'col-12' ?>">
                    <div class="card card-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history me-2 text-secondary"></i> System Logs</h6>
                        </div>
                        <div class="list-group list-group-flush p-3 pt-0" id="activityFeed">
                            <?php if(mysqli_num_rows($logs_q) > 0): ?>
                                <?php mysqli_data_seek($logs_q, 0); // Reset pointer since we used it for AJAX block above ?>
                                <?php while($log = mysqli_fetch_assoc($logs_q)): ?>
                                <div class="list-group-item border-0 border-bottom px-0 py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark text-truncate" style="max-width: 180px; font-size: 0.85rem;"><?= htmlspecialchars($log['action']) ?></span>
                                        <small class="text-muted" style="font-size: 0.7rem;"><i class="far fa-clock me-1"></i><?= date('M d, H:i', strtotime($log['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-1 text-muted text-truncate" style="font-size: 0.8rem;"><?= htmlspecialchars($log['details']) ?></p>
                                    <small class="text-primary fw-semibold" style="font-size: 0.7rem;"><i class="fas fa-user-circle me-1"></i> <?= $log['full_name'] ? htmlspecialchars($log['full_name']) : 'System' ?></small>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted small py-4">No recent activity.</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent text-center border-0 pb-3 pt-0">
                            <a href="system_logs.php" class="btn btn-light btn-sm w-100 fw-semibold text-muted shadow-sm">View All Logs &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
const earningsData = <?= json_encode($monthly_earnings) ?>;
const bookingsData = <?= json_encode($monthly_bookings) ?>;

const ctx = document.getElementById('earningsChart').getContext('2d');
const earningsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: <?= json_encode($is_super ? 'Earnings (₱)' : 'Bookings (Count)') ?>,
            data: <?= $is_super ? 'earningsData' : 'bookingsData' ?>,
            borderColor: <?= json_encode($is_super ? $theme['primary'] : $theme['accent']) ?>,
            backgroundColor: <?= json_encode($is_super ? 'rgba(46, 125, 50, 0.1)' : 'rgba(251, 192, 45, 0.1)') ?>,
            borderWidth: 3,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: <?= json_encode($is_super ? $theme['primary'] : $theme['accent']) ?>,
            pointBorderWidth: 2,
            pointRadius: 4,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 10,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { borderDash: [4, 4], color: 'rgba(0,0,0,0.05)', drawBorder: false },
                ticks: { color: '#6c757d', font: {family: 'Poppins'} }
            },
            x: { 
                grid: { display: false, drawBorder: false },
                ticks: { color: '#6c757d', font: {family: 'Poppins'} }
            }
        },
        interaction: { intersect: false, mode: 'index' }
    }
});

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'user_deleted'): ?>
Swal.fire({
    title: 'Deleted!',
    text: 'User account has been permanently deleted.',
    icon: 'success',
    confirmButtonColor: '<?= $theme['primary'] ?>'
});
<?php endif; ?>

function updateChart() {
    <?php if(!$is_super): ?>return;<?php endif; ?>
    const filter = document.getElementById('chartFilter').value;
    const title = document.getElementById('chartTitle');
    
    if(filter === 'earnings') {
        title.innerText = 'Monthly Earnings';
        earningsChart.data.datasets[0].label = 'Earnings (\u20B1)';
        earningsChart.data.datasets[0].data = earningsData;
        earningsChart.data.datasets[0].borderColor = <?= json_encode($theme['primary']) ?>;
        earningsChart.data.datasets[0].pointBorderColor = <?= json_encode($theme['primary']) ?>;
        earningsChart.data.datasets[0].backgroundColor = 'rgba(46, 125, 50, 0.1)';
    } else {
        title.innerText = 'New Bookings';
        earningsChart.data.datasets[0].label = 'Bookings (Count)';
        earningsChart.data.datasets[0].data = bookingsData;
        earningsChart.data.datasets[0].borderColor = <?= json_encode($theme['accent']) ?>;
        earningsChart.data.datasets[0].pointBorderColor = <?= json_encode($theme['accent']) ?>;
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
        confirmButtonColor: '<?= $theme['primary'] ?>',
        cancelButtonColor: '#6c757d',
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
            cancelButtonColor: '#6c757d',
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
        confirmButtonColor: '<?= $theme['primary'] ?>',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Renew Contract',
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

// Auto-refresh Activity Feed
function refreshActivity() {
    fetch('admin_dashboard.php?fetch_activity=1')
        .then(response => response.text())
        .then(html => {
            document.getElementById('activityFeed').innerHTML = html;
        });
}
setInterval(refreshActivity, 30000); // 30 seconds

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

function filterOccupancy() {
    const filter = document.getElementById('occupancyFilter').value;
    const floorFilter = document.getElementById('floorFilter').value;
    const roomTypeFilter = document.getElementById('roomTypeFilter') ? document.getElementById('roomTypeFilter').value : 'all';
    const search = document.getElementById('roomSearch').value.toLowerCase();
    const groups = document.querySelectorAll('.floor-group');
    
    // If "All Floors" is selected, show unified sorted list
    if (floorFilter === 'all') {
        groups.forEach((group, index) => {
            const items = group.querySelectorAll('.room-item');
            let visibleCount = 0;
            
            // Show floor header when showing "All Floors" so it acts as a visual divider
            const header = group.querySelector('h6');
            if (header) header.style.display = 'block';
            
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

// Scroll Listener for Floating Navbar Icons
window.addEventListener('scroll', function() {
    const navbarRight = document.querySelector('.navbar-right');
    if (navbarRight) {
        if (window.scrollY > 150) {
            navbarRight.classList.add('fixed-bottom-right');
        } else {
            navbarRight.classList.remove('fixed-bottom-right');
        }
    }
});
</script>
</body>
</html>