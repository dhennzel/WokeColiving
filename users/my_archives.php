<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get User Info
$u_query = mysqli_query($conn, "SELECT first_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['first_name'];

// Handle Restore Action
if(isset($_GET['restore_id'])){
    $rid = (int)$_GET['restore_id'];
    mysqli_query($conn, "UPDATE reservations SET is_archived=0 WHERE reservation_id=$rid AND user_id=$user_id");
    header("Location: my_archives.php?msg=restored");
    exit;
}

// Fetch Archived Reservations
$query = mysqli_query($conn, "SELECT r.*, rm.room_name, rm.room_type, rm.image 
FROM reservations r
JOIN rooms rm ON r.room_id = rm.room_id
WHERE r.user_id = $user_id AND r.is_archived = 1 ORDER BY r.reservation_id DESC");

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Archived History | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <span class="text-muted fw-bold d-none d-md-block">Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="anim-trigger anim-left">
            <h2 class="fw-bold text-success"><i class="fas fa-archive me-2"></i>Archived History</h2>
            <p class="text-muted mb-0">Your past and cancelled reservations.</p>
        </div>
        <a href="profile.php" class="btn btn-sm btn-secondary-custom anim-trigger anim-right">&larr; Back</a>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'restored') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Reservation restored to main list successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <div class="card card-custom p-4 anim-trigger anim-zoom delay-1">
        <?php if(mysqli_num_rows($query) > 0) { ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th width="10%">Room</th>
                        <th>Details</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $delay_row = 1;
                    while($row = mysqli_fetch_assoc($query)) { ?>
                    <?php
                        $start_date = $row['start_date'] ?? $row['cin'] ?? 'N/A';
                        $end_date = $row['end_date'] ?? $row['cout'] ?? 'N/A';
                        $total_price = $row['total_price'] ?? $row['total_amount'] ?? 0;
                    ?>
                    <tr class="text-muted anim-trigger anim-left delay-<?= min($delay_row++, 5) ?>">
                        <td>
                            <img src="../assets/images/<?= $row['image'] ?>" class="img-fluid rounded shadow-sm" style="height: 60px; width: 80px; object-fit: cover; opacity: 0.7;">
                        </td>
                        <td>
                            <h6 class="mb-0 fw-bold"><?= $row['room_name'] ?></h6>
                            <small><?= $row['room_type'] ?></small>
                        </td>
                        <td>
                            <small class="d-block">In: <?= $start_date ?></small>
                            <small class="d-block">Out: <?= $end_date ?></small>
                        </td>
                        <td>₱<?= number_format((float)$total_price, 2) ?></td>
                        <td>
                            <span class="badge bg-secondary rounded-pill px-3 py-2">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="room_details.php?id=<?= $row['room_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View Room
                            </a>
                            <a href="my_archives.php?restore_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-custom ms-1" onclick="confirmRestore(event, this.href)">
                                <i class="fas fa-trash-restore"></i> Restore
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
            <div class="text-center py-5 text-muted anim-trigger anim-zoom">
                <h4 class="fw-bold">No archives found</h4>
                <p>You haven't archived any reservations yet.</p>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Scroll to Top Button -->
<a href="#" class="scroll-top-btn" id="scrollTopBtn"><i class="fas fa-chevron-up"></i></a>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="users_JS/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmRestore(e, url) {
    e.preventDefault();
    Swal.fire({
        title: 'Restore Reservation?',
        text: "This will move the reservation back to your active list.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2E7D32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, restore it!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}

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
</script>
</body>
</html>