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
while($p = mysqli_fetch_assoc($pay_query)){
    $payments[] = $p;
    $total_paid += $p['amount'];
}

$start_date = $data['start_date'] ?? $data['cin'] ?? 'N/A';
$end_date = $data['end_date'] ?? $data['cout'] ?? 'N/A';
$d_start = new DateTime($start_date);
$d_end = new DateTime($end_date);
$d_diff = $d_start->diff($d_end);
$duration_str = ($d_diff->y > 0 ? $d_diff->y . " Yr " : "") . ($d_diff->m > 0 ? $d_diff->m . " Months " : "") . ($d_diff->d > 0 ? $d_diff->d . " Days" : "");
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
            border-radius: 20px; 
            box-shadow: 0 12px 28px rgba(46, 125, 50, 0.12); 
            overflow: hidden; 
            border: 1px solid rgba(255,255,255,0.8);
        }
        .receipt-header { 
            background: linear-gradient(135deg, var(--primary-green), #209158); 
            color: white; 
            padding: 30px 40px; 
            position: relative;
        }
        .receipt-header::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 10px;
            background: linear-gradient(90deg, var(--accent-yellow) 0%, #f9a825 100%);
        }
        .logo { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid var(--accent-yellow); }
        .receipt-body { padding: 40px; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; margin-bottom: 5px; }
        .table-custom { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 15px; background-color: #f8f9fa; color: var(--dark-green); border-bottom: 2px solid var(--primary-green); }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; }
        .total-section { background-color: rgba(46, 125, 50, 0.05); padding: 25px; border-radius: 15px; margin-top: 20px; border: 1px solid rgba(46, 125, 50, 0.1); }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total-row.final { font-size: 1.3rem; font-weight: bold; color: var(--dark-green); border-top: 1px solid #ddd; padding-top: 10px; }
        .sig-box { border: 2px dashed #ddd; padding: 15px; display: inline-block; margin-top: 10px; border-radius: 10px; background: #fafafa; }
        .sig-img { max-height: 60px; }
        
        /* 3D Action Buttons */
        .btn-success {
            background: linear-gradient(135deg, var(--primary-green), #43a047) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            box-shadow: 0 4px 0 #1b5e20, 0 6px 12px rgba(0,0,0,0.15) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            text-transform: uppercase !important;
        }
        .btn-success:hover { transform: translateY(2px) !important; box-shadow: 0 2px 0 #1b5e20, 0 4px 8px rgba(0,0,0,0.1) !important; }
        .btn-success:active { transform: translateY(4px) !important; box-shadow: 0 0 0 transparent !important; }
        
        .btn-secondary {
            background: #f8f9fa !important;
            color: #333333 !important;
            font-weight: 700 !important;
            border: 1px solid #ced4da !important;
            box-shadow: 0 4px 0 #adb5bd, 0 6px 12px rgba(0,0,0,0.1) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            text-transform: uppercase !important;
        }
        .btn-secondary:hover { transform: translateY(2px) !important; box-shadow: 0 2px 0 #adb5bd, 0 4px 8px rgba(0,0,0,0.05) !important; color: var(--primary-green) !important; }
        .btn-secondary:active { transform: translateY(4px) !important; box-shadow: 0 0 0 transparent !important; }

        /* Print Styles - Forces Light Mode for paper */
        @media print { 
            @page { size: A4 portrait; margin: 10mm; }
            .no-print { display: none !important; } 
            body, body.night-mode, html { height: 100vh; background: #fff !important; color: #333 !important; padding: 0 !important; margin: 0 !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .receipt-wrapper { padding: 0 !important; display: block !important; min-height: auto; }
            .receipt-container, body.night-mode .receipt-container { box-shadow: none; border: none; margin: 0; width: 100%; max-width: 100%; background: #fff !important; } 
            .receipt-body { padding: 20px !important; }
            .table-custom th, .table-custom td { padding: 8px 10px !important; font-size: 0.9rem !important; }
            .total-section { padding: 15px !important; background-color: rgba(52, 184, 117, 0.05) !important; border: 1px solid rgba(52, 184, 117, 0.2) !important; }
            body.night-mode .table-custom th, body.night-mode .table-custom td { border-bottom: 1px solid #eee !important; color: #333 !important;}
            body.night-mode .total-row.final { color: var(--dark-green) !important; border-top: 1px solid #ddd !important; }
            .mb-5 { margin-bottom: 1.5rem !important; }
            .mt-5 { margin-top: 1.5rem !important; }
        }
        /* Night Mode Styles for Receipt */
        body.night-mode { background: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .receipt-container { background: #1e1e1e !important; box-shadow: 0 15px 35px rgba(0,0,0,0.5) !important; border-color: #333 !important; }
        body.night-mode .table-custom th { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-bottom: 2px solid var(--primary-green) !important; }
        body.night-mode .table-custom td { border-bottom: 1px solid #333 !important; color: #e0e0e0 !important; }
        body.night-mode .total-section { background-color: #2c2c2c !important; border-color: #333 !important; }
        body.night-mode .total-row.final { color: var(--accent-yellow) !important; border-top: 1px solid #444 !important; }
        body.night-mode .sig-box { background: #2c2c2c !important; border-color: #444 !important; }
        body.night-mode .card-footer { background-color: #1f1f1f !important; border-top: 1px solid #333; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .text-secondary { color: #e0e0e0 !important; }
        body.night-mode .info-label { color: #bbb !important; }
        body.night-mode .border-dark { border-color: #e0e0e0 !important; } /* Fixes the authorized by underline */
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<div class="receipt-wrapper">
    <div class="receipt-container">
        <div class="receipt-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo me-3">
                <div>
                    <div class="h4 fw-bold mb-0 font-monospace">Woke Coliving INC</div>
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
                        <div class="sig-box">
                            <img src="../assets/signatures/<?= $data['signature_image'] ?>?v=<?= time() ?>" class="sig-img">
                        </div>
                    <?php elseif(!empty($data['is_walkin'])): ?>
                        <div style="border-bottom: 1px solid #333; width: 200px; margin-top: 40px;"></div>
                        <div class="small text-muted mt-1">Signature over Printed Name</div>
                        <div class="small text-muted fst-italic">(Walk-in Guest)</div>
                    <?php else: ?><div class="text-muted fst-italic">Not signed yet</div><?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <div class="info-label mb-4">Authorized By</div>
                    <div class="mt-4"><span class="fw-bold border-bottom border-dark pb-1 px-4">Woke Coliving Admin</span></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Actions -->
    <div class="position-fixed bottom-0 end-0 m-4 no-print d-flex gap-2">
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-print me-2"></i>Print</button>
        <a href="my_reservations.php" class="btn btn-secondary rounded-pill px-4 fw-bold">Back</a>
    </div>
</div>
<script>
    // Check if Night Mode is enabled from the main dashboard
    const currentUserId = "<?= $user_id ?>";
    if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
        document.body.classList.add('night-mode');
    }

    // Keep it synced if they change it in another tab
    window.addEventListener('storage', (e) => {
        if (e.key === 'nightMode_' + currentUserId) {
            if (e.newValue === 'enabled') document.body.classList.add('night-mode');
            else document.body.classList.remove('night-mode');
        }
    });
</script>
</body>
</html>