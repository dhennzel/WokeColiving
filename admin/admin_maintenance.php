<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Price Settings Update
if(isset($_POST['update_maintenance_price'])){
    $standard_price = (float)$_POST['price_maintenance_standard'];
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('price_maintenance_standard', '$standard_price') ON DUPLICATE KEY UPDATE setting_value='$standard_price'");

    // Bulk Update existing records that haven't been priced yet
    mysqli_query($conn, "UPDATE maintenance_requests SET cost='$standard_price' WHERE (cost=0 OR cost IS NULL) AND status IN ('Pending', 'Scheduled')");
    
    trigger_update($conn);
    header("Location: admin_maintenance.php?msg=price_updated");
    exit;
}

// Fetch Standard Price
$standard_maint_price = 0.00; // System default
$q_price = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'price_maintenance_standard'");
if($row_p = mysqli_fetch_assoc($q_price)){ $standard_maint_price = (float)$row_p['setting_value']; }

$message = "";
// Handle success message from redirect
if(isset($_GET['msg'])){
    if($_GET['msg'] == 'price_updated') $message = "Standard maintenance price updated successfully.";
    elseif($_GET['msg'] == 'scheduled') $message = "Maintenance scheduled successfully.";
    elseif($_GET['msg'] == 'moved') $message = "Tenant moved temporarily.";
    elseif($_GET['msg'] == 'returned') $message = "Tenant returned to original room successfully.";
    elseif($_GET['msg'] == 'full') $message = "Target room is full. Move failed.";
}

// Create table for temporary moves if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS temporary_moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    original_room_id INT NOT NULL,
    temp_room_id INT NOT NULL,
    move_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Returned') DEFAULT 'Active'
)");

// Ensure schema supports room-based requests and billing
mysqli_query($conn, "ALTER TABLE maintenance_requests MODIFY COLUMN user_id INT NULL");
$chk_cost = mysqli_query($conn, "SHOW COLUMNS FROM maintenance_requests LIKE 'cost'");
if(mysqli_num_rows($chk_cost) == 0) mysqli_query($conn, "ALTER TABLE maintenance_requests ADD COLUMN cost DECIMAL(10,2) DEFAULT 0.00");

// Handle Move Tenant
if(isset($_POST['move_tenant'])){
    $res_id = (int)$_POST['reservation_id'];
    $target_room = (int)$_POST['target_room_id'];
    
    // Check target room availability
    $chk_room = mysqli_query($conn, "SELECT total_beds FROM rooms WHERE room_id=$target_room");
    $room_data = mysqli_fetch_assoc($chk_room);
    
    $chk_occ = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM reservations WHERE room_id=$target_room AND status IN ('Pending','Approved')");
    $occ_data = mysqli_fetch_assoc($chk_occ);
    
    if($occ_data['cnt'] >= $room_data['total_beds']){
        header("Location: admin_maintenance.php?msg=full");
        exit;
    }

    // Get original room
    $orig_q = mysqli_query($conn, "SELECT room_id FROM reservations WHERE reservation_id=$res_id");
    $orig_row = mysqli_fetch_assoc($orig_q);
    $orig_room = $orig_row['room_id'];
    $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$res_id");
    $uid = mysqli_fetch_assoc($u_q)['user_id'];
    
    mysqli_query($conn, "INSERT INTO temporary_moves (reservation_id, original_room_id, temp_room_id) VALUES ($res_id, $orig_room, $target_room)");
    mysqli_query($conn, "UPDATE reservations SET room_id=$target_room WHERE reservation_id=$res_id");
    
    log_activity($conn, $uid, "Tenant Moved", "Moved to temporary room ID $target_room by $admin_username");
    
    trigger_update($conn);
    header("Location: admin_maintenance.php?msg=moved");
    exit;
}

