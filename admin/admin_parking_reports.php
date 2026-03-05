<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Total Parking Earnings
$total_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE description LIKE '%Parking Fee%' AND payment_status='Paid'");
$total_earnings = mysqli_fetch_assoc($total_q)['total'] ?? 0;

// Earnings by Slot Type
$type_q = mysqli_query($conn, "
    SELECT 
        ps.slot_type, 
        SUM(p.amount) as earnings
    FROM payments p
    JOIN parking_reservations pr ON SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, '(Parking ID: ', -1), ')', 1) = pr.id
    JOIN parking_slots ps ON pr.slot_id = ps.id
    WHERE p.description LIKE '%Parking Fee%' AND p.payment_status='Paid'
    GROUP BY ps.slot_type
");
$type_data = [];
while ($row = mysqli_fetch_assoc($type_q)) {
    $type_data[] = $row;
}

// Monthly Trends
$monthly_data = [];
$m_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total 
    FROM payments 
    WHERE description LIKE '%Parking Fee%' AND payment_status='Paid' 
    GROUP BY month ORDER BY month ASC
");
while ($row = mysqli_fetch_assoc($m_query)) {
    $monthly_data[$row['month']] = $row['total'];
}

// Continuous timeline for chart
$chart_data = [];
if (!empty($monthly_data)) {
    $start = new DateTime(min(array_keys($monthly_data)) . '-01');
    $end = new DateTime(date('Y-m-01'));
    $end->modify('+1 month');
    $period = new DatePeriod($start, new DateInterval('P1M'), $end);

    foreach ($period as $dt) {
        $ym = $dt->format("Y-m");
        $chart_data[] = [
            'month' => $ym,
            'earnings' => $monthly_data[$ym] ?? 0
        ];
    }
}

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
    <title>Parking Reports | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            </div>

            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5 active"><i class="fas fa-parking me-2"></i>Parkings</a>
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
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
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
        <div class="container-fluid px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Parking Revenue Report</h4>
                <a href="admin_parking.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Parking Mgt</a>
            </div>

            <div class="card card-stat p-4 mb-4 text-white" style="background-color: var(--primary-green);">
                <h5 class="card-title">Total Parking Earnings</h5>
                <h2 class="fw-bold">₱<?= number_format($total_earnings, 2) ?></h2>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white fw-bold py-3"><i class="fas fa-chart-line me-2 text-secondary"></i> Monthly Earnings Trend</div>
                        <div class="card-body"><canvas id="earningsChart" style="max-height: 350px;"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card card-stat h-100 p-4">
                        <h5 class="fw-bold mb-3 text-secondary">Earnings by Slot Type</h5>
                        <table class="table table-hover">
                            <thead><tr><th>Slot Type</th><th class="text-end">Earnings</th></tr></thead>
                            <tbody>
                                <?php foreach($type_data as $row): ?>
                                <tr>
                                    <td><?= $row['slot_type'] ?></td>
                                    <td class="text-end fw-bold">₱<?= number_format($row['earnings'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($type_data)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('earningsChart').getContext('2d');
const monthlyData = <?= json_encode($chart_data) ?>;

const labels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleString('default', { month: 'long', year: 'numeric' });
});
const earnings = monthlyData.map(item => item.earnings);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Parking Earnings (₱)',
            data: earnings,
            borderColor: '<?= $theme['primary'] ?>',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { callback: value => '₱' + value.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>