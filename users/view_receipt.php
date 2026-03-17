<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Reservation & User & Room Details (Verify ownership)
$query = "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, u.is_walkin, u.emergency_contact_name, u.emergency_contact_number, rm.room_name, rm.room_type, rm.floor
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

// Categorize payments for the new template
$cat_sd = 0;
$cat_rent = 0;
$cat_cusa = 0;
$cat_util = 0;
$cat_other = 0;

while($p = mysqli_fetch_assoc($pay_query)){
    $payments[] = $p;
    $amt = $p['amount'];
    $total_paid += $amt;
    
    $desc = strtolower($p['description']);
    if(strpos($desc, 'security') !== false || strpos($desc, 'deposit') !== false) {
        $cat_sd += $amt;
    } elseif(strpos($desc, 'utility') !== false || strpos($desc, 'bill') !== false) {
        $cat_util += $amt;
    } elseif(strpos($desc, 'cusa') !== false) {
        $cat_cusa += $amt;
    } elseif(strpos($desc, 'rent') !== false || strpos($desc, 'room') !== false) {
        $cat_rent += $amt;
    } else {
        // Default everything else to rental/other if ambiguous
        if($amt > 2000) $cat_rent += $amt; // Assume large payments are rent
        else $cat_other += $amt;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Woke Coliving Space Receipt Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; padding: 20px; color: #333; background: #eef2f5; }
        .receipt-container { width: 600px; border: 1px solid #ccc; padding: 20px; position: relative; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        /* Header Section */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .logo-area h1 { margin: 0; font-size: 42px; font-weight: 900; letter-spacing: -2px; line-height: 1; }
        .logo-area p { margin: 0; font-size: 14px; text-transform: lowercase; }
        
        .company-info { font-size: 10px; text-align: right; line-height: 1.4; }
        .receipt-no { color: #d9534f; font-weight: bold; font-size: 18px; margin-top: 10px; }

        /* Title Area */
        .title-bar { text-align: center; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px 0; margin-bottom: 15px; }
        .title-bar h2 { margin: 0; font-size: 16px; letter-spacing: 2px; }

        /* Main Form Fields */
        .field-row { margin-bottom: 12px; display: flex; align-items: baseline; }
        .label { font-size: 13px; font-weight: bold; margin-right: 5px; white-space: nowrap; }
        .line { border-bottom: 1px solid #000; flex-grow: 1; height: 1.2em; text-align: center; font-weight: bold; }
        
        /* Table Section */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; text-transform: uppercase; }
        .amt-col { width: 100px; text-align: right; }

        /* Footer / Signatures */
        .footer { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .sig-box { width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; font-size: 11px; }
        .sig-img { max-height: 50px; display: block; margin: 0 auto; }

        @media print {
            body { background: #fff; }
            .receipt-container { border: 1px solid #ccc; box-shadow: none; margin: 0 auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <div class="logo-area">
            <h1>woke</h1>
            <p>coliving space</p>
        </div>
        <div class="company-info">
            Company name: <strong>WOKE COLIVING INC.</strong><br>
            Branch: Kanlaon, Mandaluyong City<br>
            Address: 205 Kanlaon St. Cor. Mariveles Brgy. Highway Hills, Mandaluyong City<br>
            Contact number: 0917-307-2512
            <div class="receipt-no">Nº <?= str_pad($data['reservation_id'], 4, '0', STR_PAD_LEFT) ?></div>
        </div>
    </div>

    <div class="title-bar">
        <h2>ACKNOWLEDGEMENT RECEIPT</h2>
    </div>

    <div style="text-align: right; margin-bottom: 15px;">
        <span class="label">Date:</span> <span style="display:inline-block; width: 120px; border-bottom: 1px solid #000; text-align:center;"><?= date('F d, Y') ?></span>
    </div>

    <div class="field-row">
        <span class="label">Received from:</span> <div class="line"><?= htmlspecialchars($data['full_name']) ?></div>
    </div>

    <div class="field-row">
        <span class="label">the sum of Pesos:</span> <div class="line">P <?= number_format($total_paid, 2) ?></div>
    </div>

    <div class="field-row">
        <span class="label">as payment for:</span> <div class="line">Reservation / Stay in <?= $data['room_name'] ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>PARTICULARS</th>
                <th class="amt-col">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Reservation Fee / Security Deposit:</td><td class="amt-col"><?= $cat_sd > 0 ? number_format($cat_sd, 2) : '' ?></td></tr>
            <tr><td>Rental Payment:</td><td class="amt-col"><?= $cat_rent > 0 ? number_format($cat_rent, 2) : '' ?></td></tr>
            <tr><td>CUSA:</td><td class="amt-col"><?= $cat_cusa > 0 ? number_format($cat_cusa, 2) : '' ?></td></tr>
            <tr><td>Utilities:</td><td class="amt-col"><?= $cat_util > 0 ? number_format($cat_util, 2) : '' ?></td></tr>
            <tr><td>Other: _________________</td><td class="amt-col"><?= $cat_other > 0 ? number_format($cat_other, 2) : '' ?></td></tr>
            <tr style="font-weight: bold;">
                <td style="text-align: right;">TOTAL PAYMENT:</td>
                <td class="amt-col"><?= number_format($total_paid, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="sig-box">
            <?php if(!empty($data['signature_image'])): ?>
                <img src="../assets/signatures/<?= $data['signature_image'] ?>?v=<?= time() ?>" class="sig-img">
            <?php else: ?>
                <div style="height: 50px;"></div>
            <?php endif; ?>
            <div class="sig-line">Client signature over printed name</div>
        </div>
        <div class="sig-box">
            <div style="height: 50px;"></div>
            <div class="sig-line">Authorized Representative</div>
        </div>
    </div>
    
    <!-- Floating Actions -->
    <div class="position-fixed bottom-0 end-0 m-4 no-print d-flex gap-2" style="position: fixed; bottom: 20px; right: 20px;">
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-print me-2"></i>Print</button>
        <a href="my_reservations.php" class="btn btn-secondary rounded-pill px-4 fw-bold">Back</a>
    </div>
</div>

</body>
</html>