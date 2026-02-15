<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: profit_report.php");
    exit;
}

$payment_id = (int)$_GET['id'];

// Fetch Payment Details
$query = "
    SELECT p.*, r.start_date, r.end_date, u.full_name, u.email, u.phone_number, rm.room_name, rm.room_type
    FROM payments p
    JOIN reservations r ON p.reservation_id = r.reservation_id
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE p.payment_id = $payment_id
";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0){
    die("Payment record not found.");
}

$payment = mysqli_fetch_assoc($result);
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; padding: 30px; }
        .label { font-weight: 600; color: #666; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 5px; }
        .value { font-size: 1.1rem; font-weight: 500; color: #333; margin-bottom: 20px; }
        .proof-img { max-width: 100%; max-height: 400px; border-radius: 10px; border: 1px solid #ddd; cursor: pointer; transition: 0.3s; }
        .proof-img:hover { opacity: 0.9; }
        
        /* Modal for Image Preview */
        .modal-img { width: 100%; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold" style="color: var(--dark-green);">Payment Transaction Details</h3>
        <button onclick="history.back()" class="btn btn-outline-secondary rounded-pill">&larr; Back</button>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-custom mb-4">
                <h5 class="fw-bold text-success mb-4 border-bottom pb-2">Transaction Info #<?= str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) ?></h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Amount Paid</div>
                        <div class="value fw-bold text-success fs-4">₱<?= number_format($payment['amount'], 2) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Payment Date</div>
                        <div class="value"><?= date('F d, Y h:i A', strtotime($payment['payment_date'])) ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Payment Method</div>
                        <div class="value"><?= htmlspecialchars($payment['payment_method']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Status</div>
                        <div class="value">
                            <span class="badge <?= $payment['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $payment['payment_status'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Reference Number</div>
                        <div class="value"><?= !empty($payment['reference_number']) ? htmlspecialchars($payment['reference_number']) : '<span class="text-muted small">N/A</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Description</div>
                        <div class="value"><?= !empty($payment['description']) ? htmlspecialchars($payment['description']) : 'Room Payment' ?></div>
                    </div>
                </div>
            </div>

            <div class="card card-custom">
                <h5 class="fw-bold text-secondary mb-4 border-bottom pb-2">Payer & Reservation Info</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Tenant Name</div>
                        <div class="value"><?= htmlspecialchars($payment['full_name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Contact</div>
                        <div class="value small">
                            <?= htmlspecialchars($payment['email']) ?><br>
                            <?= htmlspecialchars($payment['phone_number']) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Room</div>
                        <div class="value"><?= htmlspecialchars($payment['room_name']) ?> (<?= $payment['room_type'] ?>)</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Stay Period</div>
                        <div class="value small">
                            <?= date('M d, Y', strtotime($payment['start_date'])) ?> to <?= date('M d, Y', strtotime($payment['end_date'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom h-100">
                <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Proof of Payment</h5>
                <?php if(!empty($payment['proof_image']) && file_exists("../uploads/proofs/" . $payment['proof_image'])): ?>
                    <div class="text-center">
                        <img src="../uploads/proofs/<?= $payment['proof_image'] ?>" class="proof-img" data-bs-toggle="modal" data-bs-target="#imageModal">
                        <p class="text-muted small mt-2">Click to enlarge</p>
                        <a href="../uploads/proofs/<?= $payment['proof_image'] ?>" download class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-download me-2"></i>Download</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center py-5 border">
                        <i class="fas fa-image fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No proof of payment uploaded.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <?php if(!empty($payment['proof_image'])): ?>
                    <img src="../uploads/proofs/<?= $payment['proof_image'] ?>" class="img-fluid rounded shadow-lg">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>