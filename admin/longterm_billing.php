<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// 1. Ensure utility_bills table exists and supports room-based billing
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS utility_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NULL,
    reservation_id INT NULL,
    split_count INT DEFAULT 1,
    bill_date DATE NOT NULL,
    electric_start DECIMAL(10,2) DEFAULT 0,
    electric_end DECIMAL(10,2) DEFAULT 0,
    electric_rate DECIMAL(10,2) DEFAULT 0,
    water_start DECIMAL(10,2) DEFAULT 0,
    water_end DECIMAL(10,2) DEFAULT 0,
    water_rate DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Update schema if table already existed without room_id
$check_room_id = mysqli_query($conn, "SHOW COLUMNS FROM utility_bills LIKE 'room_id'");
if(mysqli_num_rows($check_room_id) == 0) {
    mysqli_query($conn, "ALTER TABLE utility_bills ADD COLUMN room_id INT NULL AFTER id");
    mysqli_query($conn, "ALTER TABLE utility_bills MODIFY COLUMN reservation_id INT NULL");
    mysqli_query($conn, "ALTER TABLE utility_bills ADD COLUMN split_count INT DEFAULT 1");
}

// 2. Ensure payments table has description column for bill details
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'description'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN description VARCHAR(255) DEFAULT 'Room Payment'");
}

$message = "";
$error = "";

// Handle History Fetch (AJAX)
if(isset($_GET['fetch_history']) && isset($_GET['room_id'])){
    $rid = (int)$_GET['room_id'];
    $hq = mysqli_query($conn, "SELECT * FROM utility_bills WHERE room_id=$rid ORDER BY bill_date DESC");
    if(mysqli_num_rows($hq)>0){
        echo '<div class="table-responsive"><table class="table table-hover align-middle mb-0">';
        echo '<thead class="table-light"><tr><th>Date</th><th>Elec (kw)</th><th>Water (m³)</th><th>Total (₱)</th><th>Split</th></tr></thead><tbody>';
        while($r = mysqli_fetch_assoc($hq)){
            $e = max(0, $r['electric_end'] - $r['electric_start']);
            $w = max(0, $r['water_end'] - $r['water_start']);
            echo '<tr><td><span class="fw-bold text-dark">'.date('M d, Y', strtotime($r['bill_date'])).'</span></td><td>'.$e.'</td><td>'.$w.'</td><td class="fw-bold text-success">₱'.number_format($r['total_amount'],2).'</td><td><span class="badge bg-secondary rounded-pill">'.$r['split_count'].' Tenants</span></td></tr>';
        }
        echo '</tbody></table></div>';
    }else{
        echo '<div class="text-center text-muted py-5"><i class="fas fa-history fa-3x mb-3 opacity-25"></i><h6 class="fw-bold">No History Found</h6><p class="small">This room has not been billed yet.</p></div>';
    }
    exit;
}

// Handle Bill Generation
if(isset($_POST['generate_bill'])){
    $room_id = (int)$_POST['room_id'];
    $bill_date = $_POST['bill_date'];
    
    // Electricity
    $e_start = (float)$_POST['electric_start'];
    $e_end = (float)$_POST['electric_end'];
    $e_rate = (float)$_POST['electric_rate'];
    $e_usage = max(0, $e_end - $e_start);
    $e_cost = $e_usage * $e_rate;

    // Water
    $w_start = (float)$_POST['water_start'];
    $w_end = (float)$_POST['water_end'];
    $w_rate = (float)$_POST['water_rate'];
    $w_usage = max(0, $w_end - $w_start);
    $w_cost = $w_usage * $w_rate;

    $total = $e_cost + $w_cost;

    // Find LT tenants in this room
    $lt_q = mysqli_query($conn, "SELECT reservation_id, user_id FROM reservations WHERE room_id=$room_id AND status='Approved' AND months >= 6 AND end_date >= CURDATE()");
    $actual_count = mysqli_num_rows($lt_q);

    if($total > 0 && $actual_count > 0){
        $per_tenant = $total / $actual_count;
        
        // Insert into utility_bills for the ROOM
        $stmt = mysqli_prepare($conn, "INSERT INTO utility_bills (room_id, bill_date, electric_start, electric_end, electric_rate, water_start, water_end, water_rate, total_amount, split_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isdddddddi", $room_id, $bill_date, $e_start, $e_end, $e_rate, $w_start, $w_end, $w_rate, $total, $actual_count);
        mysqli_stmt_execute($stmt);
        
        // Insert payment for EACH tenant
        while($t = mysqli_fetch_assoc($lt_q)) {
            $res_id = $t['reservation_id'];
            $uid = $t['user_id'];
            $desc = "Utility Bill ($bill_date) - Split 1/$actual_count";
            $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($pay_stmt, "ids", $res_id, $per_tenant, $desc);
            mysqli_stmt_execute($pay_stmt);
            
            send_notification($conn, $uid, "🧾 <strong>New Utility Bill</strong><br>A utility bill of ₱".number_format($per_tenant,2)." has been generated for your room.", "Billing");
        }
        $message = "Utility bill generated and split among $actual_count tenant(s).";
        trigger_update($conn);
    } else {
        $error = "Total bill amount is 0, or no active long-term tenants found in this room.";
    }
}

