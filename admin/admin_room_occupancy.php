<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

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
        echo '<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">';
        echo '<thead class="table-light"><tr><th>Guest Name</th><th>Dates Stayed</th><th>Status</th></tr></thead><tbody>';
        while($row = mysqli_fetch_assoc($hist_q)){
            $badge = 'bg-secondary';
            if($row['status'] == 'Completed') $badge = 'bg-primary';
            elseif($row['status'] == 'Approved') $badge = 'bg-success';
            
            echo '<tr>';
            echo '<td class="fw-bold text-dark">'.htmlspecialchars($row['full_name']).'</td>';
            echo '<td class="small text-muted"><i class="fas fa-calendar-day me-1"></i> '.date('M d, Y', strtotime($row['start_date'])).' &rarr; '.date('M d, Y', strtotime($row['end_date'])).'</td>';
            echo '<td><span class="badge '.$badge.' rounded-pill px-3">'.$row['status'].'</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
    } else {
        echo '<div class="text-center text-muted py-5"><i class="fas fa-history fa-3x mb-3 opacity-25"></i><h6 class="mb-0 fw-bold">No History Found</h6><p class="small">This room has no past occupants.</p></div>';
    }
    exit;
}

// Fetch all rooms with occupancy information
$show_hidden = true; // To include maintenance rooms
$raw_rooms = get_all_rooms_with_occupancy($conn, $show_hidden);

// Deduplicate by Room Name
$rooms = [];
$seen_names = [];
foreach ($raw_rooms as $room) {
    // Determine the true display name
    $name = !empty($room['room_number']) ? trim($room['room_number']) : trim($room['room_name']);
    $display_key = strtolower($name);

    if (!isset($seen_names[$display_key])) {
        $seen_names[$display_key] = count($rooms);
        $rooms[] = $room;
    } else {
        // If it's a duplicate, keep the one that actually has the tenants
        $idx = $seen_names[$display_key];
        if ($room['occupied_count'] > $rooms[$idx]['occupied_count']) {
            $rooms[$idx] = $room;
        }
    }
}

// Fetch active maintenance and housekeeping requests per room
$active_maint_q = mysqli_query($conn, "SELECT room_id FROM maintenance_requests WHERE status IN ('Pending', 'Scheduled')");
$active_maint_rooms = [];
while($r = mysqli_fetch_assoc($active_maint_q)) $active_maint_rooms[] = $r['room_id'];

$active_house_q = mysqli_query($conn, "SELECT room_id FROM housekeeping_requests WHERE status IN ('Pending', 'Scheduled')");
$active_house_rooms = [];
while($r = mysqli_fetch_assoc($active_house_q)) $active_house_rooms[] = $r['room_id'];

