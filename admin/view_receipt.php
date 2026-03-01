<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])){
    die("Invalid Request");
}

$id = (int)$_GET['id'];

// Handle Signature Reset
if(isset($_POST['reset_signature'])){
    $reset_stmt = mysqli_prepare($conn, "UPDATE reservations SET signature_image = NULL WHERE reservation_id = ?");
    mysqli_stmt_bind_param($reset_stmt, "i", $id);
    mysqli_stmt_execute($reset_stmt);
    header("Location: view_receipt.php?id=$id");
    exit;
}

// Fetch Reservation & User & Room Details
$query = "
    SELECT r.*, u.full_name, u.email, u.phone_number, u.is_walkin, rm.room_name, rm.room_type, rm.floor
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.reservation_id = $id
";

$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0){
    die("Reservation not found.");
}
$data = mysqli_fetch_assoc($result);

// Fetch Payments
$pay_query = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id = $id AND payment_status = 'Paid' ORDER BY payment_date ASC");
$payments = [];
$total_paid = 0;
while($p = mysqli_fetch_assoc($pay_query)){
    $payments[] = $p;
    $total_paid += $p['amount'];
}

// Robust Date & Duration Handling
$start_date = $data['start_date'] ?? $data['cin'] ?? 'N/A';
$end_date = $data['end_date'] ?? $data['cout'] ?? 'N/A';
$months = $data['months'] ?? 0;

// Calculate months if missing but dates exist
if(($months == 0 || empty($months)) && $start_date != 'N/A' && $end_date != 'N/A'){
    $d1 = new DateTime($start_date);
    $d2 = new DateTime($end_date);
    $diff = $d1->diff($d2);
    $months = round($diff->days / 30, 1);
}

// Calculate precise duration string for display
$d_start = new DateTime($start_date);
$d_end = new DateTime($end_date);
$d_diff = $d_start->diff($d_end);
$duration_str = ($d_diff->y > 0 ? $d_diff->y . " Yr " : "") . ($d_diff->m > 0 ? $d_diff->m . " Mo " : "") . ($d_diff->d > 0 ? $d_diff->d . " Days" : "");
$duration_str = empty($duration_str) ? "0 Days" : trim($duration_str);

