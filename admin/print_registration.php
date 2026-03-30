<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])) die("Invalid Request");
$id = (int)$_GET['id'];

// Fetch Reservation & User & Room Details
$query = "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, u.is_walkin, u.emergency_contact_name, u.emergency_contact_number, rm.room_name, rm.room_number, rm.room_type, rm.floor
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.reservation_id = $id
";

$res = mysqli_query($conn, $query);
if(mysqli_num_rows($res) == 0) die("Reservation not found");
$data = mysqli_fetch_assoc($res);

// Fetch Payments
$pay_query = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id = $id AND payment_status = 'Paid' ORDER BY payment_date ASC");
$payments = [];
$total_paid = 0;
while($p = mysqli_fetch_assoc($pay_query)){
    $payments[] = $p;
    $total_paid += $p['amount'];
}

// Dates & Duration
$start_date = $data['start_date'] ?? 'N/A';
$end_date = $data['end_date'] ?? 'N/A';
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
    <title>Registration #<?= $data['reservation_id'] ?> | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .receipt-container { 
            width: 100%; max-width: 850px; background: #fff; margin: 40px auto;
            border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 20px; 
            box-shadow: 0 12px 28px rgba(46, 125, 50, 0.12); overflow: hidden; 
        }
        .receipt-header { background: linear-gradient(135deg, var(--primary-green), #209158); color: white; padding: 30px 40px; position: relative; }
        .receipt-header::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 10px; background: linear-gradient(90deg, var(--accent-yellow) 0%, #f9a825 100%); }
        .logo { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid var(--accent-yellow); }
        .company-name { font-weight: bold; font-size: 1.8rem; font-family: 'Playfair Display', serif; }
        .receipt-body { padding: 40px; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; margin-bottom: 5px; }
        .info-value { font-size: 1rem; font-weight: 500; color: #222; }
        .table-custom { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 15px; background-color: #f8f9fa; color: var(--dark-green); border-bottom: 2px solid var(--primary-green); font-size: 0.8rem; }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; }
        .total-section { background-color: rgba(46, 125, 50, 0.05); padding: 25px; border-radius: 15px; border: 1px solid rgba(46, 125, 50, 0.1); }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total-row.final { font-size: 1.3rem; font-weight: bold; color: var(--dark-green); border-top: 1px solid #ddd; padding-top: 10px; }
        .sig-box { border: 2px dashed #ddd; padding: 15px; display: inline-block; margin-top: 10px; border-radius: 10px; background: #fafafa; min-width: 200px; text-align: center; }
        .sig-img { max-height: 60px; }

        @media print {
            @page { size: A4 portrait; margin: 5mm; }
            body { background: white !important; font-size: 11pt; margin: 0; padding: 0; }
            .receipt-container { box-shadow: none; border-radius: 0; max-width: 100%; border: none !important; margin: 0; }
            .no-print { display: none !important; }
            .receipt-body { padding: 20px !important; }
        }
    </style>
</head>
<body>

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
            <h2 class="fw-bold mb-1">REGISTRATION</h2>
            <div class="opacity-75">#<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div class="mt-2 badge bg-warning text-dark shadow-sm">
                <?= ($balance <= 0) ? 'PAID IN FULL' : 'PARTIALLY PAID' ?>
            </div>
        </div>
    </div>

    <div class="receipt-body">
        <!-- Guest & Room Info -->
        <div class="row mb-5 g-4">
            <div class="col-4">
                <div class="info-label">Guest Details</div>
                <div class="info-value fw-bold"><?= $data['full_name'] ?></div>
                <div class="small text-muted"><?= $data['email'] ?></div>
                <div class="small text-muted"><?= $data['phone_number'] ?></div>
                <?php if(!empty($data['emergency_contact_name'])): ?>
                    <div class="small text-muted mt-1">ICE: <?= $data['emergency_contact_name'] ?> (<?= $data['emergency_contact_number'] ?>)</div>
                <?php endif; ?>
            </div>
            <div class="col-4">
                <div class="info-label">Room Details</div>
                <div class="info-value"><?= !empty($data['room_number']) ? 'Room ' . htmlspecialchars($data['room_number']) : htmlspecialchars($data['room_name']) ?></div>
                <div class="small text-muted"><?= $data['room_type'] ?> (Floor <?= $data['floor'] ?>)</div>
                <div class="small text-muted">Bed: <?= $data['bed_preference'] ?></div>
            </div>
            <div class="col-4 text-end">
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
                        <td class="text-end">₱<?= number_format($pay['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted">No payments recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="row justify-content-end mb-5">
            <div class="col-md-5">
                <div class="total-section">
                    <div class="total-row"><span>Total Contract:</span><span>₱<?= number_format($data['total_price'], 2) ?></span></div>
                    <div class="total-row text-success"><span>Total Paid:</span><span>- ₱<?= number_format($total_paid, 2) ?></span></div>
                    <div class="total-row final"><span>Balance Due:</span><span class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format(max(0, $balance), 2) ?></span></div>
                </div>
            </div>
        </div>

        <!-- Rules (registration specific) -->
        <div class="mb-4 p-3 bg-light rounded border small text-muted">
            <div class="fw-bold text-dark mb-2">Terms & Conditions</div>
            <ul class="mb-0" style="padding-left: 20px;">
                <li>Quiet hours: 10PM - 7AM. | Clean up after yourself in shared spaces.</li>
                <li>Visitors allowed until 9PM. No overnight guests without approval.</li>
                <li>Smoking and alcohol are strictly prohibited inside the premises.</li>
            </ul>
        </div>

        <!-- Signatures -->
        <div class="row mt-5 pt-4 border-top">
            <div class="col-6">
                <div class="info-label mb-2">Guest Signature</div>
                <?php if(!empty($data['signature_image'])): ?>
                    <div class="sig-box"><img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img"></div>
                <?php else: ?>
                    <div style="border-bottom: 1px solid #333; width: 200px; margin-top: 40px;"></div>
                <?php endif; ?>
                <div class="small text-muted mt-1"><?= $data['full_name'] ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="info-label mb-4">Authorized By</div>
                <div class="mt-4"><span class="fw-bold border-bottom border-dark pb-1 px-4">Woke Coliving Admin</span></div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mb-5 no-print">
    <button onclick="window.print()" class="btn btn-success rounded-pill px-5 py-2 fw-bold shadow"><i class="fas fa-print me-2"></i>Print Registration</button>
    <button onclick="window.close()" class="btn btn-secondary rounded-pill px-4 py-2 ms-2">Close</button>
</div>
</body>
</html>