<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}

$current_role = $_SESSION['admin_role'] ?? 'Admin';
$message = "";
$error = "";

// Add Admin
if(isset($_POST['add_admin'])){
    if($current_role !== 'Super Admin'){
        $error = "Only Super Admins can add new admins.";
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $raw_pass = !empty($_POST['password']) ? $_POST['password'] : '12345678';
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        if(!empty($_POST['password']) && (!preg_match('/[a-zA-Z]/', $raw_pass) || !preg_match('/[0-9]/', $raw_pass))){
            $error = "Password must contain at least one letter and one number.";
        }
        
        if(empty($error)){
            $password = mysqli_real_escape_string($conn, $raw_pass);
            $check = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
            if(mysqli_num_rows($check) > 0){
                $error = "Username already exists.";
            } else {
                mysqli_query($conn, "INSERT INTO admin (username, password, role) VALUES ('$username', '$password', '$role')");
                $message = "New admin added successfully.";
            }
        }
    }
}

// Delete Admin
if(isset($_GET['delete'])){
    if($current_role !== 'Super Admin'){
        $error = "Only Super Admins can delete admins.";
    } else {
        $id = (int)$_GET['delete'];
        $me = $_SESSION['admin_username'];
        $check_me = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id AND username='$me'");
        if(mysqli_num_rows($check_me) > 0){
            $error = "You cannot delete your own account.";
        } else {
            mysqli_query($conn, "DELETE FROM admin WHERE id=$id");
            $message = "Admin deleted successfully.";
        }
    }
}

$admins = mysqli_query($conn, "SELECT * FROM admin");
$theme = get_theme_colors($conn);

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Roles | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
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
        <div class="sidebar-brand"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-users me-2"></i>Residents</span></a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            </div>
            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-building me-2"></i>Facilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="facilitiesSubmenu"><a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a><a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a><a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a><a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a><a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a></div>
            <!-- Finance -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="financeSubmenu">
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?><a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a><?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>
            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-cogs me-2"></i>Operations</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="operationsSubmenu"><a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a><a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a><a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a></div>
            <!-- Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true"><span><i class="fas fa-cog me-2"></i>System Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse show" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5 active"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4">
            <h4 class="fw-bold mb-4" style="color: var(--dark-green);">Manage Admin Roles</h4>
            <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-custom p-4 mb-4">
                        <h5 class="fw-bold mb-3">Add New Admin</h5>
                        <form method="POST">
                            <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Default: 12345678"></div>
                            <div class="mb-3"><label class="form-label">Role</label><select name="role" class="form-select"><option value="Admin">Admin</option><option value="Super Admin">Super Admin</option></select></div>
                            <button type="submit" name="add_admin" class="btn btn-custom w-100">Add Admin</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card card-custom p-4">
                        <h5 class="fw-bold mb-3">Existing Admins</h5>
                        <table class="table table-hover">
                            <thead><tr><th>Username</th><th>Role</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($admins)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><span class="badge <?= $row['role'] == 'Super Admin' ? 'bg-danger' : 'bg-primary' ?>"><?= $row['role'] ?></span></td>
                                    <td><?php if($current_role == 'Super Admin' && $row['username'] != $_SESSION['admin_username']): ?><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this admin?')">Delete</a><?php endif; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>