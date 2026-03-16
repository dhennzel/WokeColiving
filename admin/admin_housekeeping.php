<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

$message = "";
$error = "";

// Ensure schema supports room-based requests and billing
mysqli_query($conn, "ALTER TABLE housekeeping_requests MODIFY COLUMN user_id INT NULL");
$chk_cost = mysqli_query($conn, "SHOW COLUMNS FROM housekeeping_requests LIKE 'cost'");
if(mysqli_num_rows($chk_cost) == 0) mysqli_query($conn, "ALTER TABLE housekeeping_requests ADD COLUMN cost DECIMAL(10,2) DEFAULT 0.00");

// Handle Status Update
if(isset($_POST['update_request'])){
    $req_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $sched_date = !empty($_POST['scheduled_date']) ? "'".$_POST['scheduled_date']."'" : "NULL";
    
    $cost = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
    $charge_user = isset($_POST['charge_user']);
    
    $req_q = mysqli_query($conn, "SELECT user_id, description FROM housekeeping_requests WHERE request_id=$req_id");
    $req_data = mysqli_fetch_assoc($req_q);
    $uid = $req_data['user_id'];
    
    if($uid) log_activity($conn, $uid, "Housekeeping Update", "Request #$req_id updated to '$status' by $admin_username");
    
    mysqli_query($conn, "UPDATE housekeeping_requests SET status='$status', scheduled_date=$sched_date, cost='$cost' WHERE request_id=$req_id");
    
    // Handle Billing
    if($charge_user && $cost > 0 && $uid){
        $res_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$uid AND status='Approved' ORDER BY end_date DESC LIMIT 1");
        if($res_row = mysqli_fetch_assoc($res_q)){
            $rid = $res_row['reservation_id'];
            $desc = "Housekeeping Fee: " . $req_data['description'];
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($stmt, "ids", $rid, $cost, $desc);
            mysqli_stmt_execute($stmt);
            send_notification($conn, $uid, "🧾 <strong>New Bill</strong><br>A housekeeping fee of ₱".number_format($cost,2)." has been added to your account.", "Billing");
        }
    }
    
    trigger_update($conn);
    header("Location: admin_housekeeping.php");
    exit;
}

// Handle Auto Schedule Weekly (All Rooms)
if(isset($_POST['auto_schedule_weekly'])){
    $sched_date = $_POST['auto_date'];
    $desc = "Weekly Routine Cleaning";
    $room_ids = isset($_POST['room_ids']) ? explode(',', $_POST['room_ids']) : [];
    
    $where = "is_archived=0";
    if(!empty($room_ids) && $room_ids[0] != "") {
        $ids = implode(',', array_map('intval', $room_ids));
        $where .= " AND room_id IN ($ids)";
    }
    
    $rooms = mysqli_query($conn, "SELECT room_id FROM rooms WHERE $where");
    $count = 0;
    while($row = mysqli_fetch_assoc($rooms)){
        $rid = $row['room_id'];
        // Try to find active tenant
        $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE room_id=$rid AND status='Approved' LIMIT 1");
        $uid = ($u_row = mysqli_fetch_assoc($u_q)) ? $u_row['user_id'] : 'NULL';
        
        // Avoid duplicates for same date/room
        $chk = mysqli_query($conn, "SELECT request_id FROM housekeeping_requests WHERE room_id=$rid AND scheduled_date='$sched_date' AND status!='Cancelled'");
        if(mysqli_num_rows($chk) == 0){
            $sql = "INSERT INTO housekeeping_requests (user_id, room_id, description, status, scheduled_date) VALUES ($uid, $rid, '$desc', 'Scheduled', '$sched_date')";
            mysqli_query($conn, $sql);
            if($uid != 'NULL') send_notification($conn, $uid, "🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for " . date('M d, Y', strtotime($sched_date)) . ".", "Housekeeping");
            $count++;
        }
    }
    trigger_update($conn);
    $message = "Weekly cleaning auto-scheduled for $count rooms.";
}

// Handle Admin Schedule (Free)
if(isset($_POST['schedule_cleaning'])){
    $room_id = (int)$_POST['room_id'];
    $sched_date = $_POST['scheduled_date'];
    $desc = "Routine Cleaning (Admin Scheduled)";
    
    // Find a user in this room to attach the request to (Optional now)
    $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE room_id=$room_id AND status='Approved' LIMIT 1");
    $uid = ($u_row = mysqli_fetch_assoc($u_q)) ? $u_row['user_id'] : 'NULL';
    
    $sql = "INSERT INTO housekeeping_requests (user_id, room_id, description, status, scheduled_date) VALUES ($uid, $room_id, '$desc', 'Scheduled', '$sched_date')";
    mysqli_query($conn, $sql);
    
    trigger_update($conn);
    $message = "Routine cleaning scheduled successfully.";
    if($uid != 'NULL') {
        log_activity($conn, $uid, "Housekeeping Scheduled", "Admin $admin_username scheduled cleaning for $sched_date");
        send_notification($conn, $uid, "🧹 <strong>Housekeeping Scheduled</strong><br>Admin has scheduled a routine cleaning for your room on " . date('M d, Y', strtotime($sched_date)) . ".", "Housekeeping");
    }
}

