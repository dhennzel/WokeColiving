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
$room_check = mysqli_query($conn, "SELECT room_id, reservation_id FROM reservations WHERE user_id='$user_id' AND status='Approved' LIMIT 1");
$has_active_reservation = (mysqli_num_rows($room_check) > 0);

// Handle Cancellation
if(isset($_GET['cancel_id'])){
    $cancel_id = (int)$_GET['cancel_id'];
    // Only allow cancelling if status is Pending
    $stmt = mysqli_prepare($conn, "UPDATE housekeeping_requests SET status='Cancelled' WHERE request_id=? AND user_id=? AND status='Pending'");
    mysqli_stmt_bind_param($stmt, "ii", $cancel_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        trigger_update($conn);
        header("Location: housekeeping.php?msg=cancelled");
        exit;
    }
}

// Handle Form Submission
if (isset($_POST['submit_request'])) {
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if ($has_active_reservation) {
        $room_row = mysqli_fetch_assoc($room_check);
        $room_id = $room_row['room_id'];
        
        $sql = "INSERT INTO housekeeping_requests (user_id, room_id, description, status) VALUES ('$user_id', '$room_id', '$description', 'Pending')";
        if (mysqli_query($conn, $sql)) {
            trigger_update($conn);
            header("Location: housekeeping.php?msg=submitted");
            exit;
        } else {
            $error = "Error submitting request: " . mysqli_error($conn);
        }
    } else {
        $error = "You do not have an active room reservation to request housekeeping for.";
    }
}

// Fetch User's Requests
$requests_query = mysqli_query($conn, "SELECT h.*, r.room_name 
    FROM housekeeping_requests h 
    LEFT JOIN rooms r ON h.room_id = r.room_id 
    WHERE h.user_id='$user_id' 
    ORDER BY h.created_at DESC");

// Get User Name for Navbar
$u_query = mysqli_query($conn, "SELECT first_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['first_name']; // Just for navbar compatibility if needed

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Housekeeping Requests | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        
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
        body.night-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .dropdown-item { color: #e0e0e0; }
        body.night-mode .dropdown-item:hover { background-color: #333; }
        body.night-mode .form-control, body.night-mode textarea { background-color: #2c2c2c; color: #e0e0e0; border-color: #444; }
        body.night-mode .form-control:focus { background-color: #333; color: #fff; }
        body.night-mode .table { color: #e0e0e0; background-color: transparent; }
        body.night-mode .table thead th { background-color: #1f1f1f; border-color: #333; color: #e0e0e0; }
        body.night-mode .table td, body.night-mode .table th { background-color: #1e1e1e; border-color: #333; color: #e0e0e0; }
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
            <a href="profile.php" class="text-white text-decoration-none fw-bold position-relative">
                My Profile
                <?php if($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                <?php endif; ?>
            </a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container reveal" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-success"><i class="fas fa-broom me-2"></i>Housekeeping Requests</h2>
        <a href="profile.php" class="btn btn-secondary rounded-pill">&larr; Back</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'submitted') { echo "<div class='alert alert-success'>Housekeeping request submitted successfully. Fee added to bill.</div>"; } ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled') { echo "<div class='alert alert-warning'>Request cancelled.</div>"; } ?>
    <?php if ($error) { echo "<div class='alert alert-danger'>$error</div>"; } ?>

    <!-- Request Form -->
    <?php if ($has_active_reservation) { ?>
    <div class="card card-custom p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Request Housekeeping Service</h5>
            <span class="badge bg-warning text-dark">Fee: ₱200.00</span>
        </div>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Service Details</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the service needed (e.g., Room cleaning, Change bed sheets, Restock toiletries)..." required></textarea>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Note: A fee of ₱200 will be charged for on-demand housekeeping requests.</small>
            </div>
            <button type="submit" name="submit_request" class="btn btn-custom px-4">Submit Request</button>
        </form>
    </div>
    <?php } else { ?>
        <div class="alert alert-warning mb-5"><i class="fas fa-exclamation-circle me-2"></i>You must have an active (approved) room reservation to request housekeeping services.</div>
    <?php } ?>

    <!-- List of Requests -->
    <div class="card card-custom p-4">
        <h5 class="fw-bold mb-3">My Housekeeping History</h5>
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
                                <a href="housekeeping.php?cancel_id=<?= $row['request_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'Cancel this request?')">Cancel</a>
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
            <p class="text-muted">No housekeeping requests found.</p>
        <?php } ?>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

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
if(localStorage.getItem('nightMode') === 'enabled') {
    document.body.classList.add('night-mode');
}

// Sync Night Mode across tabs
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode') {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});
</script>
</body>
</html>