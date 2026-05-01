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

$msg = "";
$error = "";

// Ensure room_transfers table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS room_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    old_room_id INT NOT NULL,
    new_room_id INT NOT NULL,
    transfer_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Moved', 'Returned') DEFAULT 'Moved'
)");

// Ensure return_requested column exists
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM room_transfers LIKE 'return_requested'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE room_transfers ADD COLUMN return_requested TINYINT(1) DEFAULT 0");
}

// Ensure return_date column exists
$check_col_rd = mysqli_query($conn, "SHOW COLUMNS FROM room_transfers LIKE 'return_date'");
if(mysqli_num_rows($check_col_rd) == 0) {
    mysqli_query($conn, "ALTER TABLE room_transfers ADD COLUMN return_date DATETIME NULL DEFAULT NULL");
}

// Handle Move Tenant
if(isset($_POST['move_tenant'])){
    $reservation_id = (int)$_POST['reservation_id'];
    $new_room_id = (int)$_POST['new_room_id'];
    $bed_pref = $_POST['bed_preference'] ?? 'Any';

    if($reservation_id > 0 && $new_room_id > 0){
        // Get old room
        $old_q = mysqli_query($conn, "SELECT room_id FROM reservations WHERE reservation_id=$reservation_id");
        $old_room_id = mysqli_fetch_assoc($old_q)['room_id'] ?? 0;

        // Fetch target room details
        $chk = mysqli_query($conn, "SELECT room_name, room_type FROM rooms WHERE room_id=$new_room_id");
        $room = mysqli_fetch_assoc($chk);
        
        // Update reservation
        $query = "UPDATE reservations SET room_id=$new_room_id, bed_preference='$bed_pref' WHERE reservation_id=$reservation_id";
        if(mysqli_query($conn, $query)){
            // Record the transfer
            if($old_room_id > 0 && $old_room_id != $new_room_id){
                mysqli_query($conn, "INSERT INTO room_transfers (reservation_id, old_room_id, new_room_id) VALUES ($reservation_id, $old_room_id, $new_room_id)");
            }

            // Log & Notify
            $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$reservation_id");
            $uid = mysqli_fetch_assoc($u_q)['user_id'];
            
            log_activity($conn, $uid, "Room Re-assigned", "Moved to " . $room['room_name'] . " ($bed_pref) by $admin_username");
            send_notification($conn, $uid, "🏠 <strong>Room Changed</strong><br>You have been moved to <strong>" . $room['room_name'] . "</strong>.", "System");
            
            $msg = "Tenant moved successfully to " . $room['room_name'];
            trigger_update($conn);
        } else {
            $error = "Database error during move.";
        }
    } else {
        $error = "Invalid room selection.";
    }
}

// Handle Return Tenant
if(isset($_POST['return_tenant'])){
    $transfer_id = (int)$_POST['transfer_id'];
    $t_q = mysqli_query($conn, "SELECT * FROM room_transfers WHERE id=$transfer_id");
    $transfer = mysqli_fetch_assoc($t_q);
    
    if($transfer){
        $res_id = $transfer['reservation_id'];
        $orig_room = $transfer['old_room_id'];
        
        $chk = mysqli_query($conn, "SELECT room_name FROM rooms WHERE room_id=$orig_room");
        $room = mysqli_fetch_assoc($chk);
        
        mysqli_query($conn, "UPDATE reservations SET room_id=$orig_room, bed_preference='Any' WHERE reservation_id=$res_id");
        mysqli_query($conn, "UPDATE room_transfers SET status='Returned', return_date=NOW() WHERE id=$transfer_id");
        
        $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$res_id");
        $uid = mysqli_fetch_assoc($u_q)['user_id'] ?? 0;
        
        if($uid) {
            log_activity($conn, $uid, "Room Returned", "Returned to " . $room['room_name'] . " by $admin_username");
            send_notification($conn, $uid, "🏠 <strong>Room Returned</strong><br>You have been returned to your previous room: <strong>" . $room['room_name'] . "</strong>.", "System");
        }
        $msg = "Tenant returned successfully to " . $room['room_name'];
        trigger_update($conn);
    }
}

// Fetch Residents with Active Reservations
$query = "
    SELECT r.reservation_id, r.start_date, r.end_date, r.bed_preference,
           u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name, u.profile_image, u.email, u.phone_number, u.gender,
           rm.room_id, rm.room_name, rm.room_number, rm.room_type, rm.floor
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    WHERE r.status = 'Approved' AND r.end_date >= CURDATE()
    ORDER BY rm.floor, rm.room_name
