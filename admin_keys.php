<?php
session_start();
include("../db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

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
        trigger_update($conn);
        header("Location: admin_keys.php?msg=returned");
        exit;
    }
}

// Fetch Keys
$keys_q = mysqli_query($conn, "
    SELECT k.*, 
           kt.id as trans_id, kt.user_id, kt.released_at, 
           CONCAT(u.last_name, ', ', u.first_name) as holder_name 
    FROM `keys` k 
    LEFT JOIN key_transactions kt ON k.id = kt.key_id AND kt.status = 'Active'
    LEFT JOIN users u ON kt.user_id = u.user_id
    ORDER BY k.type, k.key_name
");

// Fetch Users for Dropdown
$users_q = mysqli_query($conn, "SELECT user_id, CONCAT(last_name, ', ', first_name) as full_name FROM users WHERE role='user' ORDER BY last_name");

// Fetch History
$history_q = mysqli_query($conn, "
    SELECT kt.*, k.key_name, k.type, CONCAT(u.last_name, ', ', u.first_name) as full_name 
    FROM key_transactions kt 
    JOIN `keys` k ON kt.key_id = k.id 
    JOIN users u ON kt.user_id = u.user_id 
    ORDER BY kt.released_at DESC LIMIT 50
");

// Sidebar counts
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
    <title>Key Monitoring | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        #wrapper { display: flex; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
            <a href="admin_waitlist.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
            <a href="admin_deletion_requests.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            <a href="admin_room_occupancy.php" class="sidebar-link"><i class="fas fa-users me-2"></i>Room Occupancy</a>
            <a href="admin_parking.php" class="sidebar-link"><i class="fas fa-parking me-2"></i>Parkings</a>
            <a href="admin_keys.php" class="sidebar-link active"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-tools me-2"></i>Utilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>
            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-cog me-2"></i>Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu"><a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a><a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a></div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4">
            <h4 class="fw-bold mb-4" style="color: var(--dark-green);">Key Monitoring System</h4>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= $_GET['msg'] == 'released' ? 'Key released successfully.' : 'Key returned successfully.' ?></div>
            <?php endif; ?>

            <!-- Key Status -->
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-secondary mb-3">Key Status</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Key Name</th><th>Type</th><th>Status</th><th>Current Holder</th><th>Released At</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($keys_q)): ?>
                            <tr>
                                <td class="fw-bold"><?= $row['key_name'] ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $row['type'] ?></span></td>
                                <td>
                                    <?php if($row['status'] == 'Available'): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Released</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['holder_name'] ? $row['holder_name'] : '-' ?></td>
                                <td><?= $row['released_at'] ? date('M d, h:i A', strtotime($row['released_at'])) : '-' ?></td>
                                <td class="text-end">
                                    <?php if($row['status'] == 'Available'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="openReleaseModal(<?= $row['id'] ?>, '<?= addslashes($row['key_name']) ?>')">Release Key</button>
                                    <?php else: ?>
                                        <a href="?action=return&id=<?= $row['trans_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Mark this key as returned?')">Return Key</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- History Log -->
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-secondary mb-3">Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small">
                        <thead><tr><th>Date Released</th><th>Key</th><th>Holder</th><th>Date Returned</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while($h = mysqli_fetch_assoc($history_q)): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($h['released_at'])) ?></td>
                                <td><?= $h['key_name'] ?></td>
                                <td><?= $h['full_name'] ?></td>
                                <td><?= $h['returned_at'] ? date('M d, Y h:i A', strtotime($h['returned_at'])) : '-' ?></td>
                                <td><?= $h['status'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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
                    <input type="hidden" name="key_id" id="modalKeyId">
                    <input type="hidden" name="release_key" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php while($u = mysqli_fetch_assoc($users_q)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= $u['full_name'] ?></option>
                            <?php endwhile; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openReleaseModal(id, name) {
    document.getElementById('modalKeyId').value = id;
    document.getElementById('modalKeyName').innerText = name;
    new bootstrap.Modal(document.getElementById('releaseModal')).show();
}
</script>
</body>
</html>