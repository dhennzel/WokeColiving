<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';
$msg = "";
$error = "";

// Handle Delete User
if(isset($_POST['delete_user'])){
    if(!$is_super){
        $error = "Access Denied: Only Super Admins can delete residents.";
    } else {
        $del_uid = (int)$_POST['user_id'];
        // Check for active reservations
        $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid AND status IN ('Pending', 'Approved')");
        if(mysqli_num_rows($check_active) > 0){
            $error = "Cannot delete user: They have active or pending reservations.";
        } else {
            // Soft delete: Mark user as archived instead of permanent deletion
            mysqli_query($conn, "UPDATE users SET is_archived=1 WHERE user_id=$del_uid");
            // Also mark any pending deletion request as Approved
            mysqli_query($conn, "UPDATE account_deletion_requests SET status='Approved' WHERE user_id=$del_uid AND status='Pending'");
            trigger_update($conn);
            $msg = "User archived successfully.";
        }
    }
}

// Fetch Residents
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$bill_filter = isset($_GET['bill_filter']) ? $_GET['bill_filter'] : 'all';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

$where = "role != 'admin' AND role != 'Super Admin' AND u.is_archived = 0";
if($search){
    $where .= " AND (last_name LIKE '%$search%' OR first_name LIKE '%$search%' OR email LIKE '%$search%')";
}

if($bill_filter == 'unpaid'){
    $where .= " AND (SELECT IFNULL(SUM(p.amount), 0) FROM payments p JOIN reservations res ON p.reservation_id = res.reservation_id WHERE res.user_id = u.user_id AND p.payment_status = 'Unpaid') > 0";
} elseif($bill_filter == 'paid'){
    $where .= " AND (SELECT IFNULL(SUM(p.amount), 0) FROM payments p JOIN reservations res ON p.reservation_id = res.reservation_id WHERE res.user_id = u.user_id AND p.payment_status = 'Unpaid') = 0";
}

if($status_filter == 'active'){
    $where .= " AND EXISTS (SELECT 1 FROM reservations WHERE user_id = u.user_id AND status IN ('Approved', 'Pending', 'Verifying'))";
} elseif($status_filter == 'completed'){
    $where .= " AND EXISTS (SELECT 1 FROM reservations WHERE user_id = u.user_id AND status = 'Completed') AND NOT EXISTS (SELECT 1 FROM reservations WHERE user_id = u.user_id AND status IN ('Approved', 'Pending', 'Verifying'))";
}

$query = mysqli_query($conn, "
    SELECT u.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), ''), IF(u.suffix IS NOT NULL AND u.suffix != '', CONCAT(' ', u.suffix), '')) as full_name,
    (SELECT IFNULL(SUM(p.amount), 0) FROM payments p JOIN reservations res ON p.reservation_id = res.reservation_id WHERE res.user_id = u.user_id AND p.payment_status != 'Cancelled') as total_billed,
    (SELECT IFNULL(SUM(p.amount), 0) FROM payments p JOIN reservations res ON p.reservation_id = res.reservation_id WHERE res.user_id = u.user_id AND p.payment_status = 'Paid') as total_paid,
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days,
    (SELECT COUNT(*) FROM reservations WHERE user_id = u.user_id AND status IN ('Approved', 'Pending', 'Verifying')) as active_count,
    (SELECT COUNT(*) FROM reservations WHERE user_id = u.user_id AND status = 'Completed') as completed_count
    FROM users u WHERE $where ORDER BY u.last_name ASC
");

// Fetch into array to allow multiple iterations for different views
$residents = [];
while($row = mysqli_fetch_assoc($query)){
    $residents[] = $row;
}

