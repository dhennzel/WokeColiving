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
$rooms_q = mysqli_query($conn, "SELECT * FROM rooms WHERE status='Available' GROUP BY room_type ORDER BY total_beds ASC LIMIT 6");

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
    <style>
        :root {
            --primary-green: #2E7D32;
            --dark-green: #1B5E20;
            --accent-yellow: #FBC02D;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
        }
        html {
            scroll-behavior: smooth;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary-green);
            border-radius: 5px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--dark-green);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            overflow-x: hidden;
        }
        h1, h2, h3, .navbar-brand {
            font-family: 'Playfair Display', serif;
        }
        .hero-section {
            position: relative;
            color: white;
            text-align: center;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .navbar {
            background: transparent;
            padding: 20px 0;
            transition: all 0.4s ease;
        }
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--accent-yellow);
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .navbar.scrolled {
            background: rgba(27, 94, 32, 0.9) !important;
            backdrop-filter: blur(10px);
            padding: 12px 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .hero-bg-carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .hero-bg-carousel .carousel-item, .hero-bg-carousel .active { height: 100%; }
        .hero-bg-carousel img { 
            width: 100%; height: 100%; object-fit: cover; 
            transition: transform 10s linear;
        }
        .hero-bg-carousel .carousel-item.active img {
            transform: scale(1.1);
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(27, 94, 32, 0.7), rgba(27, 94, 32, 0.5));
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease-out forwards 0.5s, float 4s ease-in-out infinite 1.5s;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .feature-icon {
            font-size: 3.5rem;
            color: var(--primary-green);
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--accent-yellow);
        }
        .room-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            background: white;
        }
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .room-img-wrapper {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        .room-img-wrapper img {
            transition: transform 0.5s ease;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .room-card:hover .room-img-wrapper img {
            transform: scale(1.1);
        }
        .btn-custom {
            background-color: var(--accent-yellow);
            color: var(--dark-green);
            font-weight: 700;
            border-radius: 50px;
            padding: 12px 35px;
            border: none;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(251, 192, 45, 0.3);
        }
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 20px;
            background: white;
            padding: 2.5rem 1.5rem;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .feature-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 25px 50px rgba(46, 125, 50, 0.2);
            border: 1px solid var(--accent-yellow);
        }
        .feature-icon {
            font-size: 3rem;
            color: var(--accent-yellow);
            background: var(--dark-green);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            transition: transform 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .feature-card:hover .feature-icon {
            transform: rotateY(180deg);
        }
        .btn-custom:hover {
            background-color: #F9A825;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(251, 192, 45, 0.4);
            color: var(--dark-green);
        }
        footer {
            background: var(--dark-green);
            color: white;
            padding: 3rem 0;
            margin-top: 3rem;
        }
        .section-title {
            color: var(--dark-green);
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        .section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: var(--accent-yellow);
            margin: 10px auto 0;
            border-radius: 2px;
        }
        /* Scroll Animations */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .badge-custom {
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--dark-green);
            border: 1px solid var(--primary-green);
        }
        .contact-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; }
        footer a:hover { color: var(--accent-yellow); }
        .room-img { width: 100%; height: 400px; object-fit: cover; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
    </style>
</head>
<body>

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
                <li class="nav-item"><a href="#features" class="nav-link text-white">Amenities</a></li>
                <li class="nav-item"><a href="#contact" class="nav-link text-white">Contact</a></li>
            </ul>
        <div class="d-flex gap-2">
            <?php if(isset($_SESSION['user_id'])): ?>
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
                <a href="profile.php" class="btn btn-outline-light rounded-pill px-4">My Profile</a>
                <a href="logout.php" class="btn btn-custom text-dark fw-bold">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light rounded-pill px-4">Login</a>
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
        <?php while($room = mysqli_fetch_assoc($rooms_q)): ?>
        <div class="col-lg-4 col-md-6" data-aos="fade-up">
            <div class="card room-card h-100 border-0 shadow-sm">
                <div class="room-img-wrapper">
                    <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room['room_name'] ?>">
                </div>
                <div class="card-body text-center p-4">
                    <h4 class="fw-bold text-success mb-3"><?= $room['room_name'] ?></h4>
                    <a href="room_details.php?id=<?= $room['room_id'] ?>" class="btn btn-outline-success rounded-pill px-4">View Details</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<hr class="container my-4 opacity-25" data-aos="zoom-in">

<!-- FEATURES -->
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
</div>

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
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.1);"></div>
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

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

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
                const bell = document.getElementById('notifDropdown');
                let badge = document.getElementById('notifBadge');
                if(data.unread_count > 0) {
                    if(!badge) {
                        badge = document.createElement('span');
                        badge.id = 'notifBadge';
                        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        badge.style.fontSize = '0.6rem';
                        bell.appendChild(badge);
                    }
                    badge.innerHTML = `${data.unread_count} <span class="visually-hidden">unread messages</span>`;
                } else if(badge) badge.remove();

                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => {});
                    const bellIcon = document.querySelector('#notifDropdown i');
                    if(bellIcon) { bellIcon.classList.add('shake-animation'); setTimeout(() => bellIcon.classList.remove('shake-animation'), 500); }
                }
                lastUnreadCount = data.unread_count;

                const list = document.getElementById('notifList');
                let html = `<li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light"><span class="fw-bold small text-uppercase text-muted">Notifications</span>${data.unread_count > 0 ? '<a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>' : ''}</li>`;
                if(data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        html += `<li><div class="dropdown-item p-3 border-bottom ${notif.is_read == 0 ? 'bg-white' : 'bg-light text-muted'}" style="white-space: normal;"><div class="d-flex justify-content-between mb-1"><strong class="small ${notif.is_read == 0 ? 'text-success' : ''}">${notif.type}</strong><small class="text-muted" style="font-size: 0.7rem;">${notif.created_at}</small></div><p class="mb-0 small">${notif.message}</p></div></li>`;
                    });
                } else { html += '<li class="p-3 text-center text-muted small">No notifications found.</li>'; }
                list.innerHTML = html;
            });
    }
    
    document.getElementById('notifDropdown').addEventListener('click', function() {
        const badge = document.getElementById('notifBadge');
        if(badge) badge.remove();
        fetch('get_notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_read=1'
        });
    });
    setInterval(fetchNotifications, 5000);
    fetchNotifications(); // Initial load
    <?php endif; ?>
</script>
</body>
</html>
