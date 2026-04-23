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

// Date Filter Logic
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$filter_payments = "";
$filter_payments_p = "";
$filter_reservations = "";

if($start_date && $end_date){
    $s = mysqli_real_escape_string($conn, $start_date) . " 00:00:00";
    $e = mysqli_real_escape_string($conn, $end_date) . " 23:59:59";
    $filter_payments = " AND payment_date BETWEEN '$s' AND '$e'";
    $filter_payments_p = " AND p.payment_date BETWEEN '$s' AND '$e'";
    $filter_reservations = " WHERE created_at BETWEEN '$s' AND '$e'";
}

// Total Earnings
$total_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE payment_status='Paid' $filter_payments");
$total_earnings = mysqli_fetch_assoc($total_query)['total'] ?? 0;

// Earnings by Room Type
$type_query = mysqli_query($conn, "
    SELECT r.room_type, SUM(p.amount) as earnings, COUNT(DISTINCT res.reservation_id) as bookings
    FROM reservations res
    JOIN rooms r ON res.room_id = r.room_id
    JOIN payments p ON res.reservation_id = p.reservation_id
    WHERE p.payment_status='Paid' $filter_payments_p
    GROUP BY r.room_type
    ORDER BY room_type ASC
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
    WHERE payment_status='Paid' $filter_payments
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
    WHERE payment_status='Paid' $filter_payments
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
    $filter_reservations
    GROUP BY month
");
while($row = mysqli_fetch_assoc($b_query)){
    $bookings_data[$row['month']] = $row['count'];
}

// 3. Daily Earnings (Cash Flow)
$daily_earnings_data = [];
$de_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m-%d') as day, SUM(amount) as total 
    FROM payments 
    WHERE payment_status='Paid' $filter_payments
    GROUP BY day
");
while($row = mysqli_fetch_assoc($de_query)){
    $daily_earnings_data[$row['day']] = $row['total'];
}

// 4. Daily Bookings (New Reservations)
$daily_bookings_data = [];
$db_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as day, COUNT(*) as count 
    FROM reservations 
    $filter_reservations
    GROUP BY day
");
while($row = mysqli_fetch_assoc($db_query)){
    $daily_bookings_data[$row['day']] = $row['count'];
}

// 5. Yearly Earnings (Cash Flow)
$yearly_earnings_data = [];
$ye_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y') as year, SUM(amount) as total 
    FROM payments 
    WHERE payment_status='Paid' $filter_payments
    GROUP BY year
");
while($row = mysqli_fetch_assoc($ye_query)){
    $yearly_earnings_data[$row['year']] = $row['total'];
}

// Yearly Bookings (New Reservations)
$yearly_bookings_data = [];
$yb_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y') as year, COUNT(*) as count 
    FROM reservations 
    $filter_reservations
    GROUP BY year
");
while($row = mysqli_fetch_assoc($yb_query)){
    $yearly_bookings_data[$row['year']] = $row['count'];
}

// 6. Generate Continuous Yearly Timeline
if($start_date && $end_date){
    $start_y = new DateTime(date('Y-01-01', strtotime($start_date)));
    $end_y = new DateTime(date('Y-01-01', strtotime($end_date)));
    $end_y->modify('+1 year');
} else {
    $min_date = date('Y');
    if(!empty($yearly_earnings_data)) $min_date = min($min_date, min(array_keys($yearly_earnings_data)));
    if(!empty($yearly_bookings_data)) $min_date = min($min_date, min(array_keys($yearly_bookings_data)));
    
    $start_y = new DateTime($min_date . '-01-01');
    $end_y = new DateTime(date('Y-01-01'));
    $end_y->modify('+1 year');
}
$period_y = new DatePeriod($start_y, new DateInterval('P1Y'), $end_y);

$yearly_data = [];
foreach ($period_y as $dt) {
    $y = $dt->format("Y");
    $yearly_data[] = [
        'year' => $y,
        'earnings' => $yearly_earnings_data[$y] ?? 0,
        'bookings' => $yearly_bookings_data[$y] ?? 0
    ];
}

// 7. Generate Continuous Monthly Timeline
if($start_date && $end_date){
    $start = new DateTime(date('Y-m-01', strtotime($start_date)));
    $end = new DateTime(date('Y-m-01', strtotime($end_date)));
    $end->modify('+1 month');
} else {
    $min_date = date('Y-m');
    if(!empty($earnings_data)) $min_date = min($min_date, min(array_keys($earnings_data)));
    if(!empty($bookings_data)) $min_date = min($min_date, min(array_keys($bookings_data)));
    
    $start = new DateTime($min_date . '-01');
    $end = new DateTime(date('Y-m-01'));
    $end->modify('+1 month');
}
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

// 8. Generate Continuous Daily Timeline
if($start_date && $end_date){
    $start_d = new DateTime(date('Y-m-d', strtotime($start_date)));
    $end_d = new DateTime(date('Y-m-d', strtotime($end_date)));
    $end_d->modify('+1 day');
} else {
    $start_d = new DateTime('-30 days');
    $end_d = new DateTime('today');
    $end_d->modify('+1 day');
}
$period_d = new DatePeriod($start_d, new DateInterval('P1D'), $end_d);

$daily_data = [];
foreach ($period_d as $dt) {
    $ymd = $dt->format("Y-m-d");
    $daily_data[] = [
        'day' => $ymd,
        'earnings' => $daily_earnings_data[$ymd] ?? 0,
        'bookings' => $daily_bookings_data[$ymd] ?? 0
    ];
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
    <title>Profit Report | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .print-header { display: none; }
        
        @media print {
            /* 1. Remove Browser Headers & Footers (Removes the localhost URL) */
            @page { 
                size: A4 portrait; 
                margin: 0mm !important; 
            }

            /* 2. Base Document Reset for Paper */
            body, html { 
                background: #ffffff !important; 
                margin: 0 !important; 
                padding: 15mm !important; 
                font-family: 'Poppins', sans-serif !important;
                font-size: 11pt !important;
            }
            
            * { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }

            /* 3. Hide UI Interactivity Elements & Dropdowns */
            .sidebar, .top-navbar, .no-print, .btn, form, 
            select, option, .form-select,  /* Hides the chart filters */
            [class*="fa-chevron-down"], /* Hides generic dropdown arrows */
            .dropdown-toggle /* Hides bootstrap dropdowns */
            { 
                display: none !important; 
            }

            /* Hiding the specific yellow arrow button you pointed out */
            .btn-warning, .bg-warning[role="button"], button[style*="background-color: var(--warning)"] {
                display: none !important;
            }

            /* 4. Fix the Dark Chart Headers */
            /* Forces backgrounds to white and text to black */
            .card-header, .bg-dark, [style*="background-color: #212529"], [style*="background-color: rgb(33, 37, 41)"] {
                background-color: #ffffff !important;
                color: #000000 !important;
                border-bottom: 2px solid #ccc !important; /* Adds a line to separate from chart */
            }

            /* 5. Force Pure Black Text for general readability */
            h1, h2, h3, h4, h5, h6, p, span, div, td, th, small, a, i,
            .text-muted, .text-secondary, .text-success, .text-danger, .text-dark, .text-white, .opacity-25 { 
                color: #000000 !important; 
                opacity: 1 !important; 
                text-decoration: none !important;
            }

            /* 6. Reset App Layouts */
            .dashboard-container, .main-wrapper, .main-content { 
                display: block !important; 
                width: 100% !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                overflow: visible !important; 
            }

            /* 7. Professional Header Styling */
            .print-header { 
                display: block !important; 
                text-align: center;
                margin-bottom: 25px; 
                padding-bottom: 15px;
                border-bottom: 2px solid #2e7d32; 
            }
            .print-header h2 { font-size: 24pt !important; font-weight: bold !important; color: #2e7d32 !important; }

            /* 8. Cards Styling */
            .card { 
                border: 1px solid #c4c4c4 !important; 
                box-shadow: none !important; 
                break-inside: avoid !important; 
                page-break-inside: avoid !important; 
                margin-bottom: 15px !important; 
                background-color: #ffffff !important; 
                border-radius: 6px !important;
            }
            .card-stat { border-left-width: 4px !important; }

            /* 9. Tables */
            .table { width: 100% !important; border-collapse: collapse !important; margin-bottom: 0 !important; }
            .table th, .table td { border: 1px solid #dcdcdc !important; padding: 8px !important; }
            .table-light th { background-color: #f8f9fa !important; font-weight: bold !important; }
            tr { page-break-inside: avoid !important; break-inside: avoid !important; }

            /* 10. Progress bars & Charts */
            .progress { border: 1px solid #e0e0e0 !important; background-color: #f5f5f5 !important; }
            canvas { max-width: 100% !important; max-height: 250px !important; height: auto !important; break-inside: avoid !important; page-break-inside: avoid !important; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center no-print">
                <h1>Profit & Earnings Report</h1>
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2 align-items-center me-2">
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>" required>
                        <span class="text-muted">-</span>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>" required>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                        <?php if($start_date || $end_date): ?>
                            <a href="profit_report.php" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="fas fa-undo"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="admin_parking_reports.php" class="btn btn-outline-success btn-sm btn-export"><i class="fas fa-parking me-2"></i>Parking Reports</a>
                    <a href="admin_utilities.php#reports" class="btn btn-outline-secondary btn-sm btn-export"><i class="fas fa-archive me-2"></i>View Archive</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm btn-print"><i class="fas fa-print me-2"></i>Print Report</button>
                </div>
            </div>
            
            <div class="print-header text-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 80px; height: 80px; object-fit: cover;" class="rounded-circle mb-2">
                <h2 class="fw-bold mb-0">Woke Coliving INC</h2>
                <p class="text-muted mb-0">Profit & Revenue Report <?= ($start_date && $end_date) ? "(".date('M d, Y', strtotime($start_date))." - ".date('M d, Y', strtotime($end_date)).")" : "" ?></p>
                <small class="text-muted">Generated on <?= date('F d, Y h:i A') ?></small>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-stat p-4 text-dark" style="background-color: var(--primary-green);">
                        <h5 class="card-title text-dark">Total System Earnings</h5>
                        <h2 class="fw-bold">₱<?= number_format($total_earnings, 2) ?></h2>
                        <p class="mb-0 text-dark">Total revenue from approved reservations</p>
                    </div>
                </div>
            </div>

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
                                    <td>
                                        <i class="fas <?= $icons[$cat] ?? 'fa-circle' ?> text-<?= $color ?> me-2"></i> 
                                        <?= $cat ?>
                                    </td>
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
                        <div class="card-header bg-white text-dark fw-bold py-3 d-flex justify-content-between align-items-center border-bottom">
                            <div><i class="fas fa-chart-line me-2 text-primary"></i> <span id="lineChartTitle">Earnings Trend</span></div>
                            <div class="d-flex gap-2">
                                <select id="lineChartTimeFilter" class="form-select form-select-sm w-auto shadow-none bg-light text-dark border-0" onchange="updateLineChart()">
                                    <option value="yearly">Yearly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="daily">Daily</option>
                                </select>
                                <select id="lineChartFilter" class="form-select form-select-sm w-auto shadow-none bg-light text-dark border-0" onchange="updateLineChart()">
                                    <option value="earnings">Earnings</option>
                                    <option value="bookings">Bookings</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-4">This chart visualizes the financial growth and booking volume over time. Use the filters to adjust the view. <strong class="text-primary"><i class="fas fa-hand-pointer me-1"></i>Tip: Click on a Yearly or Monthly data point to drill down into that specific period.</strong></p>
                            <div style="position: relative; height: 320px; width: 100%;">
                                <canvas id="earningsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white text-dark fw-bold py-3 d-flex justify-content-between align-items-center border-bottom">
                            <div><i class="fas fa-chart-pie me-2 text-warning"></i> <span id="pieChartTitle">Earnings by Room Type</span></div>
                            <select id="pieChartFilter" class="form-select form-select-sm w-auto shadow-none bg-light text-dark border-0" onchange="updatePieChart()">
                                <option value="earnings">Earnings</option>
                                <option value="bookings">Bookings</option>
                            </select>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <p class="text-muted small mb-4 w-100 text-start">Distribution of revenue and reservations across different room types.</p>
                            <div style="width: 100%; height: 300px; position: relative;">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
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

            <div class="card card-stat p-4 mb-4 no-print">
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
                                WHERE p.payment_status='Paid' $filter_payments_p
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
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
const monthlyData = <?= json_encode(array_reverse($monthly_data)) ?>;
const dailyData = <?= json_encode($daily_data) ?>;
const yearlyData = <?= json_encode($yearly_data) ?>;

// Default theme colors if not provided by get_theme_colors()
const defaultTheme = {
    primary: '#2e7d32',
    accent: '#fbc02d',
    dark: '#212529'
};

const themeColors = <?= isset($theme) ? json_encode($theme) : 'defaultTheme' ?>;

let lineChart;
function initLineChart() {
    const ctx = document.getElementById('earningsChart').getContext('2d');
    lineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Data',
                data: [],
                borderColor: themeColors.primary,
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: themeColors.primary,
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                let filter = document.getElementById('lineChartFilter').value;
                                if (filter === 'earnings') {
                                    label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                                } else {
                                    label += context.parsed.y + ' bookings';
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [4, 4], color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: {
                        color: '#6c757d',
                        font: {family: 'Poppins'},
                        callback: function(value) {
                            let filter = document.getElementById('lineChartFilter').value;
                            if (filter === 'earnings') {
                                return '₱' + value.toLocaleString();
                            }
                            return value;
                        }
                    }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#6c757d', font: {family: 'Poppins'} }
                }
            },
            interaction: { intersect: false, mode: 'index' },
            onClick: (event, elements, chart) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const timeFilter = document.getElementById('lineChartTimeFilter').value;
                    
                    if (timeFilter === 'yearly') {
                        const rawData = yearlyData[index];
                        if (rawData && rawData.year) {
                            window.location.href = `?start_date=${rawData.year}-01-01&end_date=${rawData.year}-12-31`;
                        }
                    } else if (timeFilter === 'monthly') {
                        const rawData = monthlyData[index];
                        if (rawData && rawData.month) {
                            const y = rawData.month.split('-')[0];
                            const m = rawData.month.split('-')[1];
                            const lastDay = new Date(y, m, 0).getDate();
                            window.location.href = `?start_date=${y}-${m}-01&end_date=${y}-${m}-${lastDay}`;
                        }
                    }
                }
            },
            onHover: (event, elements, chart) => {
                const timeFilter = document.getElementById('lineChartTimeFilter').value;
                if (timeFilter !== 'daily') {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                } else {
                    event.native.target.style.cursor = 'default';
                }
            }
        }
    });
    updateLineChart();
}

function updateLineChart() {
    const filter = document.getElementById('lineChartFilter').value;
    const timeFilter = document.getElementById('lineChartTimeFilter').value;
    const title = document.getElementById('lineChartTitle');
    
    let sourceData = timeFilter === 'daily' ? dailyData : monthlyData;
    let labels = [];
    let dataPoints = [];
    
    if (timeFilter === 'daily') {
        labels = sourceData.map(item => {
            const date = new Date(item.day);
            return date.toLocaleDateString('default', { month: 'short', day: 'numeric' });
        });
        } else if (timeFilter === 'yearly') {
        sourceData = yearlyData;
        labels = sourceData.map(item => item.year);
    } else {
        labels = sourceData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('default', { month: 'short', year: 'numeric' });
        });
    }
    
    if(filter === 'earnings') {
        dataPoints = sourceData.map(item => item.earnings);
        title.innerText = timeFilter === 'daily' ? 'Daily Earnings Trend' : (timeFilter === 'yearly' ? 'Yearly Earnings Trend' : 'Monthly Earnings Trend');
        lineChart.data.datasets[0].label = 'Total Earnings';
        lineChart.data.datasets[0].borderColor = themeColors.primary;
        lineChart.data.datasets[0].pointBorderColor = themeColors.primary;
        lineChart.data.datasets[0].backgroundColor = 'rgba(46, 125, 50, 0.1)';
    } else {
        dataPoints = sourceData.map(item => item.bookings);
        title.innerText = timeFilter === 'daily' ? 'Daily Bookings Trend' : (timeFilter === 'yearly' ? 'Yearly Bookings Trend' : 'Monthly Bookings Trend');
        lineChart.data.datasets[0].label = 'Total Bookings';
        lineChart.data.datasets[0].borderColor = themeColors.accent;
        lineChart.data.datasets[0].pointBorderColor = themeColors.accent;
        lineChart.data.datasets[0].backgroundColor = 'rgba(251, 192, 45, 0.1)';
    }
    
    lineChart.data.labels = labels;
    lineChart.data.datasets[0].data = dataPoints;
    lineChart.update();
}

