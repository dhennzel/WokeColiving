<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}

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
    <title>System Logs | Woke Coliving INC</title>
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
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        .card-table { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .table { font-size: 0.85rem; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { #sidebar-wrapper { margin-left: -250px; } #wrapper.toggled #sidebar-wrapper { margin-left: 0; } }
    </style>
</head>
<body>
<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving
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
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
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
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5 active"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px;" class="rounded-circle shadow-sm"></a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">System Activity Logs</h4>
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
                                <td class="fw-bold"><?= $row['full_name'] ? htmlspecialchars($row['full_name']) : 'System/Admin' ?></td>
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
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });
    document.getElementById("sidebar-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });

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