<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

$current_page = basename($_SERVER['PHP_SELF']);

// Handle reservation approval/rejection
if(isset($_GET['action'])){
    $action = $_GET['action'];
    $reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $target_user_id = 0;

    // Get User ID for logging
    if($reservation_id > 0){
        $u_res = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$reservation_id");
        $u_row = mysqli_fetch_assoc($u_res);
        $target_user_id = $u_row['user_id'] ?? 0;
    }

    // Determine redirect URL
    $redirect_url = "booking_management.php";
    if(isset($_GET['redirect'])){
        if($_GET['redirect'] == 'view_user' && isset($_GET['uid'])){
            $redirect_url = "view_user.php?uid=" . (int)$_GET['uid'];
        } elseif($_GET['redirect'] == 'dashboard') {
            $redirect_url = "admin_dashboard.php";
        }
    }

    if($action == 'reject'){
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status='Cancelled' WHERE reservation_id=?");
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Reservation Rejected", "Reservation #$reservation_id cancelled by $admin_username.");
            send_notification($conn, $target_user_id, "❌ <strong>Reservation Rejected</strong><br>Your booking #$reservation_id has been cancelled. Please contact support for details.", "Booking Rejected");
        }
    } elseif($action == 'terminate'){
        // End Contract (Expiring/Expired)
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status='Completed' WHERE reservation_id=?");
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Contract Ended", "Reservation #$reservation_id marked as Completed by $admin_username.");
            send_notification($conn, $target_user_id, "🏁 <strong>Contract Completed</strong><br>Your stay for reservation #$reservation_id has been marked as completed. Thank you for staying with us!", "Contract Ended");
        }
    } elseif($action == 'verify'){
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status='Verifying' WHERE reservation_id=?");
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Reservation Verifying", "Reservation #$reservation_id moved to Verifying status by $admin_username.");
            send_notification($conn, $target_user_id, "🔍 <strong>Reservation Verifying</strong><br>Your booking #$reservation_id is now being verified. Please ensure payment and lease signing are completed.", "Booking Update");
        }
    } elseif($action == 'renew' && isset($_GET['months'])){
        // Renew Contract
        $months_to_add = (int)$_GET['months'];
        $description = isset($_GET['desc']) && !empty($_GET['desc']) ? mysqli_real_escape_string($conn, $_GET['desc']) : "Contract Renewal ($months_to_add months)";

        if($months_to_add > 0){
            $res_q = mysqli_query($conn, "SELECT * FROM reservations WHERE reservation_id=$reservation_id");
            $res = mysqli_fetch_assoc($res_q);
            
            // Calculate new end date
            $current_end = new DateTime($res['end_date']);
            $current_end->modify("+$months_to_add months");
            $new_end_date = $current_end->format('Y-m-d');
            
            // Calculate price increase
            $room_id = $res['room_id'];
            $room_q = mysqli_query($conn, "SELECT total_price FROM rooms WHERE room_id=$room_id");
            $room_price = mysqli_fetch_assoc($room_q)['total_price'];
            $added_cost = $room_price * $months_to_add;
            
            // Update Reservation & Add Payment Record
            mysqli_query($conn, "UPDATE reservations SET end_date='$new_end_date', months=months+$months_to_add, total_price=total_price+$added_cost WHERE reservation_id=$reservation_id");
            
            $ins_pay = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($ins_pay, "ids", $reservation_id, $added_cost, $description);
            mysqli_stmt_execute($ins_pay);
            
            if($target_user_id) {
                log_activity($conn, $target_user_id, "Contract Renewed", "Contract #$reservation_id extended by $months_to_add months by $admin_username.");
                send_notification($conn, $target_user_id, "🔄 <strong>Contract Renewed</strong><br>Your stay has been extended by $months_to_add months. Please check your billing.", "Contract Renewed");
            }
        }
    } elseif($action == 'mark_paid' && isset($_GET['pid'])){
        // Mark Payment as Paid
        $pid = (int)$_GET['pid'];
        mysqli_query($conn, "UPDATE payments SET payment_status='Paid', payment_date=NOW() WHERE payment_id=$pid");
        
        // Log activity if user known (fetch if not set from reservation_id)
        if($target_user_id == 0) {
            $p_q = mysqli_query($conn, "SELECT r.user_id FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE p.payment_id=$pid");
            if($row = mysqli_fetch_assoc($p_q)) $target_user_id = $row['user_id'];
        }
        
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Payment Confirmed", "Payment #$pid marked as Paid by $admin_username.");
            send_notification($conn, $target_user_id, "✅ <strong>Payment Confirmed</strong><br>Your payment #$pid has been verified and marked as Paid.", "Payment Update");
        }
        header("Location: $redirect_url");
        exit;
    } elseif($action == 'cancel_payment' && isset($_GET['pid'])){
        $pid = (int)$_GET['pid'];
        
        if($target_user_id == 0) {
            $p_q = mysqli_query($conn, "SELECT r.user_id FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE p.payment_id=$pid");
            if($row = mysqli_fetch_assoc($p_q)) $target_user_id = $row['user_id'];
        }
        
        mysqli_query($conn, "UPDATE payments SET payment_status='Cancelled' WHERE payment_id=$pid");
        
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Payment Cancelled", "Payment #$pid cancelled by $admin_username.");
            send_notification($conn, $target_user_id, "❌ <strong>Payment Cancelled</strong><br>Your payment #$pid has been cancelled.", "Payment Update");
        }
        header("Location: $redirect_url");
        exit;
    } elseif($action == 'reject_payment' && isset($_GET['pid'])){
        $pid = (int)$_GET['pid'];
        
        if($target_user_id == 0) {
            $p_q = mysqli_query($conn, "SELECT r.user_id FROM payments p JOIN reservations r ON p.reservation_id = r.reservation_id WHERE p.payment_id=$pid");
            if($row = mysqli_fetch_assoc($p_q)) $target_user_id = $row['user_id'];
        }
        
        mysqli_query($conn, "UPDATE payments SET proof_image=NULL, reference_number=NULL, payment_method='Cash' WHERE payment_id=$pid");
        
        if($target_user_id) {
            log_activity($conn, $target_user_id, "Payment Rejected", "Payment proof for #$pid was rejected by $admin_username.");
            send_notification($conn, $target_user_id, "❌ <strong>Payment Rejected</strong><br>Your uploaded payment proof for Payment #$pid was rejected. Please re-upload a valid proof of payment.", "Payment Update");
        }
        header("Location: $redirect_url");
        exit;
    } elseif($action == 'approve'){
        // Direct Approval using existing room_id
        $res_q = mysqli_query($conn, "SELECT * FROM reservations WHERE reservation_id=$reservation_id");
        $res_data = mysqli_fetch_assoc($res_q);
        
        if($res_data && $res_data['status'] == 'Verifying'){
            $target_user_id = $res_data['user_id'];
            $current_room_id = $res_data['room_id'];
            $s_date = $res_data['start_date'];
            $e_date = $res_data['end_date'];

            // Check Availability for these dates
            $chk_room = mysqli_query($conn, "SELECT total_beds FROM rooms WHERE room_id=$current_room_id");
            $room_cap = mysqli_fetch_assoc($chk_room)['total_beds'];
            
            // Count overlapping reservations (excluding this one)
            $chk_occ = mysqli_query($conn, "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id=$current_room_id AND status IN ('Pending', 'Approved') AND reservation_id != $reservation_id AND start_date < '$e_date' AND end_date > '$s_date' GROUP BY bed_preference");
            
            $occ = 0;
            while($row_occ = mysqli_fetch_assoc($chk_occ)) {
                if ($row_occ['bed_preference'] == 'Whole Room') {
                    $occ += $room_cap;
                } else {
                    $occ += $row_occ['cnt'];
                }
            }
            
            // Consider if the current reservation we are approving is a 'Whole Room'
            $current_req_size = ($res_data['bed_preference'] == 'Whole Room') ? $room_cap : 1;

            if(($occ + $current_req_size) > $room_cap){
                $redirect = isset($_GET['redirect']) && $_GET['redirect'] == 'view_user' ? "view_user.php?uid=$target_user_id" : "booking_management.php";
                $sep = (strpos($redirect, '?') === false) ? '?' : '&';
                header("Location: $redirect{$sep}error=Room is fully booked for these dates.");
                exit;
            }

            if(!empty($res_data['extended_from'])){
                // MERGE EXTENSION INTO ORIGINAL CONTRACT
                $parent_id = $res_data['extended_from'];
                $new_end = $res_data['end_date'];
                $added_months = $res_data['months'];
                $added_price = $res_data['total_price'];

                mysqli_begin_transaction($conn);
                try {
                    // Update parent reservation
                    $upd = mysqli_query($conn, "UPDATE reservations SET end_date='$new_end', months = months + $added_months, total_price = total_price + $added_price, room_id = $current_room_id, status = 'Approved' WHERE reservation_id=$parent_id");
                    if(!$upd) throw new Exception("Failed to update parent reservation");
                    
                    // Move payments and delete temp request
                    $mov = mysqli_query($conn, "UPDATE payments SET reservation_id=$parent_id WHERE reservation_id=$reservation_id");
                    if(!$mov) throw new Exception("Failed to move payments");
                    
                    $del = mysqli_query($conn, "DELETE FROM reservations WHERE reservation_id=$reservation_id");
                    if(!$del) throw new Exception("Failed to delete request");
                    
                    mysqli_commit($conn);
                    
                    log_activity($conn, $target_user_id, "Reservation Extended", "Contract #$parent_id updated by $admin_username.");
                    send_notification($conn, $target_user_id, "🔄 <strong>Stay Extended!</strong><br>Your extension request has been approved.", "Extension Approved");
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                }
            } else {
                // NORMAL APPROVAL
                mysqli_query($conn, "UPDATE reservations SET status='Approved' WHERE reservation_id=$reservation_id");
                log_activity($conn, $target_user_id, "Reservation Approved", "Reservation #$reservation_id approved by $admin_username.");
                send_notification($conn, $target_user_id, "🎉 <strong>Reservation Approved!</strong><br>Your booking has been approved.", "Booking Approved");
            }
        }
        header("Location: $redirect_url");
        exit;
    } elseif($action == 'toggle_dnr' && isset($_GET['uid'])){
        // Toggle Do Not Renew Flag
        $uid = (int)$_GET['uid'];
        mysqli_query($conn, "UPDATE users SET do_not_renew = NOT do_not_renew WHERE user_id=$uid");
        header("Location: $redirect_url");
        exit;
    }
    trigger_update($conn); // Auto-refresh user view
    header("Location: $redirect_url");
    exit;
}

