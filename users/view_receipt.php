<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Reservation & User & Room Details, including emergency contacts
$query = "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, rm.room_name 
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.reservation_id = $id AND r.user_id = $user_id
";

$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0){ die("Reservation not found."); }
$data = mysqli_fetch_assoc($result);

// Fetch Payments and categorize
$pay_query = mysqli_query($conn, "SELECT * FROM payments WHERE reservation_id = $id AND payment_status = 'Paid' ORDER BY payment_date ASC");
$total_paid = 0;
$cat_sd = 0; $cat_rent = 0; $cat_cusa = 0; $cat_util = 0; $cat_other = 0;
$cat_parking = 0; // New category for parking
$last_method = 'Cash'; // Default

while($p = mysqli_fetch_assoc($pay_query)){
    $amt = $p['amount'];
    $total_paid += $amt;
    $last_method = $p['payment_method']; // Capture last method used

    $desc = strtolower($p['description']);
    if(strpos($desc, 'security') !== false || strpos($desc, 'deposit') !== false) $cat_sd += $amt;
    elseif(strpos($desc, 'parking') !== false) $cat_parking += $amt; // Categorize parking
    elseif(strpos($desc, 'utility') !== false) $cat_util += $amt;
    elseif(strpos($desc, 'cusa') !== false) $cat_cusa += $amt;
    elseif(strpos($desc, 'rent') !== false) $cat_rent += $amt;
    else $cat_other += $amt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Acknowledgement Receipt - Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; background: #f4f7f6; padding: 40px 0; }
        .receipt-container { 
            width: 750px; background: #fff; margin: auto; padding: 30px; 
            border: 1px solid #ddd; position: relative; box-shadow: 0 0 10px rgba(0,0,0,0.05);
            font-size: 14px;
        }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .logo-area h1 { margin: 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; line-height: 1; color: #333; }
        .logo-area p { margin: 0; font-size: 0.9rem; font-weight: bold; color: #555; }
        .company-info { font-size: 11px; text-align: right; line-height: 1.3; color: #444; }
        .receipt-no { color: #d9534f; font-weight: bold; font-size: 20px; margin-top: 8px; }

        .title-bar { text-align: center; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 0; margin-bottom: 20px; }
        .title-bar h2 { margin: 0; font-size: 18px; letter-spacing: 4px; font-weight: bold; }

        .field-row { margin-bottom: 15px; display: flex; align-items: baseline; }
        .label { font-size: 14px; font-weight: bold; margin-right: 10px; }
        .line { border-bottom: 1px solid #000; flex-grow: 1; padding-left: 10px; font-style: italic; font-weight: 500; }
        .line.large { font-size: 1.2rem; }
        .main-content { display: flex; gap: 20px; margin-top: 20px; }
        
        /* Left Column: Mode of Payment */
        .payment-modes { width: 180px; border: 1px solid #000; padding: 10px; font-size: 13px; }
        .mode-item { display: flex; align-items: center; margin-bottom: 5px; }
        .checkbox { width: 15px; height: 15px; border: 1px solid #000; margin-right: 8px; text-align: center; line-height: 13px; font-weight: bold; }

        /* Right Column: Table */
        .particulars-table { flex-grow: 1; border-collapse: collapse; }
        .particulars-table th, .particulars-table td { border: 1px solid #000; padding: 6px 10px; font-size: 13px; }
        .particulars-table th { background: #eee; text-align: center; }
        .amt-val { text-align: right; width: 120px; font-family: monospace; font-size: 14px; }

        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-box { width: 48%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; font-size: 12px; font-weight: bold; }
        .sig-img { max-height: 60px; margin-bottom: -10px; }

        @media print {
            @page { margin: 0; }
            html, body { margin: 0 !important; padding: 15mm !important; background: #fff !important; }
            .receipt-container { border: none !important; box-shadow: none !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <div class="company-info" style="text-align: left;">
            <h1>WOKE COLIVING INC.</h1>
            <p>205 Kanlaon St., Mariveles Bgy., Highway Hills, Mandaluyong City</p>
            <p>Contact: 0917-307-2552</p>
        </div>
        <div class="receipt-details">
            <div class="receipt-no" style="color: #333;">Receipt</div>
            <div style="font-size: 12px; margin-top: 5px;">Date: <?= date('F d, Y') ?></div>
        </div>
    </div>

    <div class="title-bar"><h2>ACKNOWLEDGEMENT RECEIPT</h2></div>

    <div style="text-align: right; margin-bottom: 20px;">
        <span class="label">Date:</span> 
        <span style="border-bottom: 1px solid #000; min-width: 150px; display: inline-block; text-align: center;"></span>
    </div>

    <div class="field-row">
        <span class="label">Received from:</span> <div class="line"><?= htmlspecialchars($data['full_name']) ?></div>
    </div>

    <div class="field-row">
        <span class="label">as payment for:</span> 
        <div class="line"><?= htmlspecialchars($data['room_name']) ?> - Monthly Rent / Fees</div>
    </div>

    <div class="main-content">
        <div class="payment-modes">
            <strong>Mode of Payment:</strong><br>
            <div class="mode-item"><div class="checkbox"><?= ($last_method == 'GCash') ? '✓' : '' ?></div> GCash</div>
            <div class="mode-item"><div class="checkbox"><?= ($last_method == 'Bank Transfer' || $last_method == 'Bank') ? '✓' : '' ?></div> Bank</div>
            <div class="mode-item"><div class="checkbox"><?= ($last_method == 'Cash') ? '✓' : '' ?></div> Cash</div>
        </div>

        <table class="particulars-table">
            <thead>
                <tr>
                    <th colspan="2" style="text-align: left;">PARTICULARS</th>
                    <th>PARTICULARS</th>
                    <th>AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Reservation Fee / Security Deposit</td><td class="amt-val"><?= $cat_sd > 0 ? number_format($cat_sd, 2) : '' ?></td></tr>
                <tr><td>Rental Payment</td><td class="amt-val"><?= $cat_rent > 0 ? number_format($cat_rent, 2) : '' ?></td></tr>
                <tr><td>Other Pk Specific</td><td class="amt-val"><?= $cat_parking > 0 ? number_format($cat_parking, 2) : '' ?></td></tr>
                <!-- Removed CUSA and Utilities as per new format -->
                <tr><td>Other</td><td class="amt-val"><?= ($cat_cusa + $cat_util + $cat_other) > 0 ? number_format(($cat_cusa + $cat_util + $cat_other), 2) : '' ?></td></tr>
                <tr style="font-weight: bold; background: #f9f9f9;">
                    <td style="text-align: right;">TOTAL PAYMENT</td>
                    <td class="amt-val"><?= number_format($total_paid, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="sig-box">
            <?php if(!empty($data['signature_image'])): ?>
                <img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img">
            <?php else: ?>
                <div style="height: 40px;"></div>
            <?php endif; ?>
            <div class="sig-line">Received by (Signature over printed name)</div>
        </div>
        <div class="sig-box">
            <div style="height: 40px;"></div>
            <div class="sig-line">Authorized Representative</div>
        </div>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: right;">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <a href="my_reservations.php" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

</body>
</html>