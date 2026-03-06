<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Handle Actions
if(isset($_POST['handle_request'])){
    $req_id = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $user_id = (int)$_POST['user_id'];

    if($action == 'reject'){
        mysqli_query($conn, "UPDATE account_deletion_requests SET status='Rejected' WHERE request_id=$req_id");
        send_notification($conn, $user_id, "❌ <strong>Deletion Request Rejected</strong><br>Your request to delete your account has been rejected by the admin.", "System");
        header("Location: admin_deletion_requests.php?msg=rejected");
        exit;
    } elseif($action == 'approve'){
        // Perform Deletion
        mysqli_begin_transaction($conn);
        try {
            // 1. Get Reservation IDs to clean up child records
            $res_ids = [];
            $r_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$user_id");
            while($row = mysqli_fetch_assoc($r_q)){
                $res_ids[] = $row['reservation_id'];
            }

            if(!empty($res_ids)){
                $ids_str = implode(',', $res_ids);
                // Delete Payments linked to reservations
                mysqli_query($conn, "DELETE FROM payments WHERE reservation_id IN ($ids_str)");
                // Try deleting from optional tables
                try { mysqli_query($conn, "DELETE FROM utility_bills WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM temporary_moves WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
            }

            // 2. Delete records linked directly to user
            try { mysqli_query($conn, "UPDATE reservations SET extended_from = NULL WHERE user_id=$user_id"); } catch(Exception $e){}
            mysqli_query($conn, "DELETE FROM reservations WHERE user_id=$user_id");
            
            try { mysqli_query($conn, "DELETE FROM activity_logs WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM maintenance_requests WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM housekeeping_requests WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM waitlist WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM user_update_requests WHERE user_id=$user_id"); } catch(Exception $e){}

            // Delete the user (Cascade will remove the request record)
            mysqli_query($conn, "DELETE FROM users WHERE user_id=$user_id");
            
            trigger_update($conn);
            mysqli_commit($conn);
            header("Location: admin_deletion_requests.php?msg=approved");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("Location: admin_deletion_requests.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Fetch Pending Requests
$query = mysqli_query($conn, "SELECT r.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, u.email, u.created_at as user_joined 
                              FROM account_deletion_requests r 
                              JOIN users u ON r.user_id = u.user_id 
                              WHERE r.status='Pending' 
                              ORDER BY r.created_at ASC");

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
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
    <title>Deletion Requests | Woke Coliving INC</title>
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
        <div class="sidebar-brand">Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Residents</span>
                </a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                    <?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?>
                </a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-ol me-2"></i>Waitlist</span>
                    <?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?>
                </a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 active d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-times me-2"></i>Deletion Req</span>
                    <?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?>
                </a>
            </div>

            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_house ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4">
            <h3 class="fw-bold mb-4" style="color: var(--dark-green);">Account Deletion Requests</h3>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
                <div class="alert alert-success">Request approved. User account deleted.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'rejected'): ?>
                <div class="alert alert-warning">Request rejected. User notified.</div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">Error: <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="card card-custom p-4">
                <?php if(mysqli_num_rows($query) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>User</th><th>Email</th><th>Joined</th><th>Requested</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['user_joined'])) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmAction(event, 'approve')">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="handle_request" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger me-1"><i class="fas fa-trash me-1"></i> Approve Delete</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="confirmAction(event, 'reject')">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="handle_request" value="1">
                                        <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">No pending deletion requests.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmAction(e, type) {
    e.preventDefault();
    const msg = type === 'approve' 
        ? "Are you sure? This will PERMANENTLY DELETE the user and all their data." 
        : "Reject this deletion request?";
    
    Swal.fire({
        title: 'Confirm Action',
        text: msg,
        icon: type === 'approve' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: type === 'approve' ? '#d33' : '#6c757d',
        confirmButtonText: 'Yes, proceed'
    }).then((result) => {
        if (result.isConfirmed) e.target.submit();
    });
}
</script>
</body>
</html>