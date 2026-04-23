<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    die("Unauthorized");
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    die("Invalid Request");
}

$tenant_id = (int)$_POST['tenant_id'];
$res_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$room_info = htmlspecialchars($_POST['room_info']);
$clearance_date = htmlspecialchars($_POST['clearance_date']);
$deposit_amount = (float)$_POST['deposit_amount'];
$deduction_amount = (float)$_POST['deduction_amount'];
$net_refund = (float)$_POST['net_refund'];
$deduction_remarks = htmlspecialchars($_POST['deduction_remarks']);

$q = mysqli_query($conn, "SELECT u.first_name, u.last_name, u.email, u.phone_number, u.address, r.start_date, r.end_date FROM users u LEFT JOIN reservations r ON r.reservation_id = $res_id WHERE u.user_id=$tenant_id");
$tenant = mysqli_fetch_assoc($q);
$tenant_name = trim(($tenant['first_name'] ?? '') . ' ' . ($tenant['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenant Clearance - <?= $tenant_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; background: #f0f2f5; padding: 20px; }
        .print-container { background: white; max-width: 800px; margin: 0 auto; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-top: 10px solid #2e7d32; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 20px; }
        .logo { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 10px; }
        .title { font-weight: bold; font-size: 26px; color: #2e7d32; text-transform: uppercase; letter-spacing: 2px; }
        .subtitle { font-size: 14px; color: #555; }
        .content-section { margin-bottom: 30px; line-height: 1.6; font-size: 15px; }
        .amount-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .amount-table th, .amount-table td { padding: 12px; border: 1px solid #ddd; }
        .amount-table th { background: #f8f9fa; width: 70%; }
        .signatures { margin-top: 60px; display: flex; justify-content: space-between; }
        .sig-box { text-align: center; width: 45%; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-weight: bold; }
        @media print {
            body { background: white; padding: 0; }
            .print-container { box-shadow: none; padding: 0; max-width: 100%; border-top: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-success px-4 rounded-pill shadow fw-bold"><i class="fas fa-print me-2"></i> Print Clearance</button>
        <button onclick="window.close()" class="btn btn-secondary px-4 rounded-pill shadow ms-2 fw-bold">Close Window</button>
    </div>
    
    <div class="print-container">
        <div class="header">
            <img src="../Images/WokeLogo.jpg" class="logo" alt="Woke Coliving">
            <div class="title">Tenant Clearance Form</div>
            <div class="subtitle">Woke Coliving INC. | 205 Kanlaon St. Mandaluyong, Philippines</div>
        </div>
        
        <div class="content-section">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Tenant Information</h5>
            <div class="row mb-4">
                <div class="col-6">
                    <strong>Name:</strong> <?= strtoupper(htmlspecialchars($tenant_name ?: 'Unknown Tenant')) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($tenant['email'] ?? 'N/A') ?><br>
                    <strong>Phone:</strong> <?= htmlspecialchars($tenant['phone_number'] ?? 'N/A') ?>
                </div>
                <div class="col-6 text-end">
                    <strong>Room:</strong> <?= $room_info ?><br>
                    <strong>Stay Period:</strong> <?= !empty($tenant['start_date']) ? date('M d, Y', strtotime($tenant['start_date'])) . ' to ' . date('M d, Y', strtotime($tenant['end_date'])) : 'N/A' ?><br>
                    <strong>Clearance Date:</strong> <?= date('F d, Y', strtotime($clearance_date)) ?>
                </div>
                <div class="col-12 mt-2"><strong>Address:</strong> <?= htmlspecialchars($tenant['address'] ?? 'N/A') ?></div>
            </div>
            <p>This is to certify that <strong><?= strtoupper(htmlspecialchars($tenant_name ?: 'Unknown Tenant')) ?></strong> has successfully completed their stay and is hereby cleared of all property and room accountabilities as of the date stated above.</p>
            <p>The security deposit will be refunded minus any deductions for property damages, lost items, or unpaid utility bills as detailed below.</p>
        </div>

        <div class="content-section">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Financial Settlement</h5>
            <table class="amount-table">
                <tr>
                    <td><strong>Security Deposit Amount</strong></td>
                    <td class="text-end fw-bold">₱ <?= number_format($deposit_amount, 2) ?></td>
                </tr>
                <tr>
                    <td>
                        <strong class="text-danger">Less: Damages & Unpaid Bills</strong><br>
                        <span class="text-muted small" style="white-space: pre-wrap;"><?= !empty($deduction_remarks) ? $deduction_remarks : 'None' ?></span>
                    </td>
                    <td class="text-end text-danger fw-bold">- ₱ <?= number_format($deduction_amount, 2) ?></td>
                </tr>
                <tr class="bg-light">
                    <td><h5 class="fw-bold mb-0">Net Refundable Amount</h5></td>
                    <td class="text-end"><h4 class="fw-bold text-success mb-0">₱ <?= number_format($net_refund, 2) ?></h4></td>
                </tr>
            </table>
        </div>

        <div class="content-section mt-5">
            <p class="small text-muted fst-italic">By signing below, both parties agree to the deductions listed and the final refundable amount. The tenant acknowledges receipt of the net refundable amount (if any) and clears Woke Coliving INC of any further liabilities regarding the security deposit.</p>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"><?= strtoupper($tenant_name) ?></div>
                <small>Tenant Signature over Printed Name</small>
            </div>
            <div class="sig-box">
                <div class="sig-line">WOKE COLIVING ADMIN</div>
                <small>Authorized Representative</small>
            </div>
        </div>
    </div>
</body>
</html>