<?php
include '../db.php';
session_start();

// Redirect to login if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get User Info for Navbar
$u_query = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

// GET ROOM ID
$room_id = (int)$_GET['id'];
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

$room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$room_id"));

// Calculate available beds
function getAvailableBeds($conn, $room_id, $checkin, $checkout) {
    $query = mysqli_query($conn, "
        SELECT COUNT(*) AS booked
        FROM reservations
        WHERE room_id = $room_id
        AND status IN ('Pending','Approved')
        AND start_date < '$checkout'
        AND end_date > '$checkin'
    ");
    $res = mysqli_fetch_assoc($query);
    return $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_beds FROM rooms WHERE room_id=$room_id"))['total_beds'] - $res['booked'];
}

$available_beds = getAvailableBeds($conn, $room_id, $checkin, $checkout);

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $room['room_name'] ?> | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="../index.php">
        <img src="../Images/WokeLogo.jpg
    </a>
    <div class="d-flex align-items-center gap-3">
        <a href="profile.php" class="text-white text-decoration-none fw-bold">My Profile</a>
        <span class="text-white fw-bold">Hello, <?= htmlspecialchars($user_info['full_name']) ?></span>
        <a href="logout.php" class="btn btn-sm btn-light rounded-pill fw-bold">Logout</a>
    </div>
</div>

<!-- ROOM DETAILS -->
<div class="container mt-5">
    <a href="reservation_now.php" class="btn btn-outline-secondary mb-3">&larr; Back to Rooms</a>
    <div class="card border-0 shadow-sm p-4">
    <div class="row">

        <!-- IMAGE -->
        <div class="col-md-6">
            <img src="../assets/images/<?= $room['image'] ?>" class="room-image">
        </div>

        <!-- INFO + RESERVATION -->
        <div class="col-md-6 room-info">
            <h2><?= $room['room_name'] ?></h2>
            <p class="text-muted">Type: <strong><?= $room['room_type'] ?></strong></p>
            <p class="price">₱<?= number_format($room['total_price'],2) ?> <span>/ month</span></p>
            
            <div class="mb-3">
                <span class="badge bg-success rounded-pill px-3 py-2">Beds Left: <?= $available_beds ?></span>
            </div>

            <?php if(isset($error)) { ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php } ?>
            <?php if(isset($success)) { ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php } ?>
        </div>
    </div>
    </div>
</div>

</body>
</html>