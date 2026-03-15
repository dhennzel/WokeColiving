

<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Handle Notify Action
if(isset($_GET['notify_id'])){
    $notify_id = (int)$_GET['notify_id'];
    $wl_q = mysqli_query($conn, "SELECT * FROM waitlist WHERE id=$notify_id");
    if($wl_row = mysqli_fetch_assoc($wl_q)){
        $uid = $wl_row['user_id'];
        $room_type = $wl_row['room_type'];
        
        // Send notification
        $msg = "🎉 <strong>Good News!</strong><br>A spot in <strong>$room_type</strong> is now available. Go to 'Book a Room' to reserve it now before it's gone!";
        send_notification($conn, $uid, $msg, "Room Availability");
        
        // Mark as notified
        mysqli_query($conn, "UPDATE waitlist SET notified_at=NOW() WHERE id=$notify_id");
        
        header("Location: admin_waitlist.php?msg=notified");
        exit;
    }
}

// Handle Remove Action
if(isset($_GET['remove_id'])){
    $remove_id = (int)$_GET['remove_id'];
    mysqli_query($conn, "DELETE FROM waitlist WHERE id=$remove_id");
    header("Location: admin_waitlist.php?msg=removed");
    exit;
}

// Fetch all waitlist entries, grouped by room type
$waitlist_data = [];
$query = mysqli_query($conn, "SELECT w.*, u.first_name, u.last_name, u.email, u.phone_number FROM waitlist w JOIN users u ON w.user_id = u.user_id ORDER BY w.room_type, w.created_at ASC");
while($row = mysqli_fetch_assoc($query)){
    $waitlist_data[$row['room_type']][] = $row;
}

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
    <title>Waitlist Management | Woke Coliving INC</title>
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
                <h1>Waitlist Management</h1>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'notified'): ?>
                <div class="alert alert-success">User has been notified successfully.</div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'removed'): ?>
                <div class="alert alert-warning">User removed from waitlist.</div>
            <?php endif; ?>

            <?php if(empty($waitlist_data)): ?>
                <div class="card card-custom p-5 text-center">
                    <h5 class="text-muted">The waitlist is currently empty.</h5>
                </div>
            <?php else: ?>
                <?php foreach($waitlist_data as $room_type => $users): ?>
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white fw-bold text-success fs-5">
                        <i class="fas fa-bed me-2"></i> <?= htmlspecialchars($room_type) ?>
                        <span class="badge bg-secondary ms-2"><?= count($users) ?> waiting</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>#</th><th>User</th><th>Contact</th><th>Date Joined</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                <?php foreach($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><a href="view_user.php?uid=<?= $user['user_id'] ?>" class="text-decoration-none fw-bold"><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></a></td>
                                    <td>
                                        <small class="d-block text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                        <small class="d-block text-muted"><?= htmlspecialchars($user['phone_number']) ?></small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if(!empty($user['notified_at'])): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Notified</span>
                                            <small class="d-block text-muted"><?= date('M d, H:i', strtotime($user['notified_at'])) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Waiting</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if(empty($user['notified_at'])): ?>
                                        <a href="admin_waitlist.php?notify_id=<?= $user['id'] ?>" class="btn btn-sm btn-primary" onclick="confirmAction(event, this.href, 'Notify this user about availability?')">
                                            <i class="fas fa-paper-plane me-1"></i> Notify
                                        </a>
                                        <?php else: ?>
                                            <span class="text-muted small me-2">Already Notified</span>
                                        <?php endif; ?>
                                        <a href="admin_waitlist.php?remove_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="confirmAction(event, this.href, 'Remove user from waitlist?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function confirmAction(e, url, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2E7D32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}
</script>
</body>
</html>