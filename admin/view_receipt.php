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

$query = "
    SELECT r.*, u.full_name, u.email, u.phone_number, rm.room_name, rm.room_type, p.payment_method, p.reference_number, p.payment_date, p.amount
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    LEFT JOIN payments p ON r.reservation_id = p.reservation_id
    WHERE r.reservation_id = $id
";

$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0){
    die("Reservation not found.");
}
$data = mysqli_fetch_assoc($result);

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
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $data['reservation_id'] ?> | Woke Coliving</title>
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
        body { background: #f4f6f8; font-family: 'Poppins', sans-serif; }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        .receipt-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header { border-bottom: 3px solid var(--accent-yellow); padding-bottom: 20px; margin-bottom: 30px; }
        .logo { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; }
        .company-name { color: var(--dark-green); font-weight: bold; font-size: 1.5rem; font-family: 'Playfair Display', serif; }
        .label { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; }
        .value { font-size: 1.1rem; font-weight: 500; color: #000; }
        .sig-box { border: 1px dashed #ccc; padding: 10px; display: inline-block; margin-top: 10px; }
        .sig-img { max-height: 80px; background-color: #fff; }
        @media print {
            body { background: #fff; }
            .receipt-container { box-shadow: none; border: none; margin: 0; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-container">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo me-3">
                <div>
                    <div class="company-name">Woke Coliving INC</div>
                    <small class="text-muted">123 Coliving Street, City Center</small><br>
                    <small class="text-muted">contact@wokecoliving.com | +63 912 345 6789</small>
                </div>
            </div>
            <div class="text-end">
                <h3 class="fw-bold text-uppercase mb-0">Receipt</h3>
                <div class="text-muted">#<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div class="small text-muted">Date: <?= date('M d, Y') ?></div>
            </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="mb-3">
                    <div class="label">Guest Name</div>
                    <div class="value"><?= $data['full_name'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Contact Info</div>
                    <div><?= $data['email'] ?></div>
                    <div><?= $data['phone_number'] ?></div>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="mb-3">
                    <div class="label">Room Details</div>
                    <div class="value"><?= $data['room_name'] ?></div>
                    <div><?= $data['room_type'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Stay Duration</div>
                    <div>In: <strong><?= $start_date ?></strong></div>
                    <div>Out: <strong><?= $end_date ?></strong></div>
                    <div>(<?= $months ?> Months)</div>
                </div>
            </div>
        </div>

        <!-- Payment Table -->
        <table class="table table-bordered mb-4">
            <thead style="background-color: var(--primary-green); color: white;">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Room Reservation Fee (<?= $months ?> Months)<br>
                        <small class="text-muted">Payment Method: <?= $data['payment_method'] ?? 'N/A' ?></small><br>
                        <small class="text-muted">Ref No: <?= $data['reference_number'] ?? 'N/A' ?></small><br>
                        <small class="text-dark fw-bold">Utilities: <?= ($months >= 6) ? 'Tenant pays Water & Electric' : 'Included in Rent' ?></small>
                    </td>
                    <td class="text-end fw-bold">₱<?= number_format($data['total_price'], 2) ?></td>
                </tr>
                <tr>
                    <td class="text-end fw-bold">TOTAL PAID</td>
                    <td class="text-end fw-bold fs-5" style="color: var(--primary-green);">₱<?= number_format($data['total_price'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <div class="label mb-2">Guest Signature</div>
                <?php if(!empty($data['signature_image'])): ?>
                    <div class="sig-box">
                        <img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img">
                    </div>
                    <div class="small text-muted mt-1">Signed Electronically</div>
                    <form method="POST" class="mt-2 no-print" id="resetSigForm">
                        <input type="hidden" name="reset_signature" value="1">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmResetSig()">
                            <i class="fas fa-undo me-1"></i> Reset Signature
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-muted fst-italic">Not signed yet</div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <div class="label mb-4">Authorized By</div>
                <div class="border-bottom d-inline-block" style="width: 200px; margin-bottom: 5px;"></div>
                <div class="fw-bold">Woke Coliving Admin</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-3 border-top text-muted small">
            <p>This is a computer-generated receipt. No signature required for the issuer.</p>
            <p>&copy; <?= date('Y') ?> Woke Coliving INC. All rights reserved.</p>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-success btn-lg"><i class="fas fa-print me-2"></i>Print Receipt</button>
            <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">Close</button>
        </div>
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