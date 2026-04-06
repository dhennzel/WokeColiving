<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Calculate Balance for Navbar Badge
$financial_q = mysqli_query($conn, "SELECT IFNULL(SUM(p.amount), 0) as total_billed, IFNULL(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END), 0) as total_paid FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE r.user_id=$user_id AND p.payment_status != 'Cancelled'");
$fin = mysqli_fetch_assoc($financial_q);
$total_billed = $fin['total_billed'] ?? 0;
$total_paid = $fin['total_paid'] ?? 0;
$user_balance = $total_billed - $total_paid;

// Get User Info
$u_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['last_name'] . ', ' . $user_info['first_name'] . (!empty($user_info['middle_name']) ? ' ' . $user_info['middle_name'] : '');

// Handle Update Info Action
if(isset($_POST['update_info'])){
    // Check for existing pending request
    $check_pending = mysqli_query($conn, "SELECT request_id FROM user_update_requests WHERE user_id=$user_id AND status='Pending'");
    if(mysqli_num_rows($check_pending) > 0){
        $_SESSION['swal'] = ['title' => 'Request Pending', 'text' => 'You already have a pending update request. Please wait for admin approval.', 'icon' => 'warning'];
    } else {
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
        $company = mysqli_real_escape_string($conn, $_POST['company']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $ec_name = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
        $ec_number = mysqli_real_escape_string($conn, $_POST['emergency_contact_number']);
        
        // Handle File Upload for School ID
        $school_id_filename = null;
        if(isset($_FILES['school_id_image']) && $_FILES['school_id_image']['error'] == 0){
            $target_dir = "../uploads/proofs/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = time() . '_school_' . basename($_FILES["school_id_image"]["name"]);
            if(move_uploaded_file($_FILES["school_id_image"]["tmp_name"], $target_dir . $filename)){
                $school_id_filename = $filename;
            }
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO user_update_requests (user_id, gender, occupation, company, address, emergency_contact_name, emergency_contact_number, school_id_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $gender, $occupation, $company, $address, $ec_name, $ec_number, $school_id_filename);
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION['swal'] = ['title' => 'Request Submitted', 'text' => 'Your profile update has been submitted for admin approval.', 'icon' => 'success'];
        } else {
            $_SESSION['swal'] = ['title' => 'Error', 'text' => 'Failed to submit request.', 'icon' => 'error'];
        }
    }
}

// Handle Archive Action
if(isset($_GET['archive_id'])){
    $aid = (int)$_GET['archive_id'];
    mysqli_query($conn, "UPDATE reservations SET is_archived=1 WHERE reservation_id=$aid AND user_id=$user_id");
    header("Location: my_reservations.php?msg=archived");
    exit;
}

// Fetch Reservations
$query = mysqli_query($conn, "SELECT r.*, rm.room_name, rm.room_type, rm.image 
FROM reservations r
JOIN rooms rm ON r.room_id = rm.room_id
WHERE r.user_id = $user_id AND r.is_archived = 0 ORDER BY r.reservation_id DESC");

// Fetch Activity Logs
$logs_query = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id=$user_id ORDER BY created_at DESC");

// Fetch Unread Count & Notifications
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Reservations | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="users_CSS/app.css">
    <style>
        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .navbar-user { background: #1f1f1f !important; border-bottom: 1px solid #333 !important; }
        body.night-mode .card, body.night-mode .card-custom, body.night-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .modal-header { border-bottom-color: #333 !important; }
        body.night-mode .modal-footer { background-color: #1e1e1e !important; border-top-color: #333 !important; }
        body.night-mode .dropdown-menu { background-color: #1e1e1e !important; border-color: #333 !important; }
        body.night-mode .dropdown-item { color: #e0e0e0 !important; }
        body.night-mode .dropdown-item:hover { background-color: #333 !important; }
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333 !important; color: #fff !important; border-color: var(--primary-green) !important; }
        body.night-mode .bg-light, body.night-mode .bg-white { background-color: #2c2c2c !important; color: #e0e0e0 !important; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .border, body.night-mode .border-bottom, body.night-mode .border-top { border-color: #444 !important; }
        body.night-mode .table { color: #e0e0e0 !important; }
        body.night-mode .table th, body.night-mode .table td { border-color: #444 !important; background-color: transparent !important; color: #e0e0e0 !important; }
        body.night-mode .table-light th, body.night-mode .table-light td { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        body.night-mode .btn-secondary-custom { background-color: #2c2c2c !important; color: #e0e0e0 !important; border: 1px solid #444 !important; }
        body.night-mode .btn-secondary-custom:hover { background-color: #444 !important; color: #fff !important; }
        body.night-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.night-mode .form-control[type="file"] { color: #34B875 !important; }
        body.night-mode .form-control::file-selector-button { background-color: #1e1e1e !important; color: #34B875 !important; border-color: #444 !important; }
        body.night-mode .form-control:hover::file-selector-button { background-color: #333 !important; }
        body.night-mode .navbar-user .nav-link, body.night-mode .navbar-user .navbar-brand, body.night-mode .navbar-user .text-muted { color: #34B875 !important; }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
        body.night-mode .form-label { color: #34B875 !important; }
        body.night-mode .utility-block { background-color: #2c2c2c !important; border: 1px solid #444 !important; border-radius: 10px; color: #e0e0e0 !important; }
        body.night-mode .navbar-toggler { border-color: rgba(255,255,255,0.5); }
        body.night-mode .navbar-toggler-icon { filter: invert(1) brightness(200%); }
        body.night-mode .table-striped>tbody>tr:nth-of-type(odd)>* { --bs-table-accent-bg: rgba(255, 255, 255, 0.05); color: #e0e0e0 !important; }

        @media print {
            body * { visibility: hidden; }
            .modal.show, .modal.show * { visibility: visible; }
            .modal.show { position: absolute; left: 0; top: 0; width: 100%; height: auto; margin: 0; padding: 0; background: none; }
            .modal-dialog { max-width: 100%; margin: 0; }
            .modal-content { border: none; box-shadow: none; width: 100%; }
            .no-print { display: none !important; }
            
            body.night-mode, body.night-mode * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body.night-mode .modal-content { background-color: #1e1e1e !important; color: #e0e0e0 !important; }
            body.night-mode .table-light th, body.night-mode .table-light td { background-color: #2c2c2c !important; color: #e0e0e0 !important; border-color: #444 !important; }
        }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
    })();
</script>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-user fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="d-flex align-items-center gap-3 ms-auto mt-3 mt-lg-0">
                <span class="text-muted fw-bold d-none d-md-block">Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
                <a href="logout.php" class="btn btn-accent btn-sm fw-bold px-3">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container animate-fade-in" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 anim-trigger">
        <div>
            <h2 class="fw-bold text-success"><i class="fas fa-suitcase me-2"></i>My Reservations</h2>
            <p class="text-muted mb-0">Welcome back, <strong><?= htmlspecialchars($user_info['full_name']) ?></strong>!</p>
        </div>
        <div>
            <button onclick="location.reload()" class="btn btn-sm btn-secondary-custom me-2"><i class="fas fa-sync-alt me-2"></i>Refresh</button>
            <button type="button" class="btn btn-sm btn-accent me-2" data-bs-toggle="modal" data-bs-target="#updateInfoModal"><i class="fas fa-user-edit me-2"></i>Update Info</button>
            <button type="button" class="btn btn-sm btn-custom me-2" data-bs-toggle="modal" data-bs-target="#activityLogModal"><i class="fas fa-history me-2"></i>Activity Logs</button>
            <a href="profile.php" class="btn btn-sm btn-secondary-custom">&larr; Back</a>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled') { ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Reservation has been cancelled successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'archived') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Reservation moved to archives successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'payment_submitted') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Payment submitted successfully! Admin will verify it shortly.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <div class="card card-custom p-4 anim-trigger delay-1">
        <?php if(mysqli_num_rows($query) > 0) { ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th width="10%">Room</th>
                        <th>Details</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)) { ?>
                    <?php
                        // Handle potential column name differences (start_date vs cin, etc.)
                        $start_date = $row['start_date'] ?? $row['cin'] ?? 'N/A';
                        $end_date = $row['end_date'] ?? $row['cout'] ?? 'N/A';
                        $total_price = $row['total_price'] ?? $row['total_amount'] ?? 0;

                        $duration = 0;
                        if($start_date != 'N/A' && $end_date != 'N/A'){
                            $d1 = new DateTime($start_date);
                            $d2 = new DateTime($end_date);
                            $duration = $d1->diff($d2)->days;
                        }
                    ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $row['reservation_id'] ?></td>
                        <td>
                            <img src="../assets/images/<?= $row['image'] ?>" class="img-fluid rounded shadow-sm" style="height: 60px; width: 80px; object-fit: cover;">
                        </td>
                        <td>
                            <h6 class="mb-0 fw-bold text-success"><?= $row['room_name'] ?></h6>
                            <small class="text-muted"><?= $row['room_type'] ?></small>
                            <?php if(!empty($row['bed_preference']) && $row['bed_preference'] != 'Any'): ?>
                                <div class="mt-1"><span class="badge bg-light text-dark border"><i class="fas fa-bed me-1"></i><?= $row['bed_preference'] ?></span></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="d-block"><i class="fas fa-calendar-check text-success me-1"></i> In: <strong><?= $start_date ?></strong></small>
                            <small class="d-block"><i class="fas fa-calendar-times text-danger me-1"></i> Out: <strong><?= $end_date ?></strong></small>
                            <small class="d-block text-muted mt-1"><i class="fas fa-hourglass-half me-1"></i> Duration: <strong><?= $duration ?> Days</strong></small>
                        </td>
                        <td class="fw-bold">₱<?= number_format((float)$total_price, 2) ?></td>
                        <td>
                            <?php 
                                $statusClass = 'bg-warning text-dark';
                                $icon = 'fa-clock';
                                if($row['status'] == 'Approved') { $statusClass = 'bg-success text-white'; $icon = 'fa-check-circle'; }
                                if($row['status'] == 'Verifying') { $statusClass = 'bg-info text-dark'; $icon = 'fa-search'; }
                                if($row['status'] == 'Cancelled') { $statusClass = 'bg-danger text-white'; $icon = 'fa-times-circle'; }
                            ?>
                            <span class="badge <?= $statusClass ?> rounded-pill px-3 py-2">
                                <i class="fas <?= $icon ?> me-1"></i> <?= $row['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php 
                                $rid = $row['reservation_id'];
                                $pay_chk = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM payments WHERE reservation_id=$rid AND payment_status='Unpaid'");
                                $has_unpaid = mysqli_fetch_assoc($pay_chk)['cnt'] > 0;
                                if($has_unpaid && in_array($row['status'], ['Pending', 'Verifying', 'Approved'])): 
                            ?>
                                <a href="pay_reservation.php?id=<?= $rid ?>" class="btn btn-sm btn-accent mb-1">
                                    <i class="fas fa-credit-card me-1"></i> <?= $row['status'] == 'Approved' ? 'Pay Bills / Advance' : 'Pay Now' ?>
                                </a>
                            <?php endif; ?>

                            <?php if($row['status'] == 'Approved' || $row['status'] == 'Verifying') { ?>
                                <?php 
                                    $show_sign = false;
                                    if(empty($row['signature_image'] ?? null)){
                                        // Logic: Walk-ins only sign if explicitly requested by admin. Regular users always sign.
                                        if($user_info['is_walkin']){
                                            if(($row['signature_required'] ?? 0) == 1) $show_sign = true;
                                        } else {
                                            $show_sign = true;
                                        }
                                    }
                                ?>
                                <?php if($show_sign) { ?>
                                    <a href="esignature.php?id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-custom">
                                        <i class="fas fa-pen-nib me-1"></i> Sign Lease
                                    </a>
                                <?php } ?>

                                <?php if($row['status'] == 'Approved'): ?>
                                    <!-- Extend Stay Button -->
                                    <a href="reservation_now.php?extend_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-accent ms-1">
                                        <i class="fas fa-history me-1"></i> Extend
                                    </a>
                                <?php endif; ?>
                            <?php } ?>
                            
                            <?php // Show Remove button for Cancelled or Past End Date (Completed)
                                $is_past = (strtotime($end_date) < time());
                                if($row['status'] == 'Cancelled' || ($row['status'] == 'Approved' && $is_past)) { ?>
                                <a href="my_reservations.php?archive_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="confirmArchive(event, this.href)">
                                    <i class="fas fa-archive"></i> Remove
                                </a>
                            <?php } ?>
                            <a href="javascript:void(0)" onclick="viewRoomDetails(<?= $row['room_id'] ?>, <?= $duration ?>, <?= $total_price ?>, '<?= addslashes($row['bed_preference'] ?? 'Any') ?>')" class="btn btn-sm btn-custom ms-1">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php } else { ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-calendar-plus text-muted" style="font-size: 4rem; opacity: 0.2;"></i>
                </div>
                <h4 class="fw-bold text-secondary">No Reservations Yet</h4>
                <p class="text-muted mb-4">You haven't booked any rooms. Start your journey with us today!</p>
                <a href="reservation_now.php" class="btn btn-custom px-4 py-2 fw-bold">
                    Browse Rooms
                </a>
            </div>
        <?php } ?>
    </div>

</div>

<!-- Activity Logs Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i>Activity History</h5>
                <button type="button" class="btn-close no-print" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if($logs_query && mysqli_num_rows($logs_query) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Date & Time</th>
                                    <th width="30%">Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($log = mysqli_fetch_assoc($logs_query)) { ?>
                                <tr>
                                    <td class="text-muted small"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars($log['details']) ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="text-center py-4 text-muted">No activity recorded yet.</div>
                <?php } ?>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-custom" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Logs</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Info Modal -->
<div class="modal fade" id="updateInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i>Update Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="update_info" value="1">
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="" disabled <?= empty($user_info['gender']) ? 'selected' : '' ?>>Select Gender</option>
                            <option value="Male" <?= ($user_info['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user_info['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Occupation</label>
                        <select name="occupation" id="occupation" class="form-select" required onchange="toggleCompanyField()">
                            <option value="" disabled <?= empty($user_info['occupation']) ? 'selected' : '' ?>>Select Status</option>
                            <option value="Student" <?= ($user_info['occupation'] ?? '') == 'Student' ? 'selected' : '' ?>>Student</option>
                            <option value="Employed" <?= ($user_info['occupation'] ?? '') == 'Employed' ? 'selected' : '' ?>>Employed</option>
                        </select>
                    </div>
                    <div class="mb-3" id="company_div" style="display: none;">
                        <label class="form-label" id="company_label">Company Name</label>
                        <input type="text" name="company" id="company" class="form-control" value="<?= htmlspecialchars($user_info['company'] ?? '') ?>">
                    </div>
                    <div class="mb-3" id="school_id_div" style="display: none;">
                        <label class="form-label">School ID</label>
                        <?php if(!empty($user_info['school_id_image'])): ?>
                            <div class="mb-2">
                                <small class="text-success"><i class="fas fa-check-circle"></i> Current ID Uploaded</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="school_id_image" id="school_id_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($user_info['address'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="label_emergency_name">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($user_info['emergency_contact_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="label_emergency_number">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user_info['emergency_contact_number'] ?? '') ?>" maxlength="11" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Room Details Modal -->
<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-success">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-success" role="status" id="roomLoading">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div id="roomContent" style="display:none;">
                    <img id="modalRoomImg" src="" class="img-fluid rounded-3 mb-3 shadow-sm" style="max-height: 250px; width: 100%; object-fit: cover;">
                    <h3 class="fw-bold text-dark" id="modalRoomName"></h3>
                    <p class="text-muted mb-1" id="modalRoomType"></p>
                    <h4 class="text-success fw-bold mb-3">₱<span id="modalRoomPrice"></span> <small class="text-muted fs-6">/ month</small></h4>
                    
                    <div class="utility-block p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Duration:</span>
                            <span class="fw-bold"><span id="modalDuration"></span> Days</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Total Paid:</span>
                            <span class="fw-bold text-success">₱<span id="modalTotal"></span></span>
                        </div>
                        <div class="d-flex justify-content-between" id="modalBedPrefRow" style="display:none;">
                            <span class="text-muted small">Bed Preference:</span>
                            <span class="fw-bold text-dark" id="modalBedPref"></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-light text-dark border"><i class="fas fa-bed me-1"></i> <span id="modalRoomBeds"></span> Beds</span>
                        <span class="badge bg-light text-dark border"><i class="fas fa-check-circle me-1"></i> <span id="modalRoomStatus"></span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary-custom px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="none"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="users_JS/app.js"></script>
<script>
    <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    function confirmArchive(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Archive Reservation?',
            text: "This will move the reservation to your archives.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, archive it!'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    }

    function toggleCompanyField() {
        var occupation = document.getElementById('occupation');
        var companyDiv = document.getElementById('company_div');
        var schoolIdDiv = document.getElementById('school_id_div');
        var companyInput = document.getElementById('company');
        
        var labelName = document.getElementById('label_emergency_name');
        var labelNumber = document.getElementById('label_emergency_number');
        
        // Inputs
        var inputName = document.getElementById('emergency_contact_name');
        var inputNumber = document.getElementById('emergency_contact_number');
        
        var companyLabel = document.getElementById('company_label');

        if (occupation && occupation.value === 'Employed') {
            companyDiv.style.display = 'none';
            schoolIdDiv.style.display = 'none';
            if(companyInput) companyInput.required = false;
            if(labelName) labelName.innerText = "Company Name";
            if(labelNumber) labelNumber.innerText = "Company Number";
            if(inputName) inputName.placeholder = "Enter company name";
            if(inputNumber) inputNumber.placeholder = "Enter company contact number";
        } else if (occupation && occupation.value === 'Student') {
            companyDiv.style.display = 'block';
            schoolIdDiv.style.display = 'block';
            if(companyInput) {
                companyInput.required = true;
                companyInput.placeholder = "Enter your school name";
            }
            if(companyLabel) companyLabel.innerText = "School Name";
            if(labelName) labelName.innerText = "Guardian Name";
            if(labelNumber) labelNumber.innerText = "Guardian Contact Number";
            if(inputName) inputName.placeholder = "Enter guardian name";
            if(inputNumber) inputNumber.placeholder = "Enter guardian contact number";
        } else {
            companyDiv.style.display = 'none';
            schoolIdDiv.style.display = 'none';
            if(companyInput) companyInput.required = false;
            if(labelName) labelName.innerText = "Emergency Contact Name";
            if(labelNumber) labelNumber.innerText = "Emergency Contact Number";
            if(inputName) inputName.placeholder = "e.g. Juan Dela Cruz";
            if(inputNumber) inputNumber.placeholder = "e.g. 09123456789";
        }
    }
    // Init on load
    document.addEventListener('DOMContentLoaded', toggleCompanyField);

    function viewRoomDetails(roomId, duration, totalPrice, bedPref) {
        var myModal = new bootstrap.Modal(document.getElementById('roomDetailsModal'));
        document.getElementById('roomLoading').style.display = 'block';
        document.getElementById('roomContent').style.display = 'none';
        myModal.show();

        document.getElementById('modalDuration').innerText = duration;
        document.getElementById('modalTotal').innerText = parseFloat(totalPrice).toLocaleString('en-US', {minimumFractionDigits: 2});

        if (bedPref && bedPref !== 'Any') {
            document.getElementById('modalBedPrefRow').style.display = 'flex';
            document.getElementById('modalBedPref').innerText = bedPref;
        } else {
            document.getElementById('modalBedPrefRow').style.display = 'none';
        }

        fetch('get_rooms.php?id=' + roomId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalRoomImg').src = '../assets/images/' + data.image;
                document.getElementById('modalRoomName').innerText = data.room_name;
                document.getElementById('modalRoomType').innerText = data.room_type;
                document.getElementById('modalRoomPrice').innerText = parseFloat(data.total_price).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('modalRoomBeds').innerText = data.total_beds;
                document.getElementById('modalRoomStatus').innerText = data.availability;

                document.getElementById('roomLoading').style.display = 'none';
                document.getElementById('roomContent').style.display = 'block';
            })
            .catch(error => console.error('Error:', error));
    }

    // Notification Logic
    let lastUnreadCount = <?= (int)$unread_count ?>;
    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => {});
                }
                lastUnreadCount = data.unread_count;
            });
    }
    
    setInterval(fetchNotifications, 5000);
    fetchNotifications(); // Initial load

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

// Night Mode Logic
<?php if(isset($_SESSION['night_mode'])): ?>
    // Sync LocalStorage with DB preference
    if(<?= $_SESSION['night_mode'] ?> === 1) localStorage.setItem('nightMode', 'enabled');
    else localStorage.setItem('nightMode', 'disabled');
<?php else: ?>
    if(localStorage.getItem('nightMode') === 'enabled') document.body.classList.add('night-mode');
<?php endif; ?>

// Sync Night Mode across tabs
window.addEventListener('storage', (e) => {
    if (e.key === 'nightMode') {
        if (e.newValue === 'enabled') document.body.classList.add('night-mode');
        else document.body.classList.remove('night-mode');
    }
});
</script>
</body>
</html>