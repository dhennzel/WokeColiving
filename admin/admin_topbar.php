<?php
$admin_name = $_SESSION['admin_username'] ?? 'Admin';
?>
<header class="top-navbar">
    <div class="navbar-left">
        <button id="sidebarToggle" class="icon-btn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-bar d-none d-md-flex">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search anything...">
        </div>
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
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=2DC08F&color=fff" alt="Admin Profile" class="profile-img">
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