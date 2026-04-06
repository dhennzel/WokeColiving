<?php
session_start();
include '../db.php';

// Fetch Hero Image
$hero_images = ['../assets/images/hero.jpg']; // Default
$q = @mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
if($q && $row = mysqli_fetch_assoc($q)){
    $decoded = json_decode($row['setting_value'], true);
    if(is_array($decoded) && count($decoded) > 0){
        $hero_images = [];
        foreach($decoded as $img){
             if(file_exists("../assets/images/" . $img)) $hero_images[] = "../assets/images/" . $img;
        }
        if(empty($hero_images)) $hero_images = ['../assets/images/hero.jpg'];
    } elseif (!empty($row['setting_value']) && file_exists("../assets/images/" . $row['setting_value'])) {
        $hero_images = ["../assets/images/" . $row['setting_value']];
    }
}

// Fetch Living Area Image
$living_area_img = '../assets/images/hero.jpg'; // Default
$q_la = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='living_area_image'");
if($row_la = mysqli_fetch_assoc($q_la)){
    if(!empty($row_la['setting_value']) && file_exists("../assets/images/" . $row_la['setting_value'])){
        $living_area_img = "../assets/images/" . $row_la['setting_value'];
    }
}

// Handle Contact Form
if(isset($_POST['send_message'])){
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $msg = htmlspecialchars($_POST['message']);
    
    $to = "info@wokecoliving.com"; 
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $body = "<h3>New Contact Message</h3><p><strong>Name:</strong> $name</p><p><strong>Email:</strong> $email</p><p><strong>Message:</strong><br>$msg</p>";
    
    @mail($to, "Contact: $subject", $body, $headers);
    $_SESSION['swal'] = ['title' => 'Message Sent!', 'text' => 'Thank you for contacting us. We will get back to you shortly.', 'icon' => 'success'];
    header("Location: index.php");
    exit;
}

// Fetch Available Rooms
// Fetch all rooms using centralized function for accurate occupancy data
$all_rooms = get_all_rooms_with_occupancy($conn);

// Group rooms by type
$grouped_rooms = [];
foreach ($all_rooms as $room) {
    if(isset($room['is_archived']) && $room['is_archived'] == 1) continue;
    $type = $room['room_type'];
    if (!isset($grouped_rooms[$type])) $grouped_rooms[$type] = [];
    $grouped_rooms[$type][] = $room;
}

