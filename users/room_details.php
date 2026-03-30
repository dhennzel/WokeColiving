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
$room_capacity = $room['total_beds'];

while($row_occ = mysqli_fetch_assoc($occ_q)){
    $cnt = $row_occ['cnt'];
    if($row_occ['bed_preference'] == 'Whole Room') {
        $total_occupied += $room_capacity;
        $taken_any += $room_capacity;
    } else {
        $total_occupied += $cnt;
        if($row_occ['bed_preference'] == 'Upper Bunk') $taken_upper += $cnt;
        elseif($row_occ['bed_preference'] == 'Lower Bunk') $taken_lower += $cnt;
        else $taken_any += $cnt;
    }
}

$available_beds = max(0, $room['total_beds'] - $total_occupied);
$is_bunk = ($room['room_type'] == '4-Bed' || $room['room_type'] == '6-Bed');
$avail_upper = 0;
$avail_lower = 0;

if($is_bunk){
    $cap_upper = floor($room['total_beds'] / 2);
    $cap_lower = ceil($room['total_beds'] / 2);
    
    $avail_upper = max(0, $cap_upper - $taken_upper);
    $avail_lower = max(0, $cap_lower - $taken_lower);
    
    if($taken_any > 0) {
        $fill_lower = min($avail_lower, $taken_any);
        $avail_lower -= $fill_lower;
        $taken_any -= $fill_lower;
        
        $avail_upper -= $taken_any;
        $avail_upper = max(0, $avail_upper);
    }
}

// Override if Maintenance
if($room['availability'] == 'Maintenance') {
    $available_beds = 0;
    $avail_upper = 0;
    $avail_lower = 0;
}

