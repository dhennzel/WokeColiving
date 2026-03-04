<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Remove Action
if(isset($_GET['remove_id'])){
    $remove_id = (int)$_GET['remove_id'];
    mysqli_query($conn, "DELETE FROM waitlist WHERE id=$remove_id AND user_id=$user_id");
    $_SESSION['swal'] = ['title' => 'Removed', 'text' => 'You have been removed from the waitlist.', 'icon' => 'success'];
    header("Location: my_waitlist.php");
    exit;
}

// Fetch Waitlisted Items
$query = mysqli_query($conn, "SELECT * FROM waitlist WHERE user_id = $user_id ORDER BY created_at DESC");

// Fetch User Info for Navbar
$u_query = mysqli_query($conn, "SELECT first_name, night_mode FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Waitlist | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-green: #2E7D32; --dark-green: #1B5E20; --accent-yellow: #FBC02D; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .navbar { background: var(--dark-green); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table thead th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card-custom { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .table { color: #e0e0e0; }
        body.night-mode .table thead th { background-color: #1f1f1f; border-color: #333; }
        body.night-mode .table td, body.night-mode .table th { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .table-hover tbody tr:hover > * { background-color: #2c2c2c; }
    </style>
</head>
<body class="<?= ($user_info['night_mode'] == 1) ? 'night-mode' : '' ?>">

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
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                <?php endif; ?>
            </a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-success"><i class="fas fa-list-ol me-2"></i>My Waitlist</h2>
            <p class="text-muted mb-0">Rooms you are currently waiting for.</p>
        </div>
        <a href="profile.php" class="btn btn-secondary rounded-pill">&larr; Back</a>
    </div>

    <div class="card card-custom p-4">
        <?php if(mysqli_num_rows($query) > 0) { ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Room Type</th>
                        <th>Date Joined</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)) { ?>
                    <tr>
                        <td class="fw-bold text-success"><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= date('F d, Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if(!empty($row['notified_at'])): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Notified on <?= date('M d', strtotime($row['notified_at'])) ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Waiting</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="my_waitlist.php?remove_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="confirmLeave(event, this.href)">
                                <i class="fas fa-times me-1"></i> Leave Waitlist
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
            <div class="text-center py-5">
                <div class="mb-3"><i class="fas fa-list-ol text-muted" style="font-size: 4rem; opacity: 0.2;"></i></div>
                <h4 class="fw-bold text-secondary">Your Waitlist is Empty</h4>
                <p class="text-muted mb-4">You can join a waitlist from the reservation page if a room is full.</p>
                <a href="reservation_now.php" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-search me-2"></i>Browse Rooms
                </a>
            </div>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    // Night Mode Logic
    if(localStorage.getItem('nightMode') === 'enabled') {
        document.body.classList.add('night-mode');
    }
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode') {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });

    function confirmLeave(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Leave Waitlist?',
            text: "Are you sure you want to leave this waitlist?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, leave it!'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    }
</script>
</body>
</html>