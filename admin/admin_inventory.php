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

// --- AUTO-SYNC DEFAULT INVENTORY PER ROOM ---
// Ensures every active room has the base items tracking its total beds
$rooms_sync_q = mysqli_query($conn, "SELECT room_id, total_beds FROM rooms WHERE is_archived=0");
$defaults = [
    'Beds' => 'Furniture', 
    'Bed Sheets' => 'Linens', 
    'Pillows' => 'Linens', 
    'Pillow Cases' => 'Linens'
];

while($r = mysqli_fetch_assoc($rooms_sync_q)) {
    $rid = $r['room_id'];
    $beds = $r['total_beds'];
    foreach($defaults as $item => $cat) {
        $chk = mysqli_query($conn, "SELECT id, quantity FROM inventory_items WHERE room_id=$rid AND item_name='$item'");
        if(mysqli_num_rows($chk) == 0) {
            mysqli_query($conn, "INSERT INTO inventory_items (item_name, category, room_id, quantity, status) VALUES ('$item', '$cat', $rid, $beds, 'Good')");
        } else {
            $existing = mysqli_fetch_assoc($chk);
            if($existing['quantity'] != $beds) {
                $item_id = $existing['id'];
                mysqli_query($conn, "UPDATE inventory_items SET quantity=$beds WHERE id=$item_id");
            }
        }
    }
}

// --- HANDLE FORM SUBMISSIONS ---
// Handle Add/Update Custom Room Item
if(isset($_POST['save_room_item'])) {
    $id = (int)$_POST['item_id'];
    $room_id = (int)$_POST['room_id'];
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $qty = (int)$_POST['quantity'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    if($id > 0) {
        mysqli_query($conn, "UPDATE inventory_items SET item_name='$name', category='$category', quantity=$qty, status='$status', notes='$notes' WHERE id=$id");
        $msg = "Item updated successfully.";
    } else {
        mysqli_query($conn, "INSERT INTO inventory_items (item_name, category, room_id, quantity, status, notes) VALUES ('$name', '$category', $room_id, $qty, '$status', '$notes')");
        $msg = "Item added to room successfully.";
    }
    
    log_activity($conn, 0, "Room Inventory Updated", "Item '$name' updated for Room ID $room_id by $admin_username.");
    header("Location: admin_inventory.php?msg=" . urlencode($msg));
    exit;
}

// Handle Delete Item
if(isset($_GET['delete_item'])) {
    $id = (int)$_GET['delete_item'];
    mysqli_query($conn, "DELETE FROM inventory_items WHERE id=$id");
    header("Location: admin_inventory.php?msg=" . urlencode("Item removed from room."));
    exit;
}

// --- FETCH DATA ---
// Get Rooms via centralized logic
$raw_rooms = get_all_rooms_with_occupancy($conn);
$rooms = [];
$seen_names = [];
foreach ($raw_rooms as $room) {
    $name = !empty($room['room_number']) ? trim($room['room_number']) : trim($room['room_name']);
    $display_key = strtolower($name);
    if (!isset($seen_names[$display_key])) {
        $seen_names[$display_key] = count($rooms);
        $rooms[] = $room;
    } else {
        $idx = $seen_names[$display_key];
        if ($room['occupied_count'] > $rooms[$idx]['occupied_count']) {
            $rooms[$idx] = $room;
        }
    }
}

// Fetch Inventory specific to rooms
$inv_q = mysqli_query($conn, "SELECT * FROM inventory_items ORDER BY category, item_name");
$room_inventory = [];
while($row = mysqli_fetch_assoc($inv_q)) {
    $room_inventory[$row['room_id']][] = $row;
}

// Group rooms by room_type
$grouped_rooms = [];
foreach ($rooms as &$room) {
    $room['inventory'] = $room_inventory[$room['room_id']] ?? [];
    $type = $room['room_type'];
    if (!isset($grouped_rooms[$type])) $grouped_rooms[$type] = [];
    $grouped_rooms[$type][] = $room;
}
unset($room);

// Sidebar Badges Setup
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
    <title>Room Inventory Management | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .card-room {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg, 16px);
            overflow: hidden;
            transition: all var(--transition-speed);
            cursor: pointer;
            background: var(--bg-surface);
            box-shadow: var(--shadow-sm);
        }
        .card-room:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-green);
        }
        .card-room img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }
        .card-room-summary {
            cursor: pointer;
        }
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
                <div>
                    <h1>Room Inventory</h1>
                    <small class="text-muted">Select a room type to manage the items allocated inside specific rooms.</small>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Room Type Cards -->
            <div class="row g-4" id="roomTypesContainer">
                <?php foreach($grouped_rooms as $type => $rooms_in_type): 
                    $type_total_rooms = count($rooms_in_type);
                    $type_total_beds = array_sum(array_column($rooms_in_type, 'total_beds'));
                    $first_room = $rooms_in_type[0] ?? null;
                    if (!$first_room) continue;
                    $image = $first_room['image'];
                ?>
                <div class="col-md-4">
                    <div class="card card-room card-room-summary h-100" onclick="openTypeModal('<?= md5($type) ?>')">
                        <img src="../assets/images/<?= $image ?>" alt="<?= $type ?>">
                        <div class="card-body text-center d-flex flex-column">
                            <h3 class="fw-bold text-dark mb-2"><?= $type ?></h3>
                            <div class="d-flex justify-content-center gap-3 text-muted small mb-3 mt-auto">
                                <span><i class="fas fa-door-open me-1"></i> <?= $type_total_rooms ?> Rooms</span>
                                <span><i class="fas fa-bed me-1"></i> <?= $type_total_beds ?> Beds</span>
                            </div>
                            <div class="alert alert-info py-2 mb-0 fw-bold w-100 text-dark">
                                <i class="fas fa-boxes me-1"></i> View Room Inventory
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </main>
    </div>
