<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get User Info
$u_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);
$user_info['full_name'] = $user_info['last_name'] . ', ' . $user_info['first_name'] . (!empty($user_info['middle_name']) ? ' ' . $user_info['middle_name'] : '');

// Ensure user_update_requests table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_update_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gender VARCHAR(20),
    occupation VARCHAR(50),
    company VARCHAR(100),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    school_id_image VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

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

// Ensure is_archived column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'is_archived'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

// Ensure signature_required column exists
$check_sig = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'signature_required'");
if(mysqli_num_rows($check_sig) == 0) {
    mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN signature_required TINYINT(1) DEFAULT 0");
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
// Ensure table exists to prevent errors
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

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
    <style>
        :root {
            --primary-green: #2E7D32;
            --dark-green: #1B5E20;
            --accent-yellow: #FBC02D;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        .navbar { background: var(--dark-green); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table thead th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .table tbody tr { transition: 0.2s; }
        .table tbody tr:hover { background-color: #f1f8e9; }
        @keyframes shake { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-10deg); } 60% { transform: rotate(5deg); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0deg); } }
        .shake-animation { animation: shake 0.5s; }
        
        @media print {
            body * { visibility: hidden; }
            #activityLogModal, #activityLogModal * { visibility: visible; }
            #activityLogModal { position: absolute; left: 0; top: 0; width: 100%; height: 100%; overflow: visible !important; }
            .modal-dialog { margin: 0; width: 100%; max-width: 100%; }
            .modal-content { border: none; box-shadow: none; }
            .no-print { display: none !important; }
        }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212; color: #e0e0e0; }
        body.night-mode .navbar { background: #1f1f1f !important; }
        body.night-mode .card, body.night-mode .card-custom { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .text-dark { color: #e0e0e0 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode .bg-light { background-color: #2c2c2c !important; }
        body.night-mode .dropdown-menu { background-color: #1e1e1e; border-color: #333; }
        body.night-mode .dropdown-item { color: #e0e0e0; }
        body.night-mode .dropdown-item:hover { background-color: #333; }
        
        /* Updated Table Styles Below */
        body.night-mode .table { color: #e0e0e0; background-color: transparent; } 
        body.night-mode .table thead th { background-color: #1f1f1f; border-color: #333; color: #e0e0e0; }
        body.night-mode .table td, body.night-mode .table th { background-color: #1e1e1e; border-color: #333; color: #e0e0e0; } 
        
        /* Modal Night Mode Styles */
        body.night-mode .modal-content { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .modal-header { border-bottom-color: #333; }
        body.night-mode .modal-footer { border-top-color: #333; }
        body.night-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.night-mode button.close { color: #e0e0e0; text-shadow: none; }
        
        /* Button Styles */
        body.night-mode .btn-outline-dark { color: #e0e0e0; border-color: #e0e0e0; }
        body.night-mode .btn-outline-dark:hover { background-color: #e0e0e0; color: #121212; }

        /* Form & Table Fixes */
        body.night-mode .form-control, body.night-mode .form-select { background-color: #2c2c2c; color: #e0e0e0; border-color: #444; }
        body.night-mode .form-control:focus, body.night-mode .form-select:focus { background-color: #333; color: #fff; }
        body.night-mode .table-hover tbody tr:hover > * { background-color: #2c2c2c; color: #fff; }
    </style>
</head>
<body class="<?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="profile.php" class="text-white text-decoration-none fw-bold position-relative">
                My Profile
                <?php if($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                <?php endif; ?>
            </a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars($user_info['first_name']) ?></span>
            <a href="logout.php" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 text-dark">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-success"><i class="fas fa-suitcase me-2"></i>My Reservations</h2>
            <p class="text-muted mb-0">Welcome back, <strong><?= htmlspecialchars($user_info['full_name']) ?></strong>!</p>
        </div>
        <div>
            <button onclick="location.reload()" class="btn btn-outline-primary fw-bold me-2 rounded-pill"><i class="fas fa-sync-alt me-2"></i>Refresh</button>
            <button type="button" class="btn btn-outline-warning fw-bold me-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#updateInfoModal"><i class="fas fa-user-edit me-2"></i>Update Info</button>
            <button type="button" class="btn btn-outline-success fw-bold me-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#activityLogModal"><i class="fas fa-history me-2"></i>Activity Logs</button>
            <a href="profile.php" class="btn btn-secondary rounded-pill">&larr; Back</a>
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

    <div class="card card-custom p-4">
        <?php if(mysqli_num_rows($query) > 0) { ?>
        <div class="table-responsive">
            <table class="table align-middle">
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
                                if($has_unpaid && ($row['status'] == 'Pending' || $row['status'] == 'Verifying')): 
                            ?>
                                <a href="pay_reservation.php?id=<?= $rid ?>" class="btn btn-sm btn-warning rounded-pill mb-1">
                                    <i class="fas fa-credit-card me-1"></i> Pay Now
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
                                    <a href="esignature.php?id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-success rounded-pill">
                                        <i class="fas fa-pen-nib me-1"></i> Sign Lease
                                    </a>
                                <?php } elseif(!empty($row['signature_image'])) { ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-file-signature"></i> Signed</span>
                                <?php } ?>
                                
                                <a href="view_receipt.php?id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill ms-1">
                                    <i class="fas fa-file-invoice"></i> Receipt
                                </a>

                                <?php if($row['status'] == 'Approved'): ?>
                                    <!-- Extend Stay Button -->
                                    <a href="reservation_now.php?extend_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-warning rounded-pill ms-1">
                                        <i class="fas fa-history me-1"></i> Extend
                                    </a>
                                <?php endif; ?>
                            <?php } ?>
                            
                            <?php // Show Remove button for Cancelled or Past End Date (Completed)
                                $is_past = (strtotime($end_date) < time());
                                if($row['status'] == 'Cancelled' || ($row['status'] == 'Approved' && $is_past)) { ?>
                                <a href="my_reservations.php?archive_id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="return confirm('Remove this reservation to archives?')">
                                    <i class="fas fa-archive"></i> Remove
                                </a>
                            <?php } ?>
                            <a href="javascript:void(0)" onclick="viewRoomDetails(<?= $row['room_id'] ?>, <?= $duration ?>, <?= $total_price ?>, '<?= addslashes($row['bed_preference'] ?? 'Any') ?>')" class="btn btn-sm btn-primary rounded-pill ms-1">
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
                <a href="reservation_now.php" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-search me-2"></i>Browse Rooms
                </a>
            </div>
        <?php } ?>
    </div>

</div>

<!-- Activity Logs Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Logs</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Info Modal -->
<div class="modal fade" id="updateInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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
                        <label class="form-label">Company Name</label>
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
                        <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($user_info['emergency_contact_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="label_emergency_number">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_number" class="form-control" value="<?= htmlspecialchars($user_info['emergency_contact_number'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Room Details Modal -->
<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
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
                    
                    <div class="card bg-light border-0 p-3 mb-3">
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
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<audio id="notifSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if(isset($_SESSION['swal'])): ?>
    Swal.fire({
        title: '<?= $_SESSION['swal']['title'] ?>',
        text: '<?= $_SESSION['swal']['text'] ?>',
        icon: '<?= $_SESSION['swal']['icon'] ?>'
    });
    <?php unset($_SESSION['swal']); endif; ?>

    function toggleCompanyField() {
        var occupation = document.getElementById('occupation');
        var companyDiv = document.getElementById('company_div');
        var schoolIdDiv = document.getElementById('school_id_div');
        var companyInput = document.getElementById('company');
        
        var labelName = document.getElementById('label_emergency_name');
        var labelNumber = document.getElementById('label_emergency_number');
        
        if (occupation && occupation.value === 'Employed') {
            companyDiv.style.display = 'block';
            schoolIdDiv.style.display = 'none';
            if(labelName) labelName.innerText = "Emergency Contact/Boss Name";
            if(labelNumber) labelNumber.innerText = "Emergency Contact/Boss Contact Number";
        } else if (occupation && occupation.value === 'Student') {
            companyDiv.style.display = 'none';
            schoolIdDiv.style.display = 'block';
            if(labelName) labelName.innerText = "Guardian Name";
            if(labelNumber) labelNumber.innerText = "Guardian Contact Number";
        } else {
            companyDiv.style.display = 'none';
            schoolIdDiv.style.display = 'none';
            if(labelName) labelName.innerText = "Emergency Contact Name";
            if(labelNumber) labelNumber.innerText = "Emergency Contact Number";
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