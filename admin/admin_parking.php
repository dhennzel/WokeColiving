<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Price Settings Update
if(isset($_POST['update_parking_prices'])){
    $prices = [
        'price_parking_car_monthly' => (float)$_POST['car_monthly'],
        'price_parking_car_daily' => (float)$_POST['car_daily'],
        'price_parking_motor_monthly' => (float)$_POST['motor_monthly'],
        'price_parking_motor_daily' => (float)$_POST['motor_daily']
    ];
    
    foreach($prices as $key => $val){
        mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
    }

    // Bulk Update Existing Slots
    mysqli_query($conn, "UPDATE parking_slots SET monthly_rate='{$prices['price_parking_car_monthly']}', daily_rate='{$prices['price_parking_car_daily']}' WHERE slot_type='Car'");
    mysqli_query($conn, "UPDATE parking_slots SET monthly_rate='{$prices['price_parking_motor_monthly']}', daily_rate='{$prices['price_parking_motor_daily']}' WHERE slot_type='Motorcycle'");

    trigger_update($conn);
    header("Location: admin_parking.php?msg=prices_updated");
    exit;
}

// Fetch Current Prices
$default_parking_prices = [
    'car_monthly' => 600.00, 'car_daily' => 50.00,
    'motor_monthly' => 600.00, 'motor_daily' => 50.00
];
$q_prices = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'price_parking_%'");
while($row = mysqli_fetch_assoc($q_prices)){ 
    $key = str_replace('price_parking_', '', $row['setting_key']);
    $default_parking_prices[$key] = (float)$row['setting_value']; 
}

$message = "";
$msg_class = "alert-success";

// Handle Actions
if (isset($_POST['add_parking_reservation'])) {
    $user_id = (int)$_POST['user_id'];
    $slot_id = (int)$_POST['slot_id'];
    $start_date = $_POST['start_date'];
    $billing_type = $_POST['billing_type'];
    $payment_method = $_POST['payment_method'];
    $payment_status = $_POST['payment_status'] ?? 'Unpaid';
    $vehicle_plate = mysqli_real_escape_string($conn, $_POST['vehicle_plate'] ?? '');
    $vehicle_details = mysqli_real_escape_string($conn, $_POST['vehicle_details'] ?? '');

    // Validate: Check if user already has an active parking reservation
    $check_user_q = mysqli_query($conn, "SELECT id FROM parking_reservations WHERE user_id=$user_id AND status='Active'");
    if (mysqli_num_rows($check_user_q) > 0) {
        header("Location: admin_parking.php?msg=user_has_slot");
        exit;
    }

    // Validate: Check if slot is actually available
    $check_slot_q = mysqli_query($conn, "SELECT status FROM parking_slots WHERE id=$slot_id");
    $slot_status_row = mysqli_fetch_assoc($check_slot_q);
    if ($slot_status_row['status'] !== 'Available') {
        header("Location: admin_parking.php?msg=slot_occupied");
        exit;
    }

    // Get slot details
    $slot_q = mysqli_query($conn, "SELECT * FROM parking_slots WHERE id=$slot_id");
    $slot = mysqli_fetch_assoc($slot_q);

    $cost = ($billing_type == 'Monthly') ? $slot['monthly_rate'] : $slot['daily_rate'];
    $end_date_sql_val = ($billing_type == 'Monthly') ? null : $start_date;

    // Insert parking reservation FIRST to get ID
    $pr_stmt = mysqli_prepare($conn, "INSERT INTO parking_reservations (user_id, slot_id, start_date, end_date, total_cost, billing_type, vehicle_plate, vehicle_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($pr_stmt, "iissdsss", $user_id, $slot_id, $start_date, $end_date_sql_val, $cost, $billing_type, $vehicle_plate, $vehicle_details);
    mysqli_stmt_execute($pr_stmt);
    $pr_id = mysqli_insert_id($conn);

    // NOW create description with the new ID
    if ($billing_type == 'Monthly') {
        $desc = "Monthly Parking Fee (" . date('F Y', strtotime($start_date)) . ") for " . $slot['slot_name'] . " (Parking ID: $pr_id)";
    } else { // Daily
        $desc = "Daily Parking Fee ($start_date) for " . $slot['slot_name'] . " (Parking ID: $pr_id)";
    }

    // Update slot status
    mysqli_query($conn, "UPDATE parking_slots SET status='Occupied' WHERE id=$slot_id");

    // Add to payments table, linking to an active room reservation
    $active_res_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$user_id AND status='Approved' ORDER BY end_date DESC LIMIT 1");
    if ($active_res_row = mysqli_fetch_assoc($active_res_q)) {
        $room_res_id = $active_res_row['reservation_id'];
        $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, parking_reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($pay_stmt, "iidssss", $room_res_id, $pr_id, $cost, $payment_method, $payment_status, $start_date, $desc);
        mysqli_stmt_execute($pay_stmt);
    }

    send_notification($conn, $user_id, "🅿️ <strong>Parking Assigned</strong><br>You have been assigned to " . $slot['slot_name'] . ". A fee of ₱" . number_format($cost, 2) . " has been added to your account.", "Parking");
    log_activity($conn, $user_id, "Parking Assigned", "Assigned to " . $slot['slot_name'] . " by $admin_username");
    trigger_update($conn);
    header("Location: admin_parking.php?msg=added");
    exit;
}

// Handle Edit Parking Reservation
if (isset($_POST['edit_parking_reservation'])) {
    $pr_id = (int)$_POST['pr_id'];
    $vehicle_plate = mysqli_real_escape_string($conn, $_POST['vehicle_plate'] ?? '');
    $vehicle_details = mysqli_real_escape_string($conn, $_POST['vehicle_details'] ?? '');
    
    mysqli_query($conn, "UPDATE parking_reservations SET vehicle_plate='$vehicle_plate', vehicle_details='$vehicle_details' WHERE id=$pr_id");
    
    $u_q = mysqli_query($conn, "SELECT user_id FROM parking_reservations WHERE id=$pr_id");
    if($u_row = mysqli_fetch_assoc($u_q)){
        log_activity($conn, $u_row['user_id'], "Parking Updated", "Vehicle info updated by $admin_username");
    }
    trigger_update($conn);
    header("Location: admin_parking.php?msg=updated");
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
        log_activity($conn, $pr['user_id'], "Parking Ended", "Parking reservation #$pr_id ended by $admin_username");
        trigger_update($conn);
        header("Location: admin_parking.php?msg=ended");
        exit;
    }
}