</div>

<!-- Modals for each room type -->
<?php foreach($grouped_rooms as $type => $rooms_in_type): ?>
<div class="modal fade" id="modal_<?= md5($type) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-layer-group me-2"></i><?= $type ?> Rooms</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="overflow-x: hidden;">
                <div class="row g-4 mobile-horizontal-scroll">
                    <?php foreach($rooms_in_type as $room): 
                        $room_display_name = !empty($room['room_number']) ? "Room " . $room['room_number'] : $room['room_name'];
                        $is_shared = ($room['room_type'] == '4-Bed' || $room['room_type'] == '6-Bed');
                        $total_beds = $room['total_beds'];
                        
                        $taken_upper = 0; $taken_lower = 0; $taken_any = 0;
                        $has_whole_room = false;
                        foreach($room['occupants'] as $occ) {
                            if($occ['bed_preference'] == 'Whole Room') {
                                $has_whole_room = true;
                            } elseif($occ['bed_preference'] == 'Upper Bunk') {
                                $taken_upper++;
                            } elseif($occ['bed_preference'] == 'Lower Bunk') {
                                $taken_lower++;
                            } else {
                                $taken_any++;
                            }
                        }
                        if ($has_whole_room) {
                            $taken_any = $total_beds;
                            $taken_upper = 0;
                            $taken_lower = 0;
                        }
                        
                        $cap_upper = floor($total_beds / 2);
                        $cap_lower = ceil($total_beds / 2);
                        
                        $avail_upper = max(0, $cap_upper - $taken_upper);
                        $avail_lower = max(0, $cap_lower - $taken_lower);
                        
                        if($taken_any > 0) {
                            $fill_lower = min($avail_lower, $taken_any);
                            $avail_lower -= $fill_lower;
                            $taken_any -= $fill_lower;
                            $avail_upper -= $taken_any;
                            $avail_upper = max(0, $avail_upper);
                        }
                        
                        $available_beds = $avail_upper + $avail_lower;
                        if(!$is_shared) {
                            $available_beds = max(0, $total_beds - $taken_any - $taken_upper - $taken_lower);
                        }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-room room-card-clickable h-100" 
                             data-room-id="<?= $room['room_id'] ?>" 
                             data-room-name="<?= htmlspecialchars($room_display_name, ENT_QUOTES) ?>" 
                             data-inventory="<?= htmlspecialchars(json_encode($room['inventory']), ENT_QUOTES, 'UTF-8') ?>" 
                             onclick="openRoomInventoryModal(this)">
                            <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room_display_name ?>">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title fw-bold text-dark mb-0"><?= $room_display_name ?></h5>
                                    <span class="badge bg-light text-dark border"><?= $room['floor'] ?>F</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span><i class="fas fa-bed me-1"></i> Total Beds: <?= $total_beds ?></span>
                                        <span class="<?= $available_beds > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $available_beds ?> Available</span>
                                    </div>
                                    <?php if($is_shared): ?>
                                    <div class="bg-light p-2 rounded small">
                                        <div class="d-flex justify-content-between">
                                            <span>Upper:</span> <span class="<?= $avail_upper > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $avail_upper ?> left</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Lower:</span> <span class="<?= $avail_lower > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $avail_lower ?> left</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto pt-3 text-center border-top">
                                    <span class="btn btn-sm btn-outline-primary w-100 fw-bold"><i class="fas fa-boxes me-1"></i> Manage Inventory</span>
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

<!-- Room Specific Inventory Modal -->
<div class="modal fade" id="roomInventoryModal" tabindex="-1" aria-hidden="true" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-boxes me-2"></i>Inventory: <span id="invModalRoomName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm btn-custom fw-bold rounded-pill" onclick="openManageItemModal(null)"><i class="fas fa-plus me-1"></i> Add Custom Item</button>
                </div>
                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roomInventoryTableBody">
                            <!-- Injected via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Custom Item Modal -->
