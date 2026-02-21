<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
        }
        @media print {
            #sidebar-wrapper, #menu-toggle, .btn-export, .btn-print { display: none !important; }
            #page-content-wrapper { margin: 0; padding: 0; width: 100%; }
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
            <a href="booking_management.php" class="sidebar-link"><i class="fas fa-calendar-check me-2"></i>Bookings</a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><i class="fas fa-wrench me-2"></i>Maintenance</a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><i class="fas fa-broom me-2"></i>Housekeeping</a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>
            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link active"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="settingsSubmenu"><span><i class="fas fa-cog me-2"></i>Settings</span><i class="fas fa-chevron-down small"></i></a>
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
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Profit & Earnings Report</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="admin_utilities.php#reports" class="btn btn-outline-secondary btn-sm btn-export"><i class="fas fa-archive me-2"></i>View Archive</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm btn-print"><i class="fas fa-print me-2"></i>Print Report</button>
                </div>
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