// Check for active booking
$has_active_booking = false;
if(isset($_SESSION['user_id'])){
    $uid = (int)$_SESSION['user_id'];
    $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id = $uid AND status IN ('Pending', 'Approved')");
    if(mysqli_num_rows($check_active) > 0) {
        $has_active_booking = true;
    }

    // Fetch Unread Count & Notifications
    $unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$uid AND is_read=0");
    $unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
    $notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 10");

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../CSS/index.css">
    <link rel="stylesheet" href="users_CSS/index.css">
    <style>
        :root {
            --primary-green: #34B875;
            --dark-green: #2A9A60;
            --accent-yellow: #F0B429;
            --light-bg: #F4F7F6;
            --text-dark: #2C3E50;
            --app-radius: 16px;
            --app-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--primary-green); border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--dark-green); }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6, .navbar-brand { font-family: 'Poppins', sans-serif; font-weight: 700; }
        .hero-section { position: relative; color: white; text-align: center; overflow: hidden; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .hero-bg-carousel { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .hero-bg-carousel .carousel-item, .hero-bg-carousel .active { height: 100%; }
        .hero-bg-carousel img { width: 100%; height: 100%; object-fit: cover; transition: transform 10s linear; }
        .hero-bg-carousel .carousel-item.active img { transform: scale(1.1); }
        .hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(42, 154, 96, 0.8), rgba(52, 184, 117, 0.6)); z-index: 1; }
        .hero-content { position: relative; z-index: 2; opacity: 0; transform: translateY(30px); animation: fadeInUp 1s ease-out forwards 0.5s, float 4s ease-in-out infinite 1.5s; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0px); } }
        .feature-icon { font-size: 3.5rem; color: var(--primary-green); margin-bottom: 15px; transition: transform 0.3s; }
        .feature-card:hover .feature-icon { transform: scale(1.1) rotate(5deg); color: var(--accent-yellow); }
        .room-card { border: 2px solid var(--primary-green); border-radius: var(--app-radius); overflow: hidden; box-shadow: var(--app-shadow); transition: all 0.4s ease; background: #FFFFFF; }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(52, 184, 117, 0.15); }
        .room-img-wrapper { height: 250px; overflow: hidden; position: relative; }
        .room-img-wrapper img { transition: transform 0.5s ease; width: 100%; height: 100%; object-fit: cover; }
        .room-card:hover .room-img-wrapper img { transform: scale(1.1); }
        .btn-custom { background-color: var(--primary-green); color: #FFFFFF; font-weight: 600; border-radius: 50px; padding: 10px 30px; border: none; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(52, 184, 117, 0.3); }
        .btn-custom:hover { background-color: var(--dark-green); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(52, 184, 117, 0.4); color: #FFFFFF; }
        .navbar { background: transparent; padding: 20px 0; transition: all 0.4s ease; }
        .nav-link { position: relative; transition: color 0.3s ease; }
        .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; bottom: 0; left: 0; background-color: var(--accent-yellow); transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }
        .navbar.scrolled { background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); padding: 12px 0; box-shadow: var(--app-shadow); border-bottom: 2px solid var(--primary-green); }
        .navbar.scrolled .nav-link, .navbar.scrolled .navbar-brand { color: var(--text-dark) !important; }
        .navbar.scrolled .btn-outline-light { color: var(--primary-green) !important; border-color: var(--primary-green) !important; }
        .navbar.scrolled .btn-outline-light:hover { background-color: var(--primary-green) !important; color: #FFF !important; }
        .section-title { color: var(--text-dark); font-weight: 700; margin-bottom: 10px; position: relative; display: inline-block; }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        .badge-custom { background-color: rgba(255, 255, 255, 0.9); color: var(--dark-green); border: 1px solid var(--primary-green); }
        footer { background: var(--dark-green); color: white; padding: 3rem 0; margin-top: 3rem; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; }
        footer a:hover { color: var(--accent-yellow); }
        .feature-card { transition: transform 0.3s, box-shadow 0.3s; border: none; border-radius: var(--app-radius); background: #FFFFFF; padding: 2.5rem 1.5rem; height: 100%; box-shadow: var(--app-shadow); }
        .feature-card:hover { transform: translateY(-15px) scale(1.05); box-shadow: 0 25px 50px rgba(46, 125, 50, 0.2); border: 1px solid var(--accent-yellow); }
        .contact-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .room-img { width: 100%; height: 400px; object-fit: cover; border-radius: var(--app-radius); box-shadow: var(--app-shadow); }
        .amenities-scroll-container { display: flex; overflow-x: auto; scroll-behavior: smooth; gap: 1.5rem; padding: 15px 5px; -ms-overflow-style: none; scrollbar-width: none; }
        .amenities-scroll-container::-webkit-scrollbar { display: none; }
        .amenity-item { flex: 0 0 calc(25% - 1.125rem); min-width: 260px; }
        @media (max-width: 992px) { .amenity-item { flex: 0 0 calc(33.333% - 1rem); } }
        @media (max-width: 768px) { .amenity-item { flex: 0 0 calc(50% - 0.75rem); } }
        @media (max-width: 576px) { .amenity-item { flex: 0 0 100%; } }
        .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 45px; height: 45px; border-radius: 50%; background: #FFFFFF; border: 1px solid #f4f4f4; box-shadow: 0 4px 12px rgba(0,0,0,0.1); color: var(--primary-green); font-size: 1.2rem; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; transition: all 0.3s; }
        .slider-btn:hover { background: #fff; box-shadow: 0 6px 16px rgba(0,0,0,0.15); color: var(--dark-green); }
        .prev-btn { left: -15px; }
        .next-btn { right: -15px; }
        .amenity-card { transition: transform 0.3s, box-shadow 0.3s; border-radius: var(--app-radius) !important; border: 2px solid var(--primary-green); }
        .amenity-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(52, 184, 117, 0.15) !important; }
        .amenity-icon { width: 70px; height: 70px; line-height: 70px; margin: 0 auto; background: rgba(52, 184, 117, 0.1); border-radius: 50%; transition: all 0.3s; }
        .amenity-card:hover .amenity-icon { background: var(--primary-green); }
        .amenity-card:hover .amenity-icon i { color: #ffffff !important; }
        
        /* Scroll to Top Button */
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(52, 184, 117, 0.8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            text-decoration: none;
        }
        .scroll-top-btn.visible { opacity: 1; visibility: visible; transform: translateY(0); }
        .scroll-top-btn:hover { background: rgba(42, 154, 96, 0.9); transform: translateY(-5px); box-shadow: 0 8px 25px rgba(52, 184, 117, 0.3); color: white; }

        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar.scrolled { background: rgba(30, 30, 30, 0.95) !important; border-bottom: 2px solid var(--primary-green) !important; }
        body.night-mode .navbar.scrolled .nav-link, body.night-mode .navbar.scrolled .navbar-brand { color: #34B875 !important; }
        body.night-mode .card, body.night-mode .room-card, body.night-mode .feature-card, body.night-mode .contact-card, body.night-mode .amenity-card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .bg-white { background-color: #1e1e1e !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .section-title { color: #e0e0e0 !important; }
        body.night-mode .slider-btn { background-color: #2c2c2c !important; border-color: #444 !important; color: #e0e0e0 !important; }
        body.night-mode .slider-btn:hover { background-color: #444 !important; color: var(--primary-green) !important; }
        body.night-mode .form-control { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .form-control:focus { background-color: #333 !important; color: #fff !important; }
        body.night-mode footer { background-color: #1a1a1a !important; }
        body.night-mode .contact-card .bg-success { background-color: #1b5e20 !important; }
        body.night-mode .amenity-icon { background: rgba(255, 255, 255, 0.1) !important; }
        body.night-mode .amenity-card:hover .amenity-icon { background: var(--primary-green) !important; color: #fff !important; }
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
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 30px; height: 30px; object-fit: cover;" class="me-2 rounded-circle">
            Woke Coliving INC
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a href="#home" class="nav-link text-white">Home</a></li>
                <li class="nav-item"><a href="#rooms" class="nav-link text-white">Rooms</a></li>
                <li class="nav-item"><a href="#amenities" class="nav-link text-white">Amenities</a></li>
                <li class="nav-item"><a href="#contact" class="nav-link text-white">Contact</a></li>
            </ul>
        <div class="d-flex gap-2">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-light text-success fw-bold rounded-pill px-4 position-relative" onclick="this.querySelector('.badge')?.style.setProperty('display', 'none', 'important');" title="Go to Dashboard">
                    Dashboard
                    <?php if($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.55rem; padding: 0.25rem 0.4rem;"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-custom">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-light text-success fw-bold rounded-pill px-4">Login</a>
                <a href="register.php" class="btn btn-custom">Register</a>
            <?php endif; ?>
        </div>
        </div>
</nav>

<!-- HERO SECTION -->
<div class="hero-section" id="home">
    <div class="hero-overlay"></div>
    <div id="heroCarousel" class="carousel slide carousel-fade hero-bg-carousel" data-bs-ride="carousel" data-bs-pause="false" data-bs-interval="5000">
        <div class="carousel-inner">
            <?php foreach($hero_images as $index => $img): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <img src="<?= $img ?>" alt="Hero Background">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="container hero-content">
        <h1 class="display-3 fw-bold mb-3" data-aos="fade-down" data-aos-duration="1000">Convenient and Affordable Dormitory and Bed spaces</h1>
        <p class="lead mb-5 fs-4" data-aos="fade-up" data-aos-delay="200">Your home away from home. Affordable, comfortable, and community-driven living spaces designed for you.</p>
        <a href="reservation_now.php" class="btn btn-custom btn-lg shadow-lg" data-aos="zoom-in" data-aos-delay="400"><i class="fas fa-search me-2"></i>Find Your Room Now</a>
    </div>
</div>


<!-- ROOMS SECTION -->
<div class="container py-5 mb-0" id="rooms">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="section-title display-5">Available Rooms</h2>
        <p class="text-muted">Choose the perfect space for your needs.</p>
    </div>
    <div class="row g-4">
        <?php foreach($grouped_rooms as $type => $rooms_in_type): 
            $type_total_beds = array_sum(array_column($rooms_in_type, 'total_beds'));
            $type_avail_beds = array_sum(array_column($rooms_in_type, 'available_beds'));
            $first_room = $rooms_in_type[0] ?? null;
            if (!$first_room) continue;

            $image = $first_room['image'];
            $price = $first_room['total_price'];
            $p_upper = $first_room['price_upper'];
            $p_lower = $first_room['price_lower'];
        ?>
        <div class="col-lg-4 col-md-6" data-aos="fade-up">
            <div class="card room-card h-100 border-0 shadow-sm">
                <div class="room-img-wrapper">
                    <img src="../assets/images/<?= $image ?>" alt="<?= $type ?>">
                </div>
                <div class="card-body text-center p-4">
                    <h3 class="fw-bold text-dark mb-2"><?= $type ?></h3>
                    <?php if($type != 'Single'): ?>
                        <div class="mb-2">
                            <span class="text-primary fw-bold small">Upper: ₱<?= number_format($p_upper, 2) ?></span><br>
                            <span class="text-success fw-bold small">Lower: ₱<?= number_format($p_lower, 2) ?></span>
                        </div>
                    <?php else: ?>
                        <p class="price-tag mb-2 fw-bold text-success">₱<?= number_format($price, 2) ?> <small class="text-muted fs-6">/mo</small></p>
                    <?php endif; ?>
                    <a href="room_details.php?id=<?= $first_room['room_id'] ?>" class="btn btn-outline-success rounded-pill px-4">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<hr class="container my-4 opacity-25" data-aos="zoom-in">

<!-- AMENITIES SECTION -->
<div class="container py-5 mb-5" id="amenities">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="section-title display-5">Our Amenities</h2>
        <p class="text-muted">Everything you need for comfortable living</p>
    </div>
    
    <div class="position-relative px-2 px-md-4">
        <button class="slider-btn prev-btn d-none d-md-flex" id="prevAmenity"><i class="fas fa-chevron-left"></i></button>

        <div class="amenities-scroll-container pb-2" id="amenitiesScroll">
            
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="50">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-wifi fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Wifi</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="100">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-broom fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Monthly Housekeeping</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="150">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-couch fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Fully Furnished</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="200">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-bath fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Ensuite shower and WC</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="250">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white position-relative">
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Coming Soon</span>
                    <div class="amenity-icon mb-3"><i class="fas fa-glass-cheers fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Roof top lounge and bar</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="300">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-user-clock fa-2x text-success"></i></div>
                    <h5 class="fw-bold">24H Concierge</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="350">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-lock fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Lockers</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="400">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white position-relative">
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Coming Soon</span>
                    <div class="amenity-icon mb-3"><i class="fas fa-utensils fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Food & beverage room service</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="450">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-parking fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Car and motorbike parking</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="500">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-map-marker-alt fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Location</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="550">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-tools fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Regular Maintenance</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="600">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-file-signature fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Flexible Contracts</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="650">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-comments fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Lounges and common areas</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="700">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white">
                    <div class="amenity-icon mb-3"><i class="fas fa-shield-alt fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Security guard and CCTV</h5>
                </div>
            </div>
            <div class="amenity-item" data-aos="fade-up" data-aos-delay="750">
                <div class="amenity-card p-4 text-center h-100 rounded-4 shadow-sm bg-white position-relative">
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Coming Soon</span>
                    <div class="amenity-icon mb-3"><i class="fas fa-users-rectangle fa-2x text-success"></i></div>
                    <h5 class="fw-bold">Meeting room</h5>
                </div>
            </div>
            
        </div>
        
        <button class="slider-btn next-btn d-none d-md-flex" id="nextAmenity"><i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<!-- FEATURES 
<div class="container py-4 mb-5" id="features">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="section-title display-5">Why Choose Us?</h2>
        <p class="text-muted">We provide more than just a bed; we provide a lifestyle.</p>
    </div>
    <div class="row text-center g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="p-5 border-0 rounded-4 shadow-sm h-100 bg-white feature-card">
                <div class="feature-icon"><i class="fas fa-bed"></i></div>
                <h3 class="fw-bold mb-3">Fully Furnished</h3>
                <p class="text-muted">Move in hassle-free with our ready-to-live rooms equipped with all essentials.</p>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="p-5 border-0 rounded-4 shadow-sm h-100 bg-white feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3 class="fw-bold mb-3">Community</h3>
                <p class="text-muted">Connect with like-minded individuals and grow your network in our shared spaces.</p>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="p-5 border-0 rounded-4 shadow-sm h-100 bg-white feature-card">
                <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3 class="fw-bold mb-3">Prime Location</h3>
                <p class="text-muted">Located in the heart of the city, close to transport, work, and leisure.</p>
            </div>
        </div>
    </div>
</div>-->

<!-- INFO SECTION -->
<div class="container py-5 mb-5">
    <div class="row align-items-center g-5">
        <div class="col-md-6" data-aos="fade-right">
            <img src="<?= $living_area_img ?>" class="img-fluid rounded-4 shadow-lg" alt="Living Area">
        </div>
        <div class="col-md-6" data-aos="fade-left">
            <h2 class="fw-bold text-dark mb-4 display-6">Comfort Meets Convenience</h2>
            <p class="text-muted lead mb-4">Our spaces are designed to foster productivity and relaxation. Whether you need a quiet corner to study or a vibrant lounge to socialize, we have it all.</p>
            <ul class="list-unstyled mb-4">
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> High-Speed Wi-Fi</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> 24/7 Security & CCTV</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Regular Housekeeping</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Modern Kitchen & Laundry</li>
            </ul>
            <a href="reservation_now.php" class="btn btn-outline-success rounded-pill px-4 fw-bold">Book a Visit</a>
        </div>
    </div>
</div>

<!-- TESTIMONIALS -->
<div class="container py-5 mb-5 bg-light rounded-4">
    <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="fw-bold text-success display-5">What Our Residents Say</h2>
        <p class="text-muted lead">Real stories from our community.</p>
    </div>
    
    <div id="testimonialCarousel" class="carousel slide text-center" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <i class="fas fa-quote-left fa-2x text-warning mb-3"></i>
                        <p class="lead fst-italic">"Woke Coliving has completely changed my university experience. The community is amazing, and the facilities are top-notch. It feels like a second home."</p>
                        <h5 class="fw-bold mt-3">- Sarah M., Student</h5>
                    </div>
                </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <i class="fas fa-quote-left fa-2x text-warning mb-3"></i>
                        <p class="lead fst-italic">"As a young professional, I needed a place that was quiet enough to work but social enough to meet people. This place strikes the perfect balance."</p>
                        <h5 class="fw-bold mt-3">- James D., Freelancer</h5>
                    </div>
                </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <i class="fas fa-quote-left fa-2x text-warning mb-3"></i>
                        <p class="lead fst-italic">"The staff are incredibly helpful and the location is unbeatable. I've made lifelong friends here. Highly recommended!"</p>
                        <h5 class="fw-bold mt-3">- Emily R., Nurse</h5>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>

<!-- CONTACT SECTION -->
<div class="container py-5 mb-5" id="contact">
    <div class="row justify-content-center" data-aos="fade-up">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg overflow-hidden rounded-4 contact-card">
                <div class="row g-0">
                    <div class="col-md-5 bg-success text-white p-5 d-flex flex-column justify-content-center position-relative">
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: #34B875;"></div>
                        <div class="position-relative z-2">
                            <h3 class="fw-bold mb-4">Get in Touch</h3>
                            <p class="mb-4 opacity-75">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-phone-alt fa-lg me-3 text-warning"></i>
                                <span>+63 912 345 6789</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope fa-lg me-3 text-warning"></i>
                                <span>info@wokecoliving.com</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt fa-lg me-3 text-warning"></i>
                                <span>123 Coliving Street, City Center</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 bg-white p-5">
                        <h4 class="fw-bold text-dark mb-4">Send us a Message</h4>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                        <label for="name">Your Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
                                        <label for="email">Your Email</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" placeholder="Leave a message here" id="message" name="message" style="height: 150px" required></textarea>
                                        <label for="message">Message</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-custom w-100 py-3" type="submit" name="send_message">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="container text-center">
        <h4 class="fw-bold mb-3 font-monospace">Woke Coliving INC</h4>
        <p class="mb-4 opacity-75">Redefining urban living for the modern generation.</p>
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
            <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
            <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
        </div>
        <small class="opacity-50">&copy; <?= date('Y') ?> Woke Coliving INC. All rights reserved.</small>
    </div>
</footer>

<!-- Scroll to Top Button -->
<a href="#" class="scroll-top-btn" id="scrollTopBtn"><i class="fas fa-chevron-up"></i></a>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
      duration: 1000,
      once: true,
      offset: 100
  });

  // Navbar Scroll Effect
  window.addEventListener('scroll', function() {
      if (window.scrollY > 50) {
          document.querySelector('.navbar').classList.add('scrolled');
      } else {
          document.querySelector('.navbar').classList.remove('scrolled');
      }
  });

  // Slider Navigation Logic
  document.getElementById('nextAmenity').addEventListener('click', function() {
      const container = document.getElementById('amenitiesScroll');
      container.scrollBy({ left: 300, behavior: 'smooth' }); 
  });
  document.getElementById('prevAmenity').addEventListener('click', function() {
      const container = document.getElementById('amenitiesScroll');
      container.scrollBy({ left: -300, behavior: 'smooth' });
  });

  // Scroll to Top Logic
  const scrollTopBtn = document.getElementById('scrollTopBtn');
  window.addEventListener('scroll', function() {
      if (window.scrollY > 300) scrollTopBtn.classList.add('visible');
      else scrollTopBtn.classList.remove('visible');
  });
  scrollTopBtn.addEventListener('click', function(e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    <?php if(isset($_SESSION['user_id'])): ?>
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
    const currentUserId = "<?= $_SESSION['user_id'] ?>";
    <?php if(isset($_SESSION['night_mode'])): ?>
        // Sync LocalStorage with DB preference
        if(<?= $_SESSION['night_mode'] ?> === 1) localStorage.setItem('nightMode_' + currentUserId, 'enabled');
        else localStorage.setItem('nightMode_' + currentUserId, 'disabled');
    <?php else: ?>
        if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') document.body.classList.add('night-mode');
    <?php endif; ?>

    // Sync Night Mode across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode_' + currentUserId) {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });
    <?php endif; ?>
</script>
</body>
</html>
