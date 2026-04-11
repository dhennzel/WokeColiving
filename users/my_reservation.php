<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// Fetch active or pending reservations
$query = mysqli_query($conn, "
    SELECT r.*, rm.room_name, rm.room_number, rm.room_type, rm.image,
    (SELECT COUNT(*) FROM payments WHERE reservation_id = r.reservation_id AND payment_status = 'Unpaid') as unpaid_count,
    (SELECT SUM(amount) FROM payments WHERE reservation_id = r.reservation_id AND payment_status = 'Unpaid') as balance
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.user_id = $user_id AND r.is_archived = 0
    ORDER BY r.created_at DESC
");

// Fetch Security Deposit Records
$sd_query = mysqli_query($conn, "
    SELECT p.*, r.reservation_id, r.months, r.status as res_status, rm.room_name, rm.room_number 
    FROM payments p 
    JOIN reservations r ON p.reservation_id = r.reservation_id 
    LEFT JOIN rooms rm ON r.room_id = rm.room_id 
    WHERE r.user_id=$user_id AND (p.description LIKE '%Security Deposit%' OR p.description LIKE '%Downpayment%' OR p.description LIKE '%Initial%')
    ORDER BY p.payment_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Reservations | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="users_CSS/app.css">
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-success">My Stays</h2>
            <a href="profile.php" class="btn btn-outline-secondary rounded-pill">Back to Profile</a>
        </div>

        <ul class="nav nav-pills mb-4" id="reservationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stays-tab" data-bs-toggle="tab" data-bs-target="#stays-pane" type="button" role="tab">My Stays</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sd-tab" data-bs-toggle="tab" data-bs-target="#sd-pane" type="button" role="tab">Security Deposit</button>
            </li>
        </ul>

        <div class="tab-content" id="reservationTabsContent">
            <div class="tab-pane fade show active" id="stays-pane" role="tabpanel">
        <?php if(mysqli_num_rows($query) > 0): ?>
            <div class="row g-4">
                <?php while($res = mysqli_fetch_assoc($query)): ?>
                    <div class="col-md-6">
                        <div class="card card-custom h-100 overflow-hidden shadow-sm">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="../assets/images/<?= $res['image'] ?>" class="img-fluid h-100 object-fit-cover" style="min-height: 200px;">
                                </div>
                                <div class="col-8">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="fw-bold mb-1"><?= !empty($res['room_number']) ? 'Room ' . $res['room_number'] : $res['room_name'] ?></h5>
                                            <span class="badge <?= $res['status'] == 'Approved' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $res['status'] ?></span>
                                        </div>
                                        <p class="small text-muted mb-2"><?= $res['room_type'] ?> &bull; <?= $res['bed_preference'] ?></p>
                                        <div class="small mb-3">
                                            <i class="fas fa-calendar-alt me-1 text-primary"></i> 
                                            <?= date('M d', strtotime($res['start_date'])) ?> - <?= date('M d, Y', strtotime($res['end_date'])) ?>
                                        </div>

                                        <?php if($res['balance'] > 0): ?>
                                            <div class="alert alert-warning py-2 mb-3 border-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="small fw-bold">Unpaid: ₱<?= number_format($res['balance'], 2) ?></span>
                                                    <a href="pay_reservation.php?id=<?= $res['reservation_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pay Now</a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-success small fw-bold mb-3"><i class="fas fa-check-circle"></i> No Outstanding Bills</div>
                                        <?php endif; ?>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-success rounded-pill" onclick="viewStayDetails(<?= $res['reservation_id'] ?>)">Details</button>
                                            <?php if($res['status'] == 'Approved'): ?>
                                                <a href="reservation_now.php?extend_id=<?= $res['reservation_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">Extend Stay</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-suitcase fa-4x text-muted mb-3 opacity-25"></i>
                <h5 class="text-muted">No reservations yet</h5>
                <a href="reservation_now.php" class="btn btn-success mt-3 rounded-pill px-4">Book Your First Stay</a>
            </div>
        <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="sd-pane" role="tabpanel">
                <div class="card card-custom p-4">
                    <div class="alert alert-info border-0 shadow-sm rounded-4 small mb-4">
                        <i class="fas fa-info-circle me-2"></i> <strong>Refund Policy:</strong> 
                        Security deposits are <strong>always refundable</strong> for short-term stays. 
                        For contracts of 6 months or more, the deposit is only refundable upon <strong>contract completion</strong>; otherwise, it is forfeited.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Room</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Refund Eligibility</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($sd = mysqli_fetch_assoc($sd_query)): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($sd['payment_date'])) ?></td>
                                    <td><?= !empty($sd['room_number']) ? 'Room ' . htmlspecialchars($sd['room_number']) : htmlspecialchars($sd['room_name']) ?></td>
                                    <td class="fw-bold">₱<?= number_format($sd['amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $sd['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= $sd['payment_status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if($sd['months'] < 6) {
                                            echo '<span class="badge bg-info"><i class="fas fa-check-circle me-1"></i> Always Refundable</span>';
                                        } else {
                                            if($sd['res_status'] == 'Completed') {
                                                echo '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Refundable (Completed)</span>';
                                            } elseif($sd['res_status'] == 'Cancelled') {
                                                echo '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Forfeited (Early Term)</span>';
                                            } elseif($sd['res_status'] == 'Approved') {
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Eligible if Completed</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Pending Contract</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($sd['payment_status'] == 'Unpaid'): ?>
                                            <a href="pay_reservation.php?id=<?= $sd['reservation_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pay Now</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($sd_query) == 0): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No security deposit records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-custom">
                <div class="modal-header border-0"><h5 class="modal-title fw-bold">Stay Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        function viewStayDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            document.getElementById('modalContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success"></div></div>';
            modal.show();
            // In a real app, you'd fetch this via AJAX. Here is a simple placeholder:
            fetch('get_stay_info.php?id=' + id)
                .then(r => r.text())
                .then(html => document.getElementById('modalContent').innerHTML = html);
        }

        // Night Mode Sync
        const currentUserId = "<?= $user_id ?>";
        if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') document.body.classList.add('night-mode');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>