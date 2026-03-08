<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$message = "";
$active_modal = "";
$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';

// Handle Archive Actions (Delete/Restore)
if(isset($_POST['archive_action'])) {
    $id = (int)$_POST['id'];
    $type = $_POST['type']; // 'maintenance' or 'housekeeping'
    $action = $_POST['archive_action']; // 'delete' or 'restore'
    
    // Set which modal to re-open after refresh
    if($type == 'room') $active_modal = 'modalRooms';
    elseif($type == 'maintenance') $active_modal = 'modalMaintenance';
    elseif($type == 'housekeeping') $active_modal = 'modalHousekeeping';
    elseif($type == 'user') $active_modal = 'modalUsers';

    if ($type == 'room') {
        if ($action == 'restore') {
            mysqli_query($conn, "UPDATE rooms SET is_archived='0' WHERE room_id=$id");
            $message = "Room restored successfully.";
        } elseif ($action == 'delete') {
            try {
                mysqli_query($conn, "DELETE FROM rooms WHERE room_id=$id");
                $message = "Room deleted permanently.";
            } catch (Exception $e) {
                $message = "Error: Cannot delete room. It may be linked to reservations.";
            }
        }
    } elseif ($type == 'user') {
        if (!$is_super) {
            $message = "Error: Only Super Admins can perform this action.";
        } elseif ($action == 'restore') {
            mysqli_query($conn, "UPDATE users SET is_archived='0' WHERE user_id=$id");
            $message = "User restored successfully.";
        } elseif ($action == 'delete') {
            // Permanent delete logic for user
            mysqli_begin_transaction($conn);
            try {
                // 1. Get Reservation IDs to clean up child records
                $res_ids = [];
                $r_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$id");
                while($row = mysqli_fetch_assoc($r_q)){
                    $res_ids[] = $row['reservation_id'];
                }
    
                if(!empty($res_ids)){
                    $ids_str = implode(',', $res_ids);
                    // Delete Payments linked to reservations
                    mysqli_query($conn, "DELETE FROM payments WHERE reservation_id IN ($ids_str)");
                    // Try deleting from optional tables (ignore if not exists)
                    try { mysqli_query($conn, "DELETE FROM utility_bills WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                    try { mysqli_query($conn, "DELETE FROM temporary_moves WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                }
    
                // 2. Delete records linked directly to user
                // Break self-referencing constraints if any
                try { mysqli_query($conn, "UPDATE reservations SET extended_from = NULL WHERE user_id=$id"); } catch(Exception $e){}
                mysqli_query($conn, "DELETE FROM reservations WHERE user_id=$id");
                
                try { mysqli_query($conn, "DELETE FROM activity_logs WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM maintenance_requests WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM housekeeping_requests WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM waitlist WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM user_update_requests WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM account_deletion_requests WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM parking_reservations WHERE user_id=$id"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM key_transactions WHERE user_id=$id"); } catch(Exception $e){}
    
                mysqli_query($conn, "DELETE FROM users WHERE user_id=$id");
                trigger_update($conn);
                mysqli_commit($conn);
                $message = "User permanently deleted.";
            } catch (mysqli_sql_exception $e) {
                mysqli_rollback($conn);
                $message = "Error permanently deleting user: " . $e->getMessage();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = "Error permanently deleting user: " . $e->getMessage();
            }
        }

    } else {
        $table = ($type == 'maintenance') ? 'maintenance_requests' : 'housekeeping_requests';
        
        if($action == 'delete') {
            mysqli_query($conn, "DELETE FROM $table WHERE request_id=$id");
            $message = ucfirst($type) . " record deleted permanently.";
        } elseif($action == 'restore') {
            mysqli_query($conn, "UPDATE $table SET status='Pending' WHERE request_id=$id");
            $message = ucfirst($type) . " record restored to Pending.";
        }
    }
}

// Search Logic
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
if($search) $active_modal = 'searchResults'; // Optional: Handle search visibility if needed

// Fetch Maintenance Archive
$m_sql = "SELECT m.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name FROM maintenance_requests m JOIN users u ON m.user_id = u.user_id LEFT JOIN rooms r ON m.room_id = r.room_id WHERE m.status IN ('Completed', 'Cancelled')";
if($search) $m_sql .= " AND (u.last_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR r.room_name LIKE '%$search%' OR m.description LIKE '%$search%')";
$m_sql .= " ORDER BY m.created_at DESC";
$maintenance_query = mysqli_query($conn, $m_sql);

// Fetch Housekeeping Archive
$h_sql = "SELECT h.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, r.room_name FROM housekeeping_requests h JOIN users u ON h.user_id = u.user_id LEFT JOIN rooms r ON h.room_id = r.room_id WHERE h.status IN ('Completed', 'Cancelled')";
if($search) $h_sql .= " AND (u.last_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR r.room_name LIKE '%$search%' OR h.description LIKE '%$search%')";
$h_sql .= " ORDER BY h.created_at DESC";
$housekeeping_query = mysqli_query($conn, $h_sql);

// Fetch Archived Rooms
$r_sql = "SELECT * FROM rooms WHERE is_archived='1'";
if($search) $r_sql .= " AND (room_name LIKE '%$search%' OR room_type LIKE '%$search%')";
$r_sql .= " ORDER BY room_name ASC";
$archived_rooms_query = mysqli_query($conn, $r_sql);

// Fetch Transaction Reports (All Paid Payments)
$t_sql = "SELECT p.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, rm.room_name FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id JOIN users u ON r.user_id = u.user_id JOIN rooms rm ON r.room_id = rm.room_id WHERE p.payment_status='Paid'";
if($search) $t_sql .= " AND (u.last_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR rm.room_name LIKE '%$search%' OR p.description LIKE '%$search%' OR p.reference_number LIKE '%$search%')";
$t_sql .= " ORDER BY p.payment_date DESC";
$transactions_query = mysqli_query($conn, $t_sql);

// Fetch Paid Utility Bills
$u_sql = "SELECT p.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, rm.room_name FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id JOIN users u ON r.user_id = u.user_id JOIN rooms rm ON r.room_id = rm.room_id WHERE p.payment_status = 'Paid' AND p.description LIKE 'Utility Bill%'";
if($search) $u_sql .= " AND (u.last_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR rm.room_name LIKE '%$search%' OR p.description LIKE '%$search%')";
$u_sql .= " ORDER BY p.payment_date DESC";
$utility_bills_query = mysqli_query($conn, $u_sql);

// Fetch Archived Users
$archived_users_sql = "SELECT user_id, first_name, last_name, email, created_at FROM users WHERE is_archived=1";
if($search) $archived_users_sql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
$archived_users_sql .= " ORDER BY created_at DESC";
$archived_users_query = mysqli_query($conn, $archived_users_sql);

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
    <title>Utilities Archive | Woke Coliving INC</title>
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

        /* Carousel Styles */
        .archive-slider-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding: 20px 0;
        }
        .archive-slider {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 20px;
            padding: 10px;
            scrollbar-width: none; /* Firefox */
            flex: 1;
            max-width: 900px;
        }
        .archive-slider::-webkit-scrollbar {
            display: none; /* Safari/Chrome */
        }
        .archive-card {
            min-width: 200px;
            height: 280px;
            border: 3px solid var(--dark-green);
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark-green);
        }
        .archive-card:hover {
            transform: scale(1.05);
            background: var(--dark-green);
            color: var(--accent-yellow);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .archive-card i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }
        .archive-card h3 {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .slider-btn {
            background: #fff;
            border: 3px solid var(--dark-green);
            color: var(--dark-green);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .slider-btn:hover {
            background: var(--dark-green);
            color: var(--accent-yellow);
        }
        
        /* Modal Customization */
        .modal-header {
            background-color: var(--dark-green);
            color: white;
            border-bottom: 3px solid var(--accent-yellow);
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>
<body>

<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
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
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-times me-2"></i>Deletion Req</span>
                    <?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?>
                </a>
            </div>

            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

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

            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="operationsSubmenu">
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
                <a href="admin_utilities.php" class="sidebar-link ps-5 active"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

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

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                </a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Utilities Archive</h4>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-table p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-secondary">Archive Records</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search archives..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        <?php if($search): ?><a href="admin_utilities.php" class="btn btn-sm btn-outline-secondary">Reset</a><?php endif; ?>
                    </form>
                </div>

                <div class="archive-slider-wrapper">
                    <button class="slider-btn" id="slideLeft"><i class="fas fa-arrow-left"></i></button>
                    
                    <div class="archive-slider" id="archiveSlider">
                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalMaintenance">
                            <i class="fas fa-wrench"></i>
                            <h3>Maintenance</h3>
                        </div>
                        
                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalHousekeeping">
                            <i class="fas fa-broom"></i>
                            <h3>Housekeeping</h3>
                        </div>

                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalBilling">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>Utility Bills</h3>
                        </div>

                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalRooms">
                            <i class="fas fa-bed"></i>
                            <h3>Archived Rooms</h3>
                        </div>

                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalReports">
                            <i class="fas fa-chart-line"></i>
                            <h3>Transactions</h3>
                        </div>

                        <div class="archive-card" data-bs-toggle="modal" data-bs-target="#modalUsers">
                            <i class="fas fa-user-slash"></i>
                            <h3>Archived Users</h3>
                        </div>
                    </div>

                    <button class="slider-btn" id="slideRight"><i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMaintenance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-wrench me-2"></i>Maintenance Archive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Tenant</th><th>Room</th><th>Issue</th><th>Status</th><th>Scheduled</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($maintenance_query)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><span class="badge <?= $row['status'] == 'Completed' ? 'bg-success' : 'bg-secondary' ?>"><?= $row['status'] ?></span></td>
                                <td><?= $row['scheduled_date'] ? date('M d, Y', strtotime($row['scheduled_date'])) : '-' ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Restore this request to Pending?')">
                                        <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="type" value="maintenance">
                                        <input type="hidden" name="archive_action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-primary me-1" title="Restore"><i class="fas fa-undo"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Permanently delete this record?')">
                                        <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="type" value="maintenance">
                                        <input type="hidden" name="archive_action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($maintenance_query) == 0): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No completed or cancelled maintenance requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHousekeeping" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-broom me-2"></i>Housekeeping Archive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Tenant</th><th>Room</th><th>Service</th><th>Status</th><th>Scheduled</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($housekeeping_query)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><span class="badge <?= $row['status'] == 'Completed' ? 'bg-success' : 'bg-secondary' ?>"><?= $row['status'] ?></span></td>
                                <td><?= $row['scheduled_date'] ? date('M d, Y', strtotime($row['scheduled_date'])) : '-' ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Restore this request to Pending?')">
                                        <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="type" value="housekeeping">
                                        <input type="hidden" name="archive_action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-primary me-1" title="Restore"><i class="fas fa-undo"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Permanently delete this record?')">
                                        <input type="hidden" name="id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="type" value="housekeeping">
                                        <input type="hidden" name="archive_action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($housekeeping_query) == 0): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No completed or cancelled housekeeping requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBilling" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Utility Bills</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr><th>Payment Date</th><th>Tenant</th><th>Room</th><th>Description</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($utility_bills_query)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($row['description']) ?></td>
                                <td class="text-end fw-bold text-success">₱<?= number_format($row['amount'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($utility_bills_query) == 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No paid utility bills found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRooms" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-bed me-2"></i>Archived Rooms</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Image</th><th>Room Name</th><th>Type</th><th>Price</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($archived_rooms_query)): ?>
                            <tr>
                                <td><img src="../assets/images/<?= $row['image'] ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded"></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['room_name']) ?></td>
                                <td><?= $row['room_type'] ?></td>
                                <td>₱<?= number_format($row['total_price'], 2) ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Restore this room?')">
                                        <input type="hidden" name="id" value="<?= $row['room_id'] ?>">
                                        <input type="hidden" name="type" value="room">
                                        <input type="hidden" name="archive_action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-primary me-1" title="Restore"><i class="fas fa-undo"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Permanently delete this room?')">
                                        <input type="hidden" name="id" value="<?= $row['room_id'] ?>">
                                        <input type="hidden" name="type" value="room">
                                        <input type="hidden" name="archive_action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($archived_rooms_query) == 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No archived rooms found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReports" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-chart-line me-2"></i>Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Tenant</th><th>Room</th><th>Description</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($transactions_query)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($row['description'] ?? '') ?></td>
                                <td><?= $row['payment_method'] ?></td>
                                <td class="text-end fw-bold text-success">₱<?= number_format($row['amount'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($transactions_query) == 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No transactions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsers" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-slash me-2"></i>Archived Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($archived_users_query)): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Restore this user account?')">
                                        <input type="hidden" name="id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="type" value="user">
                                        <input type="hidden" name="archive_action" value="restore">
                                        <button type="submit" class="btn btn-sm btn-outline-primary me-1" title="Restore"><i class="fas fa-undo"></i></button>
                                    </form>
                                    <?php if($is_super): // Only Super Admin can permanently delete ?>
                                    <form method="POST" class="d-inline" onsubmit="confirmForm(event, 'Permanently delete this user and ALL their data? This cannot be undone.')">
                                        <input type="hidden" name="id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="type" value="user">
                                        <input type="hidden" name="archive_action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently Delete"><i class="fas fa-user-slash"></i></button>
                                    </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger disabled" title="Super Admin Only"><i class="fas fa-user-slash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($archived_users_query) == 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No archived users found.</td></tr>
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
// Toggle Sidebar
function toggleMenu(e) {
    if(e) e.preventDefault();
    document.getElementById("wrapper").classList.toggle("toggled");
}
document.getElementById("menu-toggle").addEventListener("click", toggleMenu);
document.getElementById("sidebar-toggle").addEventListener("click", toggleMenu);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar-wrapper');
    var toggle = document.getElementById('menu-toggle');
    var wrapper = document.getElementById('wrapper');
    
    if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            wrapper.classList.remove('toggled');
        }
    }
});

// Horizontal Slider Logic
const slider = document.getElementById('archiveSlider');
document.getElementById('slideLeft').addEventListener('click', () => {
    slider.scrollBy({ left: -250, behavior: 'smooth' });
});
document.getElementById('slideRight').addEventListener('click', () => {
    slider.scrollBy({ left: 250, behavior: 'smooth' });
});

// Handle Re-opening Active Modal after POST request or Search
var activeModalID = "<?= $active_modal ?>";
if (activeModalID) {
    if (activeModalID === 'searchResults') {
        // If it's a search, maybe open the first modal or default to one
        var searchModal = new bootstrap.Modal(document.getElementById('modalReports'));
        searchModal.show();
    } else {
        var myModal = new bootstrap.Modal(document.getElementById(activeModalID));
        myModal.show();
    }
}

// Confirmation Dialogs
function confirmForm(e, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) e.target.submit();
    });
}
</script>
</body>
</html>