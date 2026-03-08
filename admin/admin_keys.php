<?php
session_start();
include("../db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Release Key
if (isset($_POST['release_key'])) {
    $key_id = (int)$_POST['key_id'];
    $user_id = (int)$_POST['user_id'];
    
    $chk = mysqli_query($conn, "SELECT status FROM `keys` WHERE id=$key_id");
    $k = mysqli_fetch_assoc($chk);
    
    if ($k['status'] == 'Available') {
        mysqli_query($conn, "INSERT INTO key_transactions (key_id, user_id) VALUES ($key_id, $user_id)");
        mysqli_query($conn, "UPDATE `keys` SET status='Released' WHERE id=$key_id");
        
        send_notification($conn, $user_id, "🔑 <strong>Key Assigned</strong><br>You have been assigned a key (ID: $key_id). Please keep it safe.", "Key System");
        log_activity($conn, $user_id, "Key Released", "Key ID $key_id released to user by $admin_username");
        trigger_update($conn);
        header("Location: admin_keys.php?msg=released");
        exit;
    }
}

// Handle Return Key
if (isset($_GET['action']) && $_GET['action'] == 'return' && isset($_GET['id'])) {
    $trans_id = (int)$_GET['id'];
    
    $t_q = mysqli_query($conn, "SELECT key_id, user_id FROM key_transactions WHERE id=$trans_id");
    if ($t = mysqli_fetch_assoc($t_q)) {
        $key_id = $t['key_id'];
        mysqli_query($conn, "UPDATE key_transactions SET status='Returned', returned_at=NOW() WHERE id=$trans_id");
        mysqli_query($conn, "UPDATE `keys` SET status='Available' WHERE id=$key_id");
        
        send_notification($conn, $t['user_id'], "🔑 <strong>Key Returned</strong><br>Key (ID: $key_id) has been marked as returned.", "Key System");
        log_activity($conn, $t['user_id'], "Key Returned", "Key ID $key_id marked as returned by $admin_username");
        trigger_update($conn);
        header("Location: admin_keys.php?msg=returned");
        exit;
    }
}

// Fetch all keys with room info
$all_keys_q = mysqli_query($conn, "
    SELECT k.*, r.room_type, r.room_number, r.room_name, r.image, r.floor,
           kt.id as trans_id, kt.user_id, kt.released_at, kt.status as trans_status,
           CONCAT(u.last_name, ', ', u.first_name) as holder_name 
    FROM `keys` k
    LEFT JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    LEFT JOIN key_transactions kt ON k.id = kt.key_id AND kt.status = 'Active'
    LEFT JOIN users u ON kt.user_id = u.user_id
    WHERE k.type = 'Room'
    ORDER BY r.room_type, r.room_number, k.key_name
");
$all_keys = [];
while($row = mysqli_fetch_assoc($all_keys_q)) {
    $all_keys[] = $row;
}

// Group keys by room type
$grouped_keys = [];
$room_type_order = ['Single', '4-Bed', '6-Bed'];
foreach($all_keys as $key) {
    $type = $key['room_type'] ?? 'Other';
    if(!isset($grouped_keys[$type])) {
        $grouped_keys[$type] = [];
    }
    $grouped_keys[$type][] = $key;
}

// Fetch Users for Dropdown - Only users with approved room reservations
$users_q = mysqli_query($conn, "SELECT DISTINCT u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name 
    FROM users u 
    JOIN reservations r ON u.user_id = r.user_id 
    WHERE r.status = 'Approved' AND u.role = 'user' 
    ORDER BY u.last_name");

// Fetch History
$history_q = mysqli_query($conn, "
    SELECT kt.*, kt.status as trans_status, k.key_name, k.type, r.room_type, CONCAT(u.last_name, ', ', u.first_name) as full_name 
    FROM key_transactions kt 
    JOIN `keys` k ON kt.key_id = k.id 
    LEFT JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    JOIN users u ON kt.user_id = u.user_id 
    ORDER BY kt.released_at DESC LIMIT 50
");

// Calculate Stats
$total_keys = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM `keys` WHERE type='Room'"));
$released_keys = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM `keys` WHERE type='Room' AND status='Released'"));
$available_keys = $total_keys - $released_keys;

// Sidebar counts
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
    <title>Key Monitoring | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary-green: <?= $theme['primary'] ?>; --dark-green: <?= $theme['dark'] ?>; --accent-yellow: <?= $theme['accent'] ?>; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        
        .card-room { border: none; border-radius: 15px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
        .card-room:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .card-room img { height: 150px; object-fit: cover; width: 100%; }
        
        .key-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; transition: 0.3s; }
        .key-card:hover { background-color: #f8f9fa; border-color: var(--primary-green); }
        .key-card.released { border-left: 4px solid #ffc107; }
        .key-card.available { border-left: 4px solid #28a745; }
        
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<div id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            
            <!-- Front Desk -->
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="frontDeskSubmenu">
                <a href="residents.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Residents</span>
                </a>
                <a href="booking_management.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-check me-2"></i>Bookings</span>
                    <?php if($pending_res > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_res ?></span><?php endif; ?>
                </a>
                <a href="admin_waitlist.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-ol me-2"></i>Waitlist</span>
                    <?php if($waitlist_count > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= $waitlist_count ?></span><?php endif; ?>
                </a>
                <a href="admin_deletion_requests.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-times me-2"></i>Deletion Req</span>
                    <?php if($del_req_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $del_req_count ?></span><?php endif; ?>
                </a>
            </div>

            <!-- Facilities -->
            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true"><span><i class="fas fa-building me-2"></i>Facilities</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse show" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5 active"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <!-- Finance & Reports -->
            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="financeSubmenu">
                <?php if($is_super): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <!-- Operations -->
            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-wrench me-2"></i>Maintenance</span><?php if($pending_maint > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span><?php endif; ?></a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center"><span><i class="fas fa-broom me-2"></i>Housekeeping</span><?php if($pending_house > 0): ?><span class="badge bg-danger rounded-pill"><?= $pending_house ?></span><?php endif; ?></a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <!-- System Settings -->
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button"><span><i class="fas fa-cog me-2"></i>System Settings</span><i class="fas fa-chevron-down small"></i></a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <?php if($is_super): ?>
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
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                </a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Key Monitoring System</h4>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= $_GET['msg'] == 'released' ? 'Key released successfully!' : 'Key returned successfully!' ?></div>
            <?php endif; ?>

            <div class="row mb-4 g-3">
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-primary mb-0"><?= $total_keys ?></h3>
                        <small class="text-muted">Total Keys</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-warning mb-0"><?= $released_keys ?></h3>
                        <small class="text-muted">Keys Released</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card card-custom p-3 text-center">
                        <h3 class="fw-bold text-success mb-0"><?= $available_keys ?></h3>
                        <small class="text-muted">Keys Available</small>
                    </div>
                </div>
            </div>

            <!-- Key Type Cards (Single, 4-Bed, 6-Bed) -->
            <div class="row g-4">
                <?php foreach($room_type_order as $type): ?>
                    <?php if(!isset($grouped_keys[$type]) || empty($grouped_keys[$type])) continue; ?>
                    <?php 
                    $keys_in_type = $grouped_keys[$type];
                    $type_total = count($keys_in_type);
                    $type_released = count(array_filter($keys_in_type, function($k) { return $k['status'] == 'Released'; }));
                    $type_available = $type_total - $type_released;
                    $type_status = 'success';
                    if($type_available == 0) $type_status = 'danger';
                    elseif($type_released > 0) $type_status = 'warning';
                    ?>
                    <div class="col-md-4">
                        <div class="card card-room h-100" onclick="openKeyModal('<?= md5($type) ?>')">
                            <div class="card-body text-center bg-white">
                                <i class="fas fa-key fa-3x mb-3 text-success"></i>
                                <h3 class="fw-bold mb-2 text-dark"><?= $type ?> Room</h3>
                                <div class="d-flex justify-content-center gap-3 small mb-3 text-muted">
                                    <span><i class="fas fa-key me-1"></i> <?= $type_total ?> Keys</span>
                                </div>
                                <div class="alert alert-<?= $type_status ?> py-2 mb-0 fw-bold">
                                    <?= $type_available ?> Available
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><?= $type_released ?>/<?= $type_total ?> Released</small>
                                </div>
                                <div class="mt-3">
                                    <span class="btn btn-sm btn-outline-success"><i class="fas fa-eye me-1"></i> View Details</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Transaction History -->
            <div class="card card-custom p-4 mt-4">
                <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-history me-2"></i>Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Date</th><th>Key</th><th>Room Type</th><th>Holder</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while($h = mysqli_fetch_assoc($history_q)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($h['released_at'])) ?></td>
                                <td class="fw-bold"><?= $h['key_name'] ?></td>
                                <td><span class="badge bg-light text-dark"><?= $h['room_type'] ?? 'N/A' ?></span></td>
                                <td><?= $h['full_name'] ?></td>
                                <td><?php if($h['trans_status'] == 'Active'): ?><span class="badge bg-warning text-dark">Active</span><?php else: ?><span class="badge bg-success">Returned</span><?php endif; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php foreach($room_type_order as $type): ?>
<?php if(!isset($grouped_keys[$type]) || empty($grouped_keys[$type])) continue; ?>
<?php $keys_in_type = $grouped_keys[$type]; ?>
<div class="modal fade" id="key_<?= md5($type) ?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-key me-2"></i><?= $type ?> Room Keys</h5>
                <div class="d-flex align-items-center me-3 ms-auto">
                    <label class="small fw-bold me-2 text-muted">Filter:</label>
                    <select class="form-select form-select-sm" onchange="filterKeys(this, '<?= md5($type) ?>')">
                        <option value="all">All Floors</option>
                        <?php for($i=2; $i<=7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <?php foreach($keys_in_type as $key): ?>
                    <?php $is_released = $key['status'] == 'Released'; ?>
                    <?php $room_display = $key['room_number'] ? "Room " . $key['room_number'] : $key['key_name']; ?>
                    <div class="col-md-6 col-lg-4 key-item" data-floor="<?= $key['floor'] ?>">
                        <div class="card card-room h-100" style="cursor: default;">
                            <img src="../assets/images/<?= $key['image'] ?>" alt="<?= $key['room_name'] ?>">
                            <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?= $room_display ?></h6>
                                    <small class="text-muted"><?= $key['key_name'] ?> (<?= $key['floor'] ?>F)</small>
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                            <?php if($is_released && $key['holder_name']): ?>
                                <div class="alert alert-warning py-2 mb-2 small">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-dark">Released</span>
                                        <span class="text-muted"><?= date('M d', strtotime($key['released_at'])) ?></span>
                                    </div>
                                    <div class="text-truncate" title="<?= $key['holder_name'] ?>"><i class="fas fa-user me-1"></i> <?= $key['holder_name'] ?></div>
                                </div>
                                <a href="?action=return&id=<?= $key['trans_id'] ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Mark this key as returned?')">
                                    <i class="fas fa-undo me-1"></i> Return Key
                                </a>
                            <?php else: ?>
                                <div class="alert alert-success py-2 mb-2 small text-center">
                                    <i class="fas fa-check-circle me-1"></i> Available
                                </div>
                                <button class="btn btn-sm btn-primary w-100" onclick="openReleaseModal(<?= $key['id'] ?>, '<?= addslashes($key['key_name']) ?>', <?= $key['reference_id'] ?>)">
                                    <i class="fas fa-share me-1"></i> Release Key
                                </button>
                            <?php endif; ?>
                            </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Release Modal -->
<div class="modal fade" id="releaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Release Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Releasing: <strong id="modalKeyName"></strong></p>
                    <p class="small text-muted">Room: <span id="modalRoomName"></span></p>
                    <input type="hidden" name="key_id" id="modalKeyId">
                    <input type="hidden" name="release_key" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant (Assigned to this room)</label>
                        <select name="user_id" id="tenantSelect" class="form-select" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Release</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openKeyModal(typeId) {
    new bootstrap.Modal(document.getElementById('key_' + typeId)).show();
}

function filterKeys(select, typeId) {
    const floor = select.value;
    const modal = document.getElementById('key_' + typeId);
    const items = modal.querySelectorAll('.key-item');
    
    items.forEach(item => {
        if(floor === 'all' || item.getAttribute('data-floor') === floor) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function openReleaseModal(id, name, roomId) {
    document.getElementById('modalKeyId').value = id;
    document.getElementById('modalKeyName').innerText = name;
    
    // Show modal first
    var modal = new bootstrap.Modal(document.getElementById('releaseModal'));
    modal.show();
    
    // Fetch tenants for this specific room
    var select = document.getElementById('tenantSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    fetch('get_room_tenants.php?room_id=' + roomId)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Choose Tenant --</option>';
            if (data.length === 0) {
                select.innerHTML = '<option value="">No tenants found for this room</option>';
            } else {
                data.forEach(function(user) {
                    var option = document.createElement('option');
                    option.value = user.user_id;
                    option.textContent = user.full_name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            select.innerHTML = '<option value="">Error loading tenants</option>';
        });
}

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
setInterval(checkUpdates, 3000);
</script>
</body>
</html>
