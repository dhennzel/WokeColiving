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
    
    // Check if user already has an active key
    $check_user = mysqli_query($conn, "SELECT id FROM key_transactions WHERE user_id=$user_id AND status='Active'");
    if (mysqli_num_rows($check_user) > 0) {
        header("Location: admin_keys.php?msg=user_has_key");
        exit;
    }

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
if (isset($_GET['action']) && $_GET['action'] == 'return') {
    $trans_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $key_id = isset($_GET['key_id']) ? (int)$_GET['key_id'] : 0;
    
    if ($trans_id > 0) {
        $t_q = mysqli_query($conn, "SELECT key_id, user_id FROM key_transactions WHERE id=$trans_id");
        if ($t = mysqli_fetch_assoc($t_q)) {
            $k_id = $t['key_id'];
            mysqli_query($conn, "UPDATE key_transactions SET status='Returned', returned_at=NOW() WHERE id=$trans_id");
            mysqli_query($conn, "UPDATE `keys` SET status='Available' WHERE id=$k_id");
            
            send_notification($conn, $t['user_id'], "🔑 <strong>Key Returned</strong><br>Key (ID: $k_id) has been marked as returned.", "Key System");
            log_activity($conn, $t['user_id'], "Key Returned", "Key ID $k_id marked as returned by $admin_username");
        }
    } elseif ($key_id > 0) {
        // Fallback for out-of-sync keys stuck in Released state
        mysqli_query($conn, "UPDATE key_transactions SET status='Returned', returned_at=NOW() WHERE key_id=$key_id AND status='Active'");
        mysqli_query($conn, "UPDATE `keys` SET status='Available' WHERE id=$key_id");
    }
    
    trigger_update($conn);
    header("Location: admin_keys.php?msg=returned");
    exit;
}

// Get show_hidden parameter
$show_hidden = true; // Always show hidden rooms on this page

// Fetch all rooms with occupancy and key info
$raw_rooms = get_all_rooms_with_occupancy($conn, $show_hidden);

// Filter out duplicate rooms and safely merge keys
$clean_rooms = [];
foreach ($raw_rooms as $room) {
    $r_id = $room['room_id'];
    
    if (!isset($clean_rooms[$r_id])) {
        // First time seeing this room. Re-index keys by key_id so we don't double-count later.
        $unique_keys = [];
        if (!empty($room['all_keys'])) {
            foreach ($room['all_keys'] as $k) {
                $unique_keys[$k['key_id']] = $k; 
            }
        }
        $room['all_keys'] = $unique_keys;
        $clean_rooms[$r_id] = $room;
    } else {
        // Room duplicate found! Update occupancy if this row has the real tenant data
        if ($room['occupied_count'] > $clean_rooms[$r_id]['occupied_count']) {
            $clean_rooms[$r_id]['occupied_count'] = $room['occupied_count'];
            $clean_rooms[$r_id]['occupancy_status'] = $room['occupancy_status'];
        }
        
        // Safely add keys without duplicating them (This fixes the 31 instead of 30 issue)
        if (!empty($room['all_keys'])) {
            foreach ($room['all_keys'] as $k) {
                $clean_rooms[$r_id]['all_keys'][$k['key_id']] = $k;
            }
        }
    }
}

// Reset the arrays back to normal so the rest of your page can read them perfectly
foreach ($clean_rooms as &$room) {
    $room['all_keys'] = array_values($room['all_keys'] ?? []);
}
unset($room);
$all_rooms = array_values($clean_rooms);

// Map bed preference to keys
foreach ($all_rooms as &$room) {
    if (!empty($room['all_keys']) && !empty($room['occupants'])) {
        $bed_map = [];
        foreach ($room['occupants'] as $occ) {
            $bed_map[$occ['user_id']] = $occ['bed_preference'];
        }
        foreach ($room['all_keys'] as &$key) {
            if ($key['key_status'] == 'Released' && isset($key['key_holder_id']) && isset($bed_map[$key['key_holder_id']])) {
                $key['holder_bed'] = $bed_map[$key['key_holder_id']];
            }
        }
    }
}
unset($room, $key);

// Group rooms by type
$grouped_rooms = [];
$room_type_order = ['Single', '4-Bed', '6-Bed']; // To maintain order
foreach ($all_rooms as $room) {
    $type = $room['room_type'] ?? 'Other';
    if (!isset($grouped_rooms[$type])) {
        $grouped_rooms[$type] = [];
    }
    $grouped_rooms[$type][] = $room;
}

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
$total_keys = 0;
$released_keys = 0;
foreach($all_rooms as $room){
    if (!empty($room['all_keys'])) {
        $total_keys += count($room['all_keys']);
        foreach($room['all_keys'] as $key) {
            if ($key['key_status'] == 'Released') {
                $released_keys++;
            }
        }
    }
}
$available_keys = $total_keys - $released_keys;

