<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$reservation_id = (int)$_GET['id'];

// Verify ownership and status
$check = mysqli_query($conn, "SELECT r.*, rm.room_type, rm.room_name FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.reservation_id=$reservation_id AND r.user_id=$user_id AND r.status IN ('Pending', 'Verifying', 'Approved')");
$res_data = mysqli_fetch_assoc($check);

if(!$res_data){ header("Location: my_reservations.php"); exit; }

// Get all unpaid payments for this reservation individually
$pay_q = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id=$reservation_id AND payment_status='Unpaid' ORDER BY payment_date ASC");
$unpaid_bills = [];
while($row = mysqli_fetch_assoc($pay_q)) {
    $unpaid_bills[] = $row;
}

// Get all payments for this reservation to show the schedule
$all_pay_q = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id=$reservation_id ORDER BY payment_date ASC");
$all_payments = [];
while($row = mysqli_fetch_assoc($all_pay_q)) {
    $all_payments[] = $row;
}

$error = "";
if(isset($_POST['submit_payment'])){
    if(empty($_POST['selected_payments'])){
        $error = "Please select at least one bill to pay.";
    } else {
        $valid_ids = array_column($unpaid_bills, 'payment_id');
        $selected_ids = array_map('intval', $_POST['selected_payments']);
        $final_ids = array_intersect($selected_ids, $valid_ids);
        
        if(empty($final_ids)) {
            $error = "Invalid bills selected.";
        } else {
            $payment_ids_str = implode(',', $final_ids);
            
            $method = $_POST['payment_method'];
            $ref_number = $_POST['ref_number'] ?? null;
            $proof_filename = null;
        
            if ($method == 'GCash') {
                $ref_number = trim($_POST['ref_number'] ?? '');
                if(isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0){
                    $target_dir = "../uploads/proofs/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $proof_filename = time() . '_gcash_' . basename($_FILES["proof_image"]["name"]);
                    if(!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_dir . $proof_filename)){
                        $error = "Error uploading proof of payment.";
                    }
                } else {
                    $error = "Proof of payment image is required for GCash.";
                }
                if(empty($ref_number)) $error = "Reference number is required for GCash.";
            }
        
            if(!$error){
                // Payments start as Unpaid until verified by admin
                $status = 'Unpaid';
                $stmt = mysqli_prepare($conn, "UPDATE payments SET payment_method=?, payment_status=?, reference_number=?, proof_image=?, payment_date=NOW() WHERE payment_id IN ($payment_ids_str)");
                mysqli_stmt_bind_param($stmt, "ssss", $method, $status, $ref_number, $proof_filename);
                
                if(mysqli_stmt_execute($stmt)){
                    log_activity($conn, $user_id, "Payment Submitted", "Reservation #$reservation_id via $method for " . count($final_ids) . " bill(s)");
                    trigger_update($conn);
                    header("Location: my_reservations.php?msg=payment_submitted");
                    exit;
                } else { $error = "Database error."; }
            }
        }
    }
}

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pay Reservation | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-green: #2E7D32; --dark-green: #1B5E20; --accent-yellow: #FBC02D; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        .navbar { background: var(--dark-green); padding: 15px 0; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }

        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card-custom { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .alert-info { background-color: #2c2c2c !important; border-color: #333 !important; color: #e0e0e0 !important; }
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333 !important; color: #fff !important; border-color: var(--primary-green) !important; }
        body.night-mode .bg-light, body.night-mode .bg-white { background-color: #2c2c2c !important; color: #e0e0e0 !important; }
        body.night-mode .border { border-color: #444 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .form-control[type="file"] { color: #34B875 !important; }
        body.night-mode .form-control::file-selector-button { background-color: #1e1e1e !important; color: #34B875 !important; border-color: #444 !important; }
        body.night-mode .form-control:hover::file-selector-button { background-color: #333 !important; }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
        body.night-mode .accordion-item { background-color: #1e1e1e !important; border-color: #333 !important; }
        body.night-mode .accordion-button { background-color: #2c2c2c !important; color: #e0e0e0 !important; }
        body.night-mode .accordion-button:not(.collapsed) { background-color: #2c2c2c !important; color: #e0e0e0 !important; box-shadow: inset 0 -1px 0 rgba(255,255,255,0.1); }
        body.night-mode .accordion-body { background-color: #1e1e1e !important; color: #e0e0e0 !important; }
        body.night-mode .custom-checkbox-box { background-color: #2c2c2c !important; border-color: #444 !important; }
        body.night-mode .custom-checkbox-box .text-muted { color: #34B875 !important; }
        body.night-mode #included_bills_list li { color: #34B875 !important; }
        body.night-mode .form-check-input { background-color: #333 !important; border-color: #555 !important; }
        body.night-mode .form-check-input:checked { background-color: var(--primary-green) !important; border-color: var(--primary-green) !important; }
        body.night-mode .table { color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .table th, body.night-mode .table td { background-color: transparent !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .table-light th, body.night-mode .table-light td { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
    })();
</script>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">Woke Coliving INC</a>
    </div>
</nav>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-custom p-4">
                <h3 class="fw-bold text-success mb-4">Complete Your Payment</h3>
                <div class="alert alert-info">
                    <strong>Reservation Details</strong><br>
                    Room: <?= $res_data['room_name'] ?> (<?= $res_data['room_type'] ?>)<br>
                </div>
                
                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <?php if(!empty($unpaid_bills)): ?>
                <form method="POST" enctype="multipart/form-data" id="paymentForm" onsubmit="return validatePaymentForm()">
                    
                    <div class="accordion mb-4 shadow-sm" id="billsAccordion">
                        <div class="accordion-item border-0 rounded-4 overflow-hidden">
                            <h2 class="accordion-header" id="headingBills">
                                <button class="accordion-button bg-light text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBills" aria-expanded="true" aria-controls="collapseBills">
                                    <i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Select Bills to Pay
                                </button>
                            </h2>
                            <div id="collapseBills" class="accordion-collapse collapse show" aria-labelledby="headingBills" data-bs-parent="#billsAccordion">
                                <div class="accordion-body p-3">
                                    <div class="form-check mb-3 pb-2 border-bottom">
                                        <input class="form-check-input" type="checkbox" id="selectAllBills" onclick="toggleSelectAllBills(this)" style="width: 1.2em; height: 1.2em;">
                                        <label class="form-check-label fw-bold ms-2 mt-1" for="selectAllBills">
                                            Select All Bills
                                        </label>
                                    </div>
                                    <div id="bill_checkboxes_container">
                                        <?php foreach($unpaid_bills as $index => $bill): ?>
                                            <label class="form-check custom-checkbox-box mb-2 p-3 border rounded d-flex justify-content-between align-items-center" style="cursor:pointer;" for="bill_<?= $bill['payment_id'] ?>">
                                                <div class="d-flex align-items-center">
                                                    <input class="form-check-input bill-checkbox me-3 mt-0" style="width: 1.5em; height: 1.5em;" type="checkbox" name="selected_payments[]" value="<?= $bill['payment_id'] ?>" id="bill_<?= $bill['payment_id'] ?>" data-amount="<?= $bill['amount'] ?>" data-desc="<?= htmlspecialchars($bill['description']) ?>" onchange="calculateTotal(); checkSelectAllState()" <?= $index === 0 ? 'checked' : '' ?>>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($bill['description']) ?></div>
                                                        <div class="small text-muted">Added: <?= date('M d, Y', strtotime($bill['payment_date'])) ?></div>
                                                    </div>
                                                </div>
                                                <div class="fw-bold text-success fs-5">
                                                    ₱<?= number_format($bill['amount'], 2) ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-2 text-dark">Bills Included in this Payment:</h6>
                        <ul class="list-unstyled mb-0 small text-muted" id="included_bills_list">
                            <!-- Populated by JS -->
                        </ul>
                    </div>

                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                        <strong class="mb-0 fs-5">Selected Total:</strong>
                        <span class="h3 fw-bold mb-0 text-success">₱<span id="selectedTotalDisplay">0.00</span></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentDetails()">
                            <option value="Cash">Cash (Pay at Property)</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>

                    <div id="gcash_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                        <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash</h6>
                        <div class="text-center mb-3">
                            <p class="small text-muted mb-2">Scan the QR code below to pay:</p>
                            <img src="../Images/gcash_qr.jpg" alt="GCash QR Code" class="img-fluid border rounded shadow-sm mb-2" style="max-height: 250px;">
                            <p class="fw-bold text-dark mb-0">Account Name: WOKE COLIVING INC</p>
                            <p class="fw-bold text-dark">Number: 0917 123 4567</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Amount to Pay</label>
                            <input type="text" class="form-control fw-bold text-success" id="gcash_amount_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Reference Number*</label>
                            <input type="text" name="ref_number" class="form-control" placeholder="Enter the 13-digit Reference No.">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small">Proof of Payment* (Screenshot)</label>
                            <input type="file" name="proof_image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="submit_payment" class="btn btn-success btn-lg rounded-pill">Submit Payment</button>
                        <a href="my_reservations.php" class="btn btn-outline-secondary rounded-pill">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-success text-center py-4 rounded-4 border-0 shadow-sm">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i><br>
                        <strong class="fs-5">All caught up!</strong><br><span class="text-muted">You have no unpaid bills currently due for this reservation.</span>
                    </div>
                    <div class="d-grid gap-2 mb-4">
                        <a href="my_reservations.php" class="btn btn-outline-secondary rounded-pill fw-bold">Back to Reservations</a>
                    </div>
                <?php endif; ?>

                <div class="mt-5">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>All Payment Months</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Due / Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_payments as $pay): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($pay['description']) ?></td>
                                    <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                                    <td>₱<?= number_format($pay['amount'], 2) ?></td>
                                    <td>
                                        <?php 
                                            $s = $pay['payment_status'];
                                            $cls = 'bg-warning text-dark';
                                            if($s == 'Paid') $cls = 'bg-success';
                                            elseif($s == 'Cancelled') $cls = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $cls ?> rounded-pill px-3"><?= $s ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function togglePaymentDetails() {
    let method = document.getElementById('payment_method').value;
    document.getElementById('gcash_div').style.display = (method === 'GCash') ? 'block' : 'none';
}

function toggleSelectAllBills(source) {
    let checkboxes = document.querySelectorAll('.bill-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = source.checked;
    });
    calculateTotal();
}

function checkSelectAllState() {
    let checkboxes = document.querySelectorAll('.bill-checkbox');
    let selectAll = document.getElementById('selectAllBills');
    let allChecked = true;
    checkboxes.forEach(function(checkbox) {
        if(!checkbox.checked) allChecked = false;
    });
    if(selectAll) selectAll.checked = allChecked;
}

function calculateTotal() {
    let total = 0;
    let checkboxes = document.querySelectorAll('.bill-checkbox');
    let listContainer = document.getElementById('included_bills_list');
    
    if(listContainer) listContainer.innerHTML = '';

    checkboxes.forEach(function(checkbox) {
        if(checkbox.checked) {
            let amt = parseFloat(checkbox.getAttribute('data-amount'));
            total += amt;
            
            let li = document.createElement('li');
            li.className = 'mb-1';
            li.innerHTML = `<i class="fas fa-check-circle text-success me-2"></i>${checkbox.getAttribute('data-desc')} <strong class="float-end">₱${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
            if(listContainer) listContainer.appendChild(li);
        }
    });

    let display = document.getElementById('selectedTotalDisplay');
    if(display) display.innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    let gcashAmountDisplay = document.getElementById('gcash_amount_display');
    if(gcashAmountDisplay) {
        gcashAmountDisplay.value = '₱ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function validatePaymentForm() {
    let selectedCount = document.querySelectorAll('.bill-checkbox:checked').length;
    if(selectedCount === 0) {
        Swal.fire('No Bills Selected', 'Please select at least one bill to pay.', 'warning');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
    checkSelectAllState();
});

// Night Mode Logic
const currentUserId = "<?= $user_id ?>";
if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
    document.body.classList.add('night-mode');
}
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode_' + currentUserId) {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});

// Notification Logic
let lastUnreadCount = <?= (int)$unread_count ?>;
function fetchNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if(data.unread_count > lastUnreadCount) {
                const audio = document.getElementById('notifSound');
                if(audio) audio.play().catch(e => {});
            }
            lastUnreadCount = data.unread_count;
        });
}
setInterval(fetchNotifications, 5000);
fetchNotifications(); // Initial load

// Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php').then(r => r.text()).then(t => {
        if(lastUpdate == 0) lastUpdate = t; else if (t > lastUpdate) location.reload();
    });
}
setInterval(checkUpdates, 3000);
</script>
</body>
</html>