<div class="modal fade" id="manageItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="manageItemModalTitle">Add Custom Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="form_item_id" value="0">
                    <input type="hidden" name="room_id" id="form_room_id" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Name</label>
                        <input type="text" name="item_name" id="form_item_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" id="form_category" class="form-select">
                                <option value="Linens">Linens</option>
                                <option value="Furniture">Furniture</option>
                                <option value="Appliances">Appliances</option>
                                <option value="Cleaning Supplies">Cleaning Supplies</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" name="quantity" id="form_quantity" class="form-control" min="1" required>
                            <small id="qty_help" class="text-muted" style="display:none; font-size: 0.7rem;">Auto-synced with room capacity.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Condition Status</label>
                            <select name="status" id="form_status" class="form-select">
                                <option value="Good">Good</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Repair">Repair</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Notes</label>
                            <input type="text" name="notes" id="form_notes" class="form-control" placeholder="Optional notes...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_room_item" class="btn btn-custom rounded-pill">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
let currentRoomId = 0;

function openTypeModal(typeHash) {
    new bootstrap.Modal(document.getElementById('modal_' + typeHash)).show();
}

function openRoomInventoryModal(element) {
    const roomId = element.getAttribute('data-room-id');
    const roomName = element.getAttribute('data-room-name');
    const items = JSON.parse(element.getAttribute('data-inventory') || '[]');
    
    currentRoomId = roomId;
    document.getElementById('invModalRoomName').innerText = roomName;
    
    const tbody = document.getElementById('roomInventoryTableBody');
    const defaults = ['Beds', 'Bed Sheets', 'Pillows', 'Pillow Cases'];
    tbody.innerHTML = '';
    
    if(items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No inventory items found for this room.</td></tr>';
    } else {
        items.forEach(item => {
            let badgeClass = 'bg-success';
            if(item.status === 'Damaged') badgeClass = 'bg-danger';
            else if(item.status === 'Repair') badgeClass = 'bg-warning text-dark';
            else if(item.status === 'Lost') badgeClass = 'bg-dark';
            
            let safeItemStr = JSON.stringify(item).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
            
            let deleteAction = defaults.includes(item.item_name) ? 
                `<button class="btn btn-sm btn-outline-secondary" disabled title="Cannot remove base items"><i class="fas fa-trash"></i></button>` : 
                `<a href="?delete_item=${item.id}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item from the room?');"><i class="fas fa-trash"></i></a>`;

            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold text-dark">${item.item_name}</td>
                <td><span class="badge bg-secondary">${item.category}</span></td>
                <td class="text-center fw-bold fs-6">${item.quantity}</td>
                <td><span class="badge ${badgeClass}">${item.status}</span></td>
                <td class="small text-muted">${item.notes || '-'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick='openManageItemModal(${safeItemStr})'><i class="fas fa-edit"></i> Edit</button>
                    ${deleteAction}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    new bootstrap.Modal(document.getElementById('roomInventoryModal')).show();
}

function openManageItemModal(item) {
    document.getElementById('form_room_id').value = currentRoomId;
    const nameInput = document.getElementById('form_item_name');
    const qtyInput = document.getElementById('form_quantity');
    const qtyHelp = document.getElementById('qty_help');
    const defaults = ['Beds', 'Bed Sheets', 'Pillows', 'Pillow Cases'];
    
    if(item) {
        document.getElementById('manageItemModalTitle').innerText = 'Edit Item';
        document.getElementById('form_item_id').value = item.id;
        nameInput.value = item.item_name;
        document.getElementById('form_category').value = item.category;
        qtyInput.value = item.quantity;
        document.getElementById('form_status').value = item.status;
        document.getElementById('form_notes').value = item.notes || '';
        
        // Lock default item names from being changed to avoid auto-sync duplicates
        if (defaults.includes(item.item_name)) {
            nameInput.readOnly = true;
            qtyInput.readOnly = true;
            qtyHelp.style.display = 'block';
        } else {
            nameInput.readOnly = false;
            qtyInput.readOnly = false;
            qtyHelp.style.display = 'none';
        }
    } else {
        document.getElementById('manageItemModalTitle').innerText = 'Add Custom Item';
        document.getElementById('form_item_id').value = '0';
        nameInput.value = '';
        nameInput.readOnly = false;
        document.getElementById('form_category').value = 'General';
        qtyInput.value = '1';
        qtyInput.readOnly = false;
        qtyHelp.style.display = 'none';
        document.getElementById('form_status').value = 'Good';
        document.getElementById('form_notes').value = '';
    }
    
    new bootstrap.Modal(document.getElementById('manageItemModal')).show();
}
</script>
</body>
</html>