// Fetch Data for Display
$slots_q = mysqli_query($conn, "
    SELECT ps.*, CONCAT(u.first_name, ' ', u.last_name) as occupant_name 
    , pr.start_date as res_start, pr.vehicle_plate, pr.vehicle_details, pr.id as pr_id
    FROM parking_slots ps 
    LEFT JOIN parking_reservations pr ON ps.id = pr.slot_id AND pr.status = 'Active'
    LEFT JOIN users u ON pr.user_id = u.user_id 
    WHERE ps.is_archived = 0 
    ORDER BY ps.slot_type, ps.slot_name
");
$parking_slots = ['Car' => [], 'Motorcycle' => []];
while ($row = mysqli_fetch_assoc($slots_q)) {
    $parking_slots[$row['slot_type']][] = $row;
}

$reservations_q = mysqli_query($conn, "
    SELECT pr.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, ps.slot_name, ps.slot_type,
    (SELECT payment_status FROM payments WHERE parking_reservation_id = pr.id OR description LIKE CONCAT('%(Parking ID: ', pr.id, ')%') ORDER BY payment_id DESC LIMIT 1) as pay_status,
    (SELECT description FROM payments WHERE parking_reservation_id = pr.id OR description LIKE CONCAT('%(Parking ID: ', pr.id, ')%') ORDER BY payment_id DESC LIMIT 1) as pay_desc
    FROM parking_reservations pr 
    JOIN users u ON pr.user_id = u.user_id 
    JOIN parking_slots ps ON pr.slot_id = ps.id 
    WHERE pr.status = 'Active' 
    ORDER BY pr.start_date DESC
");
$users_q = mysqli_query($conn, "SELECT user_id, CONCAT(last_name, ', ', first_name) as full_name FROM users WHERE user_id IN (SELECT DISTINCT user_id FROM reservations WHERE status='Approved') AND user_id NOT IN (SELECT user_id FROM parking_reservations WHERE status='Active') ORDER BY last_name ASC");

// Check Overdue Parking
$overdue_check = mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_reservations WHERE status='Active' AND end_date < CURDATE()");
$overdue_count = mysqli_fetch_assoc($overdue_check)['c'];
$show_overdue_modal = ($overdue_count > 0);

// Sidebar counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
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
    <link rel="stylesheet" href="admin.css">
    <style>
        .slot-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease; background: var(--bg-surface); box-shadow: var(--shadow-sm); cursor: default; display: flex; flex-direction: column; justify-content: center; }
        .slot-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
        .slot-card.occupied { border-top: 4px solid var(--danger-color); }
        .slot-card.available { border-top: 4px solid var(--primary-green); }
        .slot-card.available:hover { background-color: rgba(52, 184, 117, 0.05); }
        .slot-card .status-icon { font-size: 2rem; margin-bottom: 10px; color: var(--text-muted); transition: color 0.3s; }
        .slot-select-card { cursor: pointer; border: 2px solid transparent; transition: all 0.2s; border-radius: 12px; background: var(--bg-surface); box-shadow: var(--shadow-sm); }
        .slot-select-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
        .slot-select-card.selected { border-color: var(--primary-green); background-color: rgba(52, 184, 117, 0.05); }
        .slot-select-card.selected i { color: var(--primary-green) !important; }
        .slot-select-card.disabled { opacity: 0.6; cursor: not-allowed; background-color: var(--bg-surface-hover); }
        .slot-select-card.disabled:hover { transform: none; box-shadow: var(--shadow-sm); border-color: transparent; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Parking Management</h1>
                <div>
                    <?php if($show_overdue_modal): ?>
                    <button class="btn btn-danger btn-sm me-2" onclick="openOverdueModal()">
                        <i class="fas fa-exclamation-triangle me-2"></i>Overdue Alert (<?= $overdue_count ?>)
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#priceSettingsModal"><i class="fas fa-tags me-2"></i>Set Prices</button>
                    <a href="admin_parking_reports.php" class="btn btn-outline-success"><i class="fas fa-chart-bar me-2"></i>View Reports</a>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <?php
                $msg_class = 'alert-success';
                $msg_text = '';
                if($_GET['msg'] == 'added') { $msg_text = 'Parking reservation created successfully.'; }
                elseif($_GET['msg'] == 'ended') { $msg_text = 'Parking reservation ended successfully.'; }
                elseif($_GET['msg'] == 'user_has_slot') { $msg_class = 'alert-danger'; $msg_text = 'Failed: This user already has an active parking reservation.'; }
                elseif($_GET['msg'] == 'prices_updated') { $msg_text = 'Default parking rates updated successfully.'; }
                elseif($_GET['msg'] == 'slot_occupied') { $msg_class = 'alert-danger'; $msg_text = 'Failed: This parking slot is already occupied.'; }
            elseif($_GET['msg'] == 'updated') { $msg_text = 'Parking reservation updated successfully.'; }
            ?>
            <?php if($msg_text): ?><div class="alert <?= $msg_class ?>"><?= $msg_text ?></div><?php endif; ?>
            <?php endif; ?>

            <!-- Slot Monitoring -->
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-secondary mb-4"><i class="fas fa-chart-pie me-2"></i>Slot Monitoring</h5>
                
                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-car me-2 text-primary"></i>Car Slots <span class="badge bg-primary rounded-pill ms-1"><?= count($parking_slots['Car']) ?></span></h6>
                <div class="row g-4 mb-4">
                    <?php foreach($parking_slots['Car'] as $slot): ?>
                    <?php 
                        $is_reserved = !empty($slot['occupant_name']);
                        $is_actually_parked = $is_reserved && (strtotime($slot['res_start']) <= time());
                        $status_class = $is_reserved ? ($is_actually_parked ? 'occupied' : 'reserved') : 'available';
                    ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="slot-card <?= $status_class ?> h-100 d-flex flex-column justify-content-center" <?= !$is_reserved ? 'onclick="openReservationModal('.$slot['id'].')" style="cursor: pointer;" title="Click to assign tenant"' : 'onclick="openEditReservationModal('.$slot['pr_id'].', \''.htmlspecialchars(addslashes($slot['vehicle_plate'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['vehicle_details'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['occupant_name'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['slot_name'] ?? '')).'\')" style="cursor: pointer;" title="Click to edit vehicle info"' ?>>
                            <i class="fas fa-car status-icon <?= $is_reserved ? ($is_actually_parked ? 'text-danger' : 'text-warning') : 'text-success' ?>"></i>
                            <div class="fw-bold text-dark mb-1"><?= $slot['slot_name'] ?></div>
                            <?php if($is_reserved): ?>
                                <span class="badge <?= $is_actually_parked ? 'bg-danger' : 'bg-warning text-dark' ?> mb-2 mx-auto"><?= $is_actually_parked ? 'Occupied' : 'Reserved' ?></span>
                                <div class="mt-auto pt-2 border-top small fw-bold text-muted text-truncate" title="<?= htmlspecialchars($slot['occupant_name']) ?>">
                                    <?php if(!empty($slot['vehicle_plate'])): ?>
                                        <div class="text-primary mb-1"><i class="fas fa-hashtag me-1"></i><?= htmlspecialchars($slot['vehicle_plate']) ?></div>
                                    <?php endif; ?>
                                    <i class="fas fa-user-lock me-1"></i><?= htmlspecialchars($slot['occupant_name']) ?>
                                    <?php if(!$is_actually_parked): ?><br><small class="text-muted">Starts: <?= date('M d', strtotime($slot['res_start'])) ?></small><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-success mb-2 mx-auto">Available</span>
                                <div class="mt-auto pt-2 border-top small text-muted">
                                    Ready to use
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-motorcycle me-2 text-warning"></i>Motorcycle Slots <span class="badge bg-warning text-dark rounded-pill ms-1"><?= count($parking_slots['Motorcycle']) ?></span></h6>
                <div class="row g-4">
                    <?php foreach($parking_slots['Motorcycle'] as $slot): ?>
                    <?php 
                        $is_reserved = !empty($slot['occupant_name']);
                        $is_actually_parked = $is_reserved && (strtotime($slot['res_start']) <= time());
                        $status_class = $is_reserved ? ($is_actually_parked ? 'occupied' : 'reserved') : 'available';
                    ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="slot-card <?= $status_class ?> h-100 d-flex flex-column justify-content-center" <?= !$is_reserved ? 'onclick="openReservationModal('.$slot['id'].')" style="cursor: pointer;" title="Click to assign tenant"' : 'onclick="openEditReservationModal('.$slot['pr_id'].', \''.htmlspecialchars(addslashes($slot['vehicle_plate'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['vehicle_details'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['occupant_name'] ?? '')).'\', \''.htmlspecialchars(addslashes($slot['slot_name'] ?? '')).'\')" style="cursor: pointer;" title="Click to edit vehicle info"' ?>>
                            <i class="fas fa-motorcycle status-icon <?= $is_reserved ? ($is_actually_parked ? 'text-danger' : 'text-warning') : 'text-success' ?>"></i>
                            <div class="fw-bold text-dark mb-1"><?= $slot['slot_name'] ?></div>
                            <?php if($is_reserved): ?>
                                <span class="badge <?= $is_actually_parked ? 'bg-danger' : 'bg-warning text-dark' ?> mb-2 mx-auto"><?= $is_actually_parked ? 'Occupied' : 'Reserved' ?></span>
                                <div class="mt-auto pt-2 border-top small fw-bold text-muted text-truncate" title="<?= htmlspecialchars($slot['occupant_name']) ?>">
                                    <?php if(!empty($slot['vehicle_plate'])): ?>
                                        <div class="text-primary mb-1"><i class="fas fa-hashtag me-1"></i><?= htmlspecialchars($slot['vehicle_plate']) ?></div>
                                    <?php endif; ?>
                                    <i class="fas fa-user-lock me-1"></i><?= htmlspecialchars($slot['occupant_name']) ?>
                                    <?php if(!$is_actually_parked): ?><br><small class="text-muted">Starts: <?= date('M d', strtotime($slot['res_start'])) ?></small><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-success mb-2 mx-auto">Available</span>
                                <div class="mt-auto pt-2 border-top small text-muted">
                                    Ready to use
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Active Reservations -->
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-secondary mb-4"><i class="fas fa-list me-2"></i>Active Parking Reservations</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th class="ps-3">Tenant</th><th>Slot</th><th>Vehicle Info</th><th>Billing</th><th>Start Date</th><th>Payment</th><th class="text-end pe-3">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($reservations_q)): ?>
                            <tr>
                                <td class="fw-bold text-dark ps-3"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['slot_name']) ?> 
                                    <span class="badge bg-light text-dark border ms-1"><?= $row['slot_type'] ?> Slot</span>
                                </td>
                                <td>
                                    <?php if(!empty($row['vehicle_plate'])): ?>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($row['vehicle_plate']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['vehicle_details']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info text-dark"><?= $row['billing_type'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                <td>
                                    <?php 
                                        $ps = $row['pay_status'] ?? 'Unpaid';
                                        $pdesc = $row['pay_desc'] ?? '';
                                        $ps_class = ($ps == 'Paid') ? 'bg-success' : 'bg-warning text-dark';
                                        
                                        if ($ps == 'Cancelled' && strpos($pdesc, 'Carried over') !== false) {
                                            $ps = 'Merged to Next Bill';
                                            $ps_class = 'bg-info text-dark';
                                        }
                                    ?>
                                    <span class="badge <?= $ps_class ?>"><?= $ps ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="endParkingReservation(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['full_name'])) ?>', '<?= htmlspecialchars(addslashes($row['slot_name'])) ?>', '<?= $row['slot_type'] ?>')"><i class="fas fa-stop-circle me-1"></i>End</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($reservations_q) == 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">No active parking reservations.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<!-- Add Reservation Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-parking me-2"></i>New Parking Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">SELECT TENANT</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php while($u = mysqli_fetch_assoc($users_q)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">SELECT PARKING SLOT</label>
                        <input type="hidden" name="slot_id" id="selected_slot_id" required>
                        
                        <ul class="nav nav-pills mb-3 nav-fill gap-2" id="slotTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-pill fw-bold" id="car-tab" data-bs-toggle="pill" data-bs-target="#car_slots" type="button"><i class="fas fa-car me-2"></i>Car Slots</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill fw-bold" id="motor-tab" data-bs-toggle="pill" data-bs-target="#motor_slots" type="button"><i class="fas fa-motorcycle me-2"></i>Motorcycle Slots</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content border rounded-4 p-3 bg-white shadow-sm" style="max-height: 350px; overflow-y: auto;">
                            <div class="tab-pane fade show active" id="car_slots" role="tabpanel">
                                <div class="row g-3">
                                    <?php if(empty($parking_slots['Car'])): ?>
                                        <div class="col-12 text-center text-muted py-3">No car slots configured.</div>
                                    <?php else: ?>
                                        <?php foreach($parking_slots['Car'] as $slot): ?>
                                            <?php 
                                                $is_occupied = !empty($slot['occupant_name']);
                                            ?>
                                            <div class="col-md-4 col-6">
                                                <div class="card slot-select-card h-100 <?= $is_occupied ? 'disabled' : '' ?>" <?= !$is_occupied ? "onclick='selectSlot(this, {$slot['id']})'" : "" ?>>
                                                    <div class="card-body text-center p-3">
                                                        <i class="fas fa-car fa-2x <?= $is_occupied ? 'text-danger' : 'text-success' ?> mb-2"></i>
                                                        <div class="fw-bold text-dark mb-1"><?= $slot['slot_name'] ?></div>
                                                        <?php if($is_occupied): ?>
                                                            <span class="badge bg-danger w-100 text-truncate" title="<?= htmlspecialchars($slot['occupant_name']) ?>"><i class="fas fa-user-lock me-1"></i> <?= htmlspecialchars($slot['occupant_name']) ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success w-100"><i class="fas fa-check me-1"></i> Available</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="motor_slots" role="tabpanel">
                                <div class="row g-3">
                                    <?php if(empty($parking_slots['Motorcycle'])): ?>
                                        <div class="col-12 text-center text-muted py-3">No motorcycle slots configured.</div>
                                    <?php else: ?>
                                        <?php foreach($parking_slots['Motorcycle'] as $slot): ?>
                                            <?php 
                                                $is_occupied = !empty($slot['occupant_name']);
                                            ?>
                                            <div class="col-md-4 col-6">
                                                <div class="card slot-select-card h-100 <?= $is_occupied ? 'disabled' : '' ?>" <?= !$is_occupied ? "onclick='selectSlot(this, {$slot['id']})'" : "" ?>>
                                                    <div class="card-body text-center p-3">
                                                        <i class="fas fa-motorcycle fa-2x <?= $is_occupied ? 'text-danger' : 'text-success' ?> mb-2"></i>
                                                        <div class="fw-bold text-dark mb-1"><?= $slot['slot_name'] ?></div>
                                                        <?php if($is_occupied): ?>
                                                            <span class="badge bg-danger w-100 text-truncate" title="<?= htmlspecialchars($slot['occupant_name']) ?>"><i class="fas fa-user-lock me-1"></i> <?= htmlspecialchars($slot['occupant_name']) ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success w-100"><i class="fas fa-check me-1"></i> Available</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div id="slot_error" class="text-danger small mt-2 fw-bold" style="display:none;"><i class="fas fa-exclamation-circle me-1"></i> Please select a parking slot.</div>
                    </div>

                    <div class="row bg-white p-3 rounded-4 border shadow-sm mx-0">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">VEHICLE PLATE NUMBER</label>
                            <input type="text" name="vehicle_plate" class="form-control" placeholder="e.g. ABC 1234">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">VEHICLE MAKE/MODEL</label>
                            <input type="text" name="vehicle_details" class="form-control" placeholder="e.g. Toyota Vios Black">
                        </div>
                    </div>

                    <div class="row bg-white p-3 rounded-4 border shadow-sm mx-0 mt-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">START DATE</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">BILLING TYPE</label>
                            <select name="billing_type" class="form-select" required>
                                <option value="Monthly">Monthly</option>
                                <option value="Daily">Daily</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">PAYMENT METHOD</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-muted">PAYMENT STATUS</label>
                            <select name="payment_status" class="form-select" required>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_parking_reservation" class="btn btn-success fw-bold rounded-pill px-4" onclick="return validateSlot()"><i class="fas fa-check me-2"></i>Confirm Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Reservation Modal -->
<div class="modal fade" id="editReservationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Vehicle Info</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light p-4">
                    <input type="hidden" name="pr_id" id="edit_pr_id">
                    <p class="mb-3 text-muted">Updating vehicle information for <strong id="edit_tenant_name" class="text-dark"></strong> at <strong id="edit_slot_name" class="text-dark"></strong>.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">VEHICLE PLATE NUMBER</label>
                        <input type="text" name="vehicle_plate" id="edit_vehicle_plate" class="form-control" placeholder="e.g. ABC 1234">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">VEHICLE MAKE/MODEL</label>
                        <input type="text" name="vehicle_details" id="edit_vehicle_details" class="form-control" placeholder="e.g. Toyota Vios Black">
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_parking_reservation" class="btn btn-primary fw-bold rounded-pill px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Price Settings Modal -->
<div class="modal fade" id="priceSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-tags me-2"></i>Parking Rate Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-light p-4">
                    <p class="text-muted small mb-4">Set the default rates for parking slots. Saving these values will update all existing slots.</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-car me-2"></i>Car Rates</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Monthly Rate</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="car_monthly" class="form-control" value="<?= $default_parking_prices['car_monthly'] ?>" required></div>
                                    </div>
                                    <div>
                                        <label class="form-label small fw-bold">Daily Rate</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="car_daily" class="form-control" value="<?= $default_parking_prices['car_daily'] ?>" required></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold text-warning mb-3"><i class="fas fa-motorcycle me-2"></i>Motorcycle Rates</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Monthly Rate</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="motor_monthly" class="form-control" value="<?= $default_parking_prices['motor_monthly'] ?>" required></div>
                                    </div>
                                    <div>
                                        <label class="form-label small fw-bold">Daily Rate</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="motor_daily" class="form-control" value="<?= $default_parking_prices['motor_daily'] ?>" required></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_parking_prices" class="btn btn-primary fw-bold rounded-pill px-4">Update Rates</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Overdue Modal -->
<div class="modal fade" id="overdueModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-circle me-2"></i>Overdue Parking Reservations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tenant</th>
                                <th>Slot</th>
                                <th>Due Date</th>
                                <th>Overdue By</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($show_overdue_modal):
                                $od_q = mysqli_query($conn, "SELECT pr.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, ps.slot_name, ps.slot_type FROM parking_reservations pr JOIN users u ON pr.user_id = u.user_id JOIN parking_slots ps ON pr.slot_id = ps.id WHERE pr.status = 'Active' AND pr.end_date < CURDATE() ORDER BY pr.end_date ASC");
                                while($row = mysqli_fetch_assoc($od_q)):
                                    $days = (new DateTime())->diff(new DateTime($row['end_date']))->days;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['slot_name']) ?> <span class="badge bg-light text-dark border ms-1"><?= $row['slot_type'] ?></span></td>
                                <td class="text-danger fw-bold"><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                                <td class="text-danger small"><?= $days ?> days ago</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-danger" onclick="endParkingReservation(<?= $row['id'] ?>, '<?= addslashes($row['full_name']) ?>', '<?= addslashes($row['slot_name']) ?>', '<?= $row['slot_type'] ?>')">End Reservation</button>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function selectSlot(card, id) {
    document.querySelectorAll('.slot-select-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selected_slot_id').value = id;
    document.getElementById('slot_error').style.display = 'none';
}

function validateSlot() {
    if(!document.getElementById('selected_slot_id').value) {
        document.getElementById('slot_error').style.display = 'block';
        return false;
    }
    return true;
}

function openReservationModal(slotId) {
    // Find the slot inside the modal visual picker and trigger a click to select it
    const card = document.querySelector(`.slot-select-card[onclick*="selectSlot(this, ${slotId})"]`);
    if (card) {
        const isMotor = card.closest('#motor_slots');
        if (isMotor) {
            document.getElementById('motor-tab').click();
        } else {
            document.getElementById('car-tab').click();
        }
        selectSlot(card, slotId);
    }
    new bootstrap.Modal(document.getElementById('addReservationModal')).show();
}

function openEditReservationModal(prId, plate, details, tenantName, slotName) {
    document.getElementById('edit_pr_id').value = prId;
    document.getElementById('edit_vehicle_plate').value = plate;
    document.getElementById('edit_vehicle_details').value = details;
    document.getElementById('edit_tenant_name').innerText = tenantName;
    document.getElementById('edit_slot_name').innerText = slotName;
    
    new bootstrap.Modal(document.getElementById('editReservationModal')).show();
}

function endParkingReservation(id, tenantName, slotName, slotType) {
    const icon = slotType === 'Car' ? 'fa-car' : 'fa-motorcycle';
    
    Swal.fire({
        title: 'End Parking Reservation?',
        html: `
            <div class="text-start">
                <p class="mb-2">Are you sure you want to end this parking reservation?</p>
                <div class="bg-light p-3 rounded">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-user text-secondary me-2"></i>
                        <strong>Tenant:</strong>&nbsp;<span>${tenantName}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas ${icon} text-secondary me-2"></i>
                        <strong>Slot:</strong>&nbsp;<span>${slotName}</span>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">This action will mark the reservation as completed and free up the parking slot.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, End Reservation',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?action=end&id=' + id;
        }
    });
}

function openOverdueModal() {
    new bootstrap.Modal(document.getElementById('overdueModal')).show();
}

<?php if($show_overdue_modal): ?>
document.addEventListener('DOMContentLoaded', function() {
    openOverdueModal();
});
<?php endif; ?>

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

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) {
            lastUpdate = t;
        } else if (t > lastUpdate) {
            sessionStorage.setItem('playNotifSound', 'true');
            location.reload();
        }
    });
}
setInterval(checkUpdates, 3000);

document.addEventListener('DOMContentLoaded', () => {
    if(sessionStorage.getItem('playNotifSound') === 'true') {
        let audio = new Audio('../assets/sounds/notification.mp3');
        audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
        audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
        sessionStorage.removeItem('playNotifSound');
    }
});
</script>
</body>
</html>