// Fetch user name for navbar if logged in
$user_name = "";
$unread_count = 0;
if(isset($_SESSION['user_id'])){
    $uid = $_SESSION['user_id'];
    $u_q = mysqli_query($conn, "SELECT first_name FROM users WHERE user_id=$uid");
    if($u_row = mysqli_fetch_assoc($u_q)){
        $user_name = $u_row['first_name'];
        
        // Fetch Unread Count & Notifications
        $unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$uid AND is_read=0");
        $unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
        $notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 10");
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
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        body { overflow: hidden; height: 100vh; }
        .room-img { width: 100%; height: 350px; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        /* Night Mode Overrides */
        body.night-mode { background-color: #121212 !important; color: #e0e0e0; }
        body.night-mode .navbar-user { background: #1f1f1f !important; border-bottom: 1px solid #333 !important; }
        body.night-mode .card, body.night-mode .card-custom { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .border { border-color: #444 !important; }
        body.night-mode .utility-block { background-color: #2c2c2c !important; border-color: #444 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar-user .nav-link, body.night-mode .navbar-user .navbar-brand, body.night-mode .navbar-user .text-muted { color: #34B875 !important; }
        body.night-mode .navbar-toggler { border-color: rgba(255,255,255,0.5); }
        body.night-mode .navbar-toggler-icon { filter: invert(1) brightness(200%); }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
    </style>
</head>
<bodyfunction() {
        const currentUserId = "<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>";
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="d-flex align-items-center gap-3 ms-auto mt-3 mt-lg-0">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="nav-link fw-bold position-relative">
                        My Profile
                        <?php if($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        <?php endif; ?>
                    </a>
                    <span class="text-muted fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_name) ?></span>
                    <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-success rounded-pill px-4 fw-bold">Login</a>
                    <a href="register.php" class="btn btn-custom px-4 fw-bold">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container animate-fade-in d-flex align-items-center justify-content-center" style="height: 100vh; padding-top: 70px;">
    <div class="card card-custom p-4 border-0 shadow-sm w-100" style="max-height: calc(100vh - 100px); overflow-y: auto;">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 anim-trigger delay-1">
                <img src="../assets/images/<?= $room['image'] ?>" class="room-img shadow-sm" alt="<?= $room['room_name'] ?>">
        </div>
            <div class="col-lg-6 anim-trigger delay-2">
            <h2 class="fw-bold text-success display-6"><?= $room['room_name'] ?></h2>
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="badge bg-secondary fs-6"><?= $room['room_type'] ?></span>
                <span class="text-muted"><i class="fas fa-bed me-2"></i><?= $room['total_beds'] ?> Total Beds</span>
                <span class="<?= $available_beds > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><i class="fas fa-check-circle me-2"></i><?= $available_beds ?> Available</span>
            </div>
            
            <div class="d-flex flex-column gap-3 mb-3">
                <div>
                    <div id="whole-room-price-container" class="mb-1" style="display: none;">
                        <h3 class="fw-bold mb-0 text-primary" id="whole-room-price-display"></h3>
                    </div>
                    <h3 class="fw-bold mb-0" id="price-display">₱<?= number_format($room['total_price'], 2) ?> <small class="fs-6 text-muted">/ month</small></h3>
                    <small id="rate-note" class="text-muted fst-italic">Rates are inclusive of water and electric fee.</small>
                </div>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="rate_type" id="short_term_rate" autocomplete="off" checked onchange="updatePrices('short')">
                    <label class="btn btn-outline-success flex-fill" for="short_term_rate">Short Term (1 Mo)</label>

                    <input type="radio" class="btn-check" name="rate_type" id="long_term_rate" autocomplete="off" onchange="updatePrices('long')">
                    <label class="btn btn-outline-success flex-fill" for="long_term_rate">Long Term (6 Mo)</label>

                    <input type="radio" class="btn-check" name="rate_type" id="daily_rate" autocomplete="off" onchange="updatePrices('daily')">
                    <label class="btn btn-outline-success flex-fill" for="daily_rate">Daily</label>
                </div>
            </div>


            <?php if($is_bunk): ?>
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold text-success mb-3"><i class="fas fa-layer-group me-2"></i>Bed Availability</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 border rounded h-100 d-flex flex-column bg-light">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-arrow-down fa-lg me-3 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Lower Bunks</small>
                                    <strong class="<?= $avail_lower > 0 ? 'text-success' : 'text-danger' ?>"><?= $avail_lower ?> Available</strong>
                                    <div id="lower-bunk-price" class="small text-dark fw-bold">₱<?= number_format($room['price_lower'] > 0 ? $room['price_lower'] : $room['total_price'], 2) ?></div>
                                </div>
                            </div>
                            <?php if($avail_lower > 0): ?>
                                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>&bed_preference=Lower+Bunk" class="btn btn-sm btn-outline-primary w-100 mt-auto fw-bold">Book Lower</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary text-white w-100 mt-auto fw-bold" disabled>Fully Booked</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded h-100 d-flex flex-column bg-light">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-arrow-up fa-lg me-3 text-info"></i>
                                <div>
                                    <small class="text-muted d-block">Upper Bunks</small>
                                    <strong class="<?= $avail_upper > 0 ? 'text-success' : 'text-danger' ?>"><?= $avail_upper ?> Available</strong>
                                    <div id="upper-bunk-price" class="small text-dark fw-bold">₱<?= number_format($room['price_upper'] > 0 ? $room['price_upper'] : $room['total_price'], 2) ?></div>
                                </div>
                            </div>
                            <?php if($avail_upper > 0): ?>
                                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>&bed_preference=Upper+Bunk" class="btn btn-sm btn-outline-info text-dark w-100 mt-auto fw-bold">Book Upper</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary text-white w-100 mt-auto fw-bold" disabled>Fully Booked</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

            <div class="d-flex gap-3 mt-4">
                <a href="reservation_now.php?room_type=<?= urlencode($room['room_type']) ?>" class="btn btn-custom btn-lg shadow px-5"><i class="fas fa-calendar-check me-2"></i> Book This Room</a>
                <a href="../index.php" class="btn btn-secondary-custom btn-lg px-4">Back</a>
            </div>
        </div>
    </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="users_JS/app.js"></script>
<script>
    const prices = {
        short: {
            lower: <?= (float)($room['price_lower'] > 0 ? $room['price_lower'] : $room['total_price']) ?>,
            upper: <?= (float)($room['price_upper'] > 0 ? $room['price_upper'] : $room['total_price']) ?>,
            base: <?= (float)$room['total_price'] ?>,
            whole: <?= (float)($room['price_whole'] ?? 0) ?>,
            note: 'Rates are inclusive of water and electric fee.'
        },
        long: {
            lower: <?= (float)($room['long_term_price_lower'] ?? 0) ?>,
            upper: <?= (float)($room['long_term_price_upper'] ?? 0) ?>,
            base: <?= (float)(($room['long_term_price_lower'] ?? 0) > 0 ? $room['long_term_price_lower'] : 0) ?>,
            whole: <?= (float)($room['long_term_price_whole'] ?? 0) ?>,
            note: 'Rates are for 6 months contract lease & EXCLUDES utility charges.'
        },
        daily: {
            lower: <?= (float)($room['daily_price_bed'] ?? 0) ?>,
            upper: <?= (float)($room['daily_price_bed'] ?? 0) ?>,
            base: <?= (float)($room['daily_price_room'] ?? 0) ?>,
            whole: <?= (float)($room['daily_price_room'] ?? 0) ?>,
            note: 'Standard daily rates.'
        }
    };

    function updatePrices(term) {
        const priceData = prices[term];
        const wholeRoomContainer = document.getElementById('whole-room-price-container');
        const perBedDisplay = document.getElementById('price-display');

        // Update note
        document.getElementById('rate-note').innerText = priceData.note;

        // Update bunk prices if they exist
        const lowerPriceEl = document.getElementById('lower-bunk-price');
        const upperPriceEl = document.getElementById('upper-bunk-price');

        if (lowerPriceEl && priceData.lower > 0) {
            lowerPriceEl.innerText = `₱${priceData.lower.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        }
        if (upperPriceEl && priceData.upper > 0) {
            upperPriceEl.innerText = `₱${priceData.upper.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        }

        // Handle Whole Room vs Per Bed display
        if (priceData.whole > 0) {
            wholeRoomContainer.style.display = 'block';
            document.getElementById('whole-room-price-display').innerHTML = `<small class="text-muted fs-6">Whole Room:</small> ₱${priceData.whole.toLocaleString('en-US', {minimumFractionDigits: 2})} <small class="fs-6 text-muted">/ ${term === 'daily' ? 'night' : 'month'}</small>`;
        } else {
            wholeRoomContainer.style.display = 'none';
        }

        // Always show per bed/base price if it exists
        if(priceData.base > 0 || priceData.lower > 0) {
            perBedDisplay.style.display = 'block'; // Show per-bed price
            let priceHtml = '';
            if(priceData.lower > 0 && priceData.upper > 0 && priceData.lower !== priceData.upper) {
                 // Show range
                 let min = Math.min(priceData.lower, priceData.upper);
                 let max = Math.max(priceData.lower, priceData.upper);
                 priceHtml = `₱${min.toLocaleString('en-US', {minimumFractionDigits: 2})} - ₱${max.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            } else {
                 let p = priceData.base > 0 ? priceData.base : (priceData.lower > 0 ? priceData.lower : priceData.upper);
                 priceHtml = `₱${p.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            }
            perBedDisplay.innerHTML = `${priceHtml} <small class="fs-6 text-muted">/ ${term === 'daily' ? 'night' : 'month'}</small>`;
        } else {
            perBedDisplay.style.display = 'none';
        }
    }

    // Initialize prices on load
    document.addEventListener('DOMContentLoaded', function() {
        updatePrices('short');
    });

    // Night Mode Logic
    const currentUserId = "<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>";
    if(currentUserId && localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
        document.body.classList.add('night-mode');
    } else if (!currentUserId && localStorage.getItem('nightMode') === 'enabled') {
        document.body.classList.add('night-mode');
    }

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (currentUserId && e.key === 'nightMode_' + currentUserId) {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        } else if (!currentUserId && e.key === 'nightMode') {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });
</script>
</body>
</html>