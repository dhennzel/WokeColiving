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
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>