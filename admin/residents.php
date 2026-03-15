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
$where = "role != 'admin' AND role != 'Super Admin' AND u.is_archived = 0";
if($search){
    $where .= " AND (last_name LIKE '%$search%' OR first_name LIKE '%$search%' OR email LIKE '%$search%')";
}

$query = mysqli_query($conn, "
    SELECT u.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name,
    (SELECT months FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_months,
    (SELECT DATEDIFF(end_date, start_date) FROM reservations WHERE user_id = u.user_id AND status = 'Approved' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1) as res_days
    FROM users u WHERE $where ORDER BY u.last_name ASC
");

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
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Residents Directory</h1>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search residents..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                    <a href="add_reservation.php" class="btn btn-sm btn-custom"><i class="fas fa-user-plus me-1"></i> Add Resident</a>
                </div>
            </div>

            <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="card card-table p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Resident</th><th>Contact</th><th>Status</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query)): ?>
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
                                    <?php if($row['do_not_renew']): ?><span class="badge bg-danger">Do Not Renew</span>
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
                                <td class="text-end">
                                    <a href="view_user.php?uid=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                    <?php if($is_super): ?>
                                    <form method="POST" class="d-inline" onsubmit="confirmDeleteUser(event)">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($query) == 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No residents found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
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
</script>
</body>
</html>