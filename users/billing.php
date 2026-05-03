<?php
session_start();
include("../db.php");
date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$theme = get_theme_colors($conn);

// Financial Summary
$financial_q = mysqli_query($conn, "SELECT IFNULL(SUM(p.amount), 0) as total_billed, IFNULL(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END), 0) as total_paid FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$user_id AND p.payment_status != 'Cancelled'");
$fin = mysqli_fetch_assoc($financial_q);
$total_billed = $fin['total_billed'] ?? 0;
$total_paid = $fin['total_paid'] ?? 0;
$balance = $total_billed - $total_paid;

// Payment History
$payments = mysqli_query($conn, "SELECT p.*, rm.room_name, rm.room_number, r.start_date, r.end_date FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id LEFT JOIN rooms rm ON r.room_id = rm.room_id WHERE r.user_id=$user_id ORDER BY p.payment_id ASC");

// Fetch User Info for Navbar
$u_query = mysqli_query($conn, "SELECT first_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing & Payments | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; }
        .summary-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .balance-card { background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); color: white; }

        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar { background-color: #1f1f1f !important; border-bottom: 1px solid #333 !important; }
        body.night-mode .card, body.night-mode .summary-card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .bg-white, body.night-mode .bg-light { background-color: #1e1e1e !important; color: #e0e0e0 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .border, body.night-mode .border-bottom, body.night-mode .border-start { border-color: #444 !important; }
        body.night-mode .table { color: #e0e0e0 !important; }
        body.night-mode .table th, body.night-mode .table td { border-color: #444 !important; background-color: transparent !important; color: #e0e0e0 !important; }
        body.night-mode .balance-card { background: linear-gradient(135deg, #1B5E20, #0a3a10) !important; color: #e0e0e0 !important; }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .table th, .table td { padding: 8px 10px !important; font-size: 0.85rem; }
            .table th { font-size: 0.75rem; }
            .btn-sm { padding: 4px 8px !important; font-size: 0.75rem !important; }
            .badge { padding: 4px 8px !important; }
            .summary-card { padding: 15px !important; }
            .mb-4.d-flex { flex-direction: column; align-items: flex-start !important; gap: 15px; }
            .mb-4.d-flex > div:last-child { display: flex; flex-wrap: wrap; gap: 10px; width: 100%; }
        }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
        else if (localStorage.getItem(nightModeKey) === 'disabled') document.body.classList.remove('night-mode');
    })();
</script>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="../guest.php">Dormitory</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-muted fw-bold d-none d-md-block">Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5 animate-fade-in">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-dark">Billing Statement</h2>
            <p class="text-muted">Track your total payments and outstanding balance.</p>
        </div>
        <div>
            <a href="my_reservations.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 me-2"><i class="fas fa-calendar-check me-2"></i>My Bookings</a>
            <a href="profile.php" class="btn btn-sm btn-secondary-custom">&larr; Back</a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card summary-card balance-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small opacity-75 fw-bold text-uppercase">Remaining Balance</span>
                    <i class="fas fa-wallet fa-2x opacity-25"></i>
                </div>
                <h2 class="fw-bold mb-2">₱<?= number_format($balance, 2) ?></h2>
                <div class="mt-auto">
                    <?php if($balance <= 0): ?>
                        <span class="badge bg-white text-success rounded-pill px-3">Fully Paid</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark rounded-pill px-3">With Balance</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card p-4 h-100 border-start border-primary border-5">
                <span class="small text-muted fw-bold text-uppercase mb-3 d-block">Total Billed Amount</span>
                <h2 class="fw-bold text-dark">₱<?= number_format($total_billed, 2) ?></h2>
                <p class="small text-muted mt-2">Sum of all room rent and utilities.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card p-4 h-100 border-start border-success border-5">
                <span class="small text-muted fw-bold text-uppercase mb-3 d-block">Total Payments Made</span>
                <h2 class="fw-bold text-success">₱<?= number_format($total_paid, 2) ?></h2>
                <p class="small text-muted mt-2">Verified payments credited to your account.</p>
            </div>
        </div>
    </div>

    <div class="card summary-card p-4">
        <h5 class="fw-bold text-dark mb-4"><i class="fas fa-history me-2 text-primary"></i>Payment History</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Stay Duration</th>
                        <th>Room</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($pay = mysqli_fetch_assoc($payments)): ?>
                    <?php
                        $display_date = $pay['payment_date'];
                        $desc = preg_replace('/\s*\[FULL\]\s*/i', '', $pay['description']);

                        // Logic para itugma ang date sa billing month/period kung ito ay recurring rent o utility
                        if (preg_match('/Month (\d+) Rent/i', $desc, $matches)) {
                            // Para sa "Month X Rent", kalkulahin ang petsa base sa simula ng reservation
                            $m_idx = (int)$matches[1] - 1;
                            $calc_dt = new DateTime($pay['start_date']);
                            $calc_dt->modify("+$m_idx months");
                            $display_date = $calc_dt->format('Y-m-d H:i:s');
                        } elseif (preg_match('/Utility Bill \((\d{4}-\d{2}-\d{2})\)/i', $desc, $matches)) {
                            // Gamitin ang petsang nakasaad sa utility bill description
                            $display_date = $matches[1];
                        } elseif (strpos(strtolower($desc), 'initial') !== false || strpos(strtolower($desc), 'walk-in') !== false || strpos(strtolower($desc), 'full payment') !== false) {
                            // Para sa mga unang bayad, ipakita ang mismong start date ng stay
                            $display_date = $pay['start_date'];
                        }

                        $d1 = new DateTime($pay['start_date']);
                        $d2 = new DateTime($pay['end_date']);
                        $interval = $d1->diff($d2);
                        $m = ($interval->y * 12) + $interval->m;
                        $d = $interval->d;
                        $duration_label = ($m > 0 ? "$m Mo" : "") . ($m > 0 && $d > 0 ? ", " : "") . ($d > 0 || $m == 0 ? "$d Days" : "");
                    ?>
                    <tr>
                        <td class="small text-muted"><?= $display_date ? date('D, M d, Y', strtotime($display_date)) : 'N/A' ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($pay['description']) ?></td>
                        <td class="small text-muted"><?= $duration_label ?></td>
                        <td><?= !empty($pay['room_number']) ? 'Room ' . $pay['room_number'] : $pay['room_name'] ?></td>
                        <td class="small"><?= $pay['payment_method'] ?></td>
                        <td class="text-end fw-bold">₱<?= number_format($pay['amount'], 2) ?></td>
                        <td class="text-center">
                            <?php 
                                $s = $pay['payment_status'];
                                $cls = 'bg-warning text-dark';
                                if($s == 'Paid') $cls = 'bg-success';
                                elseif($s == 'Cancelled') $cls = 'bg-danger';
                            ?>
                            <span class="badge <?= $cls ?> rounded-pill px-3"><?= $s ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($payments) == 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No payment history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Notification Logic
let lastUnreadCount = <?= (int)$unread_count ?>;
function fetchNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if(data.unread_count > lastUnreadCount) {
                const audio = document.getElementById('notifSound');
                if(audio) audio.play().catch(e => {});
            }
            lastUnreadCount = data.unread_count;
        });
}
setInterval(fetchNotifications, 5000);
fetchNotifications(); // Initial load

// Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php').then(r => r.text()).then(t => {
        if(lastUpdate == 0) lastUpdate = t; else if (t > lastUpdate) location.reload();
    });
}
setInterval(checkUpdates, 3000);

// Night Mode Logic
const currentUserId = "<?= $user_id ?>";
if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
    document.body.classList.add('night-mode');
}
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode_' + currentUserId) {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});
</script>
</body>
</html>