// Map to rooms
foreach ($rooms as &$room) {
    $room['has_maintenance'] = in_array($room['room_id'], $active_maint_rooms);
    $room['has_housekeeping'] = in_array($room['room_id'], $active_house_rooms);
}
unset($room);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Occupancy | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-room {
                border: 1px solid var(--border-color);
                border-radius: var(--radius-lg, 16px);
            overflow: hidden;
                transition: all var(--transition-speed);
            cursor: pointer;
                background: var(--bg-surface);
                box-shadow: var(--shadow-sm);
        }
        .card-room:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-hover);
                border-color: var(--primary-green);
        }
        .card-room img {
            width: 100%;
                height: 180px;
            object-fit: cover;
                border-bottom: 1px solid var(--border-color);
        }
        .card-room-summary {
            cursor: pointer;
        }
        
        .occupant-card {
            border-left: 4px solid var(--primary-green);
            background: var(--bg-surface-hover);
            border-radius: var(--radius-md, 12px);
            padding: 15px;
            margin-bottom: 10px;
            color: var(--text-main);

        }
        .occupant-card.pending {
            border-left-color: #ffc107;
            background: rgba(240, 180, 41, 0.1);
        }
        
        .room-header {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            color: var(--text-main);
            border-radius: var(--radius-md, 12px);
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
        
        .room-card-clickable {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 2px solid transparent;
        }
        .room-card-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
            border-color: var(--primary-green) !important;
        }

        /* Print Styles */
        .print-only { display: none; }
        @media print {
            @page { margin: 10mm; }
            body, html { background: #fff !important; margin: 0 !important; padding: 0 !important; color: #000 !important; }
            .dashboard-container, .modal, .modal-backdrop, .sidebar-backdrop { display: none !important; }
            
            .print-only { display: block !important; width: 100%; color: #000 !important; }
            .print-only h2 { margin-bottom: 5px; font-weight: bold; font-family: sans-serif; }
            .print-only h4 { margin-top: 0; font-family: sans-serif; }
            .print-only table { width: 100%; border-collapse: collapse; margin-top: 20px; font-family: sans-serif; }
            .print-only th, .print-only td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px; text-align: left; vertical-align: top; }
            .print-only th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; color-adjust: exact; font-weight: bold; }
            .print-only .summary-table { margin-top: 0; }
            .print-only .summary-table th, .print-only .summary-table td { border: none !important; padding: 4px !important; font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Room Occupancy</h1>
                    <small class="text-muted">Click on a room type to view detailed occupancy</small>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill btn-sm me-2 shadow-sm">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                    <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill btn-sm">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>

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
        </main>
    </div>
</div>

<!-- Print Only Container -->
<div class="print-only">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" alt="Dormitory Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid #F0B429; margin-bottom: 10px;">
        <h2>Dormitory</h2>
        <h4>Room Occupancy Report</h4>
        <p>As of <?= date('F d, Y h:i A') ?></p>
    </div>
    
    <table class="summary-table">
        <tr>
            <td><strong>Total Rooms:</strong> <?= $total_rooms ?></td>
            <td><strong>Total Occupants:</strong> <?= $total_occupants ?></td>
            <td><strong>Partially Occupied:</strong> <?= $partial_count ?></td>
            <td><strong>Vacant Rooms:</strong> <?= $vacant_count ?></td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Room</th>
                <th>Type / Floor</th>
                <th>Status</th>
                <th>Occupancy</th>
                <th>Occupants</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($grouped_rooms as $type => $rooms_in_type): ?>
                <?php foreach($rooms_in_type as $room): 
                    $room_display = !empty($room['room_number']) ? 'Room ' . $room['room_number'] : $room['room_name'];
                    $occ_names = [];
                    if(!empty($room['occupants'])){
                        foreach($room['occupants'] as $occ) {
                            $bed = (isset($occ['bed_preference']) && $occ['bed_preference'] != 'Any') ? " ({$occ['bed_preference']})" : "";
                            $occ_names[] = htmlspecialchars($occ['full_name']) . $bed;
                        }
                    }
                    $occ_str = empty($occ_names) ? '<i>Vacant</i>' : implode('<br>', $occ_names);
                ?>
                <tr>
                    <td><strong><?= $room_display ?></strong></td>
                    <td><?= $type ?> (<?= $room['floor'] ?? 2 ?>F)</td>
                    <td><?= $room['occupancy_status'] ?></td>
                    <td><?= $room['occupied_count'] ?> / <?= $room['total_beds'] ?></td>
                    <td><?= $occ_str ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modals for each room type (Like Room Inventory) -->
<?php foreach($grouped_rooms as $type => $rooms_in_type): ?>
<div class="modal fade" id="occupancy_<?= md5($type) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-users me-2"></i><?= $type ?> Occupancy</h5>
                <div class="d-flex align-items-center me-3 ms-auto">
                    <input type="text" class="form-control form-control-sm border-0 shadow-sm me-2" placeholder="Search room or tenant..." onkeyup="filterOccupancyModal('<?= md5($type) ?>')" style="width: 200px;">
                    <label class="small fw-bold me-2 text-white opacity-75">Filter:</label>
                    <select class="form-select form-select-sm border-0 shadow-sm" onchange="filterOccupancyModal('<?= md5($type) ?>')" style="width: 120px;">
                        <option value="all">All Floors</option>
                        <?php for($i=2; $i<=7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                        <?php endfor; ?>
                    </select>
                    <select class="form-select form-select-sm border-0 shadow-sm" id="genderFilter_<?= md5($type) ?>" onchange="filterOccupancyModal('<?= md5($type) ?>')" style="width: 120px; margin-left: 8px;">
                        <option value="all">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Any">Mixed</option>
                    </select>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
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
                        
                        // Gender icon logic
                        $gender_icon = 'fa-question-circle';
                        $gender_title = 'Mixed/Not Set';
                        if (isset($room['gender'])) {
                            if ($room['gender'] == 'Male') {
                                $gender_icon = 'fa-mars text-primary';
                                $gender_title = 'Male Only';
                            } elseif ($room['gender'] == 'Female') {
                                $gender_icon = 'fa-venus text-danger';
                                $gender_title = 'Female Only';
                            }
                        }
                        
                        // Concatenate occupant names for search
                        $occupant_names_str = "";
                        if (!empty($room['occupants'])) {
                            $occupant_names = array_map(function($occ) { return strtolower($occ['full_name']); }, $room['occupants']);
                            $occupant_names_str = htmlspecialchars(implode(' ', $occupant_names), ENT_QUOTES);
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 occupancy-room-item" data-floor="<?= $floor ?>" data-status="<?= $room['occupancy_status'] ?>" data-name="<?= strtolower($room_display) ?>" data-occupants="<?= $occupant_names_str ?>" data-gender="<?= $room['gender'] ?? 'Any' ?>">
                        <div class="card card-custom h-100 room-card-clickable" style="overflow: hidden; cursor: pointer;" onclick="openRoomOccupantsModal(this)" data-room-name="<?= htmlspecialchars($room_display, ENT_QUOTES) ?>" data-occupants="<?= htmlspecialchars(json_encode($room['occupants']), ENT_QUOTES, 'UTF-8') ?>">
                            <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room_display ?>" style="height: 150px; object-fit: cover; width: 100%;">
                            <div class="card-body d-flex flex-column">
                                <!-- Room Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <?= $room_display ?>
                                        </h6>
                                        <small class="text-muted"><i class="fas fa-building me-1"></i> <?= $floor ?>F | <?= $room['room_type'] ?></small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark border" title="<?= $gender_title ?>"><i class="fas <?= $gender_icon ?>"></i></span>
                                        <span class="status-badge <?= $status_class ?>"><?= $room['occupancy_status'] ?></span>
                                    </div>
                                </div>

                                <?php if($room['has_maintenance'] || $room['has_housekeeping']): ?>
                                <div class="d-flex gap-2 mb-2">
                                    <?php if($room['has_maintenance']): ?>
                                        <span class="badge bg-danger text-white" style="font-size: 0.65rem;"><i class="fas fa-tools me-1"></i> Maintenance</span>
                                    <?php endif; ?>
                                    <?php if($room['has_housekeeping']): ?>
                                        <span class="badge bg-info text-dark" style="font-size: 0.65rem;"><i class="fas fa-broom me-1"></i> Housekeeping</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

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

                                <!-- Actions -->
                                <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                                    <small class="text-success fw-bold"><i class="fas fa-users me-1"></i> View Occupants</small>
                                    <button class="btn btn-sm btn-outline-secondary py-1 px-3 rounded-pill position-relative z-3" style="font-size: 0.75rem;" onclick="event.stopPropagation(); viewHistory(<?= $room['room_id'] ?>, '<?= addslashes($room_display) ?>')"><i class="fas fa-history me-1"></i> History</button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i>Room History: <span id="histRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4" id="histContent">
                <!-- Content loads here -->
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Room Occupants Modal -->
<div class="modal fade" id="roomOccupantsModal" tabindex="-1" aria-hidden="true" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-users me-2"></i>Occupants: <span id="occModalRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-0" id="occModalContent">
                <!-- Content loaded via JS -->
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function openOccupancyModal(typeId) {
    new bootstrap.Modal(document.getElementById('occupancy_' + typeId)).show();
}

function filterOccupancyModal(typeId) {
    const modal = document.getElementById('occupancy_' + typeId);
    const floorSelect = modal.querySelector('select');
    const genderSelect = document.getElementById('genderFilter_' + typeId);
    const searchInput = modal.querySelector('input[type="text"]');
    
    const floor = floorSelect ? floorSelect.value : 'all';
    const genderFilter = genderSelect ? genderSelect.value : 'all';
    const search = searchInput ? searchInput.value.toLowerCase() : '';
    const items = modal.querySelectorAll('.occupancy-room-item');
    
    items.forEach(item => {
        const itemFloor = item.getAttribute('data-floor');
        const itemName = item.getAttribute('data-name') || '';
        const itemOccupants = item.getAttribute('data-occupants') || '';
        const itemGender = item.getAttribute('data-gender') || 'Any';
        
        let show = (floor === 'all' || itemFloor === floor);
        if (show && genderFilter !== 'all' && itemGender !== genderFilter) show = false;
        if (show && search !== '') {
            if (!itemName.includes(search) && !itemOccupants.includes(search)) show = false;
        }
        item.style.display = show ? 'block' : 'none';
    });
}

function openRoomOccupantsModal(element) {
    const roomName = element.getAttribute('data-room-name');
    const occupants = JSON.parse(element.getAttribute('data-occupants'));
    
    document.getElementById('occModalRoomName').innerText = roomName;
    const content = document.getElementById('occModalContent');
    
    if (occupants.length === 0) {
        content.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i><h6 class="fw-bold">No Occupants</h6><p class="small">This room is currently vacant.</p></div>';
    } else {
        let html = '<div class="list-group list-group-flush">';
        occupants.forEach(occ => {
            let bedClass = 'bg-secondary';
            let bedIcon = 'fa-random';
            if(occ.bed_preference === 'Upper Bunk') { bedClass = 'bg-info text-dark'; bedIcon = 'fa-arrow-up'; }
            else if(occ.bed_preference === 'Lower Bunk') { bedClass = 'bg-primary'; bedIcon = 'fa-arrow-down'; }
            
            let statusBadge = occ.status === 'Pending' ? '<span class="badge bg-warning text-dark ms-2">Pending</span>' : '';

            let genderIcon = '';
            if (occ.gender === 'Male') {
                genderIcon = '<i class="fas fa-mars text-primary ms-2" title="Male"></i>';
            } else if (occ.gender === 'Female') {
                genderIcon = '<i class="fas fa-venus text-danger ms-2" title="Female"></i>';
            }

            let avatarHtml = occ.profile_image ? `<img src="../uploads/profiles/${occ.profile_image}" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">` : `<div class="rounded-circle shadow-sm bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 50px; height: 50px; font-size: 1.2rem;">${occ.full_name.charAt(0).toUpperCase()}</div>`;
            let linkUrl = `view_user.php?uid=${occ.user_id}`;
            
            let d1 = new Date(occ.start_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
            let d2 = new Date(occ.end_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});

            html += `
            <a href="${linkUrl}" class="list-group-item list-group-item-action d-flex align-items-center p-4 border-bottom">
                ${avatarHtml}
                <div class="ms-3 flex-grow-1">
                    <h6 class="mb-1 fw-bold text-dark">${occ.full_name} ${statusBadge} ${genderIcon}</h6>
                    <div class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> ${d1} - ${d2}</div>
                </div>
                <div class="text-end ms-2">
                    <span class="badge ${bedClass} rounded-pill px-3 py-2"><i class="fas ${bedIcon} me-1"></i> ${occ.bed_preference}</span>
                </div>
            </a>`;
        });
        html += '</div>';
        content.innerHTML = html;
    }
    
    const occModal = new bootstrap.Modal(document.getElementById('roomOccupantsModal'));
    occModal.show();
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
</script>
</body>
</html>
