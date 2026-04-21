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
    <title>Parking Reports | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <script>
    const currentAdminUser = "<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin', ENT_QUOTES, 'UTF-8') ?>";
    window.currentAdminUser = currentAdminUser;
    if(localStorage.getItem('adminNightMode_' + currentAdminUser) === 'enabled') {
        document.body.classList.add('night-mode');
    }
</script>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
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
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
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
            borderColor: <?= json_encode($theme['primary']) ?>,
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