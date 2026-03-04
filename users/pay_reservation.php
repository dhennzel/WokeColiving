<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$reservation_id = (int)$_GET['id'];

// Verify ownership and status
$check = mysqli_query($conn, "SELECT r.*, rm.room_type, rm.room_name FROM reservations r JOIN rooms rm ON r.room_id = rm.room_id WHERE r.reservation_id=$reservation_id AND r.user_id=$user_id AND r.status IN ('Pending', 'Verifying')");
$res_data = mysqli_fetch_assoc($check);

if(!$res_data){ header("Location: my_reservations.php"); exit; }

// Get unpaid payment
$pay_q = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id=$reservation_id AND payment_status='Unpaid' LIMIT 1");
$payment = mysqli_fetch_assoc($pay_q);

if(!$payment){ header("Location: my_reservations.php?msg=already_paid"); exit; }

$error = "";
if(isset($_POST['submit_payment'])){
    $method = $_POST['payment_method'];
    $ref_number = $_POST['ref_number'] ?? null;
    $proof_filename = null;

    if ($method == 'GCash') {
        if (empty($ref_number)) { $error = "GCash Reference Number is required."; }
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
            $target_dir = "../uploads/proofs/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $proof_filename = time() . '_' . basename($_FILES["proof_image"]["name"]);
            if (!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_dir . $proof_filename)) {
                $error = "Error uploading proof.";
            }
        } else { $error = "Proof of payment is required for GCash."; }
    }

    if(!$error){
        $status = ($method == 'GCash' || $method == 'PayPal') ? 'Paid' : 'Unpaid';
        $stmt = mysqli_prepare($conn, "UPDATE payments SET payment_method=?, payment_status=?, reference_number=?, proof_image=?, payment_date=NOW() WHERE payment_id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $method, $status, $ref_number, $proof_filename, $payment['payment_id']);
        
        if(mysqli_stmt_execute($stmt)){
            log_activity($conn, $user_id, "Payment Submitted", "Reservation #$reservation_id via $method");
            trigger_update($conn);
            header("Location: my_reservations.php?msg=payment_submitted");
            exit;
        } else { $error = "Database error."; }
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
    <style>
        :root { --primary-green: #2E7D32; --dark-green: #1B5E20; --accent-yellow: #FBC02D; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        .navbar { background: var(--dark-green); padding: 15px 0; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card-custom { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .alert-info { background-color: #2c2c2c; border-color: #333; color: #e0e0e0; }
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c; color: #e0e0e0; border-color: #444; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333; color: #fff; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
    </style>
</head>
<body>
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
                    Amount Due: <span class="h4 fw-bold">₱<?= number_format($payment['amount'], 2) ?></span>
                </div>
                
                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentDetails()">
                            <option value="Cash">Cash (Pay at Property)</option>
                            <option value="GCash">GCash</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>

                    <div id="gcash_div" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                        <h6 class="fw-bold text-primary"><i class="fas fa-mobile-alt me-2"></i>Pay via GCash</h6>
                        <div class="text-center mb-3">
                            <img src="../Images/gcash_qr.png" alt="GCash QR" style="width: 150px;">
                            <p class="fw-bold mt-1">0967-310-3156 (Woke Coliving)</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">GCash Reference Number</label>
                            <input type="text" name="ref_number" class="form-control" placeholder="Enter Ref No.">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Upload Proof (Screenshot)</label>
                            <input type="file" name="proof_image" class="form-control" accept="image/*">
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

// Night Mode Logic
if(localStorage.getItem('nightMode') === 'enabled') {
    document.body.classList.add('night-mode');
}
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode') {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});
</script>
</body>
</html>