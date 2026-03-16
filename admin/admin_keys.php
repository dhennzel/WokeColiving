<?php
session_start();
include("../db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Release Key
if (isset($_POST['release_key'])) {
    $key_id = (int)$_POST['key_id'];
    $user_id = (int)$_POST['user_id'];
    
    $chk = mysqli_query($conn, "SELECT status FROM `keys` WHERE id=$key_id");
    $k = mysqli_fetch_assoc($chk);
    
    if ($k['status'] == 'Available') {
        mysqli_query($conn, "INSERT INTO key_transactions (key_id, user_id) VALUES ($key_id, $user_id)");
        mysqli_query($conn, "UPDATE `keys` SET status='Released' WHERE id=$key_id");
        
        send_notification($conn, $user_id, "🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: $key_id). Please keep it safe.", "Key System");
        log_activity($conn, $user_id, "Key Released", "Key ID $key_id released to user by $admin_username");
        trigger_update($conn);
        header("Location: admin_keys.php?msg=released");
        exit;
    }
}

// Handle Return Key
if (isset($_GET['action']) && $_GET['action'] == 'return' && isset($_GET['id'])) {
    $trans_id = (int)$_GET['id'];
    
    $t_q = mysqli_query($conn, "SELECT key_id, user_id FROM key_transactions WHERE id=$trans_id");
    if ($t = mysqli_fetch_assoc($t_q)) {
        $key_id = $t['key_id'];
        mysqli_query($conn, "UPDATE key_transactions SET status='Returned', returned_at=NOW() WHERE id=$trans_id");
        mysqli_query($conn, "UPDATE `keys` SET status='Available' WHERE id=$key_id");
        
        send_notification($conn, $t['user_id'], "🔑 <strong>Key Returned</strong><br>Key (ID: $key_id) has been marked as returned.", "Key System");
        log_activity($conn, $t['user_id'], "Key Returned", "Key ID $key_id marked as returned by $admin_username");
        trigger_update($conn);
        header("Location: admin_keys.php?msg=returned");
        exit;
    }
}

// Get show_hidden parameter
$show_hidden = true; // Always show hidden rooms on this page

// Fetch all rooms with occupancy and key info
$all_rooms = get_all_rooms_with_occupancy($conn, $show_hidden);

// Group rooms by type
$grouped_rooms = [];
$room_type_order = ['Single', '4-Bed', '6-Bed']; // To maintain order
foreach ($all_rooms as $room) {
    $type = $room['room_type'] ?? 'Other';
    if (!isset($grouped_rooms[$type])) {
        $grouped_rooms[$type] = [];
    }
    $grouped_rooms[$type][] = $room;
}

// Fetch History
$history_q = mysqli_query($conn, "
    SELECT kt.*, kt.status as trans_status, k.key_name, k.type, r.room_type, CONCAT(u.last_name, ', ', u.first_name) as full_name 
    FROM key_transactions kt 
    JOIN `keys` k ON kt.key_id = k.id 
    LEFT JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    JOIN users u ON kt.user_id = u.user_id 
    ORDER BY kt.released_at DESC LIMIT 50
");

// Calculate Stats
$total_keys = 0;
$released_keys = 0;
foreach($all_rooms as $room){
    if (!empty($room['all_keys'])) {
        $total_keys += count($room['all_keys']);
        foreach($room['all_keys'] as $key) {
            if ($key['key_status'] == 'Released') {
                $released_keys++;
            }
        }
    }
}
$available_keys = $total_keys - $released_keys;

// Released keys count for new button
$released_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM key_transactions WHERE status='Active'"))['c'];

// Sidebar counts
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
    <title>Key Monitoring | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        
        .card-room { border: none; border-radius: 15px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
        .card-room:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .card-room img { height: 150px; object-fit: cover; width: 100%; }
        
        .key-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; transition: 0.3s; }
        .key-card:hover { background-color: #f8f9fa; border-color: var(--primary-green); }
        
        .occupant-card { border-left: 4px solid var(--primary-green); background: #f8f9fa; border-radius: 8px; padding: 8px 12px; margin-bottom: 8px; }
        .occupant-card.pending { border-left-color: #ffc107; background: #fff8e1; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-vacant { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-full { background-color: #f8d7da; color: #721c24; }
        .status-maintenance { background-color: #e2e3e5; color: #383d41; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
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
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true"><span><i class="fas fa-building me-2"></i>Facilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5 active"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <?php if($is_super): ?>
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
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-cog me-2"></i>System Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu">
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

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Key Monitoring System</h4>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success">
                    <?php 
                    $msg = $_GET['msg'];
                    if($msg == 'released') echo 'Key released successfully!';
                    elseif($msg == 'returned') echo 'Key returned successfully!';
                    ?>
                </div>
            <?php endif; ?>

            <div class="row mb-4 g-3">
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-primary mb-0"><?= $total_keys ?></h3>
                        <small class="text-muted">Total Keys</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-warning mb-0"><?= $released_keys ?></h3>
                        <small class="text-muted">Keys Released</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-success mb-0"><?= $available_keys ?></h3>
                        <small class="text-muted">Keys Available</small>
                    </div>
                </div>
            </div>

        

            <!-- Key Type Cards (Single, 4-Bed, 6-Bed) -->
            <div class="row g-4">
                <?php foreach($room_type_order as $type): ?>
                    <?php if(!isset($grouped_rooms[$type]) || empty($grouped_rooms[$type])) continue; ?>
                    <?php 
                    $rooms_in_type = $grouped_rooms[$type];
                    $type_total = count($rooms_in_type);
                    $type_released = 0;
                    foreach($rooms_in_type as $room){
                        if(isset($room['key_info']['key_status']) && $room['key_info']['key_status'] == 'Released'){
                            $type_released++;
                        }
                    }
                    $type_available = $type_total - $type_released;
                    $type_status = 'success';
                    if($type_available == 0) $type_status = 'danger';
                    elseif($type_released > 0) $type_status = 'warning';
                    ?>
                    <div class="col-md-4">
                        <div class="card card-room h-100" onclick="openKeyModal('<?= md5($type) ?>')">
                            <div class="card-body text-center bg-white">
                                <i class="fas fa-key fa-3x mb-3 text-success"></i>
                                <h3 class="fw-bold mb-2 text-dark"><?= $type ?> Room</h3>
                                <div class="d-flex justify-content-center gap-3 small mb-3 text-muted">
                                    <span><i class="fas fa-key me-1"></i> <?= $type_total ?> Keys</span>
                                </div>
                                <div class="alert alert-<?= $type_status ?> py-2 mb-0 fw-bold">
                                    <?= $type_available ?> Available
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><?= $type_released ?>/<?= $type_total ?> Released</small>
                                </div>
                                <div class="mt-3">
                                    <span class="btn btn-sm btn-outline-success"><i class="fas fa-eye me-1"></i> View Details</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Transaction History -->
            <div class="card card-custom p-4 mt-4">
                <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-history me-2"></i>Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Date</th><th>Key</th><th>Room Type</th><th>Holder</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while($h = mysqli_fetch_assoc($history_q)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($h['released_at'])) ?></td>
                                <td class="fw-bold"><?= $h['key_name'] ?></td>
                                <td><span class="badge bg-light text-dark"><?= $h['room_type'] ?? 'N/A' ?></span></td>
                                <td><?= $h['full_name'] ?></td>
                                <td><?php if($h['trans_status'] == 'Active'): ?><span class="badge bg-warning text-dark">Active</span><?php else: ?><span class="badge bg-success">Returned</span><?php endif; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php foreach($room_type_order as $type): ?>
<?php if(!isset($grouped_rooms[$type]) || empty($grouped_rooms[$type])) continue; ?>
<?php $rooms_in_type = $grouped_rooms[$type]; ?>
<div class="modal fade" id="key_<?= md5($type) ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-key me-2"></i><?= $type ?> Room Keys</h5>
                <div class="d-flex align-items-center me-3 ms-auto">
                    <label class="small fw-bold me-2 text-muted">Filter:</label>
                    <select class="form-select form-select-sm" onchange="filterKeys(this, '<?= md5($type) ?>')">
                        <option value="all">All Floors</option>
                        <?php for($i=2; $i<=7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <?php foreach($rooms_in_type as $room): ?>
                    <?php
                        $room_display = $room['room_name'];
                        if (!empty($room['room_number'])) {
                            $room_display = "Room " . $room['room_number'];
                        } elseif (is_numeric($room['room_name'])) {
                            $room_display = "Room " . $room['room_name'];
                        }
                        $floor = $room['floor'] ?? 2;
                        
                        $status_class = 'status-vacant';
                        if($room['occupancy_status'] == 'Fully Occupied') $status_class = 'status-full';
                        elseif($room['occupancy_status'] == 'Partially Occupied') $status_class = 'status-partial';
                        elseif($room['occupancy_status'] == 'Maintenance') $status_class = 'status-maintenance';

                        $key_info = $room['key_info'];
                        $is_released = $key_info && $key_info['key_status'] == 'Released';
                    ?>
                    <div class="col-md-6 col-lg-4 key-item" data-floor="<?= $room['floor'] ?>">
                        <div class="card card-custom h-100">
                            <img src="../assets/images/<?= $room['image'] ?>" style="height: 150px; object-fit: cover; width: 100%;">
                            <div class="card-body d-flex flex-column">
                                <!-- Room Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?= $room_display ?></h6>
                                        <small class="text-muted"><i class="fas fa-building me-1"></i> <?= $floor ?>F | <?= $room['room_type'] ?></small>
                                    </div>
                                    <span class="status-badge <?= $status_class ?>"><?= $room['occupancy_status'] ?></span>
                                </div>

                                <!-- Occupancy Bar -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><i class="fas fa-bed me-1"></i> Bed Availability</span>
                                        <span class="fw-bold"><?= $room['occupied_count'] ?>/<?= $room['total_beds'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $percent = $room['total_beds'] > 0 ? ($room['occupied_count'] / $room['total_beds']) * 100 : 0;
                                        $bar_class = 'bg-success';
                                        if($percent >= 100) $bar_class = 'bg-danger';
                                        elseif($percent > 0) $bar_class = 'bg-warning';
                                        ?>
                                        <div class="progress-bar <?= $bar_class ?>" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>

                                <!-- Current Occupants -->
                                <div>
                                    <small class="text-muted fw-bold"><i class="fas fa-users me-1"></i> Current Occupants</small>
                                    <?php if(empty($room['occupants'])): ?>
                                        <p class="text-muted small mb-0 mt-2">No current occupants</p>
                                    <?php else: ?>
                                        <div style="max-height: 120px; overflow-y: auto;" class="mt-2">
                                            <?php foreach($room['occupants'] as $occupant): ?>
                                            <div class="occupant-card <?= $occupant['status'] == 'Pending' ? 'pending' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div><strong><?= $occupant['full_name'] ?></strong></div>
                                                    <small class="text-muted"><?= date('M d', strtotime($occupant['end_date'])) ?></small>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Key Status Section -->
                                <div class="mt-auto pt-3 border-top">
                                    <div class="mb-2">
                                        <small class="text-muted fw-bold"><i class="fas fa-key me-1"></i> KEY STATUS</small>
                                    </div>
                                    <div style="max-height: 140px; overflow-y: auto;">
                                    <?php if(!empty($room['all_keys'])): ?>
                                        <?php foreach($room['all_keys'] as $key): ?>
                                            <div class="d-flex justify-content-between align-items-center p-2 rounded mb-1" style="background-color: <?= $key['key_status'] == 'Available' ? '#e8f5e9' : '#fff3cd' ?>;">
                                                <div class="small">
                                                    <strong class="text-dark"><?= htmlspecialchars($key['key_name']) ?></strong>
                                                    <?php if($key['key_status'] == 'Released'): ?>
                                                        <div class="text-muted text-truncate" style="max-width: 120px;" title="<?= htmlspecialchars($key['key_holder_name']) ?>">
                                                            <i class="fas fa-user-tag me-1"></i>
                                                            <?= htmlspecialchars($key['key_holder_name']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if($key['key_status'] == 'Available'): ?>
                                                    <button class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.7rem;" onclick="openReleaseModal(<?= $key['key_id'] ?>, '<?= addslashes(htmlspecialchars($key['key_name'])) ?>', <?= $room['room_id'] ?>, '<?= addslashes(htmlspecialchars($room_display)) ?>')">
                                                        Release
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.7rem;" onclick="confirmUnrelease(<?= $key['trans_id'] ?>, '<?= addslashes(htmlspecialchars($key['key_name'])) ?>', '<?= addslashes(htmlspecialchars($key['key_holder_name'])) ?>')">
                                                        Return
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">No keys configured for this room.</p>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Confirm Unrelease Modal -->
<div class="modal fade" id="confirmUnreleaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Unrelease</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to unrelease <strong id="unreleaseKeyName"></strong> from <strong id="unreleaseHolderName"></strong>?</p>
                <p class="text-muted small mt-2">This action will mark the key as 'Available' and record the return time.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmUnreleaseBtn" class="btn btn-danger rounded-pill px-4">Yes, Unrelease</a>
            </div>
        </div>
    </div>
</div>

<!-- Release Modal -->
<div class="modal fade" id="releaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Release Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Releasing: <strong id="modalKeyName"></strong></p>
                    <p class="small text-muted">Room: <span id="modalRoomName"></span></p>
                    <input type="hidden" name="key_id" id="modalKeyId">
                    <input type="hidden" name="release_key" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant (Assigned to this room)</label>
                        <select name="user_id" id="tenantSelect" class="form-select" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Release</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unrelease Keys List Modal -->
<div class="modal fade" id="unreleaseListModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i> Released Keys Management
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <input type="text" id="searchReleased" class="form-control form-control-sm" placeholder="Search by room, key, holder...">
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadReleasedKeys()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="releasedKeysTable">
                        <thead class="table-warning">
                            <tr>
                                <th>Room</th>
                                <th>Key</th>
                                <th>Holder</th>
                                <th>Released</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" class="text-center text-muted">Click Refresh or open to load released keys...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openKeyModal(typeId) {
    new bootstrap.Modal(document.getElementById('key_' + typeId)).show();
}

function filterKeys(select, typeId) {
    const floor = select.value;
    const modal = document.getElementById('key_' + typeId);
    const items = modal.querySelectorAll('.key-item');
    
    items.forEach(item => {
        if(floor === 'all' || item.getAttribute('data-floor') === floor) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function openReleaseModal(id, name, roomId, roomName) {
    document.getElementById('modalKeyId').value = id;
    document.getElementById('modalKeyName').innerText = name;
    document.getElementById('modalRoomName').innerText = roomName;
    
    // Show modal first
    var modal = new bootstrap.Modal(document.getElementById('releaseModal'));
    modal.show();
    
    // Fetch tenants for this specific room
    var select = document.getElementById('tenantSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    fetch('get_room_tenants.php?room_id=' + roomId)
        .then(response => response.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.toLowerCase().includes('<html')) {
                    window.location.reload();
                }
                throw new Error("Invalid JSON response");
            }
        })
        .then(data => {
            select.innerHTML = '<option value="">-- Choose Tenant --</option>';
            if (data.length === 0) {
                select.innerHTML = '<option value="">No tenants found for this room</option>';
            } else {
                data.forEach(function(user) {
                    var option = document.createElement('option');
                    option.value = user.user_id;
                    option.textContent = user.full_name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            select.innerHTML = '<option value="">Error loading tenants</option>';
        });
}

function confirmUnrelease(transId, keyName, holderName) {
    document.getElementById('unreleaseKeyName').innerText = keyName;
    document.getElementById('unreleaseHolderName').innerText = holderName;
    document.getElementById('confirmUnreleaseBtn').href = `?action=return&id=${transId}`;
    var myModal = new bootstrap.Modal(document.getElementById('confirmUnreleaseModal'));
    myModal.show();
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

// Unrelease Modal functions
function openUnreleaseModal() {
    loadReleasedKeys();
    new bootstrap.Modal(document.getElementById('unreleaseListModal')).show();
}

function openRoom501UnreleaseModal() {
    loadReleasedKeys('501');
    const modalTitle = document.querySelector('#unreleaseListModal .modal-title');
    modalTitle.innerHTML = '<i class="fas fa-key me-2"></i> Room 501 Key Unrelease';
    new bootstrap.Modal(document.getElementById('unreleaseListModal')).show();
}

function loadReleasedKeys() {
    fetch('get_released_keys.php')
        .then(response => response.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.toLowerCase().includes('<html')) {
                    window.location.reload();
                }
                throw new Error("Invalid JSON response");
            }
        })
        .then(data => {
            const tbody = document.querySelector('#releasedKeysTable tbody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No currently released keys.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(row => `
                <tr>
                    <td><strong>${row.room_number || row.room_name || 'N/A'}</strong></td>
                    <td><code>${row.key_name}</code></td>
                    <td>${row.holder_name}</td>
                    <td>${new Date(row.released_at).toLocaleDateString()}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmUnrelease(${row.trans_id}, '${row.key_name.replace(/'/g, "\\'")}', '${row.holder_name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-undo"></i> Unrelease
                        </button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading released keys:', err);
            document.querySelector('#releasedKeysTable tbody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
        });
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
setInterval(checkUpdates, 3000);

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
