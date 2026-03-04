<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$message = "";
$error = "";

// Handle Status Update
if(isset($_POST['update_request'])){
    $req_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $sched_date = !empty($_POST['scheduled_date']) ? "'".$_POST['scheduled_date']."'" : "NULL";
    
    mysqli_query($conn, "UPDATE housekeeping_requests SET status='$status', scheduled_date=$sched_date WHERE request_id=$req_id");
    trigger_update($conn);
    header("Location: admin_housekeeping.php");
    exit;
}

// Handle Auto Schedule Weekly (All Occupied Rooms)
if(isset($_POST['auto_schedule_weekly'])){
    $sched_date = $_POST['auto_date'];
    $desc = "Weekly Routine Cleaning";
    
    $active_res = mysqli_query($conn, "SELECT user_id, room_id FROM reservations WHERE status='Approved'");
    $count = 0;
    while($row = mysqli_fetch_assoc($active_res)){
        $uid = $row['user_id'];
        $rid = $row['room_id'];
        
        // Avoid duplicates for same date/room
        $chk = mysqli_query($conn, "SELECT request_id FROM housekeeping_requests WHERE room_id=$rid AND scheduled_date='$sched_date' AND status!='Cancelled'");
        if(mysqli_num_rows($chk) == 0){
            $stmt = mysqli_prepare($conn, "INSERT INTO housekeeping_requests (user_id, room_id, description, status, scheduled_date) VALUES (?, ?, ?, 'Scheduled', ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $uid, $rid, $desc, $sched_date);
            mysqli_stmt_execute($stmt);
            send_notification($conn, $uid, "🧹 <strong>Weekly Cleaning</strong><br>Routine housekeeping scheduled for " . date('M d, Y', strtotime($sched_date)) . ".", "Housekeeping");
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
    
    // Find a user in this room to attach the request to (Required by DB schema)
    $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE room_id=$room_id AND status='Approved' LIMIT 1");
    if($u_row = mysqli_fetch_assoc($u_q)){
        $uid = $u_row['user_id'];
        $stmt = mysqli_prepare($conn, "INSERT INTO housekeeping_requests (user_id, room_id, description, status, scheduled_date) VALUES (?, ?, ?, 'Scheduled', ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $uid, $room_id, $desc, $sched_date);
        mysqli_stmt_execute($stmt);
        trigger_update($conn);
        $message = "Routine cleaning scheduled successfully (Free).";
        send_notification($conn, $uid, "🧹 <strong>Housekeeping Scheduled</strong><br>Admin has scheduled a routine cleaning for your room on " . date('M d, Y', strtotime($sched_date)) . ".", "Housekeeping");
    } else {
        $error = "No active tenant found in selected room to link request.";
    }
}

// Fetch All Requests
$query = "SELECT h.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name 
          FROM housekeeping_requests h 
          JOIN users u ON h.user_id = u.user_id 
          LEFT JOIN rooms r ON h.room_id = r.room_id 
          WHERE h.status NOT IN ('Completed', 'Cancelled')
          ORDER BY FIELD(h.status, 'Pending', 'Scheduled'), h.created_at DESC";
$requests = mysqli_query($conn, $query);

// Fetch Occupied Rooms for Dropdown
$rooms_q = mysqli_query($conn, "SELECT DISTINCT r.room_id, r.room_name, r.floor FROM rooms r JOIN reservations res ON r.room_id = res.room_id WHERE res.status = 'Approved' ORDER BY r.floor, r.room_name");

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];

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
        
        .card-table { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
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
            <a href="booking_management.php" class="sidebar-link d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                <?php if($pending_res > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $pending_res ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_waitlist.php" class="sidebar-link d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list-ol me-2"></i>Waitlist</span>
                <?php if($waitlist_count > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            <a href="admin_keys.php" class="sidebar-link"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 active d-flex justify-content-between align-items-center">
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
            </a>
            <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Housekeeping Management</h4>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#autoScheduleModal"><i class="fas fa-magic me-2"></i>Auto-Schedule Weekly</button>
            <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#scheduleModal"><i class="fas fa-calendar-plus me-2"></i>Schedule Cleaning</button>
        </div>
    </div>
    
    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="card card-table p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Room</th>
                        <th>Service Details</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($requests)) { ?>
                    <tr>
                        <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                        <td class="fw-bold"><?= $row['full_name'] ?></td>
                        <td class="fw-bold" style="color: var(--primary-green);"><?= $row['room_name'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <?php if($row['status'] == 'Completed') { ?>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td><?= $row['scheduled_date'] ?></td>
                            <td><button class="btn btn-sm btn-secondary" disabled><i class="fas fa-lock"></i></button></td>
                        <?php } else { ?>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                            <td>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                    <option value="Scheduled" <?= $row['status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                                    <option value="Completed" <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                                    <option value="Cancelled" <?= $row['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= $row['scheduled_date'] ?>">
                            </td>
                            <td>
                                <button type="submit" name="update_request" class="btn btn-sm btn-success"><i class="fas fa-save"></i></button>
                            </td>
                        </form>
                        <?php } ?>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Schedule Routine Cleaning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="text-muted small">This will create a free housekeeping schedule for the selected room.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Filter by Floor</label>
                        <select id="modalFloorFilter" class="form-select" onchange="filterModalRooms()">
                            <option value="all">All Floors</option>
                            <?php for($i=2; $i<=7; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Room</label>
                        <select name="room_id" id="modalRoomSelect" class="form-select" required>
                            <?php while($r = mysqli_fetch_assoc($rooms_q)): ?>
                                <option value="<?= $r['room_id'] ?>" data-floor="<?= $r['floor'] ?>"><?= $r['room_name'] ?> (Floor <?= $r['floor'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="schedule_cleaning" class="btn btn-success">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Auto Schedule Modal -->
<div class="modal fade" id="autoScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Auto-Schedule Weekly Cleaning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>This will schedule a "Weekly Routine Cleaning" for <strong>ALL occupied rooms</strong> on the selected date.</p>
                    <div class="mb-3">
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
<script>
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

function filterModalRooms() {
    const floor = document.getElementById('modalFloorFilter').value;
    const select = document.getElementById('modalRoomSelect');
    const options = select.querySelectorAll('option');
    
    options.forEach(opt => {
        if(floor === 'all' || opt.getAttribute('data-floor') === floor) {
            opt.hidden = false;
        } else {
            opt.hidden = true;
        }
    });
    select.value = ""; // Reset selection
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