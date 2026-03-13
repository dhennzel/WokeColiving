<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Key Actions
if (isset($_POST['release_key'])) {
    $key_id = (int)$_POST['key_id'];
    $user_id = (int)$_POST['user_id'];
    
    if(release_room_key($conn, $key_id, $user_id)){
        log_activity($conn, $user_id, "Key Released", "Key ID $key_id released to user by $admin_username from Occupancy page.");
        header("Location: admin_room_occupancy.php?msg=key_released");
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'return_key' && isset($_GET['trans_id'])) {
    $trans_id = (int)$_GET['trans_id'];
    
    $t_q = mysqli_query($conn, "SELECT user_id, key_id FROM key_transactions WHERE id=$trans_id");
    $trans_info = mysqli_fetch_assoc($t_q);

    if(return_room_key($conn, $trans_id)){
        if ($trans_info) {
            log_activity($conn, $trans_info['user_id'], "Key Returned", "Key ID {$trans_info['key_id']} returned by user, processed by $admin_username from Occupancy page.");
        }
        header("Location: admin_room_occupancy.php?msg=key_returned");
        exit;
    }
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
    <title>Room Occupancy | Woke Coliving INC</title>
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
        
        .card-room {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .card-room:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .card-room img {
            width: 100%;
            height: 140px;
            object-fit: cover;
        }
        .card-room-summary {
            cursor: pointer;
        }
        
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
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
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
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
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
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
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
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Room Occupancy</h4>
                        <small class="text-muted">Click on a room type to view detailed occupancy</small>
                    </div>
                </div>
                <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <!-- Success Messages -->
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'key_released'): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Key has been released successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if($_GET['msg'] == 'key_returned'): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-undo me-2"></i> Key has been marked as returned successfully!
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
            
            foreach($rooms as $r) {
                $total_occupants += $r['occupied_count'];
                if($r['occupancy_status'] == 'Vacant') $vacant_count++;
                elseif($r['occupancy_status'] == 'Fully Occupied') $full_count++;
                elseif($r['occupancy_status'] == 'Partially Occupied') $partial_count++;
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

            <!-- Room Type Cards (Like Room Inventory) -->
            <div class="row g-4">
                <?php foreach($grouped_rooms as $type => $rooms_in_type): 
                    // Calculate Aggregate Stats for the room type
                    $type_total_beds = array_sum(array_column($rooms_in_type, 'total_beds'));
                    $type_occupied = array_sum(array_column($rooms_in_type, 'occupied_count'));
                    $type_avail_beds = array_sum(array_column($rooms_in_type, 'available_beds'));
                    $first_room = $rooms_in_type[0] ?? null;

                    if (!$first_room) continue;

                    $image = $first_room['image'];
                    
                    // Determine overall status for this room type
                    $type_status = 'info';
                    if($type_avail_beds == 0) $type_status = 'danger';
                    elseif($type_occupied > 0) $type_status = 'warning';
                    else $type_status = 'success';
                ?>
                    <div class="col-md-4">
                        <div class="card card-room card-room-summary h-100" onclick="openOccupancyModal('<?= md5($type) ?>')">
                            <img src="../assets/images/<?= $image ?>" alt="<?= $type ?>">
                            <div class="card-body text-center">
                                <h3 class="fw-bold text-dark mb-2"><?= $type ?></h3>
                                <div class="d-flex justify-content-center gap-3 text-muted small mb-3">
                                    <span><i class="fas fa-door-open me-1"></i> <?= count($rooms_in_type) ?> Rooms</span>
                                    <span><i class="fas fa-bed me-1"></i> <?= $type_total_beds ?> Beds</span>
                                </div>
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="badge bg-<?= $type_status ?>">
                                        <?= $type_avail_beds ?> Beds Available
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted"><?= $type_occupied ?>/<?= $type_total_beds ?> Beds Occupied</small>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <?php 
                                        $percent = $type_total_beds > 0 ? ($type_occupied / $type_total_beds) * 100 : 0;
                                        $bar_class = 'bg-success';
                                        if($percent >= 100) $bar_class = 'bg-danger';
                                        elseif($percent > 0) $bar_class = 'bg-warning';
                                        ?>
                                        <div class="progress-bar <?= $bar_class ?>" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

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

<!-- Modals for each room type (Like Room Inventory) -->
<?php foreach($grouped_rooms as $type => $rooms_in_type): ?>
<div class="modal fade" id="occupancy_<?= md5($type) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-users me-2"></i><?= $type ?> Occupancy</h5>
                <div class="d-flex align-items-center me-3">
                    <label class="small fw-bold me-2 text-muted">Filter:</label>
                    <select class="form-select form-select-sm" onchange="filterOccupancyRooms(this, '<?= md5($type) ?>')">
                        <option value="all">All Floors</option>
                        <?php for($i=2; $i<=7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <?php foreach($rooms_in_type as $room): 
                        // Make room display name consistent with admin_rooms.php
                        $room_display = $room['room_name'];
                        if (!empty($room['room_number'])) {
                            $room_display = "Room " . $room['room_number'];
                        } elseif (is_numeric($room['room_name'])) {
                            $room_display = "Room " . $room['room_name'];
                        }
                        $floor = $room['floor'] ?? 2;
                        
                        // Status badge class
                        $status_class = 'status-vacant';
                        if($room['occupancy_status'] == 'Fully Occupied') $status_class = 'status-full';
                        elseif($room['occupancy_status'] == 'Partially Occupied') $status_class = 'status-partial';
                        elseif($room['occupancy_status'] == 'Maintenance') $status_class = 'status-maintenance';
                        
                        // Concatenate occupant names for search
                        $occupant_names_str = "";
                        if (!empty($room['occupants'])) {
                            $occupant_names = array_map(function($occ) { return strtolower($occ['full_name']); }, $room['occupants']);
                            $occupant_names_str = htmlspecialchars(implode(' ', $occupant_names), ENT_QUOTES);
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 occupancy-room-item" data-floor="<?= $floor ?>" data-status="<?= $room['occupancy_status'] ?>" data-name="<?= strtolower($room_display) ?>" data-occupants="<?= $occupant_names_str ?>">
                        <div class="card card-custom h-100" style="overflow: hidden;">
                            <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room_display ?>" style="height: 150px; object-fit: cover; width: 100%;">
                            <div class="card-body">
                                <!-- Room Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <?= $room_display ?>
                                        </h6>
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
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted fw-bold"><i class="fas fa-users me-1"></i> Current Occupants</small>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.7rem;" onclick="viewHistory(<?= $room['room_id'] ?>, '<?= addslashes($room_display) ?>')"><i class="fas fa-history me-1"></i> History</button>
                                    </div>
                                    <?php if(empty($room['occupants'])): ?>
                                        <p class="text-muted small mb-0">No current occupants</p>
                                    <?php else: ?>
                                        <div style="max-height: 150px; overflow-y: auto;">
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

                                <!-- Key Status Section -->
                                <div class="mt-auto pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted fw-bold"><i class="fas fa-key me-1"></i> Key Status</small>
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
                                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.7rem;" onclick="confirmReturn(<?= $key['trans_id'] ?>, '<?= addslashes(htmlspecialchars($key['key_name'])) ?>', '<?= addslashes(htmlspecialchars($key['key_holder_name'])) ?>')">
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

<!-- Release Modal -->
<div class="modal fade" id="releaseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-share me-2"></i>Release Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Releasing: <strong id="modalKeyName"></strong></p>
                    <p class="small text-muted">Room: <span id="modalRoomName"></span></p>
                    <input type="hidden" name="key_id" id="modalKeyId">
                    <input type="hidden" name="release_key" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant (Approved occupants of this room)</label>
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

<!-- Confirm Return Modal -->
<div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i>Confirm Key Return</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to mark <strong id="returnKeyName"></strong> as returned from <strong id="returnHolderName"></strong>?</p>
                <p class="text-muted small mt-2">This action will mark the key as 'Available' and record the return time.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmReturnBtn" class="btn btn-danger rounded-pill px-4">Yes, Mark as Returned</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openOccupancyModal(typeId) {
    new bootstrap.Modal(document.getElementById('occupancy_' + typeId)).show();
}

function filterOccupancyRooms(select, typeId) {
    const floor = select.value;
    const modal = document.getElementById('occupancy_' + typeId);
    const items = modal.querySelectorAll('.occupancy-room-item');
    
    items.forEach(item => {
        if(floor === 'all' || item.getAttribute('data-floor') === floor) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
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

function openReleaseModal(id, name, roomId, roomName) {
    document.getElementById('modalKeyId').value = id;
    document.getElementById('modalKeyName').innerText = name;
    document.getElementById('modalRoomName').innerText = roomName;
    
    var modal = new bootstrap.Modal(document.getElementById('releaseModal'));
    modal.show();
    
    var select = document.getElementById('tenantSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    fetch('get_room_tenants.php?room_id=' + roomId)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Choose Tenant --</option>';
            if (data.length === 0) {
                select.innerHTML = '<option value="">No approved occupants found for this room</option>';
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

function confirmReturn(transId, keyName, holderName) {
    document.getElementById('returnKeyName').innerText = keyName;
    document.getElementById('returnHolderName').innerText = holderName;
    document.getElementById('confirmReturnBtn').href = `admin_room_occupancy.php?action=return_key&trans_id=${transId}`;
    var myModal = new bootstrap.Modal(document.getElementById('confirmReturnModal'));
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
</script>
</body>
</html>