$balance = $data['total_price'] - $total_paid;
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?> | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        body { background: #eef2f5; font-family: 'Poppins', sans-serif; color: #333; }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        .receipt-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 850px;
            background: #fff;
            padding: 0;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        .receipt-header {
            background-color: var(--dark-green);
            color: white;
            padding: 40px;
            position: relative;
        }
        
        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background-color: var(--accent-yellow);
        }

        .logo { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid var(--accent-yellow); }
        .company-name { font-weight: bold; font-size: 1.8rem; font-family: 'Playfair Display', serif; letter-spacing: 1px; }
        
        .receipt-body { padding: 40px; }
        
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; margin-bottom: 5px; }
        .info-value { font-size: 1rem; font-weight: 500; color: #222; }
        
        .table-custom { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 15px; background-color: #f8f9fa; color: var(--dark-green); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid var(--primary-green); }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .table-custom tr:last-child td { border-bottom: none; }
        
        .total-section { background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; }
        .total-row.final { font-size: 1.3rem; font-weight: bold; color: var(--dark-green); border-top: 1px solid #ddd; padding-top: 10px; margin-bottom: 0; }
        
        .sig-box { border: 2px dashed #ddd; padding: 15px; display: inline-block; margin-top: 10px; border-radius: 10px; background: #fafafa; }
        .sig-img { max-height: 60px; }
        
        @media print {
            body { background: #fff; }
            .receipt-wrapper { padding: 0; display: block; }
            .receipt-container { box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
            .receipt-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="receipt-wrapper">
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo me-3">
                <div>
                    <div class="company-name">Woke Coliving INC</div>
                    <div class="small opacity-75">123 Coliving Street, City Center</div>
                    <div class="small opacity-75">contact@wokecoliving.com | +63 912 345 6789</div>
                </div>
            </div>
            <div class="text-end">
                <h2 class="fw-bold mb-1">RECEIPT</h2>
                <div class="opacity-75">#<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?></div>
                <?php if(!empty($data['is_walkin'])): ?>
                    <div class="badge bg-info text-dark mt-1">WALK-IN GUEST</div>
                <?php endif; ?>
                <div class="mt-2 badge bg-warning text-dark shadow-sm">
                    <?= ($balance <= 0) ? 'PAID IN FULL' : 'PARTIALLY PAID' ?>
                </div>
            </div>
        </div>

        <div class="receipt-body">
        <!-- Guest & Room Info -->
            <div class="row mb-5 g-4">
                <div class="col-md-4">
                    <div class="info-label">Billed To</div>
                    <div class="info-value fw-bold"><?= $data['full_name'] ?></div>
                    <div class="small text-muted"><?= $data['email'] ?></div>
                    <div class="small text-muted"><?= $data['phone_number'] ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Room Details</div>
                    <div class="info-value"><?= $data['room_name'] ?></div>
                    <div class="small text-muted"><?= $data['room_type'] ?> (Floor <?= $data['floor'] ?>)</div>
                    <div class="small text-muted">Bed: <?= $data['bed_preference'] ?></div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="info-label">Stay Duration</div>
                    <div class="info-value"><?= $duration_str ?></div>
                    <div class="small text-muted">In: <?= date('M d, Y', strtotime($start_date)) ?></div>
                    <div class="small text-muted">Out: <?= date('M d, Y', strtotime($end_date)) ?></div>
                </div>
            </div>

        <!-- Payment Table -->
            <h5 class="fw-bold text-secondary mb-3">Payment History</h5>
            <table class="table-custom">
                <thead>
                <tr>
                        <th>Date</th>
                    <th>Description</th>
                        <th>Method</th>
                        <th>Ref No.</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                    <?php if(count($payments) > 0): ?>
                        <?php foreach($payments as $pay): ?>
                <tr>
                            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                            <td><?= !empty($pay['description']) ? $pay['description'] : 'Payment' ?></td>
                            <td><?= $pay['payment_method'] ?></td>
                            <td><?= !empty($pay['reference_number']) ? $pay['reference_number'] : '-' ?></td>
                            <td class="text-end">₱<?= number_format($pay['amount'], 2) ?></td>
                </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No payments recorded yet.</td></tr>
                    <?php endif; ?>
            </tbody>
        </table>

            <!-- Totals -->
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="total-section">
                        <div class="total-row">
                            <span>Total Contract Price:</span>
                            <span>₱<?= number_format($data['total_price'], 2) ?></span>
                        </div>
                        <div class="total-row text-success">
                            <span>Total Paid:</span>
                            <span>- ₱<?= number_format($total_paid, 2) ?></span>
                        </div>
                        <div class="total-row final">
                            <span>Balance Due:</span>
                            <span class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format(max(0, $balance), 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Signatures -->
            <div class="row mt-5 pt-4 border-top">
            <div class="col-6">
                    <div class="info-label mb-2">Guest Signature</div>
                <?php if(!empty($data['signature_image'])): ?>
                    <div class="sig-box">
                        <img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img">
                    </div>
                    <div class="small text-muted mt-1">Signed Electronically</div>
                    <form method="POST" class="mt-2 no-print" id="resetSigForm">
                        <input type="hidden" name="reset_signature" value="1">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmResetSig()">
                                <i class="fas fa-undo me-1"></i> Reset
                        </button>
                    </form>
                <?php else: ?>
                        <div class="text-muted fst-italic mt-3">Not signed yet</div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                    <div class="info-label mb-4">Authorized By</div>
                    <div class="mt-4">
                        <span class="fw-bold border-bottom border-dark pb-1 px-4">Woke Coliving Admin</span>
                    </div>
                    <div class="small text-muted mt-2">System Generated Receipt</div>
            </div>
        </div>

        <!-- Footer -->
            <div class="text-center mt-5 pt-3 text-muted small">
                <p class="mb-1">Thank you for choosing Woke Coliving INC.</p>
                <p>&copy; <?= date('Y') ?> All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Floating Actions -->
    <div class="position-fixed bottom-0 end-0 m-4 no-print d-flex gap-2">
        <button onclick="window.print()" class="btn btn-success rounded-pill shadow-lg px-4 fw-bold"><i class="fas fa-print me-2"></i>Print</button>
        <button onclick="window.close()" class="btn btn-secondary rounded-pill shadow-lg px-4 fw-bold">Close</button>
    </div>
</div>

<script>
function confirmResetSig() {
    Swal.fire({
        title: 'Reset Signature?',
        text: "The user will have to sign again.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('resetSigForm').submit();
    });
}
</script>
</body>
</html>