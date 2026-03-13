<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';
$msg = "";
$error = "";

// Handle Delete User
if(isset($_POST['delete_user'])){
    if(!$is_super){
        $error = "Access Denied: Only Super Admins can delete residents.";
    } else {
        $del_uid = (int)$_POST['user_id'];
        // Check for active reservations
        $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid AND status IN ('Pending', 'Approved')");
        if(mysqli_num_rows($check_active) > 0){
            $error = "Cannot delete user: They have active or pending reservations.";
        } else {
            // Soft delete: Mark user as archived instead of permanent deletion
            mysqli_query($conn, "UPDATE users SET is_archived=1 WHERE user_id=$del_uid");
            // Also mark any pending deletion request as Approved
            mysqli_query($conn, "UPDATE account_deletion_requests SET status='Approved' WHERE user_id=$del_uid AND status='Pending'");
            trigger_update($conn);
            $msg = "User archived successfully.";
        }
    }
}

// Fetch Residents
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "role != 'admin' AND role != 'Super Admin' AND u.is_archived = 0";
if($search){
    $where .= " AND (last_name LIKE '%$search%' OR first_name LIKE '%$search%' OR email LIKE '%$search%')";
}

$query = mysqli_query($conn, "
    SELECT u.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name,
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days
    FROM users u WHERE $where ORDER BY u.last_name ASC
");

// Sidebar Counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residents | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { 
            --primary-green: <?= $theme['primary'] ?>; 
            --dark-green: <?= $theme['dark'] ?>; 
            --accent-yellow: <?= $theme['accent'] ?>; 
            --light-bg: #f8f9fa; 
        }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" onclick="location.href='admin_dashboard.php'"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true"><span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse show" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 active d-flex justify-content-between align-items-center"><span><i class="fas fa-users me-2"></i>Residents</span></a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            </div>
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-building me-2"></i>Facilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="facilitiesSubmenu"><a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a><a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a><a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a><a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a><a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a></div>
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="financeSubmenu"><?php if($is_super): ?><a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a><?php endif; ?><a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a></div>
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-cogs me-2"></i>Operations</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="operationsSubmenu"><a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a><a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a><a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a></div>
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-cog me-2"></i>System Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu"><a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a><?php if($is_super): ?><a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a><a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a><a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a><a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a><?php endif; ?></div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px;" class="rounded-circle shadow-sm"></a>
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Residents Directory</h4>
                </div>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search residents..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                    <a href="add_reservation.php" class="btn btn-sm btn-custom"><i class="fas fa-user-plus me-1"></i> Add Resident</a>
                </div>
            </div>

            <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="card card-table p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Resident</th><th>Contact</th><th>Status</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar">
                                            <?php if(!empty($row['profile_image'])): ?><img src="../uploads/profiles/<?= $row['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?= strtoupper(substr($row['full_name'], 0, 1)) ?><?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= $row['full_name'] ?></div>
                                            <small class="text-muted"><?= $row['email'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $row['phone_number'] ?></td>
                                <td>
                                    <?php if($row['do_not_renew']): ?><span class="badge bg-danger">Do Not Renew</span>
                                    <?php else: 
                                        $m = $row['res_months'];
                                        $d = $row['res_days'];
                                        $lbl = 'Registered'; $cls = 'bg-secondary';

                                        if($m >= 6) { $lbl = 'Long-Term'; $cls = 'bg-primary'; }
                                        elseif($d !== null && $d < 28) { $lbl = 'Daily'; $cls = 'bg-warning text-dark'; }
                                        elseif($d !== null) { $lbl = 'Short-Term'; $cls = 'bg-success'; }

                                        if($row['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in'; $cls = 'bg-info text-dark'; } else { $lbl .= '/Walk-in'; } }
                                        echo "<span class='badge $cls'>$lbl</span>";
                                    endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <a href="view_user.php?uid=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                    <?php if($is_super): ?>
                                    <form method="POST" class="d-inline" onsubmit="confirmDeleteUser(event)">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($query) == 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No residents found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });

    function confirmDeleteUser(e) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Delete User?',
            text: "Are you sure you want to delete this user? This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }

// Parent Sidebar Badges
document.addEventListener('DOMContentLoaded', function() {
    ['frontDeskSubmenu', 'operationsSubmenu'].forEach(menuId => {
        let menu = document.getElementById(menuId);
        if (menu) {
            let badges = menu.querySelectorAll('.badge');
            let total = 0;
            badges.forEach(b => total += parseInt(b.innerText) || 0);
            if (total > 0) {
                let link = document.querySelector(`[href="#${menuId}"]`);
                if(link) {
                    let icon = link.querySelector('.fa-chevron-down');
                    if(icon) icon.insertAdjacentHTML('beforebegin', `<span class="badge bg-danger rounded-pill me-2 parent-badge">${total}</span>`);
                    link.addEventListener('click', function() { let b = this.querySelector('.parent-badge'); if(b) b.style.setProperty('display', 'none', 'important'); });
                }
            }
        }
    });
});
</script>
</body>
</html>