<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get User Info
$u_query = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id=$user_id");
$user_info = mysqli_fetch_assoc($u_query);

// Ensure is_archived column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'is_archived'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
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
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving INC
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Notification Dropdown --->
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none position-relative me-3" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if($unread_count > 0): ?>
                        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?= $unread_count ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    <?php endif; ?>
                </a>
                <ul id="notifList" class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                        <span class="fw-bold small text-uppercase text-muted">Notifications</span>
                        <?php if($unread_count > 0): ?>
                            <a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <!-- Notifications will be loaded via JS -->
                </ul>
            </div>
            <a href="profile.php" class="text-white text-decoration-none fw-bold">My Profile</a>
            <span class="text-white fw-bold d-none d-md-block">| Hello, <?= htmlspecialchars(explode(' ', $user_info['full_name'])[0]) ?></span>
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
                                <?php if(empty($row['signature_image'] ?? null)) { ?>
                                    <a href="esignature.php?id=<?= $row['reservation_id'] ?>" class="btn btn-sm btn-success rounded-pill">
                                        <i class="fas fa-pen-nib me-1"></i> Sign Lease
                                    </a>
                                <?php } else { ?>
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
                const bell = document.getElementById('notifDropdown');
                let badge = document.getElementById('notifBadge');
                if(data.unread_count > 0) {
                    if(!badge) {
                        badge = document.createElement('span');
                        badge.id = 'notifBadge';
                        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        badge.style.fontSize = '0.6rem';
                        bell.appendChild(badge);
                    }
                    badge.innerHTML = `${data.unread_count} <span class="visually-hidden">unread messages</span>`;
                } else if(badge) badge.remove();

                if(data.unread_count > lastUnreadCount) {
                    const audio = document.getElementById('notifSound');
                    if(audio) audio.play().catch(e => {});
                    const bellIcon = document.querySelector('#notifDropdown i');
                    if(bellIcon) { bellIcon.classList.add('shake-animation'); setTimeout(() => bellIcon.classList.remove('shake-animation'), 500); }
                }
                lastUnreadCount = data.unread_count;

                const list = document.getElementById('notifList');
                let html = `<li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light"><span class="fw-bold small text-uppercase text-muted">Notifications</span>${data.unread_count > 0 ? '<a href="profile.php?read_all=1" class="small text-decoration-none">Mark all read</a>' : ''}</li>`;
                if(data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        html += `<li><div class="dropdown-item p-3 border-bottom ${notif.is_read == 0 ? 'bg-white' : 'bg-light text-muted'}" style="white-space: normal;"><div class="d-flex justify-content-between mb-1"><strong class="small ${notif.is_read == 0 ? 'text-success' : ''}">${notif.type}</strong><small class="text-muted" style="font-size: 0.7rem;">${notif.created_at}</small></div><p class="mb-0 small">${notif.message}</p></div></li>`;
                    });
                } else { html += '<li class="p-3 text-center text-muted small">No notifications found.</li>'; }
                list.innerHTML = html;
            });
    }
    
    document.getElementById('notifDropdown').addEventListener('click', function() {
        const badge = document.getElementById('notifBadge');
        if(badge) badge.remove();
        fetch('get_notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_read=1'
        });
    });
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
</script>
</body>
</html>