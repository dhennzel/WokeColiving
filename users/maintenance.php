<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Check for active reservation (Approved only)
$room_check = mysqli_query($conn, "SELECT room_id FROM reservations WHERE user_id='$user_id' AND status='Approved' LIMIT 1");
$has_active_reservation = (mysqli_num_rows($room_check) > 0);

// Handle Cancellation
if(isset($_GET['cancel_id'])){
    $cancel_id = (int)$_GET['cancel_id'];
    // Only allow cancelling if status is Pending
    $stmt = mysqli_prepare($conn, "UPDATE maintenance_requests SET status='Cancelled' WHERE request_id=? AND user_id=? AND status='Pending'");
    mysqli_stmt_bind_param($stmt, "ii", $cancel_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        trigger_update($conn);
        header("Location: maintenance.php?msg=cancelled");
        exit;
    }
}

// Handle Form Submission
if (isset($_POST['submit_request'])) {
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if ($has_active_reservation) {
        $room_row = mysqli_fetch_assoc($room_check);
        $room_id = $room_row['room_id'];
        
        $sql = "INSERT INTO maintenance_requests (user_id, room_id, description, status) VALUES ('$user_id', '$room_id', '$description', 'Pending')";
        if (mysqli_query($conn, $sql)) {
            trigger_update($conn);
            $message = "Maintenance request submitted successfully.";
        } else {
            $error = "Error submitting request: " . mysqli_error($conn);
        }
    } else {
        $error = "You do not have an active room reservation to request maintenance for.";
    }
}

// Fetch User's Requests
$requests_query = mysqli_query($conn, "SELECT m.*, r.room_name 
    FROM maintenance_requests m 
    LEFT JOIN rooms r ON m.room_id = r.room_id 
    WHERE m.user_id='$user_id' 
    ORDER BY m.created_at DESC");

// Get User Name for Navbar
$u_query = mysqli_query($conn, "SELECT first_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['first_name'];

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Maintenance Requests | Woke Coliving INC</title>
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
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333 !important; color: #fff !important; border-color: var(--primary-green) !important; }
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
            <a href="profile.php" class="nav-link fw-bold position-relative">
                My Profile
                <?php if($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                <?php endif; ?>
            </a>
            <span class="text-muted fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container animate-fade-in" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 anim-trigger">
        <h2 class="fw-bold text-success"><i class="fas fa-tools me-2"></i>Maintenance Requests</h2>
        <a href="profile.php" class="btn btn-sm btn-secondary-custom">&larr; Back</a>
    </div>

    <?php if ($message) { echo "<div class='alert alert-success'>$message</div>"; } ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled') { echo "<div class='alert alert-warning'>Request cancelled.</div>"; } ?>
    <?php if ($error) { echo "<div class='alert alert-danger'>$error</div>"; } ?>

    <!-- Request Form -->
    <?php if ($has_active_reservation) { ?>
    <div class="card card-custom p-4 mb-5 anim-trigger delay-1">
        <h5 class="fw-bold mb-3">Submit New Request</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Issue Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the issue in your room (e.g., Leaking faucet, AC not cooling)..." required></textarea>
            </div>
            <button type="submit" name="submit_request" class="btn btn-custom px-4">Submit Request</button>
        </form>
    </div>
    <?php } else { ?>
        <div class="alert alert-warning mb-5 anim-trigger delay-1"><i class="fas fa-exclamation-circle me-2"></i>You must have an active (approved) room reservation to submit maintenance requests.</div>
    <?php } ?>

    <!-- List of Requests -->
    <div class="card card-custom p-4 anim-trigger delay-2">
        <h5 class="fw-bold mb-3">My Request History</h5>
        <?php if (mysqli_num_rows($requests_query) > 0) { ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Room</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Scheduled Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($requests_query)) { ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        <td class="fw-bold text-success"><?= $row['room_name'] ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td>
                            <span class="badge bg-secondary rounded-pill"><?= $row['status'] ?></span>
                        </td>
                        <td>
                            <?= $row['scheduled_date'] ? $row['scheduled_date'] : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td>
                            <?php if($row['status'] == 'Pending') { ?>
                                <a href="maintenance.php?cancel_id=<?= $row['request_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'Cancel this request?')">Cancel</a>
                            <?php } else { ?>
                                <span class="text-muted small">Uncancelable</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
            <p class="text-muted">No maintenance requests found.</p>
        <?php } ?>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="users_JS/app.js"></script>
<script>
function confirmAction(e, url, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
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
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) lastUpdate = t;
        else if (t > lastUpdate) location.reload();
    });
}
setInterval(checkUpdates, 3000); // Check every 3 seconds

// Night Mode Logic
const currentUserId = "<?= $user_id ?>";
if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
    document.body.classList.add('night-mode');
}

// Sync Night Mode across tabs
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode_' + currentUserId) {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});
</script>
</body>
</html>