let pieChart;
function initPieChart() {
    const ctxPie = document.getElementById('roomTypeChart').getContext('2d');
    const roomTypeData = <?= json_encode($room_type_data) ?>;
    
    const chartColors = [
        themeColors.primary || '#2e7d32', 
        themeColors.accent || '#fbc02d', 
        themeColors.info || '#0288d1',
        '#66bb6a',
        '#fff59d'
    ];

    pieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: roomTypeData.map(item => item.room_type),
            datasets: [{
                data: roomTypeData.map(item => item.earnings),
                backgroundColor: chartColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { 
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {family: 'Poppins', size: 13}
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            if (context.parsed !== null) {
                                let filter = document.getElementById('pieChartFilter').value;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                
                                if (filter === 'earnings') {
                                    label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed) + ` (${percentage}%)`;
                                } else {
                                    label += context.parsed + ` bookings (${percentage}%)`;
                                }
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function updatePieChart() {
    const filter = document.getElementById('pieChartFilter').value;
    const title = document.getElementById('pieChartTitle');
    const roomTypeData = <?= json_encode($room_type_data) ?>;
    
    if(filter === 'earnings') {
        title.innerText = 'Earnings by Room Type';
        pieChart.data.datasets[0].data = roomTypeData.map(item => item.earnings);
    } else {
        title.innerText = 'Bookings by Room Type';
        pieChart.data.datasets[0].data = roomTypeData.map(item => item.bookings);
    }
    pieChart.update();
}

window.addEventListener('DOMContentLoaded', () => {
    // Auto select time filter based on URL date range
    const urlParams = new URLSearchParams(window.location.search);
    const sDate = urlParams.get('start_date');
    const eDate = urlParams.get('end_date');
    if (sDate && eDate) {
        const d1 = new Date(sDate);
        const d2 = new Date(eDate);
        const diffTime = Math.abs(d2 - d1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        const timeFilterEl = document.getElementById('lineChartTimeFilter');
        if (timeFilterEl) {
            if (diffDays <= 31) {
                timeFilterEl.value = 'daily';
            } else if (diffDays <= 366) {
                timeFilterEl.value = 'monthly';
            } else {
                timeFilterEl.value = 'yearly';
            }
        }
    }

    initLineChart();
    initPieChart();
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

// Notification Sound & Auto Refresh Logic
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

if(sessionStorage.getItem('playNotifSound') === 'true') {
    let audio = new Audio('../assets/sounds/notification.mp3');
    audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
    audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
    sessionStorage.removeItem('playNotifSound');
}
</script>
</body>
</html>