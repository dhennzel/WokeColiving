<?php
session_start();
include '../db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$room_id = (int)$_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id = $room_id");

if (mysqli_num_rows($query) == 0) {
    header("Location: index.php");
    exit;
}

$room = mysqli_fetch_assoc($query);

// Calculate Availability
$rid = $room['room_id'];
$today = date('Y-m-d');

// Fetch occupancy by bed preference
$occ_q = mysqli_query($conn, "SELECT bed_preference, count(*) as cnt FROM reservations WHERE room_id=$rid AND status IN ('Pending','Approved') AND start_date <= '$today' AND end_date > '$today' GROUP BY bed_preference");

$taken_upper = 0;
$taken_lower = 0;
$taken_any = 0;
$total_occupied = 0;

while($row_occ = mysqli_fetch_assoc($occ_q)){
    $cnt = $row_occ['cnt'];
    $total_occupied += $cnt;
    if($row_occ['bed_preference'] == 'Upper Bunk') $taken_upper += $cnt;
    elseif($row_occ['bed_preference'] == 'Lower Bunk') $taken_lower += $cnt;
    else $taken_any += $cnt;
}

$available_beds = max(0, $room['total_beds'] - $total_occupied);
$is_bunk = ($room['room_type'] == '4-Bed' || $room['room_type'] == '6-Bed');
$avail_upper = 0;
$avail_lower = 0;

if($is_bunk){
    $cap_upper = floor($room['total_beds'] / 2);
    $cap_lower = ceil($room['total_beds'] / 2);
    
    // Distribute 'Any' to Lower first
    $slots_lower_free = max(0, $cap_lower - $taken_lower);
    $any_in_lower = min($taken_any, $slots_lower_free);
    $any_in_upper = $taken_any - $any_in_lower;
    
    $avail_lower = max(0, $cap_lower - ($taken_lower + $any_in_lower));
    $avail_upper = max(0, $cap_upper - ($taken_upper + $any_in_upper));
}

// Fetch user name for navbar if logged in
$user_name = "";
if(isset($_SESSION['user_id'])){
    $uid = $_SESSION['user_id'];
    $u_q = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id=$uid");
    if($u_row = mysqli_fetch_assoc($u_q)){
        $user_name = $u_row['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $room['room_name'] ?> | Woke Coliving INC</title>
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
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); padding-top: 80px; }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .navbar { background: var(--dark-green); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; padding: 10px 30px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        .room-img { width: 100%; height: 350px; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a href="index.php" class="nav-link text-white">Home</a></li>
                <li class="nav-item"><a href="index.php#rooms" class="nav-link text-white">Rooms</a></li>
            </ul>
            <div class="d-flex gap-2 ms-3">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="text-white text-decoration-none fw-bold me-3">My Profile</a>
                    <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
                    <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light rounded-pill px-4">Login</a>
                    <a href="register.php" class="btn btn-custom">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <img src="../assets/images/<?= $room['image'] ?>" class="room-img" alt="<?= $room['room_name'] ?>">
        </div>
        <div class="col-lg-6">
            <h2 class="fw-bold text-success display-6"><?= $room['room_name'] ?></h2>
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge bg-secondary fs-6"><?= $room['room_type'] ?></span>
                <span class="text-muted"><i class="fas fa-bed me-2"></i><?= $room['total_beds'] ?> Total Beds</span>
                <span class="<?= $available_beds > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><i class="fas fa-check-circle me-2"></i><?= $available_beds ?> Available</span>
            </div>
            
            <?php if($is_bunk): ?>
            <div class="mb-4 p-3 bg-white rounded shadow-sm border">
                <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-layer-group me-2"></i>Bed Availability</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 border rounded h-100 d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-arrow-down fa-lg me-3 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Lower Bunks</small>
                                    <strong class="<?= $avail_lower > 0 ? 'text-success' : 'text-danger' ?>"><?= $avail_lower ?> Available</strong>
                                </div>
                            </div>
                            <?php if($avail_lower > 0): ?>
                                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>&bed_preference=Lower+Bunk" class="btn btn-sm btn-outline-primary w-100 mt-auto">Book Lower</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light text-muted w-100 mt-auto" disabled>Fully Booked</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded h-100 d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-arrow-up fa-lg me-3 text-info"></i>
                                <div>
                                    <small class="text-muted d-block">Upper Bunks</small>
                                    <strong class="<?= $avail_upper > 0 ? 'text-success' : 'text-danger' ?>"><?= $avail_upper ?> Available</strong>
                                </div>
                            </div>
                            <?php if($avail_upper > 0): ?>
                                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>&bed_preference=Upper+Bunk" class="btn btn-sm btn-outline-info w-100 mt-auto">Book Upper</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light text-muted w-100 mt-auto" disabled>Fully Booked</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <h3 class="fw-bold mb-3">₱<?= number_format($room['total_price'], 2) ?> <small class="fs-6 text-muted">/ month</small></h3>
            
            <p class="lead text-muted mb-3" style="font-size: 1rem;">Experience comfort and community in our <?= strtolower($room['room_type']) ?>. Fully furnished and ready for move-in. Perfect for students and professionals looking for a hassle-free stay.</p>
            
            <h5 class="fw-bold mb-2">Room Amenities</h5>
            <ul class="list-unstyled mb-3 row">
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> High-Speed Wi-Fi</li>
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> Air Conditioning</li>
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> Study Desk & Chair</li>
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> Wardrobe Cabinet</li>
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> Comfortable Mattress</li>
                <li class="col-6 mb-2"><i class="fas fa-check-circle text-success me-2"></i> Daily Housekeeping</li>
            </ul>

            <div class="d-flex gap-3">
                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>" class="btn btn-custom btn-lg shadow px-5">Book This Room</a>
                <a href="index.php" class="btn btn-outline-secondary btn-lg px-4">Back</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>