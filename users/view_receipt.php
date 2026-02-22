<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Reservation & User & Room Details (Verify ownership)
$query = "
    SELECT r.*, u.full_name, u.email, u.phone_number, rm.room_name, rm.room_type, rm.floor
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.reservation_id = $id AND r.user_id = $user_id
";

$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0){ die("Reservation not found or access denied."); }
$data = mysqli_fetch_assoc($result);

// Fetch Payments
$pay_query = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id = $id AND payment_status = 'Paid' ORDER BY payment_date ASC");
$payments = [];
$total_paid = 0;
while($p = mysqli_fetch_assoc($pay_query)){
    $payments[] = $p;
    $total_paid += $p['amount'];
}

$start_date = $data['start_date'] ?? $data['cin'] ?? 'N/A';
$end_date = $data['end_date'] ?? $data['cout'] ?? 'N/A';
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
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; }
        body { background: #eef2f5; font-family: 'Poppins', sans-serif; color: #333; }
        .receipt-container { width: 100%; max-width: 850px; background: #fff; margin: 40px auto; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }
        .receipt-header { background-color: var(--dark-green); color: white; padding: 40px; border-bottom: 10px solid var(--accent-yellow); }
        .receipt-body { padding: 40px; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; margin-bottom: 5px; }
        .table-custom { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 15px; background-color: #f8f9fa; color: var(--dark-green); border-bottom: 2px solid var(--primary-green); }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; }
        .total-section { background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total-row.final { font-size: 1.3rem; font-weight: bold; color: var(--dark-green); border-top: 1px solid #ddd; padding-top: 10px; }
        .sig-box { border: 2px dashed #ddd; padding: 15px; display: inline-block; margin-top: 10px; border-radius: 10px; background: #fafafa; }
        .sig-img { max-height: 60px; }
        @media print { .no-print { display: none !important; } .receipt-container { box-shadow: none; border: 1px solid #ddd; margin: 0; width: 100%; max-width: 100%; } }
    </style>
</head>
<body>
<div class="container">
    <div class="receipt-container">
        <div class="receipt-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width:70px; height:70px; border-radius:50%; border:3px solid var(--accent-yellow);" class="me-3">
                <div>
                    <div class="h4 fw-bold mb-0">Woke Coliving INC</div>
                    <div class="small opacity-75">123 Coliving Street, City Center</div>
                </div>
            </div>
            <div class="text-end">
                <h2 class="fw-bold mb-1">RECEIPT</h2>
                <div class="opacity-75">#<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>
        <div class="receipt-body">
            <div class="row mb-5 g-4">
                <div class="col-md-4">
                    <div class="info-label">Billed To</div>
                    <div class="fw-bold"><?= $data['full_name'] ?></div>
                    <div class="small text-muted"><?= $data['email'] ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Room Details</div>
                    <div class="fw-bold"><?= $data['room_name'] ?></div>
                    <div class="small text-muted"><?= $data['room_type'] ?> (Floor <?= $data['floor'] ?>)</div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="info-label">Stay Duration</div>
                    <div class="fw-bold"><?= $duration_str ?></div>
                    <div class="small text-muted">In: <?= date('M d, Y', strtotime($start_date)) ?></div>
                    <div class="small text-muted">Out: <?= date('M d, Y', strtotime($end_date)) ?></div>
                </div>
            </div>
            <h5 class="fw-bold text-secondary mb-3">Payment History</h5>
            <table class="table-custom">
                <thead><tr><th>Date</th><th>Description</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <?php foreach($payments as $pay): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                        <td><?= !empty($pay['description']) ? $pay['description'] : 'Payment' ?></td>
                        <td><?= $pay['payment_method'] ?></td>
                        <td class="text-end">₱<?= number_format($pay['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="total-section">
                        <div class="total-row"><span>Total Price:</span><span>₱<?= number_format($data['total_price'], 2) ?></span></div>
                        <div class="total-row text-success"><span>Total Paid:</span><span>- ₱<?= number_format($total_paid, 2) ?></span></div>
                        <div class="total-row final"><span>Balance:</span><span>₱<?= number_format(max(0, $balance), 2) ?></span></div>
                    </div>
                </div>
            </div>
            <div class="row mt-5 pt-4 border-top">
                <div class="col-6">
                    <div class="info-label mb-2">Guest Signature</div>
                    <?php if(!empty($data['signature_image'])): ?>
                        <div class="sig-box"><img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img"></div>
                    <?php else: ?><div class="text-muted fst-italic">Not signed yet</div><?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <div class="info-label mb-4">Authorized By</div>
                    <div class="mt-4"><span class="fw-bold border-bottom border-dark pb-1 px-4">Woke Coliving Admin</span></div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light p-3 text-center no-print">
            <button onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-print me-2"></i>Print Receipt</button>
            <a href="my_reservations.php" class="btn btn-secondary rounded-pill px-4 fw-bold ms-2">Back</a>
        </div>
    </div>
</div>
</body>
</html>