<?php
session_start();
include("../db.php");
date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$theme = get_theme_colors($conn);

$message = "";
$threshold = 5000;

// Handle Bulk Reminders
if (isset($_POST['send_bulk_reminders'])) {
    $rem_query = "SELECT u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name, SUM(p.amount) as total_balance FROM users u JOIN reservations r ON u.user_id = r.user_id JOIN payments p ON r.reservation_id = p.reservation_id WHERE p.payment_status = 'Unpaid' AND u.is_archived = 0 GROUP BY u.user_id HAVING total_balance > $threshold";
    $rem_result = mysqli_query($conn, $rem_query);
    $count = 0;
    while ($row = mysqli_fetch_assoc($rem_result)) {
        $uid = $row['user_id'];
        $bal = number_format($row['total_balance'], 2);
        $msg = "⚠️ <strong>Outstanding Balance Reminder</strong><br>Dear " . htmlspecialchars($row['full_name']) . ", this is a friendly reminder from Woke Coliving that you have an outstanding balance of <strong>₱$bal</strong>. Please settle this amount at your earliest convenience to ensure continued access to services. Thank you!";
        $timestamp = date("M d, Y h:i A");
        $subject = "Action Required: Outstanding Balance of ₱$bal - Woke Coliving ($timestamp)";
        send_notification($conn, $uid, $msg, "Billing Reminder", $subject);
        $count++;
    }
    $message = "Reminders successfully sent to $count residents.";
}

// Threshold for the report
$threshold = 5000;

$query = "
    SELECT 
        u.user_id, 
        CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, 
        u.email, 
        u.phone_number,
        u.profile_image,
        SUM(p.amount) as total_balance,
        COUNT(p.payment_id) as unpaid_count
    FROM users u
    JOIN reservations r ON u.user_id = r.user_id
    JOIN payments p ON r.reservation_id = p.reservation_id
    WHERE p.payment_status = 'Unpaid' AND u.is_archived = 0
    GROUP BY u.user_id
    HAVING total_balance > $threshold
    ORDER BY total_balance DESC
";

$result = mysqli_query($conn, $query);

// Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outstanding Balances | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Outstanding Balances Report</h1>
                <div class="d-flex gap-2">
                    <form method="POST" id="reminderForm" onsubmit="confirmBulkReminders(event)">
                        <button type="submit" name="send_bulk_reminders" class="btn btn-primary btn-sm no-print"><i class="fas fa-paper-plane me-2"></i>Send Bulk Reminders</button>
                    </form>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="fas fa-print me-2"></i>Print Report</button>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info no-print">
                <i class="fas fa-info-circle me-2"></i> This report lists all active tenants with a total unpaid balance exceeding <strong>₱<?= number_format($threshold, 2) ?></strong>.
            </div>

            <div class="card card-table p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Contact</th>
                                <th>Unpaid Items</th>
                                <th class="text-end">Total Balance</th>
                                <th class="text-end no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width:40px; height:40px; border-radius:50%; background:var(--primary-green); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; overflow:hidden;">
                                                <?php if(!empty($row['profile_image'])): ?>
                                                    <img src="../uploads/profiles/<?= $row['profile_image'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                                <small class="text-muted">ID: #<?= $row['user_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="d-block"><?= htmlspecialchars($row['email']) ?></small>
                                        <small class="text-muted"><?= htmlspecialchars($row['phone_number']) ?></small>
                                    </td>
                                    <td><span class="badge bg-warning text-dark"><?= $row['unpaid_count'] ?> Transactions</span></td>
                                    <td class="text-end fw-bold text-danger">₱<?= number_format($row['total_balance'], 2) ?></td>
                                    <td class="text-end no-print">
                                        <a href="view_user.php?uid=<?= $row['user_id'] ?>&pay_status=Unpaid" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> Review Payments</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 opacity-25"></i><p>No tenants currently exceed the balance threshold.</p></td></tr>
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
function confirmBulkReminders(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Send Bulk Reminders?',
        text: "This will send an email and in-app notification to everyone listed in this report.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#34B875',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Send All!'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('reminderForm').submit();
    });
}

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