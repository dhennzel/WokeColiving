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

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Signature Reset
if(isset($_POST['reset_signature'])){
    $reset_stmt = mysqli_prepare($conn, "UPDATE reservations SET signature_image = NULL WHERE reservation_id = ?");
    mysqli_stmt_bind_param($reset_stmt, "i", $id);
    mysqli_stmt_execute($reset_stmt);
    header("Location: view_receipt.php?id=$id");
    exit;
}

// Handle Request Signature
if(isset($_POST['request_signature'])){
    $res_id = $id; // from $_GET['id']
    $user_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$res_id");
    $uid = mysqli_fetch_assoc($user_q)['user_id'];
    
    // Ensure column exists
    $check_sig = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'signature_required'");
    if(mysqli_num_rows($check_sig) == 0) {
        mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN signature_required TINYINT(1) DEFAULT 0");
    }

    mysqli_query($conn, "UPDATE reservations SET signature_required=1 WHERE reservation_id=$res_id");
    send_notification($conn, $uid, "✍️ <strong>Signature Required</strong><br>Admin has requested your signature for Reservation #$res_id. Please go to My Reservations to sign.", "Action Required");
    log_activity($conn, $uid, "Signature Requested", "Signature requested for Reservation #$res_id by $admin_username from receipt view.");
    
    header("Location: view_receipt.php?id=$id&msg=sig_requested");
    exit;
}

// Fetch Reservation & User & Room Details
$query = "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, u.is_walkin, u.emergency_contact_name, u.emergency_contact_number, rm.room_name, rm.room_number, rm.room_type, rm.floor
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

// Logic for Acknowledgement Particulars
$sd_total = 0;
$rent_total = 0;
$other_total = 0;
$methods = [];

foreach($payments as $pay) {
    $desc = strtolower($pay['description']);
    $amt = (float)$pay['amount'];
    
    if(!in_array($pay['payment_method'], $methods)) $methods[] = $pay['payment_method'];

    if (strpos($desc, 'security') !== false || strpos($desc, 'reservation fee') !== false) {
        $sd_total += $amt;
    } elseif (strpos($desc, 'utility') !== false || strpos($desc, 'penalty') !== false || strpos($desc, 'parking') !== false || strpos($desc, 'maintenance') !== false || strpos($desc, 'housekeeping') !== false) {
        $other_total += $amt;
    } else {
        // If it's the initial payment and no specific "security" mention, 
        // we assume the first 3000 is SD (Standard Policy)
        if ($sd_total == 0 && $amt >= 3000 && (strpos($desc, 'initial') !== false || strpos($desc, 'walk-in') !== false)) {
            $sd_total = 3000;
            $rent_total += ($amt - 3000);
        } else {
            $rent_total += $amt;
        }
    }
}
$payment_methods_str = !empty($methods) ? implode(', ', $methods) : 'N/A';

// Gather Sub-items for "Other" section
$other_sub_items = [];
foreach($payments as $pay) {
    $desc = $pay['description'];
    $amt = (float)$pay['amount'];
    $ldesc = strtolower($desc);
    
    // Filter out SD and Rent
    if (strpos($ldesc, 'security') !== false || strpos($ldesc, 'reservation fee') !== false) continue;
    
    $is_rent = (strpos($ldesc, 'rent') !== false || strpos($ldesc, 'monthly') !== false || strpos($ldesc, 'stay') !== false || strpos($ldesc, 'initial') !== false || strpos($ldesc, 'walk-in') !== false);
    
    if (!$is_rent) {
        $other_sub_items[] = ['label' => $desc, 'amount' => $amt];
    }
}


// Helper function for Sum of Pesos
if (!function_exists('amountToWords')) {
    function amountToWords($number) {
        if (class_exists('NumberFormatter')) {
            $f = new NumberFormatter("en_PH", NumberFormatter::SPELLOUT);
            $whole = floor($number);
            $fraction = round(($number - $whole) * 100);
            
            $result = ucwords(str_replace('-', ' ', $f->format($whole))) . " Pesos";
            if ($fraction > 0) {
                $result .= " and " . $f->format($fraction) . " Centavos";
            }
            return $result . " Only";
        }
        return number_format($number, 2) . " Pesos Only";
    }
}

