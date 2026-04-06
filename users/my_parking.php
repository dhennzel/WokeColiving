<?php
session_start();
include("../db.php");

// Only allow logged in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's parking reservations
$reservations_q = mysqli_query($conn, "
    SELECT pr.*, ps.slot_name, ps.slot_type 
    FROM parking_reservations pr 
    JOIN parking_slots ps ON pr.slot_id = ps.id 
    WHERE pr.user_id = $user_id 
    ORDER BY pr.start_date DESC
");

$user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id"));
$theme = get_theme_colors($conn);

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Parking | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar-user { background: #1f1f1f !important; border-bottom: 1px solid #333 !important; }
        body.night-mode .card, body.night-mode .card-custom { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .bg-light, body.night-mode .bg-white { background-color: #2c2c2c !important; color: #e0e0e0 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .border, body.night-mode .border-bottom, body.night-mode .border-top { border-color: #444 !important; }
        body.night-mode .table { color: #e0e0e0 !important; }
        body.night-mode .table th, body.night-mode .table td { border-color: #444 !important; background-color: transparent !important; color: #e0e0e0 !important; }
        body.night-mode .navbar-user .nav-link, body.night-mode .navbar-user .navbar-brand, body.night-mode .navbar-user .text-muted { color: #34B875 !important; }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
    })();
</script>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-user fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <span class="text-muted fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container animate-fade-in" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 anim-trigger">
        <h2 class="fw-bold text-success"><i class="fas fa-parking me-2"></i>My Parking</h2>
        <a href="profile.php" class="btn btn-sm btn-secondary-custom">&larr; Back</a>
    </div>

    <div class="card card-custom p-4 anim-trigger delay-1">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Slot Name</th>
                        <th>Slot Type</th>
                        <th>Start Date</th>
                        <th>Billing Types</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($reservations_q) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($reservations_q)): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['slot_name']) ?></td>
                            <td>
                                <?php if($row['slot_type'] == 'Car'): ?>
                                    <i class="fas fa-car me-2 text-primary"></i>
                                <?php else: ?>
                                    <i class="fas fa-motorcycle me-2 text-warning"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($row['slot_type']) ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['billing_type']) ?></span></td>
                            <td>
                                <?php 
                                    $status_class = $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary';
                                ?>
                                <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                You have no active or past parking reservations.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="users_JS/app.js"></script>
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
</script>
</body>
</html>