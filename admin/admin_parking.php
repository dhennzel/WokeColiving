<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Actions
if (isset($_POST['add_parking_reservation'])) {
    $user_id = (int)$_POST['user_id'];
    $slot_id = (int)$_POST['slot_id'];
    $start_date = $_POST['start_date'];
    $billing_type = $_POST['billing_type'];
    $payment_method = $_POST['payment_method'];

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
        $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, ?, 'Unpaid', NOW(), ?)");
        mysqli_stmt_bind_param($pay_stmt, "idss", $room_res_id, $cost, $payment_method, $desc);
        mysqli_stmt_execute($pay_stmt);
    }

    send_notification($conn, $user_id, "🅿️ <strong>Parking Assigned</strong><br>You have been assigned to " . $slot['slot_name'] . ". A fee of ₱" . number_format($cost, 2) . " has been added to your account.", "Parking");
    log_activity($conn, $user_id, "Parking Assigned", "Assigned to " . $slot['slot_name'] . " by $admin_username");
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
        log_activity($conn, $pr['user_id'], "Parking Ended", "Parking reservation #$pr_id ended by $admin_username");
        trigger_update($conn);
        header("Location: admin_parking.php?msg=ended");
        exit;
    }
}

// Fetch Data for Display
$slots_q = mysqli_query($conn, "
    SELECT ps.*, CONCAT(u.first_name, ' ', u.last_name) as occupant_name 
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
    SELECT pr.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, ps.slot_name, ps.slot_type
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
    <link rel="stylesheet" href="admin.css">
    <style>
        .slot-card { border: 1px solid #eee; border-radius: 10px; padding: 15px; text-align: center; transition: .3s; }
        .slot-card.occupied { background-color: #f8d7da; border-color: #f5c6cb; }
        .slot-card.available { background-color: #d4edda; border-color: #c3e6cb; }
        .slot-select-card { cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
        .slot-select-card:hover { border-color: #ccc; background-color: #f9f9f9; transform: translateY(-2px); }
        .slot-select-card.selected { border-color: var(--primary-green); background-color: #e8f5e9; }
        .slot-select-card.selected i { color: var(--primary-green) !important; }
        .slot-select-card.disabled { opacity: 0.6; cursor: not-allowed; background-color: #f8f9fa; }
        .slot-select-card.disabled:hover { transform: none; box-shadow: none; border-color: transparent; }
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
                <?php if($show_overdue_modal): ?>
                <button class="btn btn-danger btn-sm" onclick="openOverdueModal()">
                    <i class="fas fa-exclamation-triangle me-2"></i>Overdue Alert (<?= $overdue_count ?>)
                </button>
                <?php endif; ?>
                <div>
                    <a href="admin_parking_reports.php" class="btn btn-outline-success"><i class="fas fa-chart-bar me-2"></i>View Reports</a>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addReservationModal"><i class="fas fa-plus me-2"></i>New Parking Reservation</button>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <?php
                $msg_class = 'alert-success';
                $msg_text = '';
                if($_GET['msg'] == 'added') { $msg_text = 'Parking reservation created successfully.'; }
                elseif($_GET['msg'] == 'ended') { $msg_text = 'Parking reservation ended successfully.'; }
                elseif($_GET['msg'] == 'user_has_slot') { $msg_class = 'alert-danger'; $msg_text = 'Failed: This user already has an active parking reservation.'; }
                elseif($_GET['msg'] == 'slot_occupied') { $msg_class = 'alert-danger'; $msg_text = 'Failed: This parking slot is already occupied.'; }
            ?>
            <?php if($msg_text): ?><div class="alert <?= $msg_class ?>"><?= $msg_text ?></div><?php endif; ?>
            <?php endif; ?>

            <!-- Slot Monitoring -->
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-secondary mb-3">Slot Monitoring</h5>
                <h6 class="fw-bold"><i class="fas fa-car me-2 text-primary"></i>Car Slots (<?= count($parking_slots['Car']) ?>)</h6>
                <div class="row g-3 mb-3">
                    <?php foreach($parking_slots['Car'] as $slot): ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                        <div class="slot-card <?= strtolower($slot['status']) ?> h-100 d-flex flex-column justify-content-center">
                            <div class="fw-bold"><?= $slot['slot_name'] ?></div>
                            <div class="small mb-1"><?= $slot['status'] ?></div>
                            <?php if($slot['status'] == 'Occupied'): ?>
                                <div class="mt-auto border-top border-dark border-opacity-10 pt-1 text-truncate small fw-bold" style="font-size: 0.75rem;" title="<?= htmlspecialchars($slot['occupant_name'] ?? '') ?>">
                                    <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($slot['occupant_name'] ?? 'Unknown') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <h6 class="fw-bold"><i class="fas fa-motorcycle me-2 text-warning"></i>Motorcycle Slots (<?= count($parking_slots['Motorcycle']) ?>)</h6>
                <div class="row g-3">
                    <?php foreach($parking_slots['Motorcycle'] as $slot): ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                        <div class="slot-card <?= strtolower($slot['status']) ?> h-100 d-flex flex-column justify-content-center">
                            <div class="fw-bold"><?= $slot['slot_name'] ?></div>
                            <div class="small mb-1"><?= $slot['status'] ?></div>
                            <?php if($slot['status'] == 'Occupied'): ?>
                                <div class="mt-auto border-top border-dark border-opacity-10 pt-1 text-truncate small fw-bold" style="font-size: 0.75rem;" title="<?= htmlspecialchars($slot['occupant_name'] ?? '') ?>">
                                    <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($slot['occupant_name'] ?? 'Unknown') ?>
                                </div>
                            <?php endif; ?>
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
                                    <button type="button" class="btn btn-sm btn-danger" onclick="endParkingReservation(<?= $row['id'] ?>, '<?= htmlspecialchars($row['full_name']) ?>', '<?= htmlspecialchars($row['slot_name']) ?>', '<?= $row['slot_type'] ?>')">End Reservation</button>
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
        </main>
    </div>
</div>

<!-- Add Reservation Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-parking me-2"></i>New Parking Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">SELECT TENANT</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php while($u = mysqli_fetch_assoc($users_q)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= $u['full_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted mb-2">SELECT PARKING SLOT</label>
                        <input type="hidden" name="slot_id" id="selected_slot_id" required>
                        
                        <ul class="nav nav-pills mb-3 nav-fill" id="slotTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="car-tab" data-bs-toggle="pill" data-bs-target="#car_slots" type="button"><i class="fas fa-car me-2"></i>Car Slots</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="motor-tab" data-bs-toggle="pill" data-bs-target="#motor_slots" type="button"><i class="fas fa-motorcycle me-2"></i>Motorcycle Slots</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                            <div class="tab-pane fade show active" id="car_slots" role="tabpanel">
                                <div class="row g-2">
                                    <?php if(empty($parking_slots['Car'])): ?>
                                        <div class="col-12 text-center text-muted py-3">No car slots configured.</div>
                                    <?php else: ?>
                                        <?php foreach($parking_slots['Car'] as $slot): ?>
                                            <div class="col-md-4 col-6">
                                                <div class="card slot-select-card h-100 <?= $slot['status'] == 'Occupied' ? 'disabled' : '' ?>" <?= $slot['status'] == 'Available' ? "onclick='selectSlot(this, {$slot['id']})'" : "" ?>>
                                                    <div class="card-body text-center p-2">
                                                        <i class="fas fa-car fa-2x text-secondary mb-2"></i>
                                                        <div class="fw-bold small"><?= $slot['slot_name'] ?></div>
                                                        <?php if($slot['status'] == 'Occupied'): ?>
                                                            <div class="badge bg-danger mt-1 d-block text-truncate" title="<?= htmlspecialchars($slot['occupant_name'] ?? 'Occupied') ?>"><?= htmlspecialchars($slot['occupant_name'] ?? 'Occupied') ?></div>
                                                        <?php else: ?>
                                                            <div class="badge bg-success mt-1 d-block">Available</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="motor_slots" role="tabpanel">
                                <div class="row g-2">
                                    <?php if(empty($parking_slots['Motorcycle'])): ?>
                                        <div class="col-12 text-center text-muted py-3">No motorcycle slots configured.</div>
                                    <?php else: ?>
                                        <?php foreach($parking_slots['Motorcycle'] as $slot): ?>
                                            <div class="col-md-4 col-6">
                                                <div class="card slot-select-card h-100 <?= $slot['status'] == 'Occupied' ? 'disabled' : '' ?>" <?= $slot['status'] == 'Available' ? "onclick='selectSlot(this, {$slot['id']})'" : "" ?>>
                                                    <div class="card-body text-center p-2">
                                                        <i class="fas fa-motorcycle fa-2x text-secondary mb-2"></i>
                                                        <div class="fw-bold small"><?= $slot['slot_name'] ?></div>
                                                        <?php if($slot['status'] == 'Occupied'): ?>
                                                            <div class="badge bg-danger mt-1 d-block text-truncate" title="<?= htmlspecialchars($slot['occupant_name'] ?? 'Occupied') ?>"><?= htmlspecialchars($slot['occupant_name'] ?? 'Occupied') ?></div>
                                                        <?php else: ?>
                                                            <div class="badge bg-success mt-1 d-block">Available</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div id="slot_error" class="text-danger small mt-1" style="display:none;">Please select a slot.</div>
                    </div>

                    <div class="row">
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
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small text-muted">PAYMENT METHOD</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_parking_reservation" class="btn btn-success fw-bold" onclick="return validateSlot()">Confirm Reservation</button>
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
</script>
</body>
</html>