// Fetch All Requests
$query = "SELECT h.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name 
          FROM housekeeping_requests h 
          LEFT JOIN users u ON h.user_id = u.user_id 
          LEFT JOIN rooms r ON h.room_id = r.room_id 
          WHERE h.status NOT IN ('Completed', 'Cancelled')
          ORDER BY FIELD(h.status, 'Pending', 'Scheduled'), h.created_at DESC";
$requests = mysqli_query($conn, $query);
$pending_reqs = [];
$scheduled_reqs = [];
while($row = mysqli_fetch_assoc($requests)){
    if($row['status'] == 'Pending') $pending_reqs[] = $row;
    else $scheduled_reqs[] = $row;
}
$groups = ['Pending' => $pending_reqs, 'Scheduled' => $scheduled_reqs];

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
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Housekeeping Management | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-req { transition: transform 0.2s; cursor: default; border: 1px solid rgba(0,0,0,0.05); }
        .req-header { background: #f8f9fa; border-bottom: 1px solid #eee; padding: 15px; }
        .req-body { padding: 15px; }
        
        /* Visual Room Picker Styles */
        .room-select-card { cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
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
                <h1>Housekeeping Management</h1>
            </div>
    
    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <h5 class="fw-bold text-secondary mb-3">Housekeeping</h5>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-room card-room-summary h-100" data-bs-toggle="modal" data-bs-target="#autoScheduleModal">
                <div class="card-body text-center p-5">
                    <i class="fas fa-magic text-primary fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2">Auto-Schedule</h3>
                    <p class="text-muted small mb-3">Generate weekly cleaning schedule for multiple rooms</p>
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
                    <h3 class="fw-bold text-dark mb-2">Schedule Cleaning</h3>
                    <p class="text-muted small mb-3">Create a specific housekeeping request for a room</p>
                    <div class="alert alert-light border py-2 mb-0 fw-bold text-success">
                        <i class="fas fa-plus me-1"></i> Create Schedule
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="fw-bold text-secondary mb-3">Housekeeping Requests</h5>
    <div class="row g-4">
        <?php foreach($groups as $status => $items): 
            $count = count($items);
            $icon = $status == 'Pending' ? 'fa-clock text-warning' : 'fa-calendar-check text-primary';
        ?>
        <div class="col-md-6">
            <div class="card card-room card-room-summary h-100" onclick="openGroupModal('<?= $status ?>')">
                <div class="card-body text-center p-5">
                    <i class="fas <?= $icon ?> fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2"><?= $status ?> Requests</h3>
                    <div class="d-flex justify-content-center gap-3 text-muted small mb-3">
                        <span><i class="fas fa-broom me-1"></i> <?= $count ?> Items</span>
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
        </main>
    </div>
</div>

<!-- Group Modals -->
<?php foreach($groups as $status => $items): ?>
<div class="modal fade" id="modal_<?= $status ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-broom me-2"></i><?= $status ?> Housekeeping Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <?php foreach($items as $row): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-req h-100 border-0 shadow-sm">
                            <div class="req-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success"><i class="fas fa-door-open me-1"></i> <?= $row['room_name'] ?></span>
                                <small class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                            </div>
                            <div class="req-body d-flex flex-column h-100">
                                <div class="mb-3">
                                    <div class="fw-bold text-dark"><?= $row['full_name'] ? $row['full_name'] : '<span class="text-muted fst-italic">Room Cleaning</span>' ?></div>
                                    <p class="small text-muted mb-0 mt-1"><?= $row['description'] ?></p>
                                </div>
                                
                                <div class="mt-auto">
                                    <form method="POST">
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
                                            <input type="number" step="0.01" name="cost" class="form-control form-control-sm" value="<?= $row['cost'] ?>">
                                        </div>
                                        <?php if($row['user_id']): ?>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="charge_user" id="charge_<?= $row['request_id'] ?>">
                                            <label class="form-check-label small" for="charge_<?= $row['request_id'] ?>">Charge to Tenant</label>
                                        </div>
                                        <?php endif; ?>
                                        <button type="submit" name="update_request" class="btn btn-sm btn-success w-100"><i class="fas fa-save me-1"></i> Update</button>
                                    </form>
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
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>Schedule Routine Cleaning</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-secondary mb-0">Select Room</h6>
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
                                <?php foreach($rooms as $room): ?>
                                <div class="col-md-4 col-lg-3 room-item-filter" data-floor="<?= $room['floor'] ?>">
                                    <div class="card room-select-card h-100" onclick="selectRoom(this, <?= $room['room_id'] ?>)">
                                        <img src="../assets/images/<?= $room['image'] ?>">
                                        <div class="card-body p-2 text-center">
                                            <div class="fw-bold small"><?= $room['room_name'] ?></div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="schedule_cleaning" class="btn btn-success" onclick="return validateSelection()">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Auto Schedule Modal -->
<div class="modal fade" id="autoScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-magic me-2"></i>Auto-Schedule Weekly Cleaning</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
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
                                            <span class="fw-bold text-success"><i class="fas fa-door-open me-1"></i> <?= $room['room_name'] ?></span>
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

                    <div class="mt-4 mb-3">
                        <label class="form-label fw-bold">Select Date</label>
                        <input type="date" name="auto_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="auto_schedule_weekly" class="btn btn-primary">Generate Schedule</button>
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

function selectRoom(card, id) {
    document.querySelectorAll('.room-select-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selected_room_id').value = id;
    document.getElementById('selection_msg').style.display = 'none';
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
</script>
</body>
</html>