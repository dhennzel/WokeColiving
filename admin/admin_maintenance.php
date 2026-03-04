<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
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
    
    mysqli_query($conn, "INSERT INTO temporary_moves (reservation_id, original_room_id, temp_room_id) VALUES ($res_id, $orig_room, $target_room)");
    mysqli_query($conn, "UPDATE reservations SET room_id=$target_room WHERE reservation_id=$res_id");
    
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
    }
    trigger_update($conn);
    header("Location: admin_maintenance.php?msg=returned");
    exit;
}

// Handle Status Update
if(isset($_POST['update_request'])){
    $req_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $sched_date = !empty($_POST['scheduled_date']) ? "'".$_POST['scheduled_date']."'" : "NULL";
    
    mysqli_query($conn, "UPDATE maintenance_requests SET status='$status', scheduled_date=$sched_date WHERE request_id=$req_id");
    trigger_update($conn);
    header("Location: admin_maintenance.php");
    exit;
}

// Fetch All Requests
$query = "SELECT m.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name 
          FROM maintenance_requests m 
          JOIN users u ON m.user_id = u.user_id 
          LEFT JOIN rooms r ON m.room_id = r.room_id 
          WHERE m.status NOT IN ('Completed', 'Cancelled')
          ORDER BY FIELD(m.status, 'Pending', 'Scheduled'), m.created_at DESC";
$requests = mysqli_query($conn, $query);

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
    <title>Maintenance Management | Woke Coliving INC</title>
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
        
        .card-table { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        
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
                <a href="admin_maintenance.php" class="sidebar-link ps-5 active d-flex justify-content-between align-items-center">
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
    <div class="d-flex align-items-center mb-4">
        <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
        </a>
        <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Maintenance Requests</h4>
    </div>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='moved') echo "<div class='alert alert-success'>Tenant moved temporarily.</div>"; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg']=='full') echo "<div class='alert alert-danger'>Target room is full. Move failed.</div>"; ?>
    
    <div class="card card-table p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Room</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($requests)) { ?>
                    <?php
                        // Check for active reservation and move status
                        $uid = $row['user_id'];
                        $res_check = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$uid AND status='Approved'");
                        $active_res = mysqli_fetch_assoc($res_check);
                        $move_btn = "";

                        if($active_res){
                            $rid = $active_res['reservation_id'];
                            $mv_check = mysqli_query($conn, "SELECT id, temp_room_id FROM temporary_moves WHERE reservation_id=$rid AND status='Active'");
                            $active_move = mysqli_fetch_assoc($mv_check);
                            
                            if($active_move){
                                $move_btn = '<button type="button" class="btn btn-sm btn-warning" onclick="confirmReturn('.$active_move['id'].')" title="Return Tenant"><i class="fas fa-undo"></i></button>';
                            } else {
                                $move_btn = '<button type="button" class="btn btn-sm btn-info text-white" onclick="openMoveModal('.$rid.', \''.addslashes($row['full_name']).'\')" title="Move Tenant"><i class="fas fa-exchange-alt"></i></button>';
                            }
                        }
                    ?>
                    <tr>
                        <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                        <td class="fw-bold"><?= $row['full_name'] ?></td>
                        <td class="fw-bold" style="color: var(--primary-green);"><?= $row['room_name'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <?php if($row['status'] == 'Completed') { ?>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td><?= $row['scheduled_date'] ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-secondary" disabled><i class="fas fa-lock"></i></button>
                                    <?= $move_btn ?>
                                </div>
                            </td>
                        <?php } else { ?>
                            <td>
                                <select name="status" form="form_<?= $row['request_id'] ?>" class="form-select form-select-sm">
                                    <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                    <option value="Scheduled" <?= $row['status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                                    <option value="Completed" <?= $row['status']=='Completed'?'selected':'' ?>>Completed</option>
                                    <option value="Cancelled" <?= $row['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="scheduled_date" form="form_<?= $row['request_id'] ?>" class="form-control form-control-sm" value="<?= $row['scheduled_date'] ?>">
                            </td>
                            <td>
                                <form method="POST" id="form_<?= $row['request_id'] ?>" class="d-flex gap-2">
                                    <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                    <button type="submit" name="update_request" class="btn btn-sm btn-success"><i class="fas fa-save"></i></button>
                                    <?= $move_btn ?>
                                </form>
                            </td>
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
</div>

<!-- Hidden Return Form -->
<form method="POST" id="returnForm" style="display:none;">
    <input type="hidden" name="move_id" id="returnMoveId">
    <input type="hidden" name="return_tenant" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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