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

// Handle Move Tenant
if(isset($_POST['move_tenant'])){
    $reservation_id = (int)$_POST['reservation_id'];
    $new_room_id = (int)$_POST['new_room_id'];
    $bed_pref = $_POST['bed_preference'] ?? 'Any';

    if($reservation_id > 0 && $new_room_id > 0){
        // Fetch target room details
        $chk = mysqli_query($conn, "SELECT room_name, room_type FROM rooms WHERE room_id=$new_room_id");
        $room = mysqli_fetch_assoc($chk);
        
        // Update reservation
        $query = "UPDATE reservations SET room_id=$new_room_id, bed_preference='$bed_pref' WHERE reservation_id=$reservation_id";
        if(mysqli_query($conn, $query)){
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

// Fetch Residents with Active Reservations
$query = "
    SELECT r.reservation_id, r.start_date, r.end_date, r.bed_preference,
           u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name, u.profile_image, u.email, u.phone_number,
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

// Sidebar Counts
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
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
    <title>Room Assignment | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: var(--primary-green); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .card-room-select { transition: transform 0.2s; cursor: default; }
        .card-room-select:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning"> Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center collapsed" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5"><span><i class="fas fa-users me-2"></i>Residents</span></a>
                <a href="booking_management.php" class="sidebar-link ps-5"><span><i class="fas fa-calendar-check me-2"></i>Bookings</span><?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?></a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5"><span><i class="fas fa-list-ol me-2"></i>Waitlist</span><?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?></a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5"><span><i class="fas fa-user-times me-2"></i>Deletion Req</span><?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?></a>
            </div>

            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5 active"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center collapsed" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center collapsed" role="button">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center collapsed" role="button">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if(($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin'): ?>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
                <?php endif; ?>
            </div>
            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3"><img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px;" class="rounded-circle shadow-sm"></a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Room Assignment & Moves</h4>
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
                                    <button class="btn btn-sm btn-primary px-3" onclick="openMoveModal(<?= $res['reservation_id'] ?>, '<?= addslashes($res['full_name']) ?>', '<?= addslashes($room_display) ?>')">
                                        <i class="fas fa-exchange-alt me-1"></i> Move
                                    </button>
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
                                foreach($room['occupants'] as $o) {
                                    if($o['bed_preference'] == 'Upper Bunk') $taken_upper++;
                                    elseif($o['bed_preference'] == 'Lower Bunk') $taken_lower++;
                                    else $taken_any++;
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
                            ?>
                            <div class="col-md-4 col-lg-3 room-card-item" data-floor="<?= $room['floor'] ?>">
                                <div class="card card-room-select h-100 shadow-sm border-0">
                                    <img src="../assets/images/<?= $room['image'] ?>" class="card-img-top" style="height: 120px; object-fit: cover;">
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0"><?= $room_display ?></h6>
                                            <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });
    document.getElementById("sidebar-toggle").addEventListener("click", function(e) { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); });

    function openMoveModal(resId, name, currentRoom) {
        document.getElementById('formResId').value = resId;
        document.getElementById('moveGuestName').innerText = name;
        document.getElementById('moveCurrentRoom').innerText = currentRoom;
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

    function filterRooms() {
        const floor = document.getElementById('floorFilter').value;
        const cards = document.querySelectorAll('.room-card-item');
        
        cards.forEach(card => {
            if (floor === 'all' || card.getAttribute('data-floor') === floor) {
                card.style.display = 'block';
                card.classList.add('animate-fade-in');
            } else {
                card.style.display = 'none';
                card.classList.remove('animate-fade-in');
            }
        });
    }
</script>
</body>
</html>