// Fetch Rooms Grouped by Type
$q_rooms = mysqli_query($conn, "
    SELECT rm.*, 
    (SELECT COUNT(DISTINCT r.reservation_id) FROM reservations r WHERE r.room_id = rm.room_id AND r.status='Approved' AND r.months >= 6 AND r.end_date >= CURDATE()) as lt_count 
    FROM rooms rm 
    WHERE rm.is_archived=0 
    ORDER BY rm.room_type, rm.floor, CAST(COALESCE(rm.room_number, rm.room_name) AS UNSIGNED)
");
$grouped_rooms = [];
while($r = mysqli_fetch_assoc($q_rooms)){
    $grouped_rooms[$r['room_type']][] = $r;
}

// Fetch Pending Counts for Sidebar
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
    <title>Utility Billing | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-room { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border: 1px solid rgba(0,0,0,0.05); }
        .card-room:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        
        .print-only { display: none; }
        @media print {
            @page { margin: 0 !important; }
            body, html { margin: 0 !important; padding: 15mm !important; background: #fff !important; }
            body * { visibility: hidden; }
            #billModal .modal-content, #billModal .modal-content * { visibility: visible; }
            #billModal .modal-content { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; background: white !important; }
            .no-print, .btn-close { display: none !important; }
            .print-only { display: block !important; margin: 0 auto; text-align: center; }
            .print-section { background: white !important; border: 1px solid #ddd !important; padding: 30px !important; margin-top: 20px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Utility Billing (Long-term Tenants)</h1>
            </div>

            <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <!-- Room Types -->
            <div class="row g-4">
                <?php foreach($grouped_rooms as $type => $rooms): 
                    $total_lt = array_sum(array_column($rooms, 'lt_count'));
                    $first = $rooms[0] ?? null;
                    if(!$first) continue;
                ?>
                <div class="col-md-4">
                    <div class="card card-room h-100 rounded-4 overflow-hidden" onclick="openRoomTypeModal('<?= md5($type) ?>')">
                        <img src="../assets/images/<?= $first['image'] ?>" style="height: 160px; object-fit: cover; width:100%;">
                        <div class="card-body text-center p-4">
                            <h3 class="fw-bold text-dark mb-3"><?= $type ?></h3>
                            <div class="alert <?= $total_lt > 0 ? 'alert-warning border-warning text-dark' : 'alert-light text-muted border' ?> fw-bold py-2 mb-3">
                                <i class="fas fa-users me-1"></i> <?= $total_lt ?> Long-term Tenants
                            </div>
                            <div class="mt-auto">
                                <span class="btn btn-sm btn-outline-success rounded-pill px-4 fw-bold"><i class="fas fa-file-invoice-dollar me-1"></i> Manage Billing</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Each Room Type -->
<?php foreach($grouped_rooms as $type => $rooms): ?>
<div class="modal fade" id="modal_<?= md5($type) ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i><?= $type ?> Utility Billing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="row g-3">
                    <?php foreach($rooms as $room): 
                        $rid = $room['room_id'];
                        $room_display = $room['room_number'] ? "Room " . $room['room_number'] : $room['room_name'];
                        
                        $lb_q = mysqli_query($conn, "SELECT bill_date, electric_end, water_end FROM utility_bills WHERE room_id=$rid ORDER BY bill_date DESC LIMIT 1");
                        $last_bill = mysqli_fetch_assoc($lb_q);
                        $prev_e = $last_bill ? $last_bill['electric_end'] : 0;
                        $prev_w = $last_bill ? $last_bill['water_end'] : 0;
                        $last_date = $last_bill ? date('M d, Y', strtotime($last_bill['bill_date'])) : 'Never';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold text-success mb-0"><?= $room_display ?></h5>
                                    <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                </div>
                                <p class="small text-muted mb-1"><i class="fas fa-users me-2 text-primary"></i> <?= $room['lt_count'] ?> Long-term Tenants</p>
                                <p class="small text-muted mb-3"><i class="fas fa-calendar-check me-2 text-info"></i> Last Billed: <?= $last_date ?></p>
                                
                                <div class="d-grid gap-2 mt-auto pt-3 border-top">
                                    <button class="btn btn-sm rounded-pill <?= $room['lt_count'] > 0 ? 'btn-custom' : 'btn-secondary disabled' ?>" onclick="openBillModal(<?= $rid ?>, '<?= addslashes($room_display) ?>', <?= $room['lt_count'] ?>, <?= $prev_e ?>, <?= $prev_w ?>)"><i class="fas fa-calculator me-1"></i> Generate Bill</button>
                                    <button class="btn btn-sm btn-outline-info rounded-pill" onclick="openHistoryModal(<?= $rid ?>, '<?= addslashes($room_display) ?>')"><i class="fas fa-history me-1"></i> View History</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i>History: <span id="histRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4" id="historyModalBody">
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Billing Modal -->
<div class="modal fade" id="billModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white no-print">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice me-2"></i>Generate Utility Bill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h4 class="fw-bold text-dark mb-1" id="modalRoomName">Room Name</h4>
                            <p class="text-muted small mb-0 d-flex align-items-center">
                                Billing Date: 
                                <input type="date" name="bill_date" class="form-control form-control-sm ms-2 w-auto border-0 bg-light shadow-sm" value="<?= date('Y-m-d') ?>" required>
                            </p>
                        </div>
                        <img src="../Images/WokeLogo.jpg" style="height: 60px;" class="d-none print-only mb-3">
                    </div>
                    
                    <input type="hidden" name="room_id" id="modalRoomId">
                    <input type="hidden" id="tenant_count" name="tenant_count">
                    
                    <div class="row g-4 mb-4 no-print">
                        <div class="col-md-6 border-end">
                            <h6 class="text-warning fw-bold mb-3"><i class="fas fa-bolt me-2"></i>Electricity</h6>
                            <div class="row g-2">
                                <div class="col-6"><label class="small text-muted fw-bold">Prev Reading</label><input type="number" step="0.01" name="electric_start" id="e_start" class="form-control" required oninput="calculateBill()"></div>
                                <div class="col-6"><label class="small text-muted fw-bold">Curr Reading</label><input type="number" step="0.01" name="electric_end" id="e_end" class="form-control border-warning" required oninput="calculateBill()"></div>
                                <div class="col-12 mt-2"><label class="small text-muted fw-bold">Rate (₱/kw)</label><input type="number" step="0.01" name="electric_rate" id="e_rate" class="form-control bg-light" value="12.00" required oninput="calculateBill()"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-info fw-bold mb-3"><i class="fas fa-tint me-2"></i>Water</h6>
                            <div class="row g-2">
                                <div class="col-6"><label class="small text-muted fw-bold">Prev Reading</label><input type="number" step="0.01" name="water_start" id="w_start" class="form-control" required oninput="calculateBill()"></div>
                                <div class="col-6"><label class="small text-muted fw-bold">Curr Reading</label><input type="number" step="0.01" name="water_end" id="w_end" class="form-control border-info" required oninput="calculateBill()"></div>
                                <div class="col-12 mt-2"><label class="small text-muted fw-bold">Rate (₱/m³)</label><input type="number" step="0.01" name="water_rate" id="w_rate" class="form-control bg-light" value="35.00" required oninput="calculateBill()"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Real-time Receipt Breakdown Area -->
                    <div class="bg-light p-4 rounded-4 border print-section">
                        <h6 class="fw-bold text-secondary mb-3 text-uppercase border-bottom pb-2" style="letter-spacing: 1px;">Calculation Breakdown</h6>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Electricity (<span id="calc_e_used" class="fw-bold text-dark">0.00</span> kw &times; ₱<span id="calc_e_rate">12.00</span>)</span>
                            <span class="fw-bold text-dark">₱ <span id="calc_e_total">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Water (<span id="calc_w_used" class="fw-bold text-dark">0.00</span> m³ &times; ₱<span id="calc_w_rate">35.00</span>)</span>
                            <span class="fw-bold text-dark">₱ <span id="calc_w_total">0.00</span></span>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold pt-3 border-top border-dark border-opacity-10">
                            <span class="text-secondary">Total Room Consumption</span>
                            <span class="text-danger fs-5">₱ <span id="calc_room_total">0.00</span></span>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold pt-3 mt-3 border-top border-dark border-opacity-10 bg-white p-3 rounded shadow-sm">
                            <span class="text-primary d-flex align-items-center"><i class="fas fa-users me-2"></i> Split Per Tenant (<span id="calc_tenant_count_display">1</span>)</span>
                            <span class="text-success fs-4">₱ <span id="calc_per_tenant">0.00</span></span>
                        </div>
                        <p class="small text-muted mt-3 mb-0 print-only fw-bold text-uppercase" style="letter-spacing: 1px;">This amount has been added to your account for payment.</p>
                    </div>

                </div>
                <div class="modal-footer bg-light no-print border-top-0 pt-0 pb-3 pe-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill me-auto fw-bold" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Breakdown</button>
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_bill" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"><i class="fas fa-paper-plane me-1"></i> Confirm & Bill Tenants</button>
                </div>
            </form>
        </div>
    </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function openRoomTypeModal(typeId) {
    new bootstrap.Modal(document.getElementById('modal_' + typeId)).show();
}

function openBillModal(id, name, count, prevE, prevW) {
    // Hide active type modal before opening bill modal
    const openModals = document.querySelectorAll('.modal.show');
    openModals.forEach(m => {
        const inst = bootstrap.Modal.getInstance(m);
        if(inst) inst.hide();
    });

    document.getElementById('modalRoomId').value = id;
    document.getElementById('modalRoomName').innerText = name;
    document.getElementById('tenant_count').value = count;
    document.getElementById('calc_tenant_count_display').innerText = count;
    
    document.getElementById('e_start').value = prevE;
    document.getElementById('w_start').value = prevW;
    document.getElementById('e_end').value = '';
    document.getElementById('w_end').value = '';
    
    calculateBill();
    new bootstrap.Modal(document.getElementById('billModal')).show();
}

function calculateBill() {
    let e_start = parseFloat(document.getElementById('e_start').value) || 0;
    let e_end = parseFloat(document.getElementById('e_end').value) || 0;
    let e_rate = parseFloat(document.getElementById('e_rate').value) || 0;
    
    let w_start = parseFloat(document.getElementById('w_start').value) || 0;
    let w_end = parseFloat(document.getElementById('w_end').value) || 0;
    let w_rate = parseFloat(document.getElementById('w_rate').value) || 0;
    
    let e_used = Math.max(0, e_end - e_start);
    let w_used = Math.max(0, w_end - w_start);
    
    let e_total = e_used * e_rate;
    let w_total = w_used * w_rate;
    let total = e_total + w_total;
    
    let t_count = parseInt(document.getElementById('tenant_count').value) || 1;
    let per_tenant = total / Math.max(1, t_count);
    
    document.getElementById('calc_e_used').innerText = e_used.toFixed(2);
    document.getElementById('calc_e_rate').innerText = e_rate.toFixed(2);
    document.getElementById('calc_e_total').innerText = e_total.toLocaleString('en-US', {minimumFractionDigits: 2});
    
    document.getElementById('calc_w_used').innerText = w_used.toFixed(2);
    document.getElementById('calc_w_rate').innerText = w_rate.toFixed(2);
    document.getElementById('calc_w_total').innerText = w_total.toLocaleString('en-US', {minimumFractionDigits: 2});
    
    document.getElementById('calc_room_total').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('calc_per_tenant').innerText = per_tenant.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function openHistoryModal(roomId, roomName) {
    document.getElementById('histRoomName').innerText = roomName;
    const modalBody = document.getElementById('historyModalBody');
    modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading history...</p></div>';
    
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();
    
    fetch('longterm_billing.php?fetch_history=1&room_id=' + roomId)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load history. Please try again.</div>';
        });
}

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