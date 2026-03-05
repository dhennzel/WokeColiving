<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Handle History Fetch (AJAX)
if(isset($_GET['fetch_history']) && isset($_GET['room_id'])){
    $rid = (int)$_GET['room_id'];
    $hist_q = mysqli_query($conn, "
        SELECT r.*, CONCAT(u.last_name, ', ', u.first_name) as full_name 
        FROM reservations r 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.room_id = $rid 
        AND (r.status = 'Completed' OR (r.status = 'Approved' AND r.end_date < CURDATE()))
        ORDER BY r.end_date DESC
    ");
    
    if(mysqli_num_rows($hist_q) > 0){
        echo '<div class="table-responsive"><table class="table table-sm table-hover small mb-0">';
        echo '<thead class="table-light"><tr><th>Name</th><th>Dates</th><th>Status</th></tr></thead><tbody>';
        while($row = mysqli_fetch_assoc($hist_q)){
            echo '<tr>';
            echo '<td class="fw-bold">'.htmlspecialchars($row['full_name']).'</td>';
            echo '<td>'.date('M d, Y', strtotime($row['start_date'])).' - '.date('M d, Y', strtotime($row['end_date'])).'</td>';
            echo '<td><span class="badge bg-secondary">'.$row['status'].'</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="text-center text-muted py-4"><i class="fas fa-history fa-2x mb-2 opacity-25"></i><p class="mb-0 small">No past occupants found.</p></div>';
    }
    exit;
}

// Handle Key Release
if (isset($_POST['release_key'])) {
    $key_id = (int)$_POST['key_id'];
    $user_id = (int)$_POST['user_id'];
    
    if ($key_id > 0 && $user_id > 0) {
        release_room_key($conn, $key_id, $user_id);
        header("Location: admin_room_occupancy.php?msg=key_released");
        exit;
    }
}

// Handle Key Return
if (isset($_GET['action']) && $_GET['action'] == 'return_key' && isset($_GET['trans_id'])) {
    $trans_id = (int)$_GET['trans_id'];
    return_room_key($conn, $trans_id);
    header("Location: admin_room_occupancy.php?msg=key_returned");
    exit;
}

// Fetch all rooms with occupancy information
$rooms = get_all_rooms_with_occupancy($conn);

// Group rooms by type
$grouped_rooms = [];
foreach ($rooms as $room) {
    $type = $room['room_type'];
    if (!isset($grouped_rooms[$type])) {
        $grouped_rooms[$type] = [];
    }
    $grouped_rooms[$type][] = $room;
}

// Fetch all active tenants for key release dropdown
$active_tenants_q = mysqli_query($conn, "
    SELECT DISTINCT u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status IN ('Approved', 'Pending')
    AND r.start_date <= CURDATE() 
    AND r.end_date > CURDATE()
    ORDER BY u.last_name
");
$active_tenants = [];
while ($t = mysqli_fetch_assoc($active_tenants_q)) {
    $active_tenants[] = $t;
}

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Occupancy & Key Monitoring | Woke Coliving INC</title>
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
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-vacant { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-full { background-color: #f8d7da; color: #721c24; }
        .status-maintenance { background-color: #e2e3e5; color: #383d41; }
        
        .key-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .key-available { background-color: #d4edda; color: #155724; }
        .key-released { background-color: #cce5ff; color: #004085; }
        
        .occupant-card {
            border-left: 4px solid var(--primary-green);
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .occupant-card.pending {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        
        .room-header {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .bed-icon {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.7rem;
        }
        .bed-upper { background-color: #e3f2fd; color: #1565c0; }
        .bed-lower { background-color: #e8f5e9; color: #2e7d32; }
        .bed-any { background-color: #f3e5f5; color: #7b1fa2; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
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
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
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
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5 active"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Room Occupancy & Key Monitoring</h4>
                        <small class="text-muted">Monitor room occupancy and manage key assignments</small>
                    </div>
                </div>
                <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <!-- Success Messages -->
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'key_released'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Key has been released successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif($_GET['msg'] == 'key_returned'): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-key me-2"></i> Key has been returned successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Summary Stats -->
            <?php
            $total_rooms = count($rooms);
            $total_occupants = 0;
            $vacant_count = 0;
            $full_count = 0;
            $partial_count = 0;
            $total_keys_released = 0;
            
            foreach($rooms as $r) {
                $total_occupants += $r['occupied_count'];
                if($r['occupancy_status'] == 'Vacant') $vacant_count++;
                elseif($r['occupancy_status'] == 'Fully Occupied') $full_count++;
                elseif($r['occupancy_status'] == 'Partially Occupied') $partial_count++;
                
                if(isset($r['key_info']['key_status']) && $r['key_info']['key_status'] == 'Released') {
                    $total_keys_released++;
                }
            }
            ?>
            <div class="row mb-4 g-3">
                <div class="col-6 col-md">
                    <div class="card card-custom p-3 text-center h-100">
                        <h3 class="fw-bold text-success mb-0"><?= $total_rooms ?></h3>
                        <small class="text-muted">Total Rooms</small>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card card-custom p-3 text-center h-100">
                        <h3 class="fw-bold text-primary mb-0"><?= $total_occupants ?></h3>
                        <small class="text-muted">Total Occupants</small>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card card-custom p-3 text-center h-100" style="cursor: pointer;" onclick="filterByReleasedKeys()" title="Click to filter by released keys">
                        <h3 class="fw-bold text-info mb-0"><?= $total_keys_released ?></h3>
                        <small class="text-muted">Keys Released</small>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card card-custom p-3 text-center h-100">
                        <h3 class="fw-bold text-warning mb-0"><?= $partial_count ?></h3>
                        <small class="text-muted">Partially Occupied</small>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card card-custom p-3 text-center h-100">
                        <h3 class="fw-bold text-danger mb-0"><?= $vacant_count ?></h3>
                        <small class="text-muted">Vacant Rooms</small>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card card-custom p-3 mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-auto fw-bold text-secondary"><i class="fas fa-filter me-2"></i>Filter:</div>
                    <div class="col-md-2">
                        <select id="floorFilter" class="form-select form-select-sm" onchange="filterRooms()">
                            <option value="all">All Floors</option>
                            <?php for($i=2; $i<=7; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="statusFilter" class="form-select form-select-sm" onchange="filterRooms()">
                            <option value="all">All Statuses</option>
                            <option value="Vacant">Vacant</option>
                            <option value="Partially Occupied">Partially Occupied</option>
                            <option value="Fully Occupied">Fully Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="keyFilter" class="form-select form-select-sm" onchange="filterRooms()">
                            <option value="all">All Keys</option>
                            <option value="Available">Key Available</option>
                            <option value="Released">Key Released</option>
                        </select>
                    </div>
                    <div class="col-md">
                        <input type="text" id="roomSearch" class="form-control form-control-sm" placeholder="Search room or occupant..." onkeyup="filterRooms()">
                    </div>
                    <div class="col-md-auto">
                         <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Room Type Sections -->
            <?php 
            $room_type_order = ['6-Bed', '4-Bed', 'Single'];
            foreach($room_type_order as $type): 
                if(!isset($grouped_rooms[$type]) || empty($grouped_rooms[$type])) continue;
            ?>
            <div class="mb-5 room-type-section">
                <div class="room-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?= $type ?> Rooms</h5>
                        <small><?= count($grouped_rooms[$type]) ?> rooms</small>
                    </div>
                    <div class="text-end">
                        <?php
                        $type_occupied = 0;
                        $type_total = 0;
                        foreach($grouped_rooms[$type] as $tr) {
                            $type_occupied += $tr['occupied_count'];
                            $type_total += $tr['total_beds'];
                        }
                        ?>
                        <small class="opacity-75"><?= $type_occupied ?>/<?= $type_total ?> Beds Occupied</small>
                    </div>
                </div>

                <div class="row">
                    <?php foreach($grouped_rooms[$type] as $room): 
                        $room_display = $room['room_number'] ? "Room " . $room['room_number'] : $room['room_name'];
                        
                        // Status badge class
                        $status_class = 'status-vacant';
                        if($room['occupancy_status'] == 'Fully Occupied') $status_class = 'status-full';
                        elseif($room['occupancy_status'] == 'Partially Occupied') $status_class = 'status-partial';
                        elseif($room['occupancy_status'] == 'Maintenance') $status_class = 'status-maintenance';
                        
                        // Key status
                        $key_info = $room['key_info'];
                        $key_status_class = $key_info['key_status'] == 'Available' ? 'key-available' : 'key-released';
                        
                        // Check for key mismatch (Released but Vacant)
                        $is_key_mismatch = ($room['occupancy_status'] == 'Vacant' && $key_info['key_status'] == 'Released');
                        $card_border_class = $is_key_mismatch ? 'border border-danger border-2' : '';

                        // Concatenate occupant names for search
                        $occupant_names_str = "";
                        if (!empty($room['occupants'])) {
                            $occupant_names = array_map(function($occ) { return strtolower($occ['full_name']); }, $room['occupants']);
                            $occupant_names_str = htmlspecialchars(implode(' ', $occupant_names), ENT_QUOTES);
                        }
                    ?>
                    <div class="col-lg-6 mb-4 room-item" data-floor="<?= $room['floor'] ?>" data-status="<?= $room['occupancy_status'] ?>" data-key-status="<?= $key_info['key_status'] ?>" data-name="<?= strtolower($room_display) ?>" data-occupants="<?= $occupant_names_str ?>">
                        <div class="card card-custom h-100 <?= $card_border_class ?>">
                            <div class="card-body">
                                <!-- Room Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <?= $room_display ?>
                                            <?php if($is_key_mismatch): ?>
                                                <i class="fas fa-exclamation-circle text-danger ms-1" title="Warning: Key is released but room is vacant"></i>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><i class="fas fa-building me-1"></i> <?= $room['floor'] ?>F | <?= $room['room_type'] ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge <?= $status_class ?>"><?= $room['occupancy_status'] ?></span>
                                    </div>
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

                                <!-- Key Status -->
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                    <div>
                                        <small class="text-muted d-block"><i class="fas fa-key me-1"></i> Room Key</small>
                                        <?php 
                                            $key_tooltip = ($key_info['key_status'] == 'Released' && !empty($key_info['key_holder_name'])) 
                                                ? 'title="Held by: ' . htmlspecialchars($key_info['key_holder_name']) . '"' 
                                                : '';
                                        ?>
                                        <span class="key-badge <?= $key_status_class ?>" <?= $key_tooltip ?>>
                                            <i class="fas <?= $key_info['key_status'] == 'Available' ? 'fa-lock-open' : 'fa-lock' ?> me-1"></i>
                                            <?= $key_info['key_status'] ?>
                                        </span>
                                    </div>
                                    <?php if($key_info['key_status'] == 'Available'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openKeyModal(<?= $key_info['key_id'] ?>, '<?= addslashes($room_display) ?>')">
                                            <i class="fas fa-key me-1"></i> Release Key
                                        </button>
                                    <?php else: ?>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Held by: <strong><?= $key_info['key_holder_name'] ?></strong></small>
                                            <a href="?action=return_key&trans_id=<?= $key_info['trans_id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Mark this key as returned?')">
                                                <i class="fas fa-undo me-1"></i> Return Key
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Current Occupants -->
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted fw-bold"><i class="fas fa-users me-1"></i> Current Occupants</small>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.7rem;" onclick="viewHistory(<?= $room['room_id'] ?>, '<?= addslashes($room_display) ?>')"><i class="fas fa-history me-1"></i> History</button>
                                    </div>
                                    <?php if(empty($room['occupants'])): ?>
                                        <p class="text-muted small mb-0">No current occupants</p>
                                    <?php else: ?>
                                        <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                                            <?php foreach($room['occupants'] as $occupant): 
                                                $bed_class = 'bed-any';
                                                $bed_icon = 'fa-random';
                                                if($occupant['bed_preference'] == 'Upper Bunk') { $bed_class = 'bed-upper'; $bed_icon = 'fa-arrow-up'; }
                                                elseif($occupant['bed_preference'] == 'Lower Bunk') { $bed_class = 'bed-lower'; $bed_icon = 'fa-arrow-down'; }
                                            ?>
                                            <div class="occupant-card <?= $occupant['status'] == 'Pending' ? 'pending' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong><?= $occupant['full_name'] ?></strong>
                                                        <span class="badge <?= $bed_class ?> bed-icon ms-1" title="<?= $occupant['bed_preference'] ?>">
                                                            <i class="fas <?= $bed_icon ?>"></i>
                                                        </span>
                                                        <?php if($occupant['status'] == 'Pending'): ?>
                                                            <span class="badge bg-warning text-dark small ms-1">Pending</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('M d', strtotime($occupant['start_date'])) ?> - <?= date('M d, Y', strtotime($occupant['end_date'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($rooms)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No rooms found</h5>
                <p class="text-muted">Please add rooms to start monitoring occupancy.</p>
                <a href="add_room.php" class="btn btn-custom"><i class="fas fa-plus me-2"></i>Add Room</a>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Key Release Modal -->
<div class="modal fade" id="keyReleaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Release Room Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Releasing key for: <strong id="modalRoomName"></strong></p>
                    <input type="hidden" name="key_id" id="modalKeyId">
                    <input type="hidden" name="release_key" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant to Assign Key</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php foreach($active_tenants as $tenant): ?>
                                <option value="<?= $tenant['user_id'] ?>"><?= $tenant['full_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Release Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room History: <span id="histRoomName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="histContent">
                <!-- Content loads here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterRooms() {
    const floor = document.getElementById('floorFilter').value;
    const status = document.getElementById('statusFilter').value;
    const keyStatus = document.getElementById('keyFilter').value;
    const search = document.getElementById('roomSearch').value.toLowerCase();
    
    document.querySelectorAll('.room-item').forEach(item => {
        const itemFloor = item.getAttribute('data-floor');
        const itemStatus = item.getAttribute('data-status');
        const itemKeyStatus = item.getAttribute('data-key-status');
        const itemName = item.getAttribute('data-name');
        const itemOccupants = item.getAttribute('data-occupants');
        
        let show = true;
        if (floor !== 'all' && itemFloor !== floor) show = false;
        if (status !== 'all' && itemStatus !== status) show = false;
        if (keyStatus !== 'all' && itemKeyStatus !== keyStatus) show = false;
        if (search && !itemName.includes(search) && !itemOccupants.includes(search)) show = false;
        
        item.style.display = show ? '' : 'none';
    });

    // Hide empty sections
    document.querySelectorAll('.room-type-section').forEach(section => {
        let hasVisible = false;
        section.querySelectorAll('.room-item').forEach(item => {
            if (item.style.display !== 'none') hasVisible = true;
        });
        section.style.display = hasVisible ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('floorFilter').value = 'all';
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('keyFilter').value = 'all';
    document.getElementById('roomSearch').value = '';
    filterRooms();
}

function filterByReleasedKeys() {
    document.getElementById('keyFilter').value = 'Released';
    document.getElementById('floorFilter').value = 'all';
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('roomSearch').value = '';
    filterRooms();
}

function viewHistory(roomId, roomName) {
    document.getElementById('histRoomName').innerText = roomName;
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();
    
    document.getElementById('histContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    
    fetch('admin_room_occupancy.php?fetch_history=1&room_id=' + roomId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('histContent').innerHTML = html;
        });
}

function openKeyModal(keyId, roomName) {
    document.getElementById('modalKeyId').value = keyId;
    document.getElementById('modalRoomName').innerText = roomName;
    new bootstrap.Modal(document.getElementById('keyReleaseModal')).show();
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
