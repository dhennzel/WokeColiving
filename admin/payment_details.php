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
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];

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
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); color: #333; }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }

        /* Sidebar Styles */
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        
        @media (max-width: 768px) { 
            #sidebar-wrapper { margin-left: -250px; } 
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; } 
        }

        .main-container { max-width: 850px; margin: 40px auto; padding: 0 20px; }
        
        .card-custom { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            background: white; 
            overflow: hidden;
        }
        
        .card-header-custom {
            background-color: var(--dark-green);
            color: white;
            padding: 25px 30px;
            border-bottom: 5px solid var(--accent-yellow);
        }
        
        .info-group { margin-bottom: 25px; }
        .info-label { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
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
        }
        
        .btn-back {
            background: white;
            color: var(--dark-green);
            border: 2px solid var(--dark-green);
            border-radius: 50px;
            padding: 8px 25px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-back:hover {
            background: var(--dark-green);
            color: white;
        }
        
        @media print {
            .no-print { display: none !important; }
            .card-custom { box-shadow: none; border: 1px solid #ddd; }
            #sidebar-wrapper { display: none; }
            #page-content-wrapper { margin: 0; padding: 0; width: 100%; }
            .main-container { max-width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper" class="no-print">
        <div class="sidebar-brand" onclick="location.href='admin_dashboard.php'">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link active d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                <?php if($pending_res > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $pending_res ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-tools me-2"></i>Utilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_house ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>
            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center"><span><i class="fas fa-cog me-2"></i>Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
            </div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid main-container">
            <div class="d-flex justify-content-between align-items-center mb-4 mt-4 no-print">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-dark me-3 d-md-none" id="menu-toggle"><i class="fas fa-bars"></i></button>
                    <h3 class="fw-bold mb-0" style="color: var(--dark-green);">Payment Transaction Details</h3>
                </div>
                <a href="<?= $back_url ?>" class="btn btn-back text-decoration-none"><i class="fas fa-arrow-left me-2"></i>Back</a>
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
            <!-- Payment Information -->
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

            <!-- Payer Details -->
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
        </div>
        
        <div class="card-footer bg-light p-3 text-center border-top no-print">
            <button onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-print me-2"></i>Print Details</button>
        </div>
    </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });

    // Auto Refresh Logic
    let lastUpdate = 0;
    function checkUpdates() {
        fetch('../check_updates.php')
        .then(r => r.text())
        .then(t => {
            if(lastUpdate == 0) lastUpdate = t;
            else if (t > lastUpdate) location.reload();
        });
    }
    setInterval(checkUpdates, 3000); // Check every 3 seconds
</script>
</body>
</html>