// Sidebar Counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residents | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body, html { background: #fff !important; margin: 0 !important; padding: 10mm !important; color: #000 !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .sidebar, .top-navbar, .navbar-restore-trigger, .no-print, form, .btn, .dropdown { display: none !important; }
            .dashboard-container, .main-wrapper, .main-content { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; }
            .page-header, .alert { display: none !important; }
            .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .table { border-collapse: collapse !important; width: 100% !important; }
            .table th, .table td { border: 1px solid #ccc !important; padding: 10px !important; color: #000 !important; font-size: 11pt !important; vertical-align: middle !important; }
            .table thead th { background-color: #f8f9fa !important; font-weight: bold !important; color: #000 !important; border-bottom: 2px solid #ccc !important; }
            .badge { border: 1px solid #666 !important; color: #000 !important; background: transparent !important; padding: 3px 6px !important; }
            #cardsView { display: none !important; }
            #tableView { display: block !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2e7d32; padding-bottom: 10px; }
            .print-header h2 { margin: 0; color: #2e7d32 !important; font-weight: bold; font-size: 24px; }
            .print-header p { margin: 0; color: #555 !important; font-size: 14px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Residents Directory <span class="badge bg-success fs-6 align-middle ms-2"><?= count($residents) ?> Total</span></h1>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex no-print">
                        <select name="status_filter" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <select name="bill_filter" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="all" <?= $bill_filter == 'all' ? 'selected' : '' ?>>All Billing</option>
                            <option value="unpaid" <?= $bill_filter == 'unpaid' ? 'selected' : '' ?>>With Balance</option>
                            <option value="paid" <?= $bill_filter == 'paid' ? 'selected' : '' ?>>Fully Paid</option>
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search residents..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="fas fa-print me-2"></i>Print</button>
                    <a href="add_reservation.php" class="btn btn-sm btn-custom no-print"><i class="fas fa-user-plus me-1"></i> Add Resident</a>
                    <div class="dropdown no-print">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding-left: 10px; padding-right: 10px;">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><a class="dropdown-item fw-bold" href="#" id="btnViewTable" onclick="setView('table', event)"><i class="fas fa-list me-2 text-muted"></i> Default Table</a></li>
                            <li><a class="dropdown-item fw-bold" href="#" id="btnViewCards" onclick="setView('cards', event)"><i class="fas fa-th-large me-2 text-muted"></i> Cards View</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <!-- Table View -->
            <div class="card card-table p-4" id="tableView">
                <div class="print-header text-center">
                    <h2>Woke Coliving INC</h2>
                    <p>Residents Directory</p>
                    <small>Generated on <?= date('F d, Y h:i A') ?></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Resident</th><th>Contact</th><th>Billing Summary</th><th>Status</th><th>Joined</th><th class="text-end no-print">Action</th></tr></thead>
                        <tbody>
                            <?php foreach($residents as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar">
                                            <?php if(!empty($row['profile_image'])): ?><img src="../uploads/profiles/<?= $row['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?= strtoupper(substr($row['full_name'], 0, 1)) ?><?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= $row['full_name'] ?></div>
                                            <small class="text-muted"><?= $row['email'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $row['phone_number'] ?></td>
                                <td>
                                    <?php 
                                        $billed = $row['total_billed'];
                                        $paid = $row['total_paid'];
                                        $balance = $billed - $paid;
                                    ?>
                                    <div class="small">Paid: ₱<?= number_format($paid, 2) ?></div>
                                    <div class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">Bal: ₱<?= number_format($balance, 2) ?></div>
                                    <?= $balance > 0 ? '<span class="badge bg-danger" style="font-size:0.65rem;">With Balance</span>' : '<span class="badge bg-success" style="font-size:0.65rem;">Fully Paid</span>' ?>
                                </td>
                                <td>
                                    <?php if($row['do_not_renew']): ?><span class="badge bg-danger">Do Not Renew</span>
                                    <?php elseif($row['active_count'] == 0 && $row['completed_count'] > 0): ?><span class="badge bg-dark">Completed</span>
                                    <?php else: 
                                        $m = $row['res_months'];
                                        $d = $row['res_days'];
                                        $lbl = 'Registered'; $cls = 'bg-secondary';

                                        if($m >= 6) { $lbl = 'Long-Term'; $cls = 'bg-primary'; }
                                        elseif($d !== null && $d < 28) { $lbl = 'Daily'; $cls = 'bg-warning text-dark'; }
                                        elseif($d !== null) { $lbl = 'Short-Term'; $cls = 'bg-success'; }

                                        if($row['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in'; $cls = 'bg-info text-dark'; } else { $lbl .= '/Walk-in'; } }
                                        echo "<span class='badge $cls'>$lbl</span>";
                                    endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end no-print">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-user='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>' onclick="openResidentModal(this)"><i class="fas fa-eye"></i> View</button>
                                    <?php if($is_super): ?>
                                    <form method="POST" class="d-inline" onsubmit="confirmDeleteUser(event)">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($residents)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No residents found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cards View -->
            <div class="row g-4" id="cardsView" style="display: none;">
                <?php foreach($residents as $row): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center d-flex flex-column align-items-center">
                            <div class="user-avatar mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                <?php if(!empty($row['profile_image'])): ?>
                                    <img src="../uploads/profiles/<?= $row['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($row['full_name']) ?></h5>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($row['email']) ?></p>
                            <p class="text-muted small mb-3"><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($row['phone_number']) ?></p>
                            <div class="mb-3 mt-auto">
                                <?php if($row['do_not_renew']): ?><span class="badge bg-danger">Do Not Renew</span>
                                <?php elseif($row['active_count'] == 0 && $row['completed_count'] > 0): ?><span class="badge bg-dark">Completed</span>
                                <?php else: 
                                    $m = $row['res_months'];
                                    $d = $row['res_days'];
                                    $lbl = 'Registered'; $cls = 'bg-secondary';

                                    if($m >= 6) { $lbl = 'Long-Term'; $cls = 'bg-primary'; }
                                    elseif($d !== null && $d < 28) { $lbl = 'Daily'; $cls = 'bg-warning text-dark'; }
                                    elseif($d !== null) { $lbl = 'Short-Term'; $cls = 'bg-success'; }

                                    if($row['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in'; $cls = 'bg-info text-dark'; } else { $lbl .= '/Walk-in'; } }
                                    echo "<span class='badge $cls'>$lbl</span>";
                                endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top border-light d-flex justify-content-between p-3 gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill" data-user='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>' onclick="openResidentModal(this)"><i class="fas fa-eye"></i> View</button>
                            <?php if($is_super): ?>
                            <form method="POST" onsubmit="confirmDeleteUser(event)" class="flex-fill m-0">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <input type="hidden" name="delete_user" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($residents)): ?>
                    <div class="col-12 text-center text-muted py-4">No residents found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<!-- View Resident Modal -->
<div class="modal fade" id="viewResidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-id-card me-2"></i>Resident Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="user-avatar shadow-sm me-3" id="modalUserAvatar" style="width: 80px; height: 80px; font-size: 2.5rem;">
                        <!-- Image or Initials -->
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark" id="modalUserName">Name</h4>
                        <div class="d-flex gap-2" id="modalUserBadges">
                            <!-- Badges -->
                        </div>
                        <div id="modalBillingSummary" class="mt-2 p-2 bg-light rounded border d-flex gap-3 small">
                            <div>Total Bill: <span class="fw-bold" id="modalTotalBill">₱0.00</span></div>
                            <div class="border-start ps-3">Paid: <span class="fw-bold text-success" id="modalTotalPaid">₱0.00</span></div>
                            <div class="border-start ps-3">Balance: <span class="fw-bold text-danger" id="modalBalance">₱0.00</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-address-book me-2"></i>Contact Details</h6>
                        <p class="mb-1 text-muted small">Email</p>
                        <p class="fw-bold mb-2" id="modalUserEmail">-</p>
                        
                        <p class="mb-1 text-muted small">Phone Number</p>
                        <p class="fw-bold mb-2" id="modalUserPhone">-</p>
                        
                        <p class="mb-1 text-muted small">Date Joined</p>
                        <p class="fw-bold mb-0" id="modalUserJoined">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-info-circle me-2"></i>Personal Info</h6>
                        <div class="row">
                            <div class="col-6"><p class="mb-1 text-muted small">Gender</p><p class="fw-bold mb-2" id="modalUserGender">-</p></div>
                            <div class="col-6"><p class="mb-1 text-muted small">Occupation</p><p class="fw-bold mb-2" id="modalUserOccupation">-</p></div>
                        </div>
                        <div id="modalCompanySection" style="display: none;">
                            <p class="mb-1 text-muted small" id="modalUserCompanyLabel">Company / School</p>
                            <p class="fw-bold mb-2" id="modalUserCompany">-</p>
                        </div>
                        <p class="mb-1 text-muted small">Address</p>
                        <p class="fw-bold mb-0" id="modalUserAddress">-</p>
                        <!-- New section for School ID -->
                        <div id="modalSchoolIdSection" class="mt-3" style="display: none;">
                            <p class="mb-1 text-muted small">School ID</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="modalViewSchoolIdBtn" onclick="showSchoolId('')">
                                <i class="fas fa-id-card me-1"></i> View School ID
                            </button>
                        </div>
                    </div>
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-ambulance me-2" id="modalEmergencyIcon"></i><span id="modalEmergencyHeader">Emergency Contact</span></h6>
                        <div class="row">
                            <div class="col-md-6"><p class="mb-1 text-muted small" id="modalUserEmergencyNameLabel">Name</p><p class="fw-bold mb-2" id="modalUserEmergencyName">-</p></div>
                            <div class="col-md-6"><p class="mb-1 text-muted small" id="modalUserEmergencyPhoneLabel">Number</p><p class="fw-bold mb-0" id="modalUserEmergencyPhone">-</p></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a href="#" id="modalBtnFullProfile" class="btn btn-outline-primary me-auto"><i class="fas fa-external-link-alt me-1"></i> Full History & Bookings</a>
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
    const currentAdminUser = "<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?>";
    const viewStorageKey = 'residentsView_' + currentAdminUser;

    document.addEventListener('DOMContentLoaded', () => {
        const view = localStorage.getItem(viewStorageKey) || 'table';
        setView(view);
    });

    function setView(viewType, event = null) {
        if (event) event.preventDefault();
        
        const tableView = document.getElementById('tableView');
        const cardsView = document.getElementById('cardsView');
        const btnTable = document.getElementById('btnViewTable');
        const btnCards = document.getElementById('btnViewCards');

        if (viewType === 'cards') {
            tableView.style.display = 'none';
            cardsView.style.display = 'flex';
            btnCards.classList.add('bg-light', 'text-success');
            btnTable.classList.remove('bg-light', 'text-success');
            localStorage.setItem(viewStorageKey, 'cards');
        } else {
            tableView.style.display = 'block';
            cardsView.style.display = 'none';
            btnTable.classList.add('bg-light', 'text-success');
            btnCards.classList.remove('bg-light', 'text-success');
            localStorage.setItem(viewStorageKey, 'table');
        }
    }

    function openResidentModal(btn) {
        const user = JSON.parse(btn.getAttribute('data-user'));
        
        // Avatar
        const avatarContainer = document.getElementById('modalUserAvatar');
        if (user.profile_image) {
            avatarContainer.innerHTML = `<img src="../uploads/profiles/${user.profile_image}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        } else {
            avatarContainer.innerHTML = user.full_name.charAt(0).toUpperCase();
        }

        // Basic details
        document.getElementById('modalUserName').innerText = user.full_name;
        document.getElementById('modalUserEmail').innerText = user.email || 'N/A';
        document.getElementById('modalUserPhone').innerText = user.phone_number || 'N/A';

        // Billing
        const billed = parseFloat(user.total_billed || 0);
        const paid = parseFloat(user.total_paid || 0);
        const bal = billed - paid;
        document.getElementById('modalTotalBill').innerText = '₱' + billed.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('modalTotalPaid').innerText = '₱' + paid.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('modalBalance').innerText = '₱' + bal.toLocaleString('en-US', {minimumFractionDigits: 2});
        
        const joinedDate = new Date(user.created_at);
        document.getElementById('modalUserJoined').innerText = joinedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

        // Personal Info
        document.getElementById('modalUserGender').innerText = user.gender || 'Not Specified';
        document.getElementById('modalUserOccupation').innerText = user.occupation || 'Not Specified';
        document.getElementById('modalUserCompany').innerText = user.company || 'Not Specified';
        document.getElementById('modalUserAddress').innerText = user.address || 'Not Specified';

        // Dynamic Labeling logic matching reservation_now.php
        const occupation = user.occupation;
        const companySection = document.getElementById('modalCompanySection');
        const companyLabel = document.getElementById('modalUserCompanyLabel');
        const emHeader = document.getElementById('modalEmergencyHeader');
        const emIcon = document.getElementById('modalEmergencyIcon');
        const emNameLabel = document.getElementById('modalUserEmergencyNameLabel');
        const emPhoneLabel = document.getElementById('modalUserEmergencyPhoneLabel');
        const schoolIdSection = document.getElementById('modalSchoolIdSection');
        const viewSchoolIdBtn = document.getElementById('modalViewSchoolIdBtn');

        if (occupation === 'Employed') {
            companySection.style.display = 'none'; // Hide redundant field for employed
            emHeader.innerText = "Company Information";
            emIcon.className = "fas fa-building me-2";
            emNameLabel.innerText = "Company Name";
            emPhoneLabel.innerText = "Company Contact";
            schoolIdSection.style.display = 'none'; // Hide school ID for employed
        } else if (occupation === 'Student') {
            companySection.style.display = 'block';
            companyLabel.innerText = "School Name";
            emHeader.innerText = "Parent Information";
            emIcon.className = "fas fa-user-shield me-2";
            emNameLabel.innerText = "Parent Name";
            emPhoneLabel.innerText = "Parent Contact";
            // Show school ID section if student and image exists
            if (user.school_id_image) {
                schoolIdSection.style.display = 'block';
                viewSchoolIdBtn.setAttribute('onclick', `showSchoolId('../uploads/proofs/${user.school_id_image}')`);
            } else {
                schoolIdSection.style.display = 'none';
            }
        } else {
            companySection.style.display = 'none';
            emHeader.innerText = "Emergency Contact";
            emIcon.className = "fas fa-ambulance me-2";
            emNameLabel.innerText = "Name";
            emPhoneLabel.innerText = "Number";
        }
        schoolIdSection.style.display = 'none'; // Hide school ID for others
        
        // Emergency
        document.getElementById('modalUserEmergencyName').innerText = user.emergency_contact_name || 'Not Specified';
        document.getElementById('modalUserEmergencyPhone').innerText = user.emergency_contact_number || 'Not Specified';

        // Badges
        let badgesHtml = '';
        if (user.do_not_renew == 1) badgesHtml += '<span class="badge bg-danger">Do Not Renew</span>';
        else if (user.active_count == 0 && user.completed_count > 0) badgesHtml += '<span class="badge bg-dark">Completed</span>';
        else {
            let m = parseInt(user.res_months) || 0;
            let d = user.res_days ? parseInt(user.res_days) : null;
            let lbl = 'Registered', cls = 'bg-secondary';
            if (m >= 6) { lbl = 'Long-Term'; cls = 'bg-primary'; } else if (d !== null && d < 28) { lbl = 'Daily'; cls = 'bg-warning text-dark'; } else if (d !== null) { lbl = 'Short-Term'; cls = 'bg-success'; }
            if (user.is_walkin == 1) { if (lbl === 'Registered') { lbl = 'Walk-in'; cls = 'bg-info text-dark'; } else { lbl += '/Walk-in'; } }
            badgesHtml += `<span class="badge ${cls}">${lbl}</span>`;
        }
        document.getElementById('modalUserBadges').innerHTML = badgesHtml;
        document.getElementById('modalBtnFullProfile').href = `view_user.php?uid=${user.user_id}`;

        new bootstrap.Modal(document.getElementById('viewResidentModal')).show();
    }

    function confirmDeleteUser(e) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Delete User?',
            text: "Are you sure you want to delete this user? This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) {
            lastUpdate = t;
        } else if (t > lastUpdate) {
            sessionStorage.setItem('playNotifSound', 'true');
            location.reload();
        }
    });
}
setInterval(checkUpdates, 3000);

document.addEventListener('DOMContentLoaded', () => {
    if(sessionStorage.getItem('playNotifSound') === 'true') {
        let audio = new Audio('../assets/sounds/notification.mp3');
        audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
        audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
        sessionStorage.removeItem('playNotifSound');
    }
});

// Remove browser headers (Title, URL) during print
window.onbeforeprint = function() {
    window.oldTitle = document.title;
    document.title = "";
};
window.onafterprint = function() {
    document.title = window.oldTitle;
};
</script>
</body>
</html>