";
$residents = mysqli_query($conn, $query);

// Fetch Rooms for Modal (Grouped)
$rooms = get_all_rooms_with_occupancy($conn);
$grouped_rooms = [];
foreach ($rooms as $room) {
    $grouped_rooms[$room['room_type']][] = $room;
}

$active_maint_q = mysqli_query($conn, "SELECT DISTINCT room_id FROM maintenance_requests WHERE status IN ('Pending', 'Scheduled')");
$active_maint_rooms = [];
while($r = mysqli_fetch_assoc($active_maint_q)) $active_maint_rooms[] = $r['room_id'];

$active_house_q = mysqli_query($conn, "SELECT DISTINCT room_id FROM housekeeping_requests WHERE status IN ('Pending', 'Scheduled')");
$active_house_rooms = [];
while($r = mysqli_fetch_assoc($active_house_q)) $active_house_rooms[] = $r['room_id'];

// Sidebar Counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Assignment | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: var(--primary-green); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .card-room-select { transition: transform 0.2s; cursor: default; }
        .card-room-select:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Room Assignment & Moves</h1>
            </div>
            
            <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <div class="card card-table p-4">
                <h5 class="mb-3 text-secondary">Current Residents</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Resident</th><th>Current Room</th><th>Contract</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php while($res = mysqli_fetch_assoc($residents)): 
                                // Make room display name consistent with admin_rooms.php
                                $room_display = $res['room_name'];
                                if (!empty($res['room_number'])) {
                                    $room_display = "Room " . $res['room_number'];
                                } elseif (is_numeric($res['room_name'])) {
                                    $room_display = "Room " . $res['room_name'];
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <?php if($res['profile_image']): ?>
                                                <img src="../uploads/profiles/<?= $res['profile_image'] ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($res['full_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= $res['full_name'] ?></div>
                                            <small class="text-muted"><?= $res['email'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success"><?= $room_display ?></span>
                                    <span class="badge bg-light text-dark border ms-1"><?= $res['room_type'] ?></span>
                                    <?php if($res['bed_preference'] != 'Any'): ?>
                                        <div class="small text-muted"><i class="fas fa-bed"></i> <?= $res['bed_preference'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="d-block">End: <?= date('M d, Y', strtotime($res['end_date'])) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $rid = $res['reservation_id'];
                                    $trans_q = mysqli_query($conn, "SELECT t.*, rm.room_name, rm.room_number FROM room_transfers t JOIN rooms rm ON t.old_room_id = rm.room_id WHERE t.reservation_id=$rid AND t.status='Moved' ORDER BY t.id DESC LIMIT 1");
                                    $latest_transfer = mysqli_fetch_assoc($trans_q);
                                    ?>
                                    <button class="btn btn-sm btn-primary px-3" onclick="openMoveModal(<?= $res['reservation_id'] ?>, '<?= addslashes($res['full_name']) ?>', '<?= addslashes($room_display) ?>', '<?= $res['gender'] ?>')">
                                        <i class="fas fa-exchange-alt me-1"></i> Move
                                    </button>
                                    <?php if($latest_transfer): 
                                        $old_display = !empty($latest_transfer['room_number']) ? "Room ".$latest_transfer['room_number'] : $latest_transfer['room_name'];
                                        $req_flag = isset($latest_transfer['return_requested']) && $latest_transfer['return_requested'] == 1;
                                    ?>
                                        <button class="btn btn-sm <?= $req_flag ? 'btn-danger position-relative' : 'btn-outline-warning text-dark' ?> px-3 ms-1 mt-1 mt-md-0" onclick="confirmReturn(<?= $latest_transfer['id'] ?>, '<?= addslashes($old_display) ?>')">
                                            <i class="fas <?= $req_flag ? 'fa-exclamation-circle' : 'fa-undo' ?> me-1"></i> <?= $req_flag ? 'Approve Return' : 'Return' ?> (<?= htmlspecialchars($old_display) ?>)
                                            <?php if($req_flag): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($residents) == 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No active residents found.</td></tr>
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

<!-- Move Tenant Modal -->
<div class="modal fade" id="moveRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Move Tenant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="moveTenantGenderVal">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Select a new room for <strong id="moveGuestName" class="text-dark"></strong>. Currently in: <span id="moveCurrentRoom" class="fw-bold"></span></p>
                    <div class="d-flex align-items-center">
                        <label class="small fw-bold me-2 text-muted">Filter Floor:</label>
                        <select id="floorFilter" class="form-select form-select-sm" style="width: 120px;" onchange="filterRooms()">
                            <option value="all">All Floors</option>
                            <?php for($i=2; $i<=7; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-pills mb-3" id="roomTabs" role="tablist">
                    <?php 
                    $first = true;
                    foreach($grouped_rooms as $type => $rooms_in_type): 
                        $type_id = md5($type);
                    ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $first ? 'active' : '' ?>" id="tab-<?= $type_id ?>" data-bs-toggle="pill" data-bs-target="#content-<?= $type_id ?>" type="button"><?= $type ?></button>
                    </li>
                    <?php $first = false; endforeach; ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <?php 
                    $first = true;
                    foreach($grouped_rooms as $type => $rooms_in_type): 
                        $type_id = md5($type);
                    ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="content-<?= $type_id ?>" role="tabpanel">
                        <div class="row g-3">
                            <?php foreach($rooms_in_type as $room): 
                                // Make room display name consistent with admin_rooms.php
                                $room_display = $room['room_name'];
                                if (!empty($room['room_number'])) {
                                    $room_display = "Room " . $room['room_number'];
                                } elseif (is_numeric($room['room_name'])) {
                                    $room_display = "Room " . $room['room_name'];
                                }
                                
                                // Availability Logic
                                $total = $room['total_beds'];
                                $occ_count = count($room['occupants']);
                                $avail = $total - $occ_count;
                                
                                // Specifics
                                $taken_upper = 0; $taken_lower = 0; $taken_any = 0;
                                $has_whole_room = false;
                                foreach($room['occupants'] as $o) {
                                    if($o['bed_preference'] == 'Whole Room') {
                                        $has_whole_room = true;
                                    } elseif($o['bed_preference'] == 'Upper Bunk') {
                                        $taken_upper++;
                                    } elseif($o['bed_preference'] == 'Lower Bunk') {
                                        $taken_lower++;
                                    } else {
                                        $taken_any++;
                                    }
                                }
                                if ($has_whole_room) {
                                    $taken_any = $total;
                                    $taken_upper = 0;
                                    $taken_lower = 0;
                                }
                                
                                $cap_half = floor($total / 2); // Assuming equal split for simplicity in display
                                if($type == '4-Bed' || $type == '6-Bed'){
                                    $cap_upper = floor($total / 2);
                                    $cap_lower = ceil($total / 2);
                                    
                                    $avail_upper = max(0, $cap_upper - $taken_upper);
                                    $avail_lower = max(0, $cap_lower - $taken_lower);
                                    
                                    // Distribute 'Any'
                                    if($taken_any > 0) {
                                        $fill_lower = min($avail_lower, $taken_any);
                                        $avail_lower -= $fill_lower;
                                        $taken_any -= $fill_lower;
                                        $avail_upper -= $taken_any;
                                        $avail_upper = max(0, $avail_upper);
                                    }
                                } else {
                                    $avail_upper = 0; $avail_lower = 0;
                                }

                                // Determine room gender based on occupants
                                $occupant_genders = array_column($room['occupants'], 'gender');
                                $occupant_genders = array_filter($occupant_genders);
                                $unique_genders = array_unique($occupant_genders);
                                $room_gender_status = $room['gender']; // Default to room's setting
                                $gender_icon = 'fa-question-circle';

                                if (count($unique_genders) === 1) {
                                    $room_gender_status = $unique_genders[0];
                                }

                                if ($room_gender_status == 'Female') {
                                    $gender_icon = 'fa-venus text-danger';
                                } elseif ($room_gender_status == 'Male') {
                                    $gender_icon = 'fa-mars text-primary';
                                }
                            ?>
                            <div class="col-md-4 col-lg-3 room-card-item" data-floor="<?= $room['floor'] ?>" data-gender="<?= $room_gender_status ?>" data-type="<?= $type ?>">
                                <div class="card card-room-select h-100 shadow-sm border-0">
                                    <img src="../assets/images/<?= $room['image'] ?>" class="card-img-top" style="height: 120px; object-fit: cover;">
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0"><?= $room_display ?></h6>
                                            <div>
                                                <?php if(in_array($room['room_id'], $active_maint_rooms) || $room['availability'] == 'Maintenance'): ?>
                                                    <span class="badge bg-danger border" title="Under Maintenance"><i class="fas fa-tools"></i></span>
                                                <?php endif; ?>
                                                <?php if(in_array($room['room_id'], $active_house_rooms)): ?>
                                                    <span class="badge bg-info text-dark border" title="Pending Housekeeping"><i class="fas fa-broom"></i></span>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-dark border" title="<?= $room_gender_status ?> Room"><i class="fas <?= $gender_icon ?>"></i></span>
                                                <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <?php if($room['availability'] == 'Maintenance'): ?>
                                                <span class="badge bg-danger w-100">Maintenance</span>
                                            <?php elseif($avail <= 0): ?>
                                                <span class="badge bg-secondary w-100">Full</span>
                                            <?php else: ?>
                                                <span class="badge bg-success w-100"><?= $avail ?> Beds Available</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-auto d-grid gap-2">
                                            <?php if($room['availability'] != 'Maintenance' && $avail > 0): ?>
                                                <?php if($type == 'Single'): ?>
                                                    <button onclick="submitMove(<?= $room['room_id'] ?>, 'Any')" class="btn btn-sm btn-outline-primary">Move Here</button>
                                                <?php else: ?>
                                                    <?php if($avail_lower > 0): ?>
                                                        <button onclick="submitMove(<?= $room['room_id'] ?>, 'Lower Bunk')" class="btn btn-sm btn-outline-success">Move Lower (<?= $avail_lower ?>)</button>
                                                    <?php endif; ?>
                                                    <?php if($avail_upper > 0): ?>
                                                        <button onclick="submitMove(<?= $room['room_id'] ?>, 'Upper Bunk')" class="btn btn-sm btn-outline-primary">Move Upper (<?= $avail_upper ?>)</button>
                                                    <?php endif; ?>
                                                    <?php if($avail_lower <= 0 && $avail_upper <= 0): ?>
                                                        <button onclick="submitMove(<?= $room['room_id'] ?>, 'Any')" class="btn btn-sm btn-outline-info">Move Any</button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light text-muted" disabled>Unavailable</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form -->
<form method="POST" id="moveForm" style="display:none;">
    <input type="hidden" name="reservation_id" id="formResId">
    <input type="hidden" name="new_room_id" id="formRoomId">
    <input type="hidden" name="bed_preference" id="formBedPref">
    <input type="hidden" name="move_tenant" value="1">
</form>

<!-- Hidden Return Form -->
<form method="POST" id="returnForm" style="display:none;">
    <input type="hidden" name="transfer_id" id="returnTransferId">
    <input type="hidden" name="return_tenant" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
    function openMoveModal(resId, name, currentRoom, gender) {
        document.getElementById('formResId').value = resId;
        document.getElementById('moveGuestName').innerText = name;
        document.getElementById('moveCurrentRoom').innerText = currentRoom;
        document.getElementById('moveTenantGenderVal').value = gender;
        document.getElementById('floorFilter').value = 'all';
        filterRooms(); // Apply initial filter
        new bootstrap.Modal(document.getElementById('moveRoomModal')).show();
    }

    function submitMove(roomId, bedPref) {
        Swal.fire({
            title: 'Confirm Move?',
            text: `Move tenant to this room (${bedPref})?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2E7D32',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Move'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formRoomId').value = roomId;
                document.getElementById('formBedPref').value = bedPref;
                document.getElementById('moveForm').submit();
            }
        });
    }

    function confirmReturn(transferId, roomName) {
        Swal.fire({
            title: 'Return Tenant?',
            text: `Return tenant back to ${roomName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2E7D32',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Return'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('returnTransferId').value = transferId;
                document.getElementById('returnForm').submit();
            }
        });
    }

    function filterRooms() {
        const floor = document.getElementById('floorFilter').value;
        const tenantGender = document.getElementById('moveTenantGenderVal').value;
        const cards = document.querySelectorAll('.room-card-item');
        
        cards.forEach(card => {
            const cardFloor = card.getAttribute('data-floor');
            const cardGender = card.getAttribute('data-gender');
            const cardType = card.getAttribute('data-type');
            let show = true;

            if (floor !== 'all' && cardFloor !== floor) show = false;
            
            // Filter by gender if tenant gender is present and room gender is strict
            if (show && tenantGender && cardGender && cardGender !== tenantGender && cardType !== 'Single') show = false;

            if (show) {
                card.style.display = 'block';
                card.classList.add('animate-fade-in');
            } else {
                card.style.display = 'none';
                card.classList.remove('animate-fade-in');
            }
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
</script>
</body>
</html>
