<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$reservation_id_from_get = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch GCash QR
$q_gcash = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='gcash_qr'");
$gcash_qr_file = ($row_gcash = mysqli_fetch_assoc($q_gcash)) ? $row_gcash['setting_value'] : "";
$gcash_qr_url = !empty($gcash_qr_file) ? "../uploads/settings/" . htmlspecialchars($gcash_qr_file) : "../Images/gcash_qr.jpg";

// Fetch reservation details including term_type and months
// Verify ownership and status
$check = mysqli_query($conn, "SELECT r.*, rm.room_type, rm.room_name FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.user_id=$user_id AND r.status IN ('Pending', 'Verifying', 'Approved') ORDER BY r.reservation_id DESC LIMIT 1");
$res_data = mysqli_fetch_assoc($check);

if(!$res_data){ header("Location: my_reservations.php"); exit; }

$reservation_id = $res_data['reservation_id']; // Use the latest active reservation for context

// Get all unpaid payments for this reservation individually
// ORDER BY payment_date ASC ensures we process them from oldest to newest
$pay_q = mysqli_query($conn, "
    SELECT p.* FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    WHERE r.user_id=$user_id AND (p.payment_status='Unpaid' OR (p.payment_status='Cancelled' AND p.description NOT LIKE '%Carried over%' AND (p.description LIKE '%Security Deposit%' OR p.description LIKE '%Downpayment%' OR p.description LIKE '%Initial%') AND r.status IN ('Pending', 'Verifying', 'Approved'))) ORDER BY p.payment_date ASC
");
$unpaid_bills = []; // This will be passed to JS
while($row = mysqli_fetch_assoc($pay_q)) {
    $desc_lower = strtolower($row['description']);
    
    $clean_desc = preg_replace('/\s*\[FULL\]\s*/i', '', $row['description']);
    $clean_desc = preg_replace('/\s*\(Parking ID: \d+\)/i', '', $clean_desc);
    $row['display_description'] = trim($clean_desc);
    
    if (strpos($desc_lower, 'security deposit') !== false || strpos($desc_lower, 'downpayment') !== false || strpos($desc_lower, 'initial') !== false) {
        $row['is_deposit'] = true;
    } else {
        $row['is_deposit'] = false;
    }
    $unpaid_bills[] = $row;
}

// Get all payments for this reservation to show the schedule
$all_pay_q = mysqli_query($conn, "
    SELECT p.* FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    WHERE r.user_id=$user_id 
    ORDER BY p.payment_date ASC
");
$all_payments = [];
while($row = mysqli_fetch_assoc($all_pay_q)) {
    $clean_desc = preg_replace('/\s*\[FULL\]\s*/i', '', $row['description']);
    $clean_desc = preg_replace('/\s*\(Parking ID: \d+\)/i', '', $clean_desc);
    $row['display_description'] = trim($clean_desc);
    
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
            } else if ($method == 'Cash') {
                $ref_number = 'Pay at Property';
                $proof_filename = 'Cash'; // Dummy value to trigger 'Submitted' state for admin
            }
        
            if(!$error){
                // Payments start as Unpaid until verified by admin
                $status = 'Unpaid';
                $is_full = (count($final_ids) >= count($unpaid_bills)) ? " [FULL]" : "";

                 // Keep original individual descriptions but mark as paid/submitted together
                $new_desc_sql = "IF(description LIKE '%[FULL]%', description, CONCAT(description, ?))";
                $stmt = mysqli_prepare($conn, "UPDATE payments SET payment_method=?, payment_status=?, reference_number=?, proof_image=?, payment_date=NOW(), description=$new_desc_sql WHERE payment_id IN ($payment_ids_str)");
                mysqli_stmt_bind_param($stmt, "sssss", $method, $status, $ref_number, $proof_filename, $is_full);

                
                if(mysqli_stmt_execute($stmt)){
                    // Log the detailed breakdown for admin reference
                    $selected_descs = [];
                    foreach ($unpaid_bills as $ub) {
                        if (in_array($ub['payment_id'], $final_ids)) {
                            $selected_descs[] = $ub['display_description'];
                        }
                    }
                    $log_desc = (count($final_ids) > 1) ? implode(', ', $selected_descs) : ($selected_descs[0] ?? 'Unknown Bill');
                    if (strlen($log_desc) > 150) $log_desc = substr($log_desc, 0, 147) . '...';
                    log_activity($conn, $user_id, "Payment Submitted", "Reservation #$reservation_id via $method for: " . $log_desc);
                    trigger_update($conn);
                    header("Location: my_reservations.php?msg=payment_submitted");
                    exit;
                } else { $error = "Database error."; }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pay Reservation | Woke Coliving</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .card, body.night-mode .bg-light { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .accordion-item, body.night-mode .accordion-button { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .border, body.night-mode .border-bottom { border-color: #444 !important; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">

<div class="container mt-5 py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card p-4 shadow-sm border-0 rounded-4">
                <h3 class="fw-bold text-success mb-4"><i class="fas fa-credit-card me-2"></i>Complete Your Payment</h3>
                <div class="alert alert-info border-0 shadow-sm rounded-4 small mb-4">
                    <i class="fas fa-info-circle me-2"></i> <strong>Reservation Details:</strong><br>
                    Room: <?= htmlspecialchars($res_data['room_name']) ?> (<?= htmlspecialchars($res_data['room_type']) ?>)
                </div>

                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <?php if(!empty($unpaid_bills)): ?>
                <form method="POST" enctype="multipart/form-data" id="paymentForm" onsubmit="return validatePaymentForm()">
                    <div class="accordion mb-4 shadow-sm" id="billsAccordion">
                        <div class="accordion-item border-0 rounded-4 overflow-hidden">
                            <h2 class="accordion-header" id="headingBills">
                                <button class="accordion-button bg-light text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBills">
                                    <i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Select Bills to Pay
                                </button>
                            </h2>
                            <div id="collapseBills" class="accordion-collapse collapse show" data-bs-parent="#billsAccordion">
                                <div class="accordion-body p-3">
                                    <div class="form-check mb-3 pb-2 border-bottom">
                                        <input class="form-check-input" type="checkbox" id="selectAllBills" onclick="toggleSelectAllBills(this)">
                                        <label class="form-check-label fw-bold ms-2 mt-1" for="selectAllBills">Select All Bills</label>
                                    </div>
                                    <div id="bill_checkboxes_container">
                                        <?php foreach($unpaid_bills as $index => $bill): ?>
                                            <label class="form-check mb-2 p-3 border rounded d-flex justify-content-between align-items-center" style="cursor:pointer;" for="bill_<?= $bill['payment_id'] ?>">
                                                <div class="d-flex align-items-center">
                                                    <input class="form-check-input bill-checkbox me-3 mt-0" style="width: 1.5em; height: 1.5em;" type="checkbox" name="selected_payments[]" value="<?= $bill['payment_id'] ?>" id="bill_<?= $bill['payment_id'] ?>" data-amount="<?= $bill['amount'] ?>" data-index="<?= $index ?>" data-desc="<?= htmlspecialchars($bill['display_description']) ?>" onchange="handleConsecutiveSelection(this); calculateTotal(); checkSelectAllState()">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($bill['display_description']) ?></div>
                                                        <div class="small text-muted">Added: <?= date('M d, Y', strtotime($bill['payment_date'])) ?></div>
                                                    </div>
                                                </div>
                                                <div class="fw-bold text-success fs-5">₱<?= number_format($bill['amount'], 2) ?></div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-4 border-0 shadow-sm">
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

                    <div id="gcash_div" class="mb-3 p-4 border rounded-4 bg-light shadow-sm" style="display:none;">
                        <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash</h6>
                        <div class="text-center mb-4">
                            <p class="small text-muted mb-2">Scan the QR code below to pay:</p>
                            <img src="<?= $gcash_qr_url ?>" alt="GCash QR Code" class="img-fluid border rounded shadow-sm mb-3" style="max-height: 250px;">
                            <p class="fw-bold text-dark mb-0">Account Name: WOKE COLIVING INC</p>
                            <p class="fw-bold text-dark">Number: 0917 123 4567</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Amount to Pay</label>
                            <input type="text" class="form-control fw-bold text-success" id="gcash_amount_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reference Number*</label>
                            <input type="text" name="ref_number" class="form-control" placeholder="11-digit Reference No." pattern="\d{11}" maxlength="11" title="Please enter exactly 11 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Proof of Payment* (Screenshot)</label>
                            <input type="file" name="proof_image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="submit_payment" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm">Submit Payment</button>
                        <a href="my_reservations.php" class="btn btn-outline-secondary rounded-pill fw-bold">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-success text-center py-5 rounded-4 border-0 shadow-sm">
                        <i class="fas fa-check-circle fa-4x mb-3 text-success"></i><br>
                        <strong class="fs-4">All caught up!</strong><br><span class="text-muted">You have no unpaid bills currently due for this reservation.</span>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <a href="my_reservations.php" class="btn btn-outline-secondary btn-lg rounded-pill fw-bold">Back to Reservations</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePaymentDetails() {
    let method = document.getElementById('payment_method').value;
    document.getElementById('gcash_div').style.display = (method === 'GCash') ? 'block' : 'none';
    
    let gcashRef = document.querySelector('input[name="ref_number"]');
    let gcashProof = document.querySelector('input[name="proof_image"]');
    
    if (method === 'GCash') {
        if(gcashRef) gcashRef.required = true;
        if(gcashProof) gcashProof.required = true;
    } else {
        if(gcashRef) gcashRef.required = false;
        if(gcashProof) gcashProof.required = false;
    }
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

function handleConsecutiveSelection(source) {
    const checkboxes = Array.from(document.querySelectorAll('.bill-checkbox'));
    const clickedIndex = parseInt(source.getAttribute('data-index'));

    if (source.checked) {
        for (let i = 0; i < clickedIndex; i++) {
            checkboxes[i].checked = true;
        }
    } else {
        for (let i = clickedIndex + 1; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
        }
    }
}

function calculateTotal() {
    let total = 0;
    let checkboxes = document.querySelectorAll('.bill-checkbox:checked');
    checkboxes.forEach(function(checkbox) {
        total += parseFloat(checkbox.getAttribute('data-amount'));
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
    const checkboxes = document.querySelectorAll('.bill-checkbox');
    if (checkboxes.length > 0) {
        checkboxes[0].checked = true;
    }
    calculateTotal();
    checkSelectAllState();
});
</script>
</body>
</html>