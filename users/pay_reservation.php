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

if(empty($unpaid_bills)){ header("Location: my_reservations.php?msg=already_paid"); exit; }

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
                // Replaced manual upload with Dragonpay redirect logic
                $ref_number = 'PENDING_DRAGONPAY';
                $proof_filename = null;
            }
        
            if(!$error){
                // GCash is set to Unpaid until Dragonpay postback confirms the payment
                $status = ($method == 'PayPal') ? 'Paid' : 'Unpaid';
                $stmt = mysqli_prepare($conn, "UPDATE payments SET payment_method=?, payment_status=?, reference_number=?, proof_image=?, payment_date=NOW() WHERE payment_id IN ($payment_ids_str)");
                mysqli_stmt_bind_param($stmt, "ssss", $method, $status, $ref_number, $proof_filename);
                
                if(mysqli_stmt_execute($stmt)){
                    log_activity($conn, $user_id, "Payment Submitted", "Reservation #$reservation_id via $method for " . count($final_ids) . " bill(s)");
                    trigger_update($conn);

                    // 🚀 DRAGONPAY REDIRECT LOGIC
                    if ($method == 'GCash') {
                        $merchant_id = 'YOUR_MERCHANT_ID'; // Replace with your Dragonpay Merchant ID
                        $secret_key  = 'YOUR_SECRET_KEY';  // Replace with your Dragonpay Password
                        
                        $totalAmount = 0;
                        foreach($unpaid_bills as $bill) {
                            if(in_array($bill['payment_id'], $final_ids)) {
                                $totalAmount += $bill['amount'];
                            }
                        }
                        
                        $txn_id      = 'PAY-' . implode('-', $final_ids); // e.g., PAY-103-104
                        $amount      = number_format((float)$totalAmount, 2, '.', ''); // Strictly 2 decimal places
                        $ccy         = 'PHP';
                        $description = 'Woke Coliving Bills Payment';
                        
                        $u_q = mysqli_query($conn, "SELECT email FROM users WHERE user_id=$user_id");
                        $u_email = mysqli_fetch_assoc($u_q)['email'] ?? '';
                        
                        $message = "$merchant_id:$txn_id:$amount:$ccy:$description:$u_email:$secret_key";
                        $digest = sha1($message);

                        $url = "https://test.dragonpay.ph/Pay.aspx?merchantid=$merchant_id&txnid=$txn_id&amount=$amount&ccy=$ccy&description=" . urlencode($description) . "&email=" . urlencode($u_email) . "&digest=$digest&procid=GCSH";

                        header("Location: $url");
                        exit;
                    }

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
                    <strong>Reservation #<?= $reservation_id ?></strong><br>
                    Room: <?= $res_data['room_name'] ?> (<?= $res_data['room_type'] ?>)<br>
                </div>
                
                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form method="POST" enctype="multipart/form-data" id="paymentForm" onsubmit="return validatePaymentForm()">
                    
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Select Bills to Pay</h5>
                    <div class="mb-4">
                        <?php foreach($unpaid_bills as $bill): ?>
                        <label class="form-check custom-checkbox-box mb-2 p-3 border rounded d-flex justify-content-between align-items-center" style="cursor:pointer;" for="bill_<?= $bill['payment_id'] ?>">
                            <div class="d-flex align-items-center">
                                <input class="form-check-input bill-checkbox me-3 mt-0" style="width: 1.5em; height: 1.5em;" type="checkbox" name="selected_payments[]" value="<?= $bill['payment_id'] ?>" id="bill_<?= $bill['payment_id'] ?>" data-amount="<?= $bill['amount'] ?>" onchange="calculateTotal()" checked>
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
                    
                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                        <strong class="mb-0 fs-5">Selected Total:</strong>
                        <span class="h3 fw-bold mb-0 text-success">₱<span id="selectedTotalDisplay">0.00</span></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentDetails()">
                            <option value="Cash">Cash (Pay at Property)</option>
                            <option value="GCash">GCash</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>

                    <div id="gcash_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                        <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash (Online)</h6>
                        <p class="small text-muted mb-2">You will be securely redirected to GCash to complete your payment via Dragonpay.</p>
                        <div class="mb-3">
                            <label class="form-label small">Amount to Pay</label>
                            <input type="text" class="form-control fw-bold text-success" id="gcash_amount_display" readonly>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="fas fa-info-circle me-1"></i> Please do not close the browser until you are redirected back to our site.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="submit_payment" class="btn btn-success btn-lg rounded-pill">Submit Payment</button>
                        <a href="my_reservations.php" class="btn btn-outline-secondary rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function togglePaymentDetails() {
    let method = document.getElementById('payment_method').value;
    document.getElementById('gcash_div').style.display = (method === 'GCash') ? 'block' : 'none';
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.bill-checkbox:checked').forEach(function(checkbox) {
        total += parseFloat(checkbox.getAttribute('data-amount'));
    });
    document.getElementById('selectedTotalDisplay').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('gcash_amount_display').value = '₱ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function validatePaymentForm() {
    if(document.querySelectorAll('.bill-checkbox:checked').length === 0) {
        Swal.fire('No Bills Selected', 'Please select at least one bill to pay.', 'warning');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
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
</script>
</body>
</html>