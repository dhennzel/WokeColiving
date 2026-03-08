<?php
session_start();
include("../db.php");
date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}

// Total Earnings
$total_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE payment_status='Paid'");
$total_earnings = mysqli_fetch_assoc($total_query)['total'] ?? 0;

// Earnings by Room Type
$type_query = mysqli_query($conn, "
    SELECT r.room_type, SUM(p.amount) as earnings, COUNT(DISTINCT res.reservation_id) as bookings
    FROM reservations res
    JOIN rooms r ON res.room_id = r.room_id
    JOIN payments p ON res.reservation_id = p.reservation_id
    WHERE p.payment_status='Paid'
    GROUP BY r.room_type
");
$room_type_data = [];
while($row = mysqli_fetch_assoc($type_query)){
    $room_type_data[] = $row;
}

// Earnings by Category (Rent, Utilities, Parking, etc.)
$cat_query = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN description LIKE '%Parking%' THEN 'Parking'
            WHEN description LIKE '%Utility%' THEN 'Utilities'
            WHEN description LIKE '%Penalty%' THEN 'Penalties'
            WHEN description LIKE '%Maintenance%' THEN 'Maintenance'
            WHEN description LIKE '%Housekeeping%' THEN 'Housekeeping'
            ELSE 'Room Rent'
        END as category,
        SUM(amount) as total,
        COUNT(*) as count
    FROM payments 
    WHERE payment_status='Paid'
    GROUP BY category
");
$cat_data = [];
$cat_counts = [];
while($row = mysqli_fetch_assoc($cat_query)){
    $cat_data[$row['category']] = $row['total'];
    $cat_counts[$row['category']] = $row['count'];
}

// --- ACCURATE MONTHLY TRENDS ---

// 1. Earnings (Cash Flow)
$earnings_data = [];
$e_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total 
    FROM payments 
    WHERE payment_status='Paid' 
    GROUP BY month
");
while($row = mysqli_fetch_assoc($e_query)){
    $earnings_data[$row['month']] = $row['total'];
}

// 2. Bookings (New Reservations)
$bookings_data = [];
$b_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM reservations 
    GROUP BY month
");
while($row = mysqli_fetch_assoc($b_query)){
    $bookings_data[$row['month']] = $row['count'];
}

// 3. Generate Continuous Timeline
$min_date = date('Y-m');
if(!empty($earnings_data)) $min_date = min($min_date, min(array_keys($earnings_data)));
if(!empty($bookings_data)) $min_date = min($min_date, min(array_keys($bookings_data)));

$start = new DateTime($min_date . '-01');
$end = new DateTime(date('Y-m-01'));
$end->modify('+1 month');
$period = new DatePeriod($start, new DateInterval('P1M'), $end);

$monthly_data = [];
foreach ($period as $dt) {
    $ym = $dt->format("Y-m");
    // Prepend to array to have recent first for table
    array_unshift($monthly_data, [
        'month' => $ym,
        'earnings' => $earnings_data[$ym] ?? 0,
        'bookings' => $bookings_data[$ym] ?? 0
    ]);
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
    <title>Profit Report | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        .print-header { display: none; }
        @media print {
            #sidebar-wrapper, #menu-toggle, .btn-export, .btn-print, .no-print { display: none !important; }
            #page-content-wrapper { margin: 0; padding: 0; width: 100%; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; margin-bottom: 20px; }
            .container-fluid { padding: 0 !important; }
            body { background-color: white; font-size: 11pt; }
            .print-header { display: block !important; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            canvas { max-height: 300px !important; width: 100% !important; }
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
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="financeSubmenu">
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="profit_report.php" class="sidebar-link ps-5 active"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
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
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Profit & Earnings Report</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="admin_parking_reports.php" class="btn btn-outline-success btn-sm btn-export"><i class="fas fa-parking me-2"></i>Parking Reports</a>
                    <a href="admin_utilities.php#reports" class="btn btn-outline-secondary btn-sm btn-export"><i class="fas fa-archive me-2"></i>View Archive</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm btn-print"><i class="fas fa-print me-2"></i>Print Report</button>
                </div>
            </div>
            
            <div class="print-header text-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 80px; height: 80px; object-fit: cover;" class="rounded-circle mb-2">
                <h2 class="fw-bold mb-0">Woke Coliving INC</h2>
                <p class="text-muted mb-0">Profit & Revenue Report</p>
                <small class="text-muted">Generated on <?= date('F d, Y h:i A') ?></small>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-stat p-4 text-white" style="background-color: var(--primary-green);">
                        <h5 class="card-title">Total System Earnings</h5>
                        <h2 class="fw-bold">₱<?= number_format($total_earnings, 2) ?></h2>
                        <p class="mb-0">Total revenue from approved reservations</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown Cards -->
            <div class="row mb-4 g-3">
                <?php 
                $icons = [
                    'Room Rent' => 'fa-bed', 'Utilities' => 'fa-bolt', 'Parking' => 'fa-car', 
                    'Penalties' => 'fa-exclamation-circle', 'Maintenance' => 'fa-tools', 'Housekeeping' => 'fa-broom'
                ];
                $colors = [
                    'Room Rent' => 'primary', 'Utilities' => 'warning', 'Parking' => 'info', 
                    'Penalties' => 'danger', 'Maintenance' => 'secondary', 'Housekeeping' => 'success'
                ];
                
                foreach($cat_data as $cat => $amount): 
                    $icon = $icons[$cat] ?? 'fa-coins';
                    $color = $colors[$cat] ?? 'secondary';
                ?>
                <div class="col-md-3 col-6">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-<?= $color ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted text-uppercase fw-bold"><?= $cat ?></small>
                                <h4 class="fw-bold mb-0 text-dark">₱<?= number_format($amount, 2) ?></h4>
                            </div>
                            <i class="fas <?= $icon ?> fa-2x text-<?= $color ?> opacity-25"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Detailed Breakdown Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-stat p-4">
                        <h5 class="fw-bold mb-3 text-secondary">Detailed Revenue Breakdown</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Transactions</th>
                                        <th>Contribution</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_total = $total_earnings > 0 ? $total_earnings : 1;
                                    foreach($cat_data as $cat => $amount): 
                                        $percent = ($amount / $grand_total) * 100;
                                        $count = $cat_counts[$cat] ?? 0;
                                        $color = $colors[$cat] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><i class="fas <?= $icons[$cat] ?? 'fa-circle' ?> text-<?= $color ?> me-2"></i> <?= $cat ?></td>
                                        <td><?= $count ?></td>
                                        <td style="width: 40%;">
                                            <div class="d-flex align-items-center">
                                                <span class="me-2 small fw-bold" style="width: 45px;"><?= number_format($percent, 1) ?>%</span>
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percent ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold">₱<?= number_format($amount, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white fw-bold py-3">
                            <i class="fas fa-chart-line me-2 text-secondary"></i> Monthly Earnings Trend
                        </div>
                        <div class="card-body">
                            <canvas id="earningsChart" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white fw-bold py-3">
                            <i class="fas fa-chart-pie me-2 text-secondary"></i> Earnings by Room Type
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <canvas id="roomTypeChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card card-stat p-4 h-100">
                        <h5 class="fw-bold mb-3 text-secondary">Earnings by Room Type</h5>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room Type</th>
                                    <th>Bookings</th>
                                    <th class="text-end">Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($room_type_data as $row): ?>
                                <tr>
                                    <td><?= $row['room_type'] ?></td>
                                    <td><?= $row['bookings'] ?></td>
                                    <td class="text-end fw-bold">₱<?= number_format($row['earnings'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card card-stat p-4 h-100">
                        <h5 class="fw-bold mb-3 text-secondary">Monthly Earnings</h5>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>New Bookings</th>
                                    <th class="text-end">Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($monthly_data as $row): ?>
                                <tr>
                                    <td><?= date('F Y', strtotime($row['month'])) ?></td>
                                    <td><?= $row['bookings'] ?></td>
                                    <td class="text-end fw-bold">₱<?= number_format($row['earnings'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card card-stat p-4 mb-4">
                <h5 class="fw-bold mb-3 text-secondary">Recent Transactions</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Payer</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $trans_q = mysqli_query($conn, "
                                SELECT p.*, CONCAT(u.last_name, ', ', u.first_name) as full_name 
                                FROM payments p 
                                JOIN reservations r ON p.reservation_id = r.reservation_id 
                                JOIN users u ON r.user_id = u.user_id 
                                WHERE p.payment_status='Paid' 
                                ORDER BY p.payment_date DESC 
                                LIMIT 50
                            ");
                            while($t = mysqli_fetch_assoc($trans_q)):
                            ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($t['payment_date'])) ?></td>
                                <td class="fw-bold"><?= $t['full_name'] ?></td>
                                <td class="small text-muted"><?= $t['description'] ?></td>
                                <td><?= $t['payment_method'] ?></td>
                                <td class="text-end fw-bold text-success">₱<?= number_format($t['amount'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart.js Implementation
const ctx = document.getElementById('earningsChart').getContext('2d');
const monthlyData = <?= json_encode(array_reverse($monthly_data)) ?>;

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
            label: 'Total Earnings (₱)',
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
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [2, 4] },
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            },
            x: { grid: { display: false } }
        }
    }
});

// Pie Chart Implementation
const ctxPie = document.getElementById('roomTypeChart').getContext('2d');
const roomTypeData = <?= json_encode($room_type_data) ?>;

new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: roomTypeData.map(item => item.room_type),
        datasets: [{
            data: roomTypeData.map(item => item.earnings),
            backgroundColor: [
                '<?= $theme['primary'] ?>',
                '<?= $theme['accent'] ?>',
                '<?= $theme['dark'] ?>',
                '#81C784',
                '#FFF176'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) label += ': ';
                        if (context.parsed !== null) {
                            label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed);
                        }
                        return label;
                    }
                }
            }
        }
    }
});

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
</script>
</body>
</html>