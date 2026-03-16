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
?>
<header class="top-navbar">
    <div class="navbar-left">
        <form action="<?= htmlspecialchars($search_action) ?>" method="GET" class="search-bar d-none d-md-flex">
            <i class="fas fa-search"></i>
            <input type="search" name="search" placeholder="<?= htmlspecialchars($search_placeholder) ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
        </form>
    </div>
    
    <div class="navbar-right">
        <button class="icon-btn notification-btn">
            <i class="fas fa-bell"></i>
            <?php if(isset($pending_count) && $pending_count > 0): ?>
                <span class="badge"><?= $pending_count ?></span>
            <?php endif; ?>
        </button>
        
        <div class="profile-dropdown">
            <div class="profile-toggle" id="profileToggle">
                <img src="<?= htmlspecialchars($admin_avatar) ?>" alt="Admin Profile" class="profile-img">
                <span class="profile-name d-none d-md-inline"><?= htmlspecialchars($admin_name) ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu" id="profileMenu">
                <a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>