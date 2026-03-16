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
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">

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
                <a href="profile.php" class="btn btn-outline-light rounded-pill px-4 position-relative">
                    My Profile
                    <?php if($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    <?php endif; ?>
                </a>
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

  // Slider Navigation Logic
  document.getElementById('nextAmenity').addEventListener('click', function() {
      const container = document.getElementById('amenitiesScroll');
      container.scrollBy({ left: 300, behavior: 'smooth' }); 
  });
  document.getElementById('prevAmenity').addEventListener('click', function() {
      const container = document.getElementById('amenitiesScroll');
      container.scrollBy({ left: -300, behavior: 'smooth' });
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
