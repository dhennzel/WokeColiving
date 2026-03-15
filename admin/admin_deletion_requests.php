<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Handle Actions
if(isset($_POST['handle_request'])){
    $req_id = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $user_id = (int)$_POST['user_id'];

    if($action == 'reject'){
        mysqli_query($conn, "UPDATE account_deletion_requests SET status='Rejected' WHERE request_id=$req_id");
        send_notification($conn, $user_id, "❌ <strong>Deletion Request Rejected</strong><br>Your request to delete your account has been rejected by the admin.", "System");
        header("Location: admin_deletion_requests.php?msg=rejected");
        exit;
    } elseif($action == 'approve'){
        // Perform Deletion
        mysqli_begin_transaction($conn);
        try {
            // 1. Get Reservation IDs to clean up child records
            $res_ids = [];
            $r_q = mysqli_query($conn, "SELECT reservation_id FROM reservations WHERE user_id=$user_id");
            while($row = mysqli_fetch_assoc($r_q)){
                $res_ids[] = $row['reservation_id'];
            }

            if(!empty($res_ids)){
                $ids_str = implode(',', $res_ids);
                // Delete Payments linked to reservations
                mysqli_query($conn, "DELETE FROM payments WHERE reservation_id IN ($ids_str)");
                // Try deleting from optional tables
                try { mysqli_query($conn, "DELETE FROM utility_bills WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
                try { mysqli_query($conn, "DELETE FROM temporary_moves WHERE reservation_id IN ($ids_str)"); } catch(Exception $e){}
            }

            // 2. Delete records linked directly to user
            try { mysqli_query($conn, "UPDATE reservations SET extended_from = NULL WHERE user_id=$user_id"); } catch(Exception $e){}
            mysqli_query($conn, "DELETE FROM reservations WHERE user_id=$user_id");
            
            try { mysqli_query($conn, "DELETE FROM activity_logs WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM maintenance_requests WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM housekeeping_requests WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM waitlist WHERE user_id=$user_id"); } catch(Exception $e){}
            try { mysqli_query($conn, "DELETE FROM user_update_requests WHERE user_id=$user_id"); } catch(Exception $e){}

            // Delete the user (Cascade will remove the request record)
            mysqli_query($conn, "DELETE FROM users WHERE user_id=$user_id");
            
            trigger_update($conn);
            mysqli_commit($conn);
            header("Location: admin_deletion_requests.php?msg=approved");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("Location: admin_deletion_requests.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Fetch Pending Requests
$query = mysqli_query($conn, "SELECT r.*, CONCAT(u.last_name, ', ', u.first_name) as full_name, u.email, u.created_at as user_joined 
                              FROM account_deletion_requests r 
                              JOIN users u ON r.user_id = u.user_id 
                              WHERE r.status='Pending' 
                              ORDER BY r.created_at ASC");

// Fetch Pending Counts for Sidebar
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
    <title>Deletion Requests | Woke Coliving INC</title>
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
            <div class="page-header">
                <h1>Account Deletion Requests</h1>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
                <div class="alert alert-success">Request approved. User account deleted.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'rejected'): ?>
                <div class="alert alert-warning">Request rejected. User notified.</div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">Error: <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="card card-custom p-4">
                <?php if(mysqli_num_rows($query) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>User</th><th>Email</th><th>Joined</th><th>Requested</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['user_joined'])) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="confirmAction(event, 'approve')">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="handle_request" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger me-1"><i class="fas fa-trash me-1"></i> Approve Delete</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="confirmAction(event, 'reject')">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="handle_request" value="1">
                                        <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">No pending deletion requests.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function confirmAction(e, type) {
    e.preventDefault();
    const msg = type === 'approve' 
        ? "Are you sure? This will PERMANENTLY DELETE the user and all their data." 
        : "Reject this deletion request?";
    
    Swal.fire({
        title: 'Confirm Action',
        text: msg,
        icon: type === 'approve' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: type === 'approve' ? '#d33' : '#6c757d',
        confirmButtonText: 'Yes, proceed'
    }).then((result) => {
        if (result.isConfirmed) e.target.submit();
    });
}
</script>
</body>
</html>