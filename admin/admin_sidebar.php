<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Gracefully pull counts if defined in the parent script
$p_res = $pending_res ?? ($pending_count ?? 0);
$w_cnt = $waitlist_count ?? 0;
$d_cnt = $del_req_count ?? 0;
$m_cnt = $pending_maint ?? 0;
$h_cnt = $pending_house ?? 0;
$front_desk_total = $p_res + $w_cnt + $d_cnt;
$operations_total = $m_cnt + $h_cnt;
$is_super = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'Super Admin';
?>
<script>
    (function() {
        const currentAdminUser = "<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin', ENT_QUOTES, 'UTF-8') ?>";
        if(localStorage.getItem('adminNightMode_' + currentAdminUser) === 'enabled') {
            document.body.classList.add('night-mode');
        }
    })();
</script>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-placeholder" id="sidebarToggle" title="Toggle Sidebar">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
        </div>
        <span class="brand-name">Woke Coliving</span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="admin_dashboard.php" class="nav-item <?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i><span>Dashboard</span>
        </a>
        
        <!-- Front Desk -->
        <a href="#frontDeskSubmenu" data-bs-toggle="collapse" onclick="document.getElementById('frontDeskBadge')?.style.setProperty('display', 'none', 'important');" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-concierge-bell"></i><span>Front Desk</span></div>
            <div class="d-flex align-items-center">
                <?php if($front_desk_total > 0): ?><span class="badge bg-danger rounded-pill me-2" id="frontDeskBadge"><?= $front_desk_total ?></span><?php endif; ?>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0;"></i>
            </div>
        </a>
        <div class="collapse <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? 'show' : '' ?>" id="frontDeskSubmenu">
            <a href="residents.php" class="nav-item <?= $current_page == 'residents.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-users" style="width: 25px;"></i><span>Residents</span>
            </a>
            <a href="booking_management.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= in_array($current_page, ['booking_management.php', 'view_user.php']) ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-calendar-check" style="width: 25px;"></i><span>Bookings</span>
                <?php if($p_res > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $p_res ?></span><?php endif; ?>
            </a>
            <a href="admin_waitlist.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'admin_waitlist.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-list-ol" style="width: 25px;"></i><span>Waitlist</span>
                <?php if($w_cnt > 0): ?><span class="badge bg-warning text-dark rounded-pill ms-auto nav-badge"><?= $w_cnt ?></span><?php endif; ?>
            </a>
            <a href="admin_deletion_requests.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'admin_deletion_requests.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-user-times" style="width: 25px;"></i><span>Deletion Req</span>
                <?php if($d_cnt > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $d_cnt ?></span><?php endif; ?>
            </a>
        </div>

        <!-- Facilities -->
        <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_rooms.php', 'admin_room_assignment.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-building"></i><span>Facilities</span></div>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
        </a>
        <div class="collapse <?= in_array($current_page, ['admin_rooms.php', 'admin_room_assignment.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php']) ? 'show' : '' ?>" id="facilitiesSubmenu">
            <a href="admin_rooms.php" class="nav-item <?= ($current_page == 'admin_rooms.php') ? 'active' : '' ?>">
    <i class="fas fa-bed"></i> <span>Manage Rooms</span>
</a>
            <a href="admin_room_assignment.php" class="nav-item <?= $current_page == 'admin_room_assignment.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-door-open" style="width: 25px;"></i><span>Assignment</span></a>
            <a href="admin_room_occupancy.php" class="nav-item <?= $current_page == 'admin_room_occupancy.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-users" style="width: 25px;"></i><span>Occupancy</span></a>
            <a href="admin_parking.php" class="nav-item <?= $current_page == 'admin_parking.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-parking" style="width: 25px;"></i><span>Parkings</span></a>
            <a href="admin_keys.php" class="nav-item <?= $current_page == 'admin_keys.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-key" style="width: 25px;"></i><span>Key Monitoring</span></a>
        </div>

        <!-- Finance & Reports -->
        <a href="#financeSubmenu" data-bs-toggle="collapse" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-file-invoice-dollar"></i><span>Finance & Reports</span></div>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
        </a>
        <div class="collapse <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php']) ? 'show' : '' ?>" id="financeSubmenu">
            <?php if($is_super): ?>
            <a href="profit_report.php" class="nav-item <?= $current_page == 'profit_report.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-chart-line" style="width: 25px;"></i><span>Profit Report</span></a>
            <?php endif; ?>
            <a href="longterm_billing.php" class="nav-item <?= $current_page == 'longterm_billing.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-receipt" style="width: 25px;"></i><span>Billing</span></a>
        </div>

        <!-- Operations -->
        <a href="#operationsSubmenu" data-bs-toggle="collapse" onclick="document.getElementById('operationsBadge')?.style.setProperty('display', 'none', 'important');" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_inventory.php', 'admin_utilities.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-cogs"></i><span>Operations</span></div>
            <div class="d-flex align-items-center">
                <?php if($operations_total > 0): ?><span class="badge bg-danger rounded-pill me-2" id="operationsBadge"><?= $operations_total ?></span><?php endif; ?>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0;"></i>
            </div>
        </a>
        <div class="collapse <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_inventory.php', 'admin_utilities.php']) ? 'show' : '' ?>" id="operationsSubmenu">
            <a href="admin_maintenance.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'admin_maintenance.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-wrench" style="width: 25px;"></i><span>Maintenance</span>
                <?php if($m_cnt > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $m_cnt ?></span><?php endif; ?>
            </a>
            <a href="admin_housekeeping.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'admin_housekeeping.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-broom" style="width: 25px;"></i><span>Housekeeping</span>
                <?php if($h_cnt > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $h_cnt ?></span><?php endif; ?>
            </a>
            <a href="admin_inventory.php" class="nav-item <?= $current_page == 'admin_inventory.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-boxes" style="width: 25px;"></i><span>Inventory</span></a>
            <a href="admin_utilities.php" class="nav-item <?= $current_page == 'admin_utilities.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-archive" style="width: 25px;"></i><span>Utilities Archive</span></a>
        </div>

        <!-- System Settings -->
        <a href="#settingsSubmenu" data-bs-toggle="collapse" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-cog"></i><span>Settings</span></div>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
        </a>
        <div class="collapse <?= in_array($current_page, ['admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'show' : '' ?>" id="settingsSubmenu">
            <?php if($is_super): ?>
            <a href="admin_roles.php" class="nav-item <?= $current_page == 'admin_roles.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-users-cog" style="width: 25px;"></i><span>Manage Roles</span></a>
            <a href="manage_hero.php" class="nav-item <?= $current_page == 'manage_hero.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-image" style="width: 25px;"></i><span>Hero Image</span></a>
            <a href="system_logs.php" class="nav-item <?= ($current_page == 'system_logs.php') ? 'active' : '' ?>">
    <i class="fas fa-list-alt"></i> <span>System Logs</span>
</a>
            <a href="backup.php" class="nav-item <?= $current_page == 'backup.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-database" style="width: 25px;"></i><span>Backup</span></a>
            <?php endif; ?>
        </div>
    </nav>
</aside>