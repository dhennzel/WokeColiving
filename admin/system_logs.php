<?php
session_start();
include("../db.php");

// ADD THIS LINE HERE:
$current_page = basename($_SERVER['PHP_SELF']);

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}
// ... rest of your code ...

// Filter Logic
$where = "1=1";
if(isset($_GET['search']) && !empty($_GET['search'])){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (u.last_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR l.action LIKE '%$search%' OR l.details LIKE '%$search%')";
}
if(isset($_GET['action_filter']) && !empty($_GET['action_filter'])){
    $act = mysqli_real_escape_string($conn, $_GET['action_filter']);
    $where .= " AND l.action = '$act'";
}

// Fetch Logs with Filter
$logs = mysqli_query($conn, "SELECT l.*, l.role as performer_role, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE $where ORDER BY l.created_at DESC LIMIT 100");

// Get distinct actions for dropdown
$actions_q = mysqli_query($conn, "SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");

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
    <title>System Logs | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>System Activity Logs</h1>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-3">
                    <select name="action_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        <?php while($ac = mysqli_fetch_assoc($actions_q)): ?>
                            <option value="<?= $ac['action'] ?>" <?= (isset($_GET['action_filter']) && $_GET['action_filter'] == $ac['action']) ? 'selected' : '' ?>><?= $ac['action'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search user, action, or details..." value="<?= $_GET['search'] ?? '' ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-custom w-100"><i class="fas fa-search me-2"></i>Filter</button></div>
                <div class="col-md-2"><a href="system_logs.php" class="btn btn-outline-secondary w-100">Reset</a></div>
            </form>

            <div class="card card-table p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead><tr><th>Date</th><th>Affected User</th><th>Action</th><th>Details</th><th>Performed By</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($logs)): ?>
                            <tr>
                                <td class="text-muted small"><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                <td class="fw-bold">
                                    <?php if(!empty($row['full_name']) && $row['user_id'] > 0): ?>
                                        <a href="view_user.php?uid=<?= $row['user_id'] ?>" class="text-decoration-none text-primary" title="View Profile"><?= htmlspecialchars($row['full_name']) ?></a>
                                    <?php else: ?>
                                        System/Admin
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['action']) ?></span></td>
                                <td class="small text-secondary"><?= htmlspecialchars($row['details']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['performed_by'] ?? 'System') ?>
                                    <?php if(!empty($row['performer_role']) && in_array($row['performer_role'], ['Admin', 'Super Admin'])): ?>
                                        <span class="badge <?= ($row['performer_role'] == 'Super Admin') ? 'bg-danger' : 'bg-primary' ?> ms-1" style="font-size: 0.7rem;"><?= htmlspecialchars($row['performer_role']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($logs) == 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No logs found matching your criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
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

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) { lastUpdate = t; } 
        else if (t > lastUpdate) { sessionStorage.setItem('playNotifSound', 'true'); location.reload(); }
    });
}
setInterval(checkUpdates, 3000);

if(sessionStorage.getItem('playNotifSound') === 'true') {
    let audio = new Audio('../assets/sounds/notification.mp3');
    audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
    audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
    sessionStorage.removeItem('playNotifSound');
}
</script>
</body>
</html>