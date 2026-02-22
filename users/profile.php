<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$u_query = mysqli_query($conn, "SELECT full_name, email FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

// Handle Mark as Read
if(isset($_GET['read_all'])){
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
    header("Location: profile.php");
    exit;
}

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];

// Fetch Notifications
try {
    $notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");
} catch (Exception $e) {
    $notif_query = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | Woke Coliving INC</title>
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
        
        .profile-card {
            transition: all 0.4s ease;
            cursor: pointer;
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
        }
        .profile-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(46, 125, 50, 0.15);
        }
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: var(--accent-yellow);
            transform: scaleX(0);
            transition: transform 0.4s;
            transform-origin: left;
        }
        .profile-card:hover::before { transform: scaleX(1); }
        
        .icon-box {
            font-size: 3rem;
            color: var(--primary-green);
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .profile-card:hover .icon-box { transform: scale(1.1) rotate(10deg); color: var(--accent-yellow); }
        
        .notif-item { border-left: 4px solid var(--dark-green); background: #fff; margin-bottom: 10px; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
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
                <?php if($notif_query && mysqli_num_rows($notif_query) > 0): ?>
                    <?php while($notif = mysqli_fetch_assoc($notif_query)): ?>
                        <li>
                            <div class="dropdown-item p-3 border-bottom <?= $notif['is_read'] == 0 ? 'bg-white' : 'bg-light text-muted' ?>" style="white-space: normal;">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="small <?= $notif['is_read'] == 0 ? 'text-success' : '' ?>"><?= htmlspecialchars($notif['type']) ?></strong>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></small>
                                </div>
                                <p class="mb-0 small"><?= $notif['message'] ?></p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="p-3 text-center text-muted small">No notifications found.</li>
                <?php endif; ?>
            </ul>
        </div>

        <span class="text-white fw-bold d-none d-md-block">Hello, <?= htmlspecialchars(explode(' ', $user_info['full_name'])[0]) ?></span>
        <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="text-center mb-5 reveal">
        <h2 class="display-5 fw-bold text-success">My Dashboard</h2>
        <p class="text-muted lead">Manage your stay, bookings, and account details.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Book a Room -->
        <div class="col-md-3 reveal delay-1">
            <a href="reservation_now.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-calendar-plus"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Book a Room</h4>
                    <p class="text-muted small">Find and book your next stay with us.</p>
                </div>
            </a>
        </div>

        <!-- My Reservations -->
        <div class="col-md-3 reveal delay-2">
            <a href="my_reservations.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-suitcase"></i></div>
                    <h4 class="fw-bold text-dark mb-3">My Reservations</h4>
                    <p class="text-muted small">View your booking history and status.</p>
                </div>
            </a>
        </div>

        <!-- Maintenance -->
        <div class="col-md-3 reveal delay-3">
            <a href="maintenance.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-tools"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Maintenance</h4>
                    <p class="text-muted small">Report issues and track repairs.</p>
                </div>
            </a>
        </div>

        <!-- Housekeeping -->
        <div class="col-md-3 reveal delay-4">
            <a href="housekeeping.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-broom"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Housekeeping</h4>
                    <p class="text-muted small">Request cleaning services.</p>
                </div>
            </a>
        </div>

        <!-- Archived History -->
        <div class="col-md-3 reveal delay-4">
            <a href="my_archives.php" class="text-decoration-none">
                <div class="card profile-card h-100 p-5 text-center">
                    <div class="icon-box"><i class="fas fa-archive"></i></div>
                    <h4 class="fw-bold text-dark mb-3">Archives</h4>
                    <p class="text-muted small">View removed and old contracts.</p>
                </div>
            </a>
        </div>
    </div>

</div>
<br><br>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let lastUnreadCount = <?= (int)$unread_count ?>;

    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                // Update Badge
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
                } else {
                    if(badge) badge.remove();
                }

                // Play Sound if count increased
                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => console.log('Audio play failed:', e));
                    
                    const bellIcon = document.querySelector('#notifDropdown i');
                    if(bellIcon) {
                        bellIcon.classList.add('shake-animation');
                        setTimeout(() => bellIcon.classList.remove('shake-animation'), 500);
                    }
                }
                lastUnreadCount = data.unread_count;

                // Update List
                const list = document.getElementById('notifList');
                let html = `<li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                        <span class="fw-bold small text-uppercase text-muted">Notifications</span>
                        ${data.unread_count > 0 ? '<a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>' : ''}
                    </li>`;
                
                if(data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const bgClass = notif.is_read == 0 ? 'bg-white' : 'bg-light text-muted';
                        const textClass = notif.is_read == 0 ? 'text-success' : '';
                        html += `<li>
                                <div class="dropdown-item p-3 border-bottom ${bgClass}" style="white-space: normal;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="small ${textClass}">${notif.type}</strong>
                                        <small class="text-muted" style="font-size: 0.7rem;">${notif.created_at}</small>
                                    </div>
                                    <p class="mb-0 small">${notif.message}</p>
                                </div>
                            </li>`;
                    });
                } else {
                    html += '<li class="p-3 text-center text-muted small">No notifications found.</li>';
                }
                list.innerHTML = html;
            })
            .catch(err => console.error('Notification fetch error:', err));
    }

    // Mark as read on click
    document.getElementById('notifDropdown').addEventListener('click', function() {
        const badge = document.getElementById('notifBadge');
        if(badge) badge.remove();
        
        fetch('get_notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_read=1'
        });
    });

    // Poll every 5 seconds
    setInterval(fetchNotifications, 5000);

    // Auto Refresh Logic (Global)
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
</script>
</body>
</html>