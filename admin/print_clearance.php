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

// Fetch Template
$q_template = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='clearance_template'");
$template_str = ($row_template = mysqli_fetch_assoc($q_template)) ? $row_template['setting_value'] : "";
if (empty($template_str)) {
    $template_str = "<div style='text-align: center; margin-bottom: 20px;'><h2>Tenant Clearance Form</h2><p>Woke Coliving INC.</p></div><p>This is to certify that <strong>{TENANT_NAME}</strong> has successfully completed their stay and is hereby cleared of all property and room accountabilities as of {CLEARANCE_DATE}.</p><p>The security deposit will be refunded minus any deductions.</p>";
}

// Auto-fill placeholders
$template_str = str_replace(
   [
        '{TENANT_NAME}', '{ROOM}', '{START_DATE}', '{END_DATE}', '{CLEARANCE_DATE}',
        '{DEPOSIT_AMOUNT}', '{DEDUCTION_AMOUNT}', '{NET_REFUND}', '{DEDUCTION_REMARKS}',
        '{TENANT_EMAIL}', '{TENANT_PHONE}', '{TENANT_ADDRESS}'
    ],
    [
        strtoupper($tenant_name ?: 'Unknown Tenant'), $room_info, (!empty($tenant['start_date']) ? date('M d, Y', strtotime($tenant['start_date'])) : 'N/A'), (!empty($tenant['end_date']) ? date('M d, Y', strtotime($tenant['end_date'])) : 'N/A'), date('F d, Y', strtotime($clearance_date)),
        number_format($deposit_amount, 2), number_format($deduction_amount, 2), number_format($net_refund, 2), nl2br($deduction_remarks ?: 'None'),
        htmlspecialchars($tenant['email'] ?? 'N/A'), htmlspecialchars($tenant['phone_number'] ?? 'N/A'), htmlspecialchars($tenant['address'] ?? 'N/A')
    ],
    $template_str
);
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
        .print-container { background: white; max-width: 800px; margin: 0 auto; padding: 40px; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-top: 10px solid #2e7d32; border-radius: 8px; }
        
        .header-logo-wrapper { text-align: center; margin-bottom: 15px; }
        .header-logo-wrapper img { width: 85px; height: 85px; object-fit: cover; border-radius: 50%; border: 3px solid #f0b429; margin-bottom: 10px; }
        
        /* Inherit TinyMCE formats inside container */
        .print-container table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .print-container td, .print-container th { padding: 10px; border: 1px solid #ddd; vertical-align: middle; }
        
        [contenteditable="true"] { outline: none; transition: background 0.2s; border-radius: 6px; padding: 4px; }
        [contenteditable="true"]:hover { background: #fdfdfd; box-shadow: 0 0 0 1px #ccc; }
        
        @media print {
            body { background: white; padding: 0; }
            .print-container { box-shadow: none; padding: 0; max-width: 100%; border: none; }
            .no-print { display: none !important; }
            [contenteditable="true"]:hover { box-shadow: none; background: transparent; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-success px-4 rounded-pill shadow fw-bold"><i class="fas fa-print me-2"></i> Print Clearance</button>
        <button onclick="window.close()" class="btn btn-secondary px-4 rounded-pill shadow ms-2 fw-bold">Close Window</button>
        <div class="small mt-2 text-muted"><i class="fas fa-edit me-1"></i> You can click on any text below to edit it before printing.</div>
    </div>
    
    <div class="print-container">
        <div contenteditable="true">
            <div class="header-logo-wrapper">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" alt="Woke Coliving">
            </div>
            <?= $template_str ?>
        </div>
    </div>
</body>
</html>