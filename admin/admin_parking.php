<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Handle Actions
if (isset($_POST['add_parking_reservation'])) {
    $user_id = (int)$_POST['user_id'];
    $slot_id = (int)$_POST['slot_id'];
    $start_date = $_POST['start_date'];
    $billing_type = $_POST['billing_type'];

    // Get slot details
    $slot_q = mysqli_query($conn, "SELECT * FROM parking_slots WHERE id=$slot_id");
    $slot = mysqli_fetch_assoc($slot_q);

    $cost = ($billing_type == 'Monthly') ? $slot['monthly_rate'] : $slot['daily_rate'];
    $end_date_sql = ($billing_type == 'Monthly') ? "NULL" : "'" . $start_date . "'";

    // Insert parking reservation FIRST to get ID
    $pr_stmt = mysqli_prepare($conn, "INSERT INTO parking_reservations (user_id, slot_id, start_date, end_date, total_cost, billing_type) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($pr_stmt, "iisdss", $user_id, $slot_id, $start_date, $end_date_sql_val, $cost, $billing_type);
    $end_date_sql_val = ($billing_type == 'Monthly') ? null : $start_date;
    mysqli_stmt_execute($pr_stmt);
    $pr_id = mysqli_insert_id($conn);

    // NOW create description with the new ID
    if ($billing_type == 'Monthly') {
        $desc = "Monthly Parking Fee (" . date('F Y') . ") for " . $slot['slot_name'] . " (Parking ID: $pr_id)";
    } else { // Daily
        $desc = "Daily Parking Fee ($start_date) for " . $slot['slot_name'] . " (Parking ID: $pr_id)";
    }

    // Update slot status
    mysqli_query($conn, "UPDATE parking_slots SET status='Occupied' WHERE id=$slot_id");

    // Add to payments table, linking to an active room reservation
    $active_res_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$user_id AND status='Approved' ORDER BY end_date DESC LIMIT 1");
    if ($active_res_row = mysqli_fetch_assoc($active_res_q)) {
        $room_res_id = $active_res_row['reservation_id'];
        $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
        mysqli_stmt_bind_param($pay_stmt, "ids", $room_res_id, $cost, $desc);
        mysqli_stmt_execute($pay_stmt);
    }

    send_notification($conn, $user_id, "🅿️ <strong>Parking Assigned</strong><br>You have been assigned to " . $slot['slot_name'] . ". A fee of ₱" . number_format($cost, 2) . " has been added to your account.", "Parking");
    trigger_update($conn);
    header("Location: admin_parking.php?msg=added");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'end') {
    $pr_id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM parking_reservations WHERE id=$pr_id");
    if ($pr = mysqli_fetch_assoc($res)) {
        $slot_id = $pr['slot_id'];
        mysqli_query($conn, "UPDATE parking_reservations SET status='Completed', end_date=CURDATE() WHERE id=$pr_id");
        mysqli_query($conn, "UPDATE parking_slots SET status='Available' WHERE id=$slot_id");
        send_notification($conn, $pr['user_id'], "🅿️ <strong>Parking Ended</strong><br>Your parking reservation for slot ID #$slot_id has been marked as completed.", "Parking");
        trigger_update($conn);
        header("Location: admin_parking.php?msg=ended");
        exit;
    }
}

// Fetch Data for Display
$slots_q = mysqli_query($conn, "SELECT * FROM parking_slots WHERE is_archived=0 ORDER BY slot_type, slot_name");
$parking_slots = ['Car' => [], 'Motorcycle' => []];
while ($row = mysqli_fetch_assoc($slots_q)) {
    $parking_slots[$row['slot_type']][] = $row;
}

$reservations_q = mysqli_query($conn, "SELECT pr.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, ps.slot_name, ps.slot_type FROM parking_reservations pr JOIN users u ON pr.user_id = u.user_id JOIN parking_slots ps ON pr.slot_id = ps.id WHERE pr.status = 'Active' ORDER BY pr.start_date DESC");
$users_q = mysqli_query($conn, "SELECT user_id, CONCAT(last_name, ', ', first_name) as full_name FROM users WHERE user_id IN (SELECT DISTINCT user_id FROM reservations WHERE status='Approved') ORDER BY last_name ASC");
$avail_slots_q = mysqli_query($conn, "SELECT id, slot_name, slot_type FROM parking_slots WHERE status='Available' AND is_archived=0 ORDER BY slot_type, slot_name");

// Sidebar counts
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
    <title>Parking Management | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        #wrapper { display: flex; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .slot-card { border: 1px solid #eee; border-radius: 10px; padding: 15px; text-align: center; transition: .3s; }
        .slot-card.occupied { background-color: #f8d7da; border-color: #f5c6cb; }
        .slot-card.available { background-color: #d4edda; border-color: #c3e6cb; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving</div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
            <a href="admin_waitlist.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
            <a href="admin_deletion_requests.php" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            <a href="admin_parking.php" class="sidebar-link active"><i class="fas fa-parking me-2"></i>Parking</a>
            <a href="admin_keys.php" class="sidebar-link"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-tools me-2"></i>Utilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>
            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-cog me-2"></i>Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu"><a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a><a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a></div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Parking Management</h4>
                <div>
                    <a href="admin_parking_reports.php" class="btn btn-outline-success"><i class="fas fa-chart-bar me-2"></i>View Reports</a>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addReservationModal"><i class="fas fa-plus me-2"></i>New Parking Reservation</button>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= $_GET['msg'] == 'added' ? 'Reservation added.' : 'Reservation ended.' ?></div>
            <?php endif; ?>

            <!-- Slot Monitoring -->
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-secondary mb-3">Slot Monitoring</h5>
                <h6 class="fw-bold"><i class="fas fa-car me-2 text-primary"></i>Car Slots (4)</h6>
                <div class="row g-3 mb-3">
                    <?php foreach($parking_slots['Car'] as $slot): ?>
                    <div class="col">
                        <div class="slot-card <?= strtolower($slot['status']) ?>">
                            <div class="fw-bold"><?= $slot['slot_name'] ?></div>
                            <div class="small"><?= $slot['status'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <h6 class="fw-bold"><i class="fas fa-motorcycle me-2 text-warning"></i>Motorcycle Slots (7)</h6>
                <div class="row g-3">
                    <?php foreach($parking_slots['Motorcycle'] as $slot): ?>
                    <div class="col">
                        <div class="slot-card <?= strtolower($slot['status']) ?>">
                            <div class="fw-bold"><?= $slot['slot_name'] ?></div>
                            <div class="small"><?= $slot['status'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Active Reservations -->
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-secondary mb-3">Active Parking Reservations</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Tenant</th><th>Slot</th><th>Billing</th><th>Start Date</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($reservations_q)): ?>
                            <tr>
                                <td class="fw-bold"><?= $row['full_name'] ?></td>
                                <td><?= $row['slot_name'] ?> (<?= $row['slot_type'] ?>)</td>
                                <td><?= $row['billing_type'] ?></td>
                                <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                <td class="text-end">
                                    <a href="?action=end&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('End this parking reservation?')">End Reservation</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($reservations_q) == 0): ?>
                                <tr><td colspan="5" class="text-center text-muted">No active parking reservations.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Reservation Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Parking Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php while($u = mysqli_fetch_assoc($users_q)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= $u['full_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Available Slot</label>
                        <select name="slot_id" class="form-select" required>
                            <option value="">-- Choose Slot --</option>
                            <?php while($s = mysqli_fetch_assoc($avail_slots_q)): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['slot_name'] ?> (<?= $s['slot_type'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing Type</label>
                        <select name="billing_type" class="form-select" required>
                            <option value="Monthly">Monthly</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_parking_reservation" class="btn btn-primary">Add Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>