// Handle Return Tenant
if(isset($_POST['return_tenant'])){
    $move_id = (int)$_POST['move_id'];
    $m_q = mysqli_query($conn, "SELECT * FROM temporary_moves WHERE id=$move_id");
    $move = mysqli_fetch_assoc($m_q);
    
    if($move){
        $res_id = $move['reservation_id'];
        $orig_room = $move['original_room_id'];
        
        mysqli_query($conn, "UPDATE reservations SET room_id=$orig_room WHERE reservation_id=$res_id");
        mysqli_query($conn, "UPDATE temporary_moves SET status='Returned' WHERE id=$move_id");
        
        $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$res_id");
        $uid = mysqli_fetch_assoc($u_q)['user_id'];
        log_activity($conn, $uid, "Tenant Returned", "Returned to original room ID $orig_room by $admin_username");
    }
    trigger_update($conn);
    header("Location: admin_maintenance.php?msg=returned");
    exit;
}

// Handle Status Update
if(isset($_POST['update_request'])){
    $req_id = (int)$_POST['request_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $sched_date = !empty($_POST['scheduled_date']) ? "'".mysqli_real_escape_string($conn, $_POST['scheduled_date'])."'" : "NULL";
    $cost = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
    $charge_user = isset($_POST['charge_user']);
    
    $req_q = mysqli_query($conn, "SELECT user_id, description FROM maintenance_requests WHERE request_id=$req_id");
    $req_data = mysqli_fetch_assoc($req_q);
    $uid = $req_data['user_id'];
    
    if($uid) log_activity($conn, $uid, "Maintenance Update", "Request #$req_id updated to '$status' by $admin_username");
    
    mysqli_query($conn, "UPDATE maintenance_requests SET status='$status', scheduled_date=$sched_date, cost='$cost' WHERE request_id=$req_id");
    
    // Handle Billing
    if($charge_user && $cost > 0 && $uid){
        $res_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$uid AND status='Approved' ORDER BY end_date DESC LIMIT 1");
        if($res_row = mysqli_fetch_assoc($res_q)){
            $rid = $res_row['reservation_id'];
            $desc = "Maintenance Fee: " . $req_data['description'];
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($stmt, "ids", $rid, $cost, $desc);
            mysqli_stmt_execute($stmt);
            send_notification($conn, $uid, "🧾 <strong>New Bill</strong><br>A maintenance fee of ₱".number_format($cost,2)." has been added to your account.", "Billing");
        }
    }
    
    trigger_update($conn);
    header("Location: admin_maintenance.php");
    exit;
}

// Handle Manual Schedule (Room Based)
if(isset($_POST['schedule_maintenance'])){
    $room_id = (int)$_POST['room_id'];
    $sched_date = mysqli_real_escape_string($conn, $_POST['scheduled_date']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Try to link to current tenant if exists, otherwise NULL
    $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE room_id=$room_id AND status='Approved' LIMIT 1");
    $uid = ($u_row = mysqli_fetch_assoc($u_q)) ? $u_row['user_id'] : 'NULL';
    
    $sql = "INSERT INTO maintenance_requests (user_id, room_id, description, status, scheduled_date, cost) VALUES ($uid, $room_id, '$desc', 'Scheduled', '$sched_date', '$standard_maint_price')";
    mysqli_query($conn, $sql);
    
    trigger_update($conn);
    header("Location: admin_maintenance.php?msg=scheduled");
    exit;
}

// Handle Auto Schedule (Preventive Maintenance for All Rooms)
if(isset($_POST['auto_schedule_maintenance'])){
    $sched_date = mysqli_real_escape_string($conn, $_POST['auto_date']);
    $desc = "Routine Preventive Maintenance";
    $room_ids = isset($_POST['room_ids']) ? explode(',', $_POST['room_ids']) : [];
    
    $where = "is_archived=0";
    if(!empty($room_ids) && $room_ids[0] != "") {
        $ids = implode(',', array_map('intval', $room_ids));
        $where .= " AND room_id IN ($ids)";
    }
    
    $rooms = mysqli_query($conn, "SELECT room_id FROM rooms WHERE $where");
    while($r = mysqli_fetch_assoc($rooms)){
        $rid = $r['room_id'];
        mysqli_query($conn, "INSERT INTO maintenance_requests (user_id, room_id, description, status, scheduled_date, cost) VALUES (NULL, $rid, '$desc', 'Scheduled', '$sched_date', '$standard_maint_price')");
    }
    trigger_update($conn);
    header("Location: admin_maintenance.php");
    exit;
}

// Fetch All Requests
$query = "SELECT m.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name, r.room_number 
          FROM maintenance_requests m 
          LEFT JOIN users u ON m.user_id = u.user_id 
          LEFT JOIN rooms r ON m.room_id = r.room_id 
          WHERE m.status NOT IN ('Completed', 'Cancelled')
          ORDER BY FIELD(m.status, 'Pending', 'Scheduled'), m.created_at DESC";
$requests = mysqli_query($conn, $query);
$pending_reqs = [];
$scheduled_reqs = [];
while($row = mysqli_fetch_assoc($requests)){
    if($row['status'] == 'Pending') $pending_reqs[] = $row;
    else $scheduled_reqs[] = $row;
}
$groups = ['Pending' => $pending_reqs, 'Scheduled' => $scheduled_reqs];

// Fetch available rooms for modal
$avail_rooms_q = mysqli_query($conn, "SELECT * FROM rooms WHERE status != 'Maintenance'");
$room_options = "";
while($ar = mysqli_fetch_assoc($avail_rooms_q)){
    $rid = $ar['room_id'];
    $capacity = $ar['total_beds'];
    $occ_q = mysqli_query($conn, "SELECT COUNT(*) as occupied FROM reservations WHERE room_id=$rid AND status IN ('Pending','Approved')");
    $occupied = mysqli_fetch_assoc($occ_q)['occupied'];
    
    if($occupied < $capacity){
        $slots = $capacity - $occupied;
        $room_options .= "<option value='".$ar['room_id']."'>".$ar['room_name']." (".$ar['room_type'].") - $slots beds free</option>";
    }
}

// Fetch Rooms for Visual Picker
$rooms_inventory = get_all_rooms_with_occupancy($conn);
$grouped_rooms = [];
foreach ($rooms_inventory as $room) {
    $grouped_rooms[$room['room_type']][] = $room;
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
    <title>Maintenance Management | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-req { transition: transform 0.2s; cursor: default; border: 1px solid rgba(0,0,0,0.05); }
        .card-req:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .req-header { background: #f8f9fa; border-bottom: 1px solid #eee; padding: 15px; }
        .req-body { padding: 15px; }
        .status-dot { height: 10px; width: 10px; background-color: #bbb; border-radius: 50%; display: inline-block; margin-right: 5px; }
        
        /* Visual Room Picker Styles */
        .room-select-card { cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
        .room-select-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .room-select-card.selected { border-color: var(--primary-green); background-color: #e8f5e9; }
        .room-select-card img { height: 100px; object-fit: cover; width: 100%; border-radius: 5px 5px 0 0; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h1>Maintenance Management</h1>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#priceSettingsModal"><i class="fas fa-tags me-2"></i>Set Standard Price</button>
                </div>
            </div>

    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>

    <h5 class="fw-bold text-secondary mb-3">Maintenance</h5>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-room card-room-summary h-100" data-bs-toggle="modal" data-bs-target="#autoScheduleModal">
                <div class="card-body text-center p-5">
                    <i class="fas fa-magic text-primary fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2">Auto-Schedule</h3>
                    <p class="text-muted small mb-3">Generate preventive maintenance for multiple rooms</p>
                    <div class="alert alert-light border py-2 mb-0 fw-bold text-primary">
                        <i class="fas fa-plus-circle me-1"></i> Start Auto-Schedule
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-room card-room-summary h-100" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                <div class="card-body text-center p-5">
                    <i class="fas fa-calendar-plus text-success fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2">Schedule Maintenance</h3>
                    <p class="text-muted small mb-3">Create a specific maintenance request for a room</p>
                    <div class="alert alert-light border py-2 mb-0 fw-bold text-success">
                        <i class="fas fa-plus me-1"></i> Create Schedule
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="fw-bold text-secondary mb-3">Maintenance Requests</h5>
    <div class="row g-4">
        <?php foreach($groups as $status => $items): 
            $count = count($items);
            $icon = $status == 'Pending' ? 'fa-clock text-warning' : 'fa-calendar-check text-primary';
            $bg = $status == 'Pending' ? 'bg-warning' : 'bg-primary';
        ?>
        <div class="col-md-6">
            <div class="card card-room card-room-summary h-100" onclick="openGroupModal('<?= $status ?>')">
                <div class="card-body text-center p-5">
                    <i class="fas <?= $icon ?> fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2"><?= $status ?> Requests</h3>
                    <div class="d-flex justify-content-center gap-3 text-muted small mb-3">
                        <span><i class="fas fa-tools me-1"></i> <?= $count ?> Items</span>
                    </div>
                    <div class="alert alert-light border py-2 mb-0 fw-bold text-dark">
                        View <?= $status ?> List
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

        </div>
    </div>
</div>

<!-- Group Modals -->
<?php foreach($groups as $status => $items): ?>
<div class="modal fade" id="modal_<?= $status ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-tools me-2"></i><?= $status ?> Maintenance Requests</h5>
                <div class="ms-auto me-3">
                    <input type="text" class="form-control form-control-sm" placeholder="Filter by Room..." onkeyup="filterMaintenance(this, 'list_<?= $status ?>')">
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4" id="list_<?= $status ?>">
                    <?php foreach($items as $row): 
                        // Check for active reservation and move status
                        $uid = $row['user_id'] ?? 0;
                        $res_check = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$uid AND status='Approved'");
                        $active_res = mysqli_fetch_assoc($res_check);
                        $move_btn = "";

                        if($active_res){
                            $rid = $active_res['reservation_id'];
                            $mv_check = mysqli_query($conn, "SELECT id, temp_room_id FROM temporary_moves WHERE reservation_id=$rid AND status='Active'");
                            $active_move = mysqli_fetch_assoc($mv_check);
                            
                            if($active_move){
                                $move_btn = '<button type="button" class="btn btn-sm btn-warning w-100 mt-1" onclick="confirmReturn('.$active_move['id'].')" title="Return Tenant"><i class="fas fa-undo me-1"></i> Return Tenant</button>';
                            } else {
                                $move_btn = '<button type="button" class="btn btn-sm btn-info text-white w-100 mt-1" onclick="openMoveModal('.$rid.', \''.addslashes($row['full_name']).'\')" title="Move Tenant"><i class="fas fa-exchange-alt me-1"></i> Move Tenant</button>';
                            }
                        }
                        $search_tags = strtolower((!empty($row['room_number']) ? 'Room ' . $row['room_number'] : $row['room_name']) . ' ' . ($row['room_number'] ?? '') . ' ' . $row['room_name']);
                    ?>
                    <div class="col-md-6 col-lg-4 maintenance-item" data-search="<?= htmlspecialchars($search_tags) ?>">
                        <div class="card card-req h-100 border-0 shadow-sm">
                            <div class="req-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success"><i class="fas fa-door-open me-1"></i> <?= !empty($row['room_number']) ? 'Room ' . htmlspecialchars($row['room_number']) : htmlspecialchars($row['room_name']) ?></span>
                                <small class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                            </div>
                            <div class="req-body d-flex flex-column h-100">
                                <div class="mb-3">
                                    <div class="fw-bold text-dark"><?= $row['full_name'] ? $row['full_name'] : '<span class="text-muted fst-italic">Room Maintenance</span>' ?></div>
                                    <p class="small text-muted mb-0 mt-1"><?= $row['description'] ?></p>
                                </div>
                                
                                <div class="mt-auto">
                                    <form method="POST" id="form_<?= $row['request_id'] ?>">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <div class="mb-2">
                                            <label class="small fw-bold text-muted">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                                <option value="Scheduled" <?= $row['status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                                                <option value="Completed" <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                                                <option value="Cancelled" <?= $row['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="small fw-bold text-muted">Scheduled Date</label>
                                            <input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= $row['scheduled_date'] ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="small fw-bold text-muted">Cost (₱)</label>
                                            <input type="number" step="0.01" name="cost" class="form-control form-control-sm" value="<?= $row['cost'] > 0 ? $row['cost'] : $standard_maint_price ?>">
                                        </div>
                                        <?php if($row['user_id']): ?>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="charge_user" id="charge_<?= $row['request_id'] ?>">
                                            <label class="form-check-label small" for="charge_<?= $row['request_id'] ?>">Charge to Tenant</label>
                                        </div>
                                        <?php endif; ?>
                                        <button type="submit" name="update_request" class="btn btn-sm btn-success w-100"><i class="fas fa-save me-1"></i> Update</button>
                                    </form>
                                    <?= $move_btn ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($items)): ?>
                        <div class="col-12 text-center py-5 text-muted">No requests in this category.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g. AC Repair, Plumbing" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Scheduled Date</label>
                            <input type="date" name="scheduled_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold text-secondary mb-0">Select Room</h6>
                            <small class="text-primary fw-bold" id="selected_tenant_display" style="display:none;"></small>
                        </div>
                        <div class="d-flex align-items-center">
                            <label class="small fw-bold me-2 text-muted">Filter:</label>
                            <select class="form-select form-select-sm" style="width: 120px;" onchange="filterRooms(this, 'scheduleRoomContainer')">
                                <option value="all">All Floors</option>
                                <?php for($i=2; $i<=7; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="room_id" id="selected_room_id" required>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-pills mb-3" id="roomTabs" role="tablist">
                        <?php $first=true; foreach($grouped_rooms as $type => $rooms): $tid = md5($type); ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $first?'active':'' ?>" id="tab-<?=$tid?>" data-bs-toggle="pill" data-bs-target="#content-<?=$tid?>" type="button"><?=$type?></button>
                        </li>
                        <?php $first=false; endforeach; ?>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="scheduleRoomContainer">
                        <?php $first=true; foreach($grouped_rooms as $type => $rooms): $tid = md5($type); ?>
                        <div class="tab-pane fade <?= $first?'show active':'' ?>" id="content-<?=$tid?>" role="tabpanel">
                            <div class="row g-3">
                                <?php foreach($rooms as $room): 
                                    $t_names = [];
                                    if(!empty($room['occupants'])) { foreach($room['occupants'] as $occ) $t_names[] = $occ['full_name']; }
                                    $t_str = !empty($t_names) ? implode(", ", $t_names) : "Vacant";
                                ?>
                                <div class="col-md-4 col-lg-3 room-item-filter" data-floor="<?= $room['floor'] ?>">
                                    <div class="card room-select-card h-100" onclick="selectRoom(this, <?= $room['room_id'] ?>)" data-tenants="<?= htmlspecialchars($t_str, ENT_QUOTES) ?>">
                                        <img src="../assets/images/<?= $room['image'] ?>">
                                        <div class="card-body p-2 text-center">
                                            <div class="fw-bold small"><?= !empty($room['room_number']) ? 'Room ' . htmlspecialchars($room['room_number']) : htmlspecialchars($room['room_name']) ?></div>
                                            <div class="badge bg-light text-dark border mt-1"><?= $room['floor'] ?>F</div>
                                            <?php if($room['availability'] == 'Maintenance'): ?>
                                                <div class="badge bg-danger mt-1">Maintenance</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $first=false; endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <span id="selection_msg" class="text-danger me-auto small fw-bold" style="display:none;">Please select a room</span>
                    <button type="submit" name="schedule_maintenance" class="btn btn-success">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Auto Schedule Modal -->
<div class="modal fade" id="autoScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title fw-bold"><i class="fas fa-magic me-2"></i>Auto-Schedule Preventive Maintenance</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <p class="mb-0 me-3">Select rooms... <span class="badge bg-primary"><span id="selected_count">0</span> Selected</span></p>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllAuto()">Select All</button>
                        </div>
                        <div class="d-flex align-items-center">
                            <label class="small fw-bold me-2 text-muted">Filter:</label>
                            <select class="form-select form-select-sm" style="width: 120px;" onchange="filterRooms(this, 'autoRoomContainer')">
                                <option value="all">All Floors</option>
                                <?php for($i=2; $i<=7; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="room_ids" id="auto_room_ids">

                    <!-- Tabs -->
                    <ul class="nav nav-pills mb-3" id="autoRoomTabs" role="tablist">
                        <?php $first=true; foreach($grouped_rooms as $type => $rooms): $tid = md5($type . '_auto'); ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $first?'active':'' ?>" id="tab-<?=$tid?>" data-bs-toggle="pill" data-bs-target="#content-<?=$tid?>" type="button"><?=$type?></button>
                        </li>
                        <?php $first=false; endforeach; ?>
                    </ul>

                    <div class="tab-content" id="autoRoomContainer">
                        <?php $first=true; foreach($grouped_rooms as $type => $rooms): $tid = md5($type . '_auto'); ?>
                        <div class="tab-pane fade <?= $first?'show active':'' ?>" id="content-<?=$tid?>" role="tabpanel">
                            <div class="row g-3">
                                <?php foreach($rooms as $room): ?>
                                <div class="col-md-4 col-lg-3 room-item-filter" data-floor="<?= $room['floor'] ?>">
                                    <div class="card card-req h-100 border-0 shadow-sm auto-select-card" onclick="toggleAutoSelect(this, <?= $room['room_id'] ?>)" style="cursor: pointer; transition: transform 0.2s;">
                                        <div class="req-header d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-success"><i class="fas fa-door-open me-1"></i> <?= !empty($room['room_number']) ? 'Room ' . htmlspecialchars($room['room_number']) : htmlspecialchars($room['room_name']) ?></span>
                                            <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                        </div>
                                        <div class="req-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?= $room['room_type'] ?></small>
                                                <i class="fas fa-check-circle text-secondary select-icon fa-lg"></i>
                                            </div>
                                            <hr class="my-2">
                                            <div class="small text-muted">
                                                <?php if($room['availability'] == 'Maintenance'): ?>
                                                    <span class="text-danger"><i class="fas fa-tools me-1"></i> Maintenance</span>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="fas fa-check me-1"></i> Available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $first=false; endforeach; ?>
                    </div>
                    <div class="mt-4 mb-3"><label class="form-label fw-bold">Select Date</label><input type="date" name="auto_date" class="form-control" min="<?= date('Y-m-d') ?>" required></div></div>
                <div class="modal-footer"><button type="submit" name="auto_schedule_maintenance" class="btn btn-primary">Generate Schedule</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Move Tenant Modal -->
<div class="modal fade" id="moveTenantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Move Tenant Temporarily</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Select a temporary room for <strong id="moveTenantName"></strong>.</p>
                    <input type="hidden" name="reservation_id" id="moveReservationId">
                    <div class="mb-3">
                        <label class="form-label">Target Room</label>
                        <select name="target_room_id" class="form-select" required>
                            <?php echo $room_options; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="move_tenant" class="btn btn-primary">Move Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Tenant Confirmation Modal -->
<div class="modal fade" id="returnTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: var(--primary-green);">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i>Confirm Return</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to return this tenant to their original room?</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom px-4" id="confirmReturnBtn">Yes, Return</button>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<!-- Hidden Return Form -->
<form method="POST" id="returnForm" style="display:none;">
    <input type="hidden" name="move_id" id="returnMoveId">
    <input type="hidden" name="return_tenant" value="1">
</form>

<!-- Price Settings Modal -->
<div class="modal fade" id="priceSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-tags me-2"></i>Maintenance Price Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Standard Maintenance Fee (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" name="price_maintenance_standard" class="form-control" value="<?= $standard_maint_price ?>" required>
                        </div>
                        <small class="text-muted">This price will be pre-filled when updating pending requests.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_maintenance_price" class="btn btn-success">Save Price</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function openGroupModal(status) {
    new bootstrap.Modal(document.getElementById('modal_' + status)).show();
}

function openMoveModal(resId, name) {
    document.getElementById('moveReservationId').value = resId;
    document.getElementById('moveTenantName').innerText = name;
    var myModal = new bootstrap.Modal(document.getElementById('moveTenantModal'));
    myModal.show();
}

let moveIdToReturn = null;

function confirmReturn(moveId) {
    moveIdToReturn = moveId;
    var myModal = new bootstrap.Modal(document.getElementById('returnTenantModal'));
    myModal.show();
}

document.getElementById('confirmReturnBtn').addEventListener('click', function() {
    if(moveIdToReturn) {
        document.getElementById('returnMoveId').value = moveIdToReturn;
        document.getElementById('returnForm').submit();
    }
});

function selectRoom(card, id) {
    document.querySelectorAll('.room-select-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selected_room_id').value = id;
    document.getElementById('selection_msg').style.display = 'none';
    
    const tenants = card.getAttribute('data-tenants');
    const display = document.getElementById('selected_tenant_display');
    if(display) {
        display.style.display = 'block';
        display.innerHTML = '<i class="fas fa-user-tag me-1"></i> Tenant(s): ' + tenants;
    }
}

function validateSelection() {
    if(!document.getElementById('selected_room_id').value) {
        document.getElementById('selection_msg').style.display = 'block';
        return false;
    }
    return true;
}

function toggleAutoSelect(card, id) {
    card.classList.toggle('selected');
    card.classList.toggle('border-primary');
    
    const icon = card.querySelector('.select-icon');
    if(card.classList.contains('selected')) {
        icon.classList.replace('text-secondary', 'text-primary');
    } else {
        icon.classList.replace('text-primary', 'text-secondary');
    }
    updateAutoIds();
}

function selectAllAuto() {
    document.querySelectorAll('.auto-select-card').forEach(card => {
        if(!card.classList.contains('selected')) card.click();
    });
}

function updateAutoIds() {
    const ids = [];
    document.querySelectorAll('.auto-select-card.selected').forEach(card => {
        // Extract ID from onclick attribute or add data-id
        const onclick = card.getAttribute('onclick');
        const id = onclick.match(/\d+/)[0];
        ids.push(id);
    });
    document.getElementById('auto_room_ids').value = ids.join(',');
    document.getElementById('selected_count').innerText = ids.length;
}

function filterRooms(select, containerId) {
    const floor = select.value;
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.room-item-filter');
    
    items.forEach(item => {
        if (floor === 'all' || item.getAttribute('data-floor') === floor) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Toast Notification for Actions
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'returned'): ?>
Toast.fire({ icon: 'success', title: 'Tenant returned to original room successfully' });
<?php endif; ?>

// Auto Refresh Logic
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

function filterMaintenance(input, containerId) {
    const filter = input.value.toLowerCase();
    const container = document.getElementById(containerId);
    const items = container.getElementsByClassName('maintenance-item');
    
    for (let i = 0; i < items.length; i++) {
        const searchData = items[i].getAttribute('data-search');
        if (searchData && searchData.includes(filter)) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>
</body>
</html>