<?php
$admin_name = $_SESSION['admin_username'] ?? 'Admin';

// Fetch Admin Profile Image
$admin_avatar = "https://ui-avatars.com/api/?name=" . urlencode($admin_name) . "&background=2DC08F&color=fff";
if (isset($conn) && isset($_SESSION['admin_username'])) {
    $q_admin_avatar = mysqli_query($conn, "SELECT profile_image FROM admin WHERE username='" . mysqli_real_escape_string($conn, $_SESSION['admin_username']) . "'");
    if ($row_admin_avatar = mysqli_fetch_assoc($q_admin_avatar)) {
        if (!empty($row_admin_avatar['profile_image']) && file_exists("../uploads/profiles/" . $row_admin_avatar['profile_image'])) {
            $admin_avatar = "../uploads/profiles/" . $row_admin_avatar['profile_image'] . "?v=" . time();
        }
    }
}

// Contextual Search Action
$search_action = 'residents.php';
$search_placeholder = 'Search residents...';

if (isset($current_page)) {
    if ($current_page == 'booking_management.php') {
        $search_action = 'booking_management.php';
        $search_placeholder = 'Search bookings...';
    } elseif ($current_page == 'admin_utilities.php') {
        $search_action = 'admin_utilities.php';
        $search_placeholder = 'Search archives...';
    } elseif ($current_page == 'system_logs.php') {
        $search_action = 'system_logs.php';
        $search_placeholder = 'Search logs...';
    }
}

// Calculate accurate total notifications based on all modules
$top_pending_res = $pending_res ?? ($pending_count ?? 0);
$top_pending_maint = $pending_maint ?? 0;
$top_pending_house = $pending_house ?? 0;
$top_del_req = $del_req_count ?? 0;
$total_notifications = $top_pending_res + $top_pending_maint + $top_pending_house + $top_del_req;
?>
<div id="navbar-restore-trigger" class="navbar-restore-trigger" title="Restore Navbar">
    <i class="fas fa-chevron-down"></i>
</div>
<header class="top-navbar">
    <div class="navbar-left">
        <form action="<?= htmlspecialchars($search_action) ?>" method="GET" class="search-bar d-none d-md-flex">
            <i class="fas fa-search"></i>
            <input type="search" name="search" placeholder="<?= htmlspecialchars($search_placeholder) ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
        </form>
    </div>
    
    <div class="navbar-right">
        <div class="dropdown">
            <button class="icon-btn notification-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <?php if($total_notifications > 0): ?>
                    <span class="badge"><?= $total_notifications ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="width: 260px;">
                <li class="px-3 py-2 border-bottom bg-light fw-bold small text-muted text-uppercase">Pending Actions</li>
                <?php if($total_notifications > 0): ?>
                    <?php if($top_pending_res > 0): ?>
                        <li><a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="booking_management.php?status=Pending"><span class="small fw-bold"><i class="fas fa-calendar-check text-warning me-2"></i> Bookings & Payments</span> <span class="badge bg-warning text-dark rounded-pill" style="box-shadow: none !important;"><?= $top_pending_res ?></span></a></li>
                    <?php endif; ?>
                    <?php if($top_pending_maint > 0): ?>
                        <li><a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="admin_maintenance.php"><span class="small fw-bold"><i class="fas fa-wrench text-danger me-2"></i> Maintenance</span> <span class="badge bg-danger rounded-pill" style="box-shadow: none !important;"><?= $top_pending_maint ?></span></a></li>
                    <?php endif; ?>
                    <?php if($top_pending_house > 0): ?>
                        <li><a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="admin_housekeeping.php"><span class="small fw-bold"><i class="fas fa-broom text-info me-2"></i> Housekeeping</span> <span class="badge bg-info text-dark rounded-pill" style="box-shadow: none !important;"><?= $top_pending_house ?></span></a></li>
                    <?php endif; ?>
                    <?php if($top_del_req > 0): ?>
                        <li><a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="admin_deletion_requests.php"><span class="small fw-bold"><i class="fas fa-user-times text-danger me-2"></i> Deletion Requests</span> <span class="badge bg-danger rounded-pill" style="box-shadow: none !important;"><?= $top_del_req ?></span></a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><span class="dropdown-item text-muted small text-center py-3">No pending actions</span></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="profile-dropdown">
            <div class="profile-toggle" id="profileToggle">
                <img src="<?= htmlspecialchars($admin_avatar) ?>" alt="Admin Profile" class="profile-img">
                <span class="profile-name d-none d-md-inline"><?= htmlspecialchars($admin_name) ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu" id="profileMenu">
                <a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="#" id="adminNightModeToggle"><i class="fas fa-moon"></i> Night Mode</a>
                <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>
<script>
    window.currentAdminUser = "<?= htmlspecialchars($admin_name ?? 'admin', ENT_QUOTES, 'UTF-8') ?>";
</script>

<!-- Notification Sound -->
<audio id="adminNotifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script>
    // Notification Sound Logic
    let lastAdminNotifCount = <?= (int)$total_notifications ?>;
    function checkAdminNotifications() {
        fetch('get_admin_notifications.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const badge = document.querySelector('.notification-btn .badge');

                if (data.total_notifications > lastAdminNotifCount) {
                    const audio = document.getElementById('adminNotifSound');
                    if (audio) {
                        audio.play().catch(e => console.error("Audio play failed:", e));
                    }
                }

                if (data.total_notifications > 0) {
                     if(badge) {
                        badge.textContent = data.total_notifications;
                        badge.style.display = 'block';
                    } else {
                        const notifBtn = document.querySelector('.notification-btn');
                        if(notifBtn) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'badge';
                            newBadge.textContent = data.total_notifications;
                            notifBtn.appendChild(newBadge);
                        }
                    }
                } else {
                    if(badge) badge.style.display = 'none';
                }
                lastAdminNotifCount = data.total_notifications;
            })
            .catch(error => console.error('Error fetching admin notifications:', error));
    }
    setInterval(checkAdminNotifications, 10000); // Poll every 10 seconds
</script>