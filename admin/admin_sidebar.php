<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Centralize all sidebar notification counts
$p_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'] ?? 0;
$p_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'] ?? 0;
$p_res += $p_pay;

$w_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'] ?? 0;
$d_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'] ?? 0;
$m_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'] ?? 0;
$h_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'] ?? 0;

$pk_cnt = 0;
try {
    $pk_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_reservations WHERE status='Active' AND end_date < CURDATE()");
    if($pk_q) $pk_cnt = mysqli_fetch_assoc($pk_q)['c'];
} catch(Exception $e){}

$fin_cnt = 0;
try {
    $fin_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM (SELECT u.user_id FROM users u JOIN reservations r ON u.user_id = r.user_id JOIN payments p ON r.reservation_id = p.reservation_id WHERE p.payment_status = 'Unpaid' AND u.is_archived = 0 GROUP BY u.user_id HAVING SUM(p.amount) > 5000) as sub");
    if($fin_q) $fin_cnt = mysqli_fetch_assoc($fin_q)['c'];
} catch(Exception $e){}

$front_desk_total = $p_res + $w_cnt + $d_cnt;
$operations_total = $m_cnt + $h_cnt;
$facilities_total = $pk_cnt;
$finance_total = $fin_cnt;

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
<style>
    /* Suppress duplicate JS badges appended by older scripts */
    .parent-badge { display: none !important; }
</style>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
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
        <a href="#facilitiesSubmenu" data-bs-toggle="collapse" onclick="document.getElementById('facilitiesBadge')?.style.setProperty('display', 'none', 'important');" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_rooms.php', 'admin_room_assignment.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-building"></i><span>Facilities</span></div>
            <div class="d-flex align-items-center">
                <?php if($facilities_total > 0): ?><span class="badge bg-danger rounded-pill me-2" id="facilitiesBadge"><?= $facilities_total ?></span><?php endif; ?>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
            </div>
        </a>
        <div class="collapse <?= in_array($current_page, ['admin_rooms.php', 'admin_room_assignment.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php']) ? 'show' : '' ?>" id="facilitiesSubmenu">
            <a href="admin_rooms.php" class="nav-item <?= ($current_page == 'admin_rooms.php') ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-bed" style="width: 25px;"></i><span>Manage Rooms</span></a>
            <a href="admin_room_assignment.php" class="nav-item <?= $current_page == 'admin_room_assignment.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-door-open" style="width: 25px;"></i><span>Assignment</span></a>
            <a href="admin_room_occupancy.php" class="nav-item <?= $current_page == 'admin_room_occupancy.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-users" style="width: 25px;"></i><span>Occupancy</span></a>
            <a href="admin_parking.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'admin_parking.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-parking" style="width: 25px;"></i><span>Parkings</span>
                <?php if($pk_cnt > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $pk_cnt ?></span><?php endif; ?>
            </a>
            <a href="admin_keys.php" class="nav-item <?= $current_page == 'admin_keys.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-key" style="width: 25px;"></i><span>Key Monitoring</span></a>
        </div>

        <!-- Finance & Reports -->
        <a href="#financeSubmenu" data-bs-toggle="collapse" onclick="document.getElementById('financeBadge')?.style.setProperty('display', 'none', 'important');" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'balance_report.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-file-invoice-dollar"></i><span>Finance & Reports</span></div>
            <div class="d-flex align-items-center">
                <?php if($finance_total > 0): ?><span class="badge bg-danger rounded-pill me-2" id="financeBadge"><?= $finance_total ?></span><?php endif; ?>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
            </div>
        </a>
        <div class="collapse <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'balance_report.php']) ? 'show' : '' ?>" id="financeSubmenu">
            <?php if($is_super): ?>
            <a href="profit_report.php" class="nav-item <?= $current_page == 'profit_report.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-chart-line" style="width: 25px;"></i><span>Profit Report</span></a>
            <?php endif; ?>
            <a href="longterm_billing.php" class="nav-item <?= $current_page == 'longterm_billing.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-receipt" style="width: 25px;"></i><span>Billing</span></a>
            <a href="balance_report.php" onclick="this.querySelector('.nav-badge')?.style.setProperty('display', 'none', 'important');" class="nav-item <?= $current_page == 'balance_report.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;">
                <i class="fas fa-file-invoice" style="width: 25px;"></i><span>Balances</span>
                <?php if($fin_cnt > 0): ?><span class="badge bg-danger rounded-pill ms-auto nav-badge"><?= $fin_cnt ?></span><?php endif; ?>
            </a>
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
        <?php if($is_super): ?>
        <a href="#settingsSubmenu" data-bs-toggle="collapse" class="nav-item d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? '' : 'collapsed' ?>">
            <div><i class="fas fa-cog"></i><span>Settings</span></div>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem; width: auto; flex-shrink: 0; margin-left: 10px;"></i>
        </a>
        <div class="collapse <?= in_array($current_page, ['admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'show' : '' ?>" id="settingsSubmenu">
            <a href="admin_roles.php" class="nav-item <?= $current_page == 'admin_roles.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-users-cog" style="width: 25px;"></i><span>Manage Roles</span></a>
            <a href="manage_hero.php" class="nav-item <?= $current_page == 'manage_hero.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-image" style="width: 25px;"></i><span>Hero Image</span></a>
            <a href="system_logs.php" class="nav-item <?= ($current_page == 'system_logs.php') ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-list-alt" style="width: 25px;"></i><span>System Logs</span></a>
            <a href="backup.php" class="nav-item <?= $current_page == 'backup.php' ? 'active' : '' ?>" style="padding-left: 55px; font-size: 0.9rem;"><i class="fas fa-database" style="width: 25px;"></i><span>Backup</span></a>
        </div>
        <?php endif; ?>
    </nav>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    
    function openSidebar() {
        sidebar.classList.add('mobile-open');
        backdrop.classList.add('show');
    }
    
    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('show');
    }

    if(mobileToggle && sidebar && backdrop) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            openSidebar();
        });
        
        backdrop.addEventListener('click', closeSidebar);
        
        // --- Swipable Sidebar Logic ---
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, {passive: true});
        
        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, {passive: true});
        
        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            if (swipeDistance > 50 && touchStartX < 40 && !sidebar.classList.contains('mobile-open')) openSidebar(); // Swipe Right from edge
            else if (swipeDistance < -50 && sidebar.classList.contains('mobile-open')) closeSidebar(); // Swipe Left to close
        }
    }
});
</script>