// Description for "Payment for"
$payment_for = $data['room_type'] . " Stay (" . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date)) . ")";
if (!empty($data['room_number'])) {
    $payment_for = "Room " . $data['room_number'] . " - " . $payment_for;
} elseif (!empty($data['room_name'])) {
    $payment_for = $data['room_name'] . " - " . $payment_for;
}

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; color: #000; }
        .receipt-wrapper { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        
        .receipt-container {
            background: #fff;
            color: #000;
            width: 100%;
            max-width: 210mm; /* A5 Landscape Width */
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(46, 125, 50, 0.12);
            padding: 10mm;
            position: relative;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, var(--primary-green), #209158);
            color: white !important;
            padding: 25px 30px;
            position: relative;
            margin: -10mm -10mm 20px -10mm;
        }
        
        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--accent-yellow) 0%, #f9a825 100%);
        }
        
        /* Header */
        .logo-text { font-size: 2.2rem; font-weight: 900; margin: 0; line-height: 0.8; letter-spacing: -1px; }
        .logo-subtitle { font-size: 0.75rem; font-weight: bold; margin: 0; text-transform: uppercase; }
        .company-details { font-size: 0.7rem; line-height: 1.2; text-align: right; }
        .receipt-no { font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; }
        .receipt-no span { color: #dc3545; }

        /* Title */
        .receipt-title-centered { text-align: center; font-weight: bold; font-size: 1.1rem; border: 1.5px solid #000; padding: 2px 20px; width: fit-content; margin: 10px auto; letter-spacing: 1px; }

        /* Two Column Content */
        .particulars-table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .particulars-table th, .particulars-table td { border: 1px solid #000; padding: 4px 8px; font-size: 0.75rem; }
        .particulars-table th { text-align: center; }
        .amt-col { width: 80px; text-align: right; }
        .sub-item { padding-left: 15px !important; font-style: italic; font-size: 0.7rem !important; }

        .form-panel { padding-left: 20px; font-size: 0.8rem; }
        .form-field { display: flex; align-items: baseline; margin-bottom: 8px; }
        .line-val { border-bottom: 1px solid #000; flex-grow: 1; padding-left: 5px; font-weight: bold; min-height: 1.2em; margin-left: 5px; }
        
        /* Footer */
        .sig-section { text-align: center; width: 45%; }
        .sig-line { border-top: 1px solid #000; margin-top: 15px; font-size: 0.7rem; padding-top: 2px; font-weight: 600; }
        .name-val { font-weight: bold; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0; }
        .sig-image-on-receipt { max-height: 60px; position: absolute; bottom: 85px; left: 50%; transform: translateX(-50%); pointer-events: none; }

        .logo { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid var(--accent-yellow); }
        @media print {
            @page { size: A5 landscape; margin: 0; }
            html, body { height: 148mm !important; width: 210mm !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .receipt-wrapper { display: block !important; padding: 0 !important; margin: 0 !important; background: none !important; width: 210mm; height: 148mm; }
            .receipt-container { 
                box-shadow: none !important; border: 1.5px solid #000 !important; margin: 0 !important; border-radius: 0 !important;
                width: 210mm !important; height: 148mm !important; padding: 5mm !important;
            }
            .receipt-header { background: #fff !important; color: #000 !important; margin: 0 0 10px 0 !important; padding: 0 0 5px 0 !important; border-bottom: 1px solid #000 !important; }
            .receipt-header::after { display: none !important; } /* Remove accent line for ordinary print */
            .logo-text { color: #000 !important; font-size: 1.5rem !important; font-weight: bold !important; }
            .logo-subtitle { font-size: 0.6rem !important; color: #000 !important; }
            .logo { border: 1px solid #000 !important; filter: grayscale(100%); width: 50px; height: 50px; }
            .receipt-no { font-size: 0.8rem !important; }
            .company-details { font-size: 0.6rem !important; }
            .receipt-title-centered { font-size: 0.9rem !important; }
            .particulars-table th, .particulars-table td { border: 1px solid #000 !important; font-size: 0.8rem !important; padding: 2px 5px !important; }
            .particulars-table .sub-item { font-size: 0.75rem !important; }
            .form-panel { font-size: 0.9rem !important; }
            .name-val { font-size: 0.85rem !important; }
            .sig-line { font-size: 0.7rem !important; }
            .sig-image-on-receipt { bottom: 95px !important; max-height: 40px !important; }
            .sidebar, .top-navbar, .page-header { display: none !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print p-3 text-center">
    <a href="booking_management.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm"><i class="fas fa-arrow-left me-2"></i>Back to System</a>
</div>

<!-- Tinanggal ang dashboard layout para mawala ang scrollbars at resibo na lang ang makita -->
<div class="receipt-wrapper" style="padding: 0; min-height: auto;">
    <div class="receipt-container shadow-sm">
        <!-- Header -->
        <div class="receipt-header d-flex justify-content-between">
            <div class="d-flex align-items-start text-white">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo me-3">
                <div>
                    <h1 class="logo-text" style="color: var(--accent-yellow);">woke</h1>
                    <p class="logo-subtitle" style="color: white;">COLIVING SPACE</p>
                    <div class="small fw-bold" style="color: black !important;">123 Coliving St., Corner Avenue, Metro Manila</div>
                    <div class="small fw-bold" style="color: black !important;">Contact: +63 912 345 6789</div>
                </div>
            </div>
            <div class="text-end">
                <div class="receipt-no"><span style="color: #000;">Receipt</span></div>
                <div class="company-details">
                    <strong>WOKE COLIVING INC.</strong><br>
                </div>
            </div>
        </div>

        <div class="receipt-title-centered">ACKNOWLEDGEMENT RECEIPT</div>

        <!-- Content Body -->
        <div class="row g-0 flex-grow-1 mt-2">
            <!-- Left Panel: Table -->
            <div class="col-5">
                <table class="particulars-table">
                    <thead>
                        <tr><th colspan="2">PARTICULARS</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Total Contract Price</td>
                            <td class="amt-col fw-bold"><?= number_format($data['total_price'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Reservation Fee / Security Deposit</td>
                            <td class="amt-col"><?= number_format($sd_total, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Rental Payment</td>
                            <td class="amt-col"><?= number_format($rent_total, 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">Other: Pls Specify</td>
                        </tr>
                        <?php foreach($other_sub_items as $item): ?>
                        <tr>
                            <td class="sub-item">- <?= htmlspecialchars($item['label']) ?></td>
                            <td class="amt-col"><?= number_format($item['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($other_sub_items)): ?>
                            <tr><td class="sub-item">&nbsp;</td><td class="amt-col"></td></tr>
                        <?php endif; ?>
                        <tr style="border-top: 2px solid #000;">
                            <td class="fw-bold" style="font-size: 1rem;">TOTAL PAYMENT</td>
                            <td class="amt-col fw-bold" style="font-size: 1rem;">₱ <?= number_format($total_paid, 2) ?></td>
                        </tr>
                        <tr style="border-top: 1px solid #000;">
                            <td class="fw-bold text-danger" style="font-size: 1rem;">REMAINING BALANCE</td>
                            <td class="amt-col fw-bold text-danger" style="font-size: 1rem;"><?= number_format(max(0, $balance), 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Right Panel: Form Fields -->
            <div class="col-7 form-panel">
                <div class="d-flex justify-content-end mb-3">
                    <div class="form-field w-50">Date: <span class="line-val"><?= date('F d, Y') ?></span></div>
                </div>
                <div class="form-field">Received from: <span class="line-val"><?= htmlspecialchars($data['full_name']) ?></span></div>
                <div class="form-field">
                    The sum of Pesos: <span class="line-val"><?= amountToWords($total_paid) ?></span>
                </div>
                <div class="form-field">
                    <div class="w-100 text-end">(Php <span class="php-val" style="min-width: 100px; display: inline-block;"><?= number_format($total_paid, 2) ?></span>)</div>
                </div>
                <div class="form-field">As payment for: <span class="line-val"><?= htmlspecialchars($payment_for) ?></span></div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="row g-0 mt-auto pt-4">
            <div class="col-7"></div> <!-- Inusog pa sa kanan gamit ang mas malaking spacer -->
            <div class="col-5">
                <div class="d-flex justify-content-between">
                    <div class="sig-section position-relative">
                        <?php if(!empty($data['signature_image'])): ?>
                            <img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-image-on-receipt">
                        <?php endif; ?>
                        <div class="name-val"><?= htmlspecialchars(strtoupper($data['full_name'])) ?></div>
                        <div class="sig-line">Client signature over printed name</div>
                    </div>
                    <div class="sig-section">
                        <div class="name-val">WOKE COLIVING ADMIN</div>
                        <div class="sig-line">Authorized Representative</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Actions (Not Printed) -->
    <div class="mt-4 no-print text-center">
        <?php if(empty($data['signature_image'])): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="request_signature" value="1">
                <button type="submit" class="btn btn-sm btn-warning text-dark me-2"><i class="fas fa-paper-plane me-1"></i> Request E-Signature</button>
            </form>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-success rounded-pill shadow-lg px-4 fw-bold"><i class="fas fa-print me-2"></i>Print</button>
        <button onclick="window.close()" class="btn btn-secondary rounded-pill shadow-lg px-4 fw-bold">Close</button>
    </div>
</div>

<script>
const currentAdminUser = "<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin', ENT_QUOTES) ?>";
if(localStorage.getItem('adminNightMode_' + currentAdminUser) === 'enabled') {
    document.body.classList.add('night-mode');
}

// Remove browser headers and footers (Title, Date, URL, Page No.) during print
window.onbeforeprint = function() {
    window.oldTitle = document.title;
    document.title = "";
};

window.onafterprint = function() {
    document.title = window.oldTitle;
};

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