// Search & Filter Logic
$where_clause = "1=1";
$params = [];
$types = "";

if(isset($_GET['search']) && !empty($_GET['search'])){
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (u.last_name LIKE ? OR u.first_name LIKE ? OR u.email LIKE ? OR rm.room_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssss";
}
if(isset($_GET['status']) && !empty($_GET['status'])){
    $status_filter = $_GET['status'];
    if($status_filter == 'Pending') {
        $where_clause .= " AND (r.status IN ('Pending', 'Verifying') OR (SELECT COUNT(*) FROM payments p WHERE p.reservation_id = r.reservation_id AND p.payment_status='Unpaid' AND p.proof_image IS NOT NULL) > 0)";
    } else {
        $where_clause .= " AND r.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}
if(isset($_GET['type']) && !empty($_GET['type'])){
    if($_GET['type'] == 'Walkin'){
        $where_clause .= " AND u.is_walkin = 1";
    } elseif($_GET['type'] == 'Ordinary'){
        $where_clause .= " AND u.is_walkin = 0";
    }
}

// Fetch Reservations with Filters
$sql = "
    SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.do_not_renew, u.profile_image, u.is_walkin, rm.room_name, rm.room_number, rm.room_type, rm.total_price AS room_monthly_price, rm.image,
    (SELECT COUNT(*) FROM payments p WHERE p.reservation_id = r.reservation_id AND p.payment_status='Unpaid' AND p.proof_image IS NOT NULL) as pending_payments
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE $where_clause
    AND r.reservation_id IN (
        SELECT MAX(reservation_id) FROM reservations GROUP BY user_id
    )
    ORDER BY r.reservation_id DESC
";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $reservations = mysqli_stmt_get_result($stmt);
} else {
    $reservations = mysqli_query($conn, $sql);
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
    <title>Booking Management | Woke Coliving INC</title>
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
                <h1>Bookings Management</h1>
            </div>
            <div class="card card-table p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex gap-2">
                        <a href="add_reservation.php" class="btn btn-sm btn-success rounded-pill"><i class="fas fa-plus me-1"></i> New Booking</a>
                        <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
                    </div>
                    <form class="d-flex gap-2" method="GET">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?= (isset($_GET['status']) && $_GET['status']=='Pending')?'selected':'' ?>>Pending</option>
                            <option value="Approved" <?= (isset($_GET['status']) && $_GET['status']=='Approved')?'selected':'' ?>>Approved</option>
                            <option value="Cancelled" <?= (isset($_GET['status']) && $_GET['status']=='Cancelled')?'selected':'' ?>>Cancelled</option>
                        </select>
                        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="Ordinary" <?= (isset($_GET['type']) && $_GET['type']=='Ordinary')?'selected':'' ?>>Ordinary</option>
                            <option value="Walkin" <?= (isset($_GET['type']) && $_GET['type']=='Walkin')?'selected':'' ?>>Walk-in</option>
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= $_GET['search'] ?? '' ?>">
                        <button class="btn btn-sm btn-custom"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Guest</th><th>Room</th><th>Stay Info</th><th>Total</th><th>Status</th><th>Receipt</th><th class="text-end">Manage</th></tr></thead>
                        <tbody>
                            <?php while($res = mysqli_fetch_assoc($reservations)) { ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar"><?php if(!empty($res['profile_image'])): ?><img src="../uploads/profiles/<?= $res['profile_image'] ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?= strtoupper(substr($res['full_name'],0,1)) ?><?php endif; ?></div>
                                        <div>
                                            <div class="fw-bold"><?= $res['full_name'] ?> <div class="dropdown d-inline ms-1"><a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v fa-sm"></i></a><ul class="dropdown-menu"><li><a class="dropdown-item" href="view_user.php?uid=<?= $res['user_id'] ?>"><i class="fas fa-eye me-2"></i>View History</a></li><li><a class="dropdown-item" href="?action=toggle_dnr&uid=<?= $res['user_id'] ?>"><i class="fas fa-flag me-2"></i><?= $res['do_not_renew'] ? 'Unflag DNR' : 'Flag DNR' ?></a></li></ul></div></div>
                                            <small class="text-muted"><?= $res['email'] ?></small>
                                            <?php 
                                                $m = $res['months'];
                                                $d = (strtotime($res['end_date']) - strtotime($res['start_date'])) / (60 * 60 * 24);
                                                $lbl = 'Registered'; $cls = 'bg-secondary';
                                                if($m >= 6) { $lbl = 'Long-Term'; $cls = 'bg-primary'; }
                                                elseif($d < 28) { $lbl = 'Daily'; $cls = 'bg-warning text-dark'; }
                                                else { $lbl = 'Short-Term'; $cls = 'bg-success'; }
                                                if($res['is_walkin']) { if($lbl == 'Registered') { $lbl = 'Walk-in'; $cls = 'bg-info text-dark'; } else { $lbl .= '/Walk-in'; } }
                                                echo "<span class='badge $cls ms-1' style='font-size: 0.6rem;'>$lbl</span>";
                                            ?>
                                            <?php if($res['do_not_renew']): ?><div class="badge bg-danger" style="font-size: 0.6rem;">Do Not Renew</div><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><div class="d-flex align-items-center"><img src="../assets/images/<?= $res['image'] ?>" class="rounded me-2" style="width:40px;height:40px;object-fit:cover;"><div><div class="fw-bold text-success"><?= $res['room_name'] ?></div><small class="text-muted"><?= $res['room_type'] ?></small></div></div></td>
                                <td>
                                    <div><i class="fas fa-calendar-alt text-muted me-1"></i> <?= date('M d, Y', strtotime($res['start_date'])) ?> - <?= date('M d, Y', strtotime($res['end_date'])) ?></div>
                                    <?php
                                        $d1 = new DateTime($res['start_date']);
                                        $d2 = new DateTime($res['end_date']);
                                        $interval = $d1->diff($d2);
                                        $duration_text = "";
                                        if($interval->y > 0) $duration_text .= $interval->y . " Yr ";
                                        if($interval->m > 0) $duration_text .= $interval->m . " Months ";
                                        if($interval->d > 0) $duration_text .= $interval->d . " Days";
                                        if(empty($duration_text)) $duration_text = "0 Days";
                                    ?>
                                    <small class="text-muted"><?= trim($duration_text) ?> <?= !empty($res['extended_from']) ? '<span class="badge bg-info text-dark" style="font-size:0.6rem">Extended</span>' : '' ?></small>
                                </td>
                                <td class="fw-bold">₱<?= number_format($res['total_price'],2) ?></td>
                                <td>
                                    <?php
                                        $s = $res['status'];
                                        $b_class = 'badge-pending';
                                        if($s == 'Approved') $b_class = 'badge-approved';
                                        elseif($s == 'Cancelled') $b_class = 'badge-cancelled';
                                        elseif($s == 'Verifying') $b_class = 'badge-verifying';
                                    ?>
                                    <span class="badge <?= $b_class ?> rounded-pill px-3"><?= $s ?></span>
                                </td>
                                <td>
                                    <?php if(!empty($res['signature_image'])) { ?>
                                        <a href="view_receipt.php?id=<?= $res['reservation_id'] ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Signed"><i class="fas fa-file-signature"></i> View</a>
                                    <?php } elseif($res['is_walkin']) { ?>
                                        <a href="view_receipt.php?id=<?= $res['reservation_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Walk-in (Manual)"><i class="fas fa-file-invoice"></i> View</a>
                                    <?php } else { ?>-<?php } ?>
                                </td>
                                <td class="text-end">
                                    <?php if($res['status'] == 'Pending' || $res['status'] == 'Verifying' || $res['pending_payments'] > 0): ?>
                                        <a href="view_user.php?uid=<?= $res['user_id'] ?><?= $res['pending_payments'] > 0 ? '&pay_status=Unpaid' : '' ?>" class="btn btn-sm btn-warning position-relative fw-bold text-dark" title="Action Required">
                                            <i class="fas fa-exclamation-circle me-1"></i> Review Request
                                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                                        </a>
                                    <?php else: ?>
                                        <a href="view_user.php?uid=<?= $res['user_id'] ?>" class="btn btn-sm btn-info text-white" title="View Profile"><i class="fas fa-user"></i> View Profile</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php } ?>
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
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'user_deleted'): ?>
    Swal.fire({
        title: 'Deleted!',
        text: 'User account has been permanently deleted.',
        icon: 'success'
    });
    <?php endif; ?>
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
    Swal.fire({
        title: 'Approved!',
        text: 'Reservation has been successfully approved.',
        icon: 'success'
    });
    <?php endif; ?>
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'extended'): ?>
    Swal.fire({
        title: 'Extended!',
        text: 'Reservation extension has been successfully approved.',
        icon: 'success'
    });
    <?php endif; ?>
</script>
</body>
</html>