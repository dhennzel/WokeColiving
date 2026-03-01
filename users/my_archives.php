<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get User Info
$u_query = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

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
    <style>
        :root {
            --primary-green: #2E7D32;
            --dark-green: #1B5E20;
            --accent-yellow: #FBC02D;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .navbar { background: var(--dark-green); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table thead th { background-color: var(--primary-green); color: white; border: none; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card, body.night-mode .card-custom { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .bg-white { background-color: #1e1e1e !important; } /* Fixes hardcoded white rows/backgrounds */
        
        /* Dropdowns */
        body.night-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .dropdown-item { color: #e0e0e0; }
        body.night-mode .dropdown-item:hover { background-color: #333; }
        
        /* Table Fixes */
        body.night-mode .table { color: #e0e0e0; background-color: transparent; }
        body.night-mode .table thead th { background-color: #1f1f1f; border-color: #333; color: #e0e0e0; }
        body.night-mode .table tr, body.night-mode .table td, body.night-mode .table th { background-color: #1e1e1e; border-color: #333; color: #e0e0e0; }
        
        /* Modal Fixes */
        body.night-mode .modal-content { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .modal-header { border-bottom-color: #333; }
        body.night-mode .modal-footer { border-top-color: #333; }
        body.night-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); } /* Makes the 'X' visible */
        
        /* Buttons */
        body.night-mode .btn-outline-dark { color: #e0e0e0; border-color: #e0e0e0; }
        body.night-mode .btn-outline-dark:hover { background-color: #e0e0e0; color: #121212; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Notification Dropdown -->
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none position-relative me-3" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if($unread_count > 0): ?>
                        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?= $unread_count ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    <?php endif; ?>
                </a>
                <ul id="notifList" class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                        <span class="fw-bold small text-uppercase text-muted">Notifications</span>
                        <?php if($unread_count > 0): ?>
                            <a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <!-- Notifications will be loaded via JS -->
                </ul>
            </div>
            <a href="profile.php" class="text-white text-decoration-none fw-bold">My Profile</a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars(explode(' ', $user_info['full_name'])[0]) ?></span>
            <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container reveal" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-success"><i class="fas fa-archive me-2"></i>Archived History</h2>
            <p class="text-muted mb-0">Your past and cancelled reservations.</p>
        </div>
        <a href="profile.php" class="btn btn-secondary rounded-pill">&larr; Back</a>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'restored') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Reservation restored to main list successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <div class="card card-custom p-4">
        <?php if(mysqli_num_rows($query) > 0) { ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th width="10%">Room</th>
                        <th>Details</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)) { ?>
                    <?php
                        $start_date = $row['start_date'] ?? $row['cin'] ?? 'N/A';
                        $end_date = $row['end_date'] ?? $row['cout'] ?? 'N/A';
                        $total_price = $row['total_price'] ?? $row['total_amount'] ?? 0;
                    ?>
                    <tr class="text-muted">
                        <td class="fw-bold">#<?= $row['reservation_id'] ?></td>
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
                            <a href="room_details.php?id=<?= $row['room_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="fas fa-eye"></i> View Room
                            </a>
                            <a href="my_archives.php?restore_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-success rounded-pill ms-1" onclick="return confirm('Restore this reservation?')">
                                <i class="fas fa-trash-restore"></i> Restore
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
            <div class="text-center py-5 text-muted">
                <h4 class="fw-bold">No archives found</h4>
                <p>You haven't archived any reservations yet.</p>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

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

// Night Mode Logic
if(localStorage.getItem('nightMode') === 'enabled') {
    document.body.classList.add('night-mode');
}
</script>
</body>
</html>