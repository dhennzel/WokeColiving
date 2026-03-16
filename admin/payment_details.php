<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: profit_report.php");
    exit;
}

$payment_id = (int)$_GET['id'];

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch Payment Details
$query = "
    SELECT p.*, r.start_date, r.end_date, r.user_id, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, rm.room_name, rm.room_type
    FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN rooms rm ON r.room_id = rm.room_id
    WHERE p.payment_id = $payment_id
";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0){
    die("Payment record not found.");
}

$payment = mysqli_fetch_assoc($result);
$theme = get_theme_colors($conn);

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

// Determine Back Link
$back_url = "javascript:history.back()";
if(!empty($payment['user_id'])){
    // Link back to User Profile -> Payments Tab (using empty pay_status to trigger tab logic)
    $back_url = "view_user.php?uid=" . $payment['user_id'] . "&pay_status=";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }

        .card-custom { 
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px; 
            box-shadow: 0 12px 28px rgba(46, 125, 50, 0.12); 
            background: white; 
            overflow: hidden;
            max-width: 850px;
            margin: 0 auto;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-green), #209158);
            color: white;
            padding: 25px 30px;
            position: relative;
        }
        
        .card-header-custom::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--accent-yellow) 0%, #f9a825 100%);
        }
        
        .info-group { margin-bottom: 25px; }
        .info-label { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            color: #888; 
            font-weight: 600; 
            margin-bottom: 5px; 
        }
        .info-value { 
            font-size: 1.1rem; 
            font-weight: 500; 
            color: var(--dark-green); 
        }
        .info-value.amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 50px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .proof-image {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            border-radius: 12px;
            border: 1px solid #ddd;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body, html { height: 100vh; margin: 0 !important; padding: 0 !important; background: #fff !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .dashboard-container, .main-wrapper, .main-content { display: block !important; padding: 0 !important; overflow: visible !important; height: auto !important; }
            .sidebar, .top-navbar { display: none !important; }
            .card-custom { box-shadow: none; border: none; border-radius: 0; margin: 0 !important; max-width: 100%; }
            .card-body { padding: 20px !important; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 no-print" style="max-width: 850px; margin: 0 auto;">
                <a href="<?= $back_url ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-bold"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>

            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold">Payment #<?= str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) ?></h4>
                        <div class="opacity-75 small"><i class="fas fa-calendar-alt me-2"></i><?= date('F d, Y h:i A', strtotime($payment['payment_date'])) ?></div>
                    </div>
                    <div class="text-end">
                        <?php 
                            $statusClass = 'bg-warning text-dark';
                            $icon = 'fa-clock';
                            if($payment['payment_status'] == 'Paid') { $statusClass = 'bg-success text-white'; $icon = 'fa-check-circle'; }
                            if($payment['payment_status'] == 'Unpaid') { $statusClass = 'bg-danger text-white'; $icon = 'fa-times-circle'; }
                        ?>
                        <span class="badge <?= $statusClass ?> status-badge shadow-sm">
                            <i class="fas <?= $icon ?> me-1"></i> <?= strtoupper($payment['payment_status']) ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <h5 class="fw-bold text-secondary mb-4 border-bottom pb-2">Payment Information</h5>
                    <div class="row">
                        <div class="col-md-4 info-group">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value amount">₱<?= number_format($payment['amount'], 2) ?></div>
                        </div>
                        <div class="col-md-4 info-group">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><i class="fas fa-credit-card me-2 text-muted"></i><?= htmlspecialchars($payment['payment_method']) ?></div>
                        </div>
                        <div class="col-md-4 info-group">
                            <div class="info-label">Reference Number</div>
                            <div class="info-value"><?= !empty($payment['reference_number']) ? htmlspecialchars($payment['reference_number']) : '<span class="text-muted fst-italic">N/A</span>' ?></div>
                        </div>
                        <div class="col-md-12 info-group">
                            <div class="info-label">Description</div>
                            <div class="info-value"><?= !empty($payment['description']) ? htmlspecialchars($payment['description']) : 'Room Payment' ?></div>
                        </div>
                    </div>

                    <h5 class="fw-bold text-secondary mb-4 border-bottom pb-2 mt-3">Payer Details</h5>
                    <div class="row">
                        <div class="col-md-6 info-group">
                            <div class="info-label">Tenant Name</div>
                            <div class="info-value fw-bold"><?= htmlspecialchars($payment['full_name'] ?? 'Unknown User') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($payment['email'] ?? '') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($payment['phone_number'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6 info-group">
                            <div class="info-label">Room Reservation</div>
                            <div class="info-value"><?= htmlspecialchars($payment['room_name'] ?? 'N/A') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($payment['room_type'] ?? '') ?></div>
                            <?php if(!empty($payment['start_date'])): ?>
                                <div class="small text-muted mt-1"><i class="fas fa-calendar-day me-1"></i> <?= date('M d', strtotime($payment['start_date'])) ?> - <?= date('M d, Y', strtotime($payment['end_date'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if(!empty($payment['proof_image'])): ?>
                    <h5 class="fw-bold text-secondary mb-4 border-bottom pb-2 mt-3 no-print">Payment Proof</h5>
                    <div class="text-center no-print">
                        <a href="../uploads/proofs/<?= htmlspecialchars($payment['proof_image']) ?>" target="_blank" title="Click to view full size">
                            <img src="../uploads/proofs/<?= htmlspecialchars($payment['proof_image']) ?>" class="proof-image" alt="Proof of Payment">
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Floating Actions -->
            <div class="position-fixed bottom-0 end-0 m-4 no-print d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success rounded-pill shadow-lg px-4 fw-bold"><i class="fas fa-print me-2"></i>Print</button>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
    // Auto Refresh Logic
    let lastUpdate = 0;
    function checkUpdates() {
        fetch('../check_updates.php')
        .then(r => r.text())
        .then(t => {
            if(lastUpdate == 0) lastUpdate = t;
            else if (t > lastUpdate) location.reload();
        })
        .catch(err => console.error("Update check failed:", err));
    }
    setInterval(checkUpdates, 3000); // Check every 3 seconds

// Parent Sidebar Badges
document.addEventListener('DOMContentLoaded', function() {
    ['frontDeskSubmenu', 'operationsSubmenu'].forEach(menuId => {
        let menu = document.getElementById(menuId);
        if (menu) {
            let badges = menu.querySelectorAll('.badge');
            let total = 0;
            badges.forEach(b => total += parseInt(b.innerText) || 0);
            if (total > 0) {
                let link = document.querySelector(`[href="#${menuId}"]`);
                if(link) {
                    let icon = link.querySelector('.fa-chevron-down');
                    if(icon) icon.insertAdjacentHTML('beforebegin', `<span class="badge bg-danger rounded-pill me-2 parent-badge">${total}</span>`);
                    link.addEventListener('click', function() { let b = this.querySelector('.parent-badge'); if(b) b.style.setProperty('display', 'none', 'important'); });
                }
            }
        }
    });
});
</script>
</body>
</html>