// Released keys count for new button
$released_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM key_transactions WHERE status='Active'"))['c'];

// Sidebar counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

// Check Room 501 Status for Auto-Popup
$check_501 = mysqli_query($conn, "SELECT COUNT(*) as c FROM `keys` k JOIN rooms r ON k.reference_id = r.room_id WHERE (r.room_name = '501' OR r.room_number = '501') AND k.status = 'Released'");
$show_501_modal = (mysqli_fetch_assoc($check_501)['c'] > 0);

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
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-custom { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: white; }
        .card-room { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; background: white; }
        .card-room:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .card-room img { height: 150px; object-fit: cover; width: 100%; }
        
        .key-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; transition: 0.3s; }
        .key-card:hover { background-color: #f8f9fa; border-color: var(--primary-green); }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-vacant { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-full { background-color: #f8d7da; color: #721c24; }
        .status-maintenance { background-color: #e2e3e5; color: #383d41; }
        
        .room-card-clickable {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 2px solid transparent;
        }
        .room-card-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
            border-color: var(--primary-green) !important;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Key Monitoring System</h1>
                <?php if($show_501_modal): ?>
                <button class="btn btn-danger btn-sm" onclick="openRoom501UnreleaseModal()">
                    <i class="fas fa-exclamation-triangle me-2"></i>Room 501 Alert
                </button>
                <?php endif; ?>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'user_has_key'): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-1"></i> Failed: This user already has an active key. Only one key per account is allowed.</div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <?php 
                        $msg = $_GET['msg'];
                        if($msg == 'released') echo 'Key released successfully!';
                        elseif($msg == 'returned') echo 'Key returned successfully!';
                        ?>
                    </div>
                <?php endif; ?>
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
                    <?php if(!isset($grouped_rooms[$type]) || empty($grouped_rooms[$type])) continue; ?>
                    <?php 
                    $rooms_in_type = $grouped_rooms[$type];
                    $type_total = count($rooms_in_type);
                    $type_released = 0;
                    foreach($rooms_in_type as $room){
                        if(isset($room['key_info']['key_status']) && $room['key_info']['key_status'] == 'Released'){
                            $type_released++;
                        }
                    }
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
        </main>
    </div>
</div>

<!-- Modals -->
<?php foreach($room_type_order as $type): ?>
<?php if(!isset($grouped_rooms[$type]) || empty($grouped_rooms[$type])) continue; ?>
<?php $rooms_in_type = $grouped_rooms[$type]; ?>
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
                    <?php foreach($rooms_in_type as $room): ?>
                    <?php
                        $room_display = $room['room_name'];
                        if (!empty($room['room_number'])) {
                            $room_display = "Room " . $room['room_number'];
                        } elseif (is_numeric($room['room_name'])) {
                            $room_display = "Room " . $room['room_name'];
                        }
                        $floor = $room['floor'] ?? 2;
                        
                        $status_class = 'status-vacant';
                        if($room['occupancy_status'] == 'Fully Occupied') $status_class = 'status-full';
                        elseif($room['occupancy_status'] == 'Partially Occupied') $status_class = 'status-partial';
                        elseif($room['occupancy_status'] == 'Maintenance') $status_class = 'status-maintenance';
                    ?>
                    <div class="col-md-6 col-lg-4 key-item" data-floor="<?= $room['floor'] ?>">
                        <div class="card card-custom h-100 room-card-clickable" style="overflow: hidden; cursor: pointer;" onclick="openRoomKeysModal(this)" data-room-id="<?= $room['room_id'] ?>" data-room-name="<?= htmlspecialchars($room_display, ENT_QUOTES) ?>" data-keys="<?= htmlspecialchars(json_encode($room['all_keys'] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                            <img src="../assets/images/<?= $room['image'] ?>" style="height: 150px; object-fit: cover; width: 100%;">
                            <div class="card-body d-flex flex-column">
                                <!-- Room Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?= $room_display ?></h6>
                                        <small class="text-muted"><i class="fas fa-building me-1"></i> <?= $floor ?>F | <?= $room['room_type'] ?></small>
                                    </div>
                                    <span class="status-badge <?= $status_class ?>"><?= $room['occupancy_status'] ?></span>
                                </div>

                                <!-- Occupancy Bar -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><i class="fas fa-bed me-1"></i> Bed Availability</span>
                                        <span class="fw-bold"><?= $room['occupied_count'] ?>/<?= $room['total_beds'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $percent = $room['total_beds'] > 0 ? ($room['occupied_count'] / $room['total_beds']) * 100 : 0;
                                        $bar_class = 'bg-success';
                                        if($percent >= 100) $bar_class = 'bg-danger';
                                        elseif($percent > 0) $bar_class = 'bg-warning';
                                        ?>
                                        <div class="progress-bar <?= $bar_class ?>" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto pt-3 border-top text-center text-success fw-bold small pb-1">
                                    <i class="fas fa-key me-1"></i> Click to Manage Keys
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

<!-- Confirm Unrelease Modal -->
<div class="modal fade" id="confirmUnreleaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Unrelease</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to unrelease <strong id="unreleaseKeyName"></strong> from <strong id="unreleaseHolderName"></strong>?</p>
                <p class="text-muted small mt-2">This action will mark the key as 'Available' and record the return time.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmUnreleaseBtn" class="btn btn-danger rounded-pill px-4">Yes, Unrelease</a>
            </div>
        </div>
    </div>
</div>

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

<!-- Unrelease Keys List Modal -->
<div class="modal fade" id="unreleaseListModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i> Released Keys Management
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <input type="text" id="searchReleased" class="form-control form-control-sm" placeholder="Search by room, key, holder...">
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadReleasedKeys()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="releasedKeysTable">
                        <thead class="table-warning">
                            <tr>
                                <th>Room</th>
                                <th>Key</th>
                                <th>Holder</th>
                                <th>Released</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" class="text-center text-muted">Click Refresh or open to load released keys...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Keys Modal -->
<div class="modal fade" id="roomKeysModal" tabindex="-1" aria-hidden="true" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-key me-2"></i>Key Status: <span id="keysModalRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4" id="keysModalContent">
                <!-- Content loaded via JS -->
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
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

function openReleaseModal(id, name, roomId, roomName) {
    var keysModalObj = bootstrap.Modal.getInstance(document.getElementById('roomKeysModal'));
    if (keysModalObj) keysModalObj.hide();

    document.getElementById('modalKeyId').value = id;
    document.getElementById('modalKeyName').innerText = name;
    document.getElementById('modalRoomName').innerText = roomName;
    
    // Show modal first
    var modal = new bootstrap.Modal(document.getElementById('releaseModal'));
    modal.show();
    
    // Fetch tenants for this specific room
    var select = document.getElementById('tenantSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    fetch('get_room_tenants.php?room_id=' + roomId)
        .then(response => response.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.toLowerCase().includes('<html')) {
                    window.location.reload();
                }
                throw new Error("Invalid JSON response");
            }
        })
        .then(data => {
            select.innerHTML = '<option value="">-- Choose Tenant --</option>';
            if (data.length === 0) {
                select.innerHTML = '<option value="">No eligible tenants (or all have keys)</option>';
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

function confirmUnrelease(transId, keyName, holderName, keyId = 0) {
    var keysModalObj = bootstrap.Modal.getInstance(document.getElementById('roomKeysModal'));
    if (keysModalObj) keysModalObj.hide();

    document.getElementById('unreleaseKeyName').innerText = keyName;
    document.getElementById('unreleaseHolderName').innerText = holderName;
    document.getElementById('confirmUnreleaseBtn').href = `?action=return&id=${transId}&key_id=${keyId}`;
    var myModal = new bootstrap.Modal(document.getElementById('confirmUnreleaseModal'));
    myModal.show();
}

function openRoomKeysModal(element) {
    const roomId = element.getAttribute('data-room-id');
    const roomName = element.getAttribute('data-room-name');
    const keys = JSON.parse(element.getAttribute('data-keys') || '[]');
    
    document.getElementById('keysModalRoomName').innerText = roomName;
    const content = document.getElementById('keysModalContent');
    
    if (keys.length === 0) {
        content.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-key fa-3x mb-3 opacity-25"></i><h6 class="fw-bold">No Keys Configured</h6><p class="small">There are no keys registered for this room.</p></div>';
    } else {
        let html = '<div class="d-flex flex-column gap-2">';
        keys.forEach(key => {
            let bgColor = key.key_status === 'Available' ? '#d4edda' : '#fff3cd';
            let borderColor = key.key_status === 'Available' ? '#c3e6cb' : '#ffeeba';
            let actionBtn = '';
            
            const safeKeyName = (key.key_name || '').replace(/'/g, "\\'");
            const safeRoomName = (roomName || '').replace(/'/g, "\\'");
            const safeHolderName = (key.key_holder_name || 'Unknown User').replace(/'/g, "\\'");

            if (key.key_status === 'Available') {
                actionBtn = `<button class="btn btn-sm btn-primary rounded-pill px-3" onclick="event.stopPropagation(); openReleaseModal(${key.key_id}, '${safeKeyName}', ${roomId}, '${safeRoomName}')"><i class="fas fa-sign-out-alt me-1"></i> Release</button>`;
            } else {
                actionBtn = `<button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="event.stopPropagation(); confirmUnrelease(${key.trans_id || 0}, '${safeKeyName}', '${safeHolderName}', ${key.key_id})"><i class="fas fa-undo me-1"></i> Return</button>`;
            }

            let holderInfo = key.key_status === 'Released'
                ? `<div class="text-muted small mt-1"><i class="fas fa-user-tag me-1"></i><a href="view_user.php?uid=${key.key_holder_id || 0}" class="text-decoration-none fw-bold" target="_blank">${key.key_holder_name || 'Unknown User'}</a>
                   ${key.holder_bed ? `<div class="small text-primary mt-1 ms-3"><i class="fas fa-bed me-1"></i>${key.holder_bed}</div>` : ''}</div>`
                : `<div class="text-success small mt-1"><i class="fas fa-check-circle me-1"></i>Available in desk</div>`;
            
            html += `
            <div class="p-3 border rounded shadow-sm d-flex justify-content-between align-items-center" style="background-color: ${bgColor}; border-color: ${borderColor} !important;">
                <div>
                    <strong class="text-dark mb-0 d-block"><i class="fas fa-key me-2 text-secondary"></i>${key.key_name}</strong>
                    ${holderInfo}
                </div>
                <div>
                    ${actionBtn}
                </div>
            </div>`;
        });
        html += '</div>';
        content.innerHTML = html;
    }
    
    const keysModal = new bootstrap.Modal(document.getElementById('roomKeysModal'));
    keysModal.show();
}

// Unrelease Modal functions
function openUnreleaseModal() {
    loadReleasedKeys();
    // Reset title in case it was changed by 501 modal
    document.querySelector('#unreleaseListModal .modal-title').innerHTML = 
        '<i class="fas fa-key me-2"></i> Released Keys Management';
    new bootstrap.Modal(document.getElementById('unreleaseListModal')).show();
}

function openRoom501UnreleaseModal() {
    loadReleasedKeys('501');
    const modalTitle = document.querySelector('#unreleaseListModal .modal-title');
    modalTitle.innerHTML = '<i class="fas fa-key me-2"></i> Room 501 Key Unrelease';
    new bootstrap.Modal(document.getElementById('unreleaseListModal')).show();
}

function loadReleasedKeys(room = '') {
    let url = 'get_released_keys.php';
    if(room) url += '?room=' + encodeURIComponent(room);
    
    fetch(url)
        .then(response => response.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.toLowerCase().includes('<html')) {
                    window.location.reload();
                }
                throw new Error("Invalid JSON response");
            }
        })
        .then(data => {
            const tbody = document.querySelector('#releasedKeysTable tbody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No currently released keys.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(row => {
                const safeKeyName = (row.key_name || '').replace(/'/g, "\\'");
                const safeHolderName = (row.holder_name || 'Unknown User').replace(/'/g, "\\'");
                return `
                <tr>
                    <td><strong>${row.room_number || row.room_name || 'N/A'}</strong></td>
                    <td><code>${row.key_name}</code></td>
                    <td>${row.holder_name || 'Unknown User'}
                        ${row.bed_preference ? `<br><small class="text-muted"><i class="fas fa-bed me-1"></i>${row.bed_preference}</small>` : ''}</td>
                    <td>${new Date(row.released_at).toLocaleDateString()}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmUnrelease(${row.trans_id || 0}, '${safeKeyName}', '${safeHolderName}', ${row.key_id})">
                            <i class="fas fa-undo"></i> Unrelease
                        </button>
                    </td>
                </tr>
            `}).join('');
        })
        .catch(err => {
            console.error('Error loading released keys:', err);
            document.querySelector('#releasedKeysTable tbody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
        });
}

<?php if($show_501_modal): ?>
document.addEventListener('DOMContentLoaded', function() {
    openRoom501UnreleaseModal();
});
<?php endif; ?>

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
