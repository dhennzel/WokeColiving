<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['uid'])){
    header("Location: admin_dashboard.php");
    exit;
}

$uid = (int)$_GET['uid'];

// Handle Delete User
if(isset($_POST['delete_user'])){
    $del_uid = (int)$_POST['user_id'];
    // Check for active reservations
    $check_active = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid AND status IN ('Pending', 'Approved')");
    if(mysqli_num_rows($check_active) > 0){
        $swal_error = "Cannot delete user: They have active or pending reservations. Please cancel/complete them first.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Get Reservation IDs to clean up child records
            $res_ids = [];
            $r_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$del_uid");
            while($row = mysqli_fetch_assoc($r_q)){
                $res_ids[] = $row['reservation_id'];
            }

            if(!empty($res_ids)){
                $ids_str = implode(',', $res_ids);
                // Delete Payments linked to reservations
                mysqli_query($conn, "DELETE FROM payments WHERE reservation_id IN ($ids_str)");
                // Try deleting from optional tables (ignore if not exists)
                try { mysqli_query($conn, "DELETE FROM utility_bills WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM temporary_moves WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
            }

            // 2. Delete records linked directly to user
            // Break self-referencing constraints if any
            try { mysqli_query($conn, "UPDATE reservations SET extended_from = NULL WHERE user_id=$del_uid"); } catch(Exception $e){}
            mysqli_query($conn, "DELETE FROM reservations WHERE user_id=$del_uid");
            
            try { mysqli_query($conn, "DELETE FROM activity_logs WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM maintenance_requests WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM housekeeping_requests WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$del_uid"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM waitlist WHERE user_id=$del_uid"); } catch(Exception $e){}

            mysqli_query($conn, "DELETE FROM users WHERE user_id=$del_uid");
            mysqli_commit($conn);
            echo "<script>window.location='booking_management.php?msg=user_deleted';</script>";
            exit;
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);
            $swal_error = "Cannot delete user. Database error: " . addslashes($e->getMessage());
        }
    }
}

// Fetch User Details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$uid");
$user = mysqli_fetch_assoc($user_query);

if(!$user){
    header("Location: admin_dashboard.php");
    exit;
}

// Fetch All Reservations for this User
$res_query = mysqli_query($conn, "
    SELECT r.*, rm.room_name, rm.room_type 
    FROM reservations r 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id=$uid 
    ORDER BY r.created_at DESC
");

// Fetch Payment History
$pay_query = mysqli_query($conn, "
    SELECT p.*, r.reservation_id, rm.room_name 
    FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id=$uid 
    ORDER BY p.payment_date DESC
");

// Fetch Activity Logs (Ensure table exists first)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$logs_query = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id=$uid ORDER BY created_at DESC");
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        
        .user-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table th { background-color: var(--primary-green); color: white; font-weight: 500; border: none; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
        }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link active"><i class="fas fa-calendar-check me-2"></i>Bookings</a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><i class="fas fa-wrench me-2"></i>Maintenance</a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><i class="fas fa-broom me-2"></i>Housekeeping</a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="settingsSubmenu">
                <span><i class="fas fa-cog me-2"></i>Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">User Profile: <?= htmlspecialchars($user['full_name']) ?></h4>
                </div>
            <a href="booking_management.php" class="btn btn-outline-secondary rounded-pill">&larr; Back to Bookings</a>
            </div>

            <div class="row">
                <!-- User Details -->
                <div class="col-md-4 mb-4">
                    <div class="card user-card p-4 h-100">
                        <div class="text-center mb-3">
                            <div class="text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2rem; background-color: var(--primary-green);">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <h5 class="fw-bold mt-3"><?= htmlspecialchars($user['full_name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                            <?php if($user['do_not_renew']): ?>
                                <span class="badge bg-danger">Do Not Renew Flagged</span>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                        <p><strong>Joined:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                        <p><strong>User ID:</strong> #<?= $user['user_id'] ?></p>
                        <form method="POST" class="mt-4" id="deleteUserForm">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <input type="hidden" name="delete_user" value="1">
                            <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDeleteUser()"><i class="fas fa-trash-alt me-2"></i>Delete User Account</button>
                        </form>
                    </div>
                </div>

                <!-- Reservation History -->
                <div class="col-md-8 mb-4">
                    <div class="card user-card p-4">
                        <h5 class="fw-bold mb-3">Reservation History</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Room</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($res_query)): ?>
                                    <tr>
                                        <td>#<?= $row['reservation_id'] ?></td>
                                        <td><?= $row['room_name'] ?> <small class="text-muted">(<?= $row['room_type'] ?>)</small></td>
                                        <td>
                                            <small>In: <?= $row['start_date'] ?></small><br>
                                            <small>Out: <?= $row['end_date'] ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $badge = 'bg-secondary';
                                                if($row['status'] == 'Approved') $badge = 'bg-success';
                                                if($row['status'] == 'Pending') $badge = 'bg-warning text-dark';
                                                if($row['status'] == 'Cancelled') $badge = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td>₱<?= number_format($row['total_price'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="booking_management.php?action=approve&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-success" onclick="confirmAction(event, this.href, 'Approve this reservation?')"><i class="fas fa-check"></i></a>
                                                <a href="booking_management.php?action=reject&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-danger" onclick="confirmAction(event, this.href, 'Reject this reservation?')"><i class="fas fa-times"></i></a>
                                            <?php elseif($row['status'] == 'Approved'): ?>
                                                <button onclick="renewContract(<?= $row['reservation_id'] ?>, <?= $user['do_not_renew'] ?>)" class="btn btn-sm btn-success me-1"><i class="fas fa-sync-alt"></i></button>
                                                <a href="booking_management.php?action=terminate&id=<?= $row['reservation_id'] ?>&redirect=view_user&uid=<?= $uid ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'End this contract?')"><i class="fas fa-ban"></i></a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($res_query) == 0): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No reservations found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card user-card p-4 mb-4">
                <h5 class="fw-bold mb-3">Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Room</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($pay = mysqli_fetch_assoc($pay_query)): ?>
                            <?php
                                $is_overdue = ($pay['payment_status'] == 'Unpaid' && strtotime($pay['payment_date']) < strtotime('-5 days'));
                                $row_class = $is_overdue ? 'table-danger' : '';
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                                <td><?= htmlspecialchars($pay['room_name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($pay['description']) ?></td>
                                <td><?= htmlspecialchars($pay['payment_method']) ?></td>
                                <td class="fw-bold">₱<?= number_format($pay['amount'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $pay['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $pay['payment_status'] ?></span>
                                    <?php if($is_overdue): ?><br><small class="text-danger fw-bold">Overdue</small><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="payment_details.php?id=<?= $pay['payment_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($pay_query) == 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">No payment history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmAction(e, url, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}

async function renewContract(id, dnr) {
    if (dnr == 1) {
        const result = await Swal.fire({
            title: 'Do Not Renew Flagged',
            text: "This user is flagged as 'DO NOT RENEW'. Override and renew?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Override'
        });
        if (!result.isConfirmed) return;
    }

    const { value: months } = await Swal.fire({
        title: 'Renew Contract',
        input: 'number',
        inputLabel: 'Enter number of months to extend',
        inputValue: 1,
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return 'You need to enter a valid number of months!'
            }
        }
    });

    if (months) {
        window.location.href = `booking_management.php?action=renew&id=${id}&months=${months}&redirect=view_user&uid=<?= $uid ?>`;
    }
}

<?php if(isset($swal_error)): ?>
Swal.fire({ title: 'Error', text: '<?= $swal_error ?>', icon: 'error' });
<?php endif; ?>

function toggleMenu(e) {
    if(e) e.preventDefault();
    document.getElementById("wrapper").classList.toggle("toggled");
}
document.getElementById("menu-toggle").addEventListener("click", toggleMenu);
document.getElementById("sidebar-toggle").addEventListener("click", toggleMenu);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar-wrapper');
    var toggle = document.getElementById('menu-toggle');
    var wrapper = document.getElementById('wrapper');
    
    if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            wrapper.classList.remove('toggled');
        }
    }
});

function confirmDeleteUser() {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to PERMANENTLY delete this user. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete user!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteUserForm').submit();
        }
    });
}
</script>
</body>
</html>