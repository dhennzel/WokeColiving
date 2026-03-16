<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';

$current_page = basename($_SERVER['PHP_SELF']);

// Ensure bed_preference column exists to prevent errors
try {
    mysqli_query($conn, "SELECT bed_preference FROM reservations LIMIT 1");
} catch (mysqli_sql_exception $e) {
    mysqli_query($conn, "ALTER TABLE reservations ADD COLUMN bed_preference VARCHAR(50) DEFAULT 'Any'");
}

// Ensure is_archived column exists
$check_col_arch = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'is_archived'");
if(mysqli_num_rows($check_col_arch) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

$error = "";

// Handle archive room
if(isset($_GET['archive_id'])){
    $archive_id = (int)$_GET['archive_id'];
    try {
        mysqli_query($conn, "UPDATE rooms SET is_archived='1' WHERE room_id=$archive_id");
        trigger_update($conn);
        header("Location: admin_rooms.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        $error = "Cannot archive room: " . $e->getMessage();
    }
}

// Handle Price Settings Update
if(isset($_POST['update_prices'])){
    $prices = [
        'price_single' => $_POST['price_single'],
        'price_single_long' => $_POST['price_single_long'],
        'price_4bed_upper' => $_POST['price_4bed_upper'],
        'price_4bed_lower' => $_POST['price_4bed_lower'],
        'price_4bed_whole' => $_POST['price_4bed_whole'],
        'price_4bed_upper_long' => $_POST['price_4bed_upper_long'],
        'price_4bed_lower_long' => $_POST['price_4bed_lower_long'],
        'price_4bed_whole_long' => $_POST['price_4bed_whole_long'],
        'price_6bed_upper' => $_POST['price_6bed_upper'],
        'price_6bed_lower' => $_POST['price_6bed_lower'],
        'price_6bed_whole' => $_POST['price_6bed_whole'],
        'price_6bed_upper_long' => $_POST['price_6bed_upper_long'],
        'price_6bed_lower_long' => $_POST['price_6bed_lower_long'],
        'price_6bed_whole_long' => $_POST['price_6bed_whole_long']
    ];
    
    foreach($prices as $key => $val){
        $val = (float)$val;
        mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
    }

    // Bulk Update Existing Rooms to reflect new default prices
    // Single Room
    mysqli_query($conn, "UPDATE rooms SET total_price='{$prices['price_single']}', long_term_price_whole='{$prices['price_single_long']}' WHERE room_type='Single'");
    
    // 4-Bed Dorm
    mysqli_query($conn, "UPDATE rooms SET 
        price_upper='{$prices['price_4bed_upper']}', price_lower='{$prices['price_4bed_lower']}', price_whole='{$prices['price_4bed_whole']}',
        long_term_price_upper='{$prices['price_4bed_upper_long']}', long_term_price_lower='{$prices['price_4bed_lower_long']}', long_term_price_whole='{$prices['price_4bed_whole_long']}',
        total_price='{$prices['price_4bed_lower']}'
        WHERE room_type='4-Bed'");

    // 6-Bed Dorm
    mysqli_query($conn, "UPDATE rooms SET 
        price_upper='{$prices['price_6bed_upper']}', price_lower='{$prices['price_6bed_lower']}', price_whole='{$prices['price_6bed_whole']}',
        long_term_price_upper='{$prices['price_6bed_upper_long']}', long_term_price_lower='{$prices['price_6bed_lower_long']}', long_term_price_whole='{$prices['price_6bed_whole_long']}',
        total_price='{$prices['price_6bed_lower']}'
        WHERE room_type='6-Bed'");

    trigger_update($conn);
    header("Location: admin_rooms.php?msg=prices_updated");
    exit;
}

// Handle Individual Room Order Save (AJAX)
if(isset($_POST['save_individual_room_order'])){
    if(!$is_super) exit;
    $room_ids = json_decode($_POST['order'], true);
    if(is_array($room_ids) && !empty($room_ids)){
        $case_sql = "CASE room_id ";
        $ids_sql = [];
        $order = 1;
        foreach($room_ids as $id){
            $id = (int)$id;
            $case_sql .= "WHEN $id THEN $order ";
            $ids_sql[] = $id;
            $order++;
        }
        $case_sql .= "END";
        $ids_str = implode(',', $ids_sql);
        mysqli_query($conn, "UPDATE rooms SET display_order = $case_sql WHERE room_id IN ($ids_str)");
    }
    exit;
}

// Handle Room Order Save (AJAX)
if(isset($_POST['save_room_order'])){
    if(!$is_super) exit;
    $order = $_POST['order'];
    $order = mysqli_real_escape_string($conn, $order);
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('room_type_order', '$order') ON DUPLICATE KEY UPDATE setting_value='$order'");
    exit;
}

// Fetch current prices for modal
$default_prices = [
    'price_single' => 14000, 'price_single_long' => 13000,
    'price_4bed_upper' => 4200, 'price_4bed_lower' => 4700, 'price_4bed_whole' => 18000,
    'price_4bed_upper_long' => 4000, 'price_4bed_lower_long' => 4500, 'price_4bed_whole_long' => 17000,
    'price_6bed_upper' => 3750, 'price_6bed_lower' => 4500, 'price_6bed_whole' => 25000,
    'price_6bed_upper_long' => 3500, 'price_6bed_lower_long' => 4200, 'price_6bed_whole_long' => 24000
];
$q_prices = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'price_%'");
while($row = mysqli_fetch_assoc($q_prices)){ $default_prices[$row['setting_key']] = (float)$row['setting_value']; }

// Fetch all rooms using centralized function for accurate occupancy data
$rooms = get_all_rooms_with_occupancy($conn);

// Group rooms by type
$grouped_rooms = [];
foreach ($rooms as $room) {
    $type = $room['room_type'];
    if (!isset($grouped_rooms[$type])) {
        $grouped_rooms[$type] = [];
    }
    $grouped_rooms[$type][] = $room;
}

// Sort room types based on saved order or alphabetically
$order_q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='room_type_order'");
$saved_order = ($row = mysqli_fetch_assoc($order_q)) ? json_decode($row['setting_value'], true) : [];

if (!empty($saved_order) && is_array($saved_order)) {
    $ordered_groups = [];
    foreach ($saved_order as $type) {
        if (isset($grouped_rooms[$type])) {
            $ordered_groups[$type] = $grouped_rooms[$type];
            unset($grouped_rooms[$type]);
        }
    }
    ksort($grouped_rooms); // Sort remaining alphabetically
    $grouped_rooms = $ordered_groups + $grouped_rooms;
} else {
    ksort($grouped_rooms);
}

// Get representative images for price modal
$type_images = ['Single' => 'hero.jpg', '4-Bed' => 'hero.jpg', '6-Bed' => 'hero.jpg'];
foreach(['Single', '4-Bed', '6-Bed'] as $t){
    if(isset($grouped_rooms[$t]) && !empty($grouped_rooms[$t][0]['image'])){
        $type_images[$t] = $grouped_rooms[$t][0]['image'];
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
=======
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="Admin_CSS/admin_style.css">
<<<<<<< HEAD
=======
>>>>>>> 81f7535ae1ae18e72ed61d1a856e96f0288310d2
>>>>>>> 7d54ef7a9337fc7ae65f8c12788f9b5cc4f935e3
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        
        .card-room {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-room:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .card-room img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .card-room-summary {
            cursor: pointer;
        }
        .price-tag {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
        }
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
            
            <a href="#frontDeskSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-concierge-bell me-2"></i>Front Desk</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['residents.php', 'booking_management.php', 'admin_waitlist.php', 'admin_deletion_requests.php', 'view_user.php']) ? 'show' : '' ?>" id="frontDeskSubmenu">
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

            <a href="#facilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-building me-2"></i>Facilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_rooms.php', 'admin_room_occupancy.php', 'admin_parking.php', 'admin_keys.php', 'add_room.php', 'edit_room.php']) ? 'show' : '' ?>" id="facilitiesSubmenu">
                <a href="admin_rooms.php" class="sidebar-link ps-5 active"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
                <a href="admin_room_assignment.php" class="sidebar-link ps-5"><i class="fas fa-door-open me-2"></i>Room Assignment</a>
                <a href="admin_room_occupancy.php" class="sidebar-link ps-5"><i class="fas fa-users me-2"></i>Room Occupancy</a>
                <a href="admin_parking.php" class="sidebar-link ps-5"><i class="fas fa-parking me-2"></i>Parkings</a>
                <a href="admin_keys.php" class="sidebar-link ps-5"><i class="fas fa-key me-2"></i>Key Monitoring</a>
            </div>

            <a href="#financeSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-file-invoice-dollar me-2"></i>Finance & Reports</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['profit_report.php', 'longterm_billing.php', 'admin_parking_reports.php']) ? 'show' : '' ?>" id="financeSubmenu">
                <?php if($is_super): ?>
                <a href="profit_report.php" class="sidebar-link ps-5"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
                <?php endif; ?>
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-receipt me-2"></i>Billing</a>
            </div>

            <a href="#operationsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-cogs me-2"></i>Operations</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_maintenance.php', 'admin_housekeeping.php', 'admin_utilities.php']) ? 'show' : '' ?>" id="operationsSubmenu">
                <a href="admin_maintenance.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-wrench me-2"></i>Maintenance</span>
                    <?php if($pending_maint > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_maint ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-broom me-2"></i>Housekeeping</span>
                    <?php if($pending_house > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_house ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
            </div>

            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? '' : 'collapsed' ?>" role="button" aria-expanded="<?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'true' : 'false' ?>">
                <span><i class="fas fa-cog me-2"></i>System Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current_page, ['admin_profile.php', 'admin_roles.php', 'manage_hero.php', 'system_logs.php', 'backup.php']) ? 'show' : '' ?>" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="admin_roles.php" class="sidebar-link ps-5"><i class="fas fa-users-cog me-2"></i>Manage Roles</a>
                <a href="manage_hero.php" class="sidebar-link ps-5"><i class="fas fa-image me-2"></i>Hero Image</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <?php if($error){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                    </a>
                    <h4 class="fw-bold mb-0" style="color: var(--dark-green);">Room Inventory</h4>
                </div>
                <div>
                    <a href="admin_utilities.php#rooms" class="btn btn-outline-secondary me-2"><i class="fas fa-archive me-2"></i>View Archive</a>
                    <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#priceSettingsModal"><i class="fas fa-tags me-2"></i>Set Prices</button>
                </div>
            </div>

            <div class="row g-4" id="roomTypesContainer">
                <?php foreach($grouped_rooms as $type => $rooms_in_type): 
                    // Calculate Aggregate Stats for the room type
                    $type_total_beds = array_sum(array_column($rooms_in_type, 'total_beds'));
                    $type_avail_beds = array_sum(array_column($rooms_in_type, 'available_beds'));
                    $first_room = $rooms_in_type[0] ?? null;

                    if (!$first_room) continue; // Skip if no rooms of this type

                    $image = $first_room['image'];
                    $price = $first_room['total_price'];
                    $p_upper = $first_room['price_upper'];
                    $p_lower = $first_room['price_lower'];
                ?>
                    <div class="col-md-4" data-type="<?= $type ?>">
                        <div class="card card-room card-room-summary h-100" onclick="openTypeModal('<?= md5($type) ?>')">
                            <img src="../assets/images/<?= $image ?>" alt="<?= $type ?>">
                            <div class="card-body text-center d-flex flex-column">
                                <h3 class="fw-bold text-dark mb-2"><?= $type ?></h3>
                                <?php if($type != 'Single'): ?>
                                    <div class="mb-2">
                                        <span class="text-primary fw-bold small">Upper: ₱<?= number_format($p_upper, 2) ?></span><br>
                                        <span class="text-success fw-bold small">Lower: ₱<?= number_format($p_lower, 2) ?></span>
                                    </div>
                                <?php else: ?>
                                    <p class="price-tag mb-2">₱<?= number_format($price, 2) ?> <small class="text-muted fs-6">/mo</small></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-center gap-3 text-muted small mb-3 mt-auto">
                                    <span><i class="fas fa-door-open me-1"></i> <?= count($rooms_in_type) ?> Rooms</span>
                                    <span><i class="fas fa-bed me-1"></i> <?= $type_total_beds ?> Beds</span>
                                </div>
                                <div class="alert <?= $type_avail_beds > 0 ? 'alert-success' : 'alert-danger' ?> py-2 mb-0 fw-bold w-100">
                                    <?= $type_avail_beds ?> Beds Available
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div> </div> <?php foreach($grouped_rooms as $type => $rooms_in_type): ?>
<div class="modal fade" id="modal_<?= md5($type) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content bg-light">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-layer-group me-2"></i><?= $type ?> Inventory</h5>
                <a href="add_room.php?type=<?= urlencode($type) ?>" class="btn btn-sm btn-custom ms-auto me-3"><i class="fas fa-plus me-1"></i>Add Room</a>
                <div class="d-flex align-items-center me-3">
                    <label class="small fw-bold me-2 text-muted">Filter:</label>
                    <select class="form-select form-select-sm" onchange="filterModalRooms(this, '<?= md5($type) ?>')">
                        <option value="all">All Floors</option>
                        <?php for($i=2; $i<=7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>th Floor</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4" id="room-container-<?= md5($type) ?>">
                    <?php foreach($rooms_in_type as $room): 
    // Detailed Calculation for Individual Room
    $floor = $room['floor'] ?? 2;
    // Calculate Dynamic Availability
    $room_id = $room['room_id'];
    $total_beds = $room['total_beds'];
    $room_type = $room['room_type'];
    // Make room display name consistent
    $room_display_name = $room['room_name'];
    if (!empty($room['room_number'])) {
        $room_display_name = "Room " . $room['room_number'];
    } elseif (is_numeric($room['room_name'])) {
        $room_display_name = "Room " . $room['room_name'];
    }
    $is_shared = ($room_type == '4-Bed' || $room_type == '6-Bed');
    
    // Count occupied beds based on active reservations
    $occ_q = mysqli_query($conn, "SELECT bed_preference, count(*) as cnt FROM reservations WHERE room_id=$room_id AND status IN ('Pending','Approved') AND start_date <= CURDATE() AND end_date > CURDATE() GROUP BY bed_preference");
    $occupied_count = 0;
    $taken_upper = 0;
    $taken_lower = 0;
    $taken_any = 0;
    
    while($occ = mysqli_fetch_assoc($occ_q)){
        $cnt = $occ['cnt'];
        if($occ['bed_preference'] == 'Whole Room') {
            $occupied_count += $total_beds;
            $taken_any += $total_beds;
        } else {
            $occupied_count += $cnt;
            if($occ['bed_preference'] == 'Upper Bunk') $taken_upper += $cnt;
            elseif($occ['bed_preference'] == 'Lower Bunk') $taken_lower += $cnt;
            else $taken_any += $cnt;
        }
    }
    
    $available_beds = max(0, $total_beds - $occupied_count);
    
    // Calculate specific bed availability for shared rooms
    $cap_upper = floor($total_beds / 2);
    $cap_lower = ceil($total_beds / 2);
    
    $avail_upper = max(0, $cap_upper - $taken_upper);
    $avail_lower = max(0, $cap_lower - $taken_lower);
    
    // Distribute 'Any' bookings (fill lower first logic)
    if($taken_any > 0) {
        $fill_lower = min($avail_lower, $taken_any);
        $avail_lower -= $fill_lower;
        $taken_any -= $fill_lower;
        
        $avail_upper -= $taken_any; // Remaining goes to upper
        $avail_upper = max(0, $avail_upper);
    }

    // Override if Maintenance
    if($room['availability'] == 'Maintenance') {
        $available_beds = 0;
        $avail_upper = 0;
        $avail_lower = 0;
    }
                    ?>
                    <div class="col-md-6 col-lg-4 room-card-item" data-floor="<?= $floor ?>" data-id="<?= $room['room_id'] ?>">
    <div class="card card-room h-100">
        <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room['room_name'] ?>">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title fw-bold text-dark"><?= $room_display_name ?></h5>
                <div>
                    <span class="badge bg-light text-dark border me-1"><i class="fas fa-venus-mars"></i> <?= $room['gender'] ?? 'Male' ?></span>
                    <span class="badge bg-light text-dark border"><?= $floor ?>F</span>
                </div>
            </div>
            <div class="mb-2">
                <?php if($is_shared): ?>
                    <div class="small fw-bold text-primary">Upper: ₱<?= number_format($room['price_upper'], 2) ?></div>
                    <div class="small fw-bold text-success">Lower: ₱<?= number_format($room['price_lower'], 2) ?></div>
                <?php else: ?>
                    <p class="price-tag mb-0">₱<?= number_format($room['total_price'],2) ?> <small class="text-muted fs-6">/mo</small></p>
                <?php endif; ?>
                
                <button class="btn btn-sm btn-link text-decoration-none p-0 mt-1 small text-success fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#rates_<?= $room['room_id'] ?>">
                    <i class="fas fa-tags me-1"></i> View Detailed Rates
                </button>
                
                <div class="collapse mt-2" id="rates_<?= $room['room_id'] ?>">
                    <div class="card card-body p-2 bg-light border-0 small shadow-none" style="font-size: 0.75rem;">
                        <div class="fw-bold text-dark border-bottom mb-1 pb-1">Short Term (1mo)</div>
                        <?php if($is_shared): ?>
                            <div class="d-flex justify-content-between"><span>Upper:</span> <span>₱<?= number_format($room['price_upper'], 2) ?></span></div>
                            <div class="d-flex justify-content-between"><span>Lower:</span> <span>₱<?= number_format($room['price_lower'], 2) ?></span></div>
                            <div class="d-flex justify-content-between"><span>Whole:</span> <span>₱<?= number_format($room['price_whole'], 2) ?></span></div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between"><span>Monthly:</span> <span>₱<?= number_format($room['total_price'], 2) ?></span></div>
                        <?php endif; ?>
                        
                        <div class="fw-bold text-dark border-bottom mt-2 mb-1 pb-1">Long Term (6mo+)</div>
                        <?php if($is_shared): ?>
                            <div class="d-flex justify-content-between"><span>Upper:</span> <span>₱<?= number_format($room['long_term_price_upper'], 2) ?></span></div>
                            <div class="d-flex justify-content-between"><span>Lower:</span> <span>₱<?= number_format($room['long_term_price_lower'], 2) ?></span></div>
                            <div class="d-flex justify-content-between"><span>Whole:</span> <span>₱<?= number_format($room['long_term_price_whole'], 2) ?></span></div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between"><span>Monthly:</span> <span>₱<?= number_format($room['long_term_price_whole'], 2) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span><i class="fas fa-bed me-1"></i> Total Beds: <?= $room['total_beds'] ?></span>
                    <?php if($room['availability'] == 'Maintenance'): ?>
                        <span class="badge bg-warning text-dark">Maintenance</span>
                    <?php else: ?>
                        <span class="<?= $available_beds > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $available_beds ?> Available</span>
                    <?php endif; ?>
                </div>
                <?php if($is_shared && $room['availability'] != 'Maintenance'): ?>
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
            <div class="d-grid gap-2 mt-auto">
                <a href="edit_room.php?id=<?= $room['room_id'] ?>" class="btn btn-outline-success fw-bold">Edit Details</a>
                <a href="admin_rooms.php?archive_id=<?= $room['room_id'] ?>" class="btn btn-outline-danger fw-bold" onclick="confirmArchive(event, this.href)">Archive Room</a>
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

<div class="modal fade" id="priceSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-tags me-2"></i>Default Room Prices</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="text-muted small mb-4">Set the default prices for new rooms. These values will be auto-filled when adding a room.</p>
                    
                    <div class="row g-4">
                        <!-- Single Room -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-sm">
                                <img src="../assets/images/<?= $type_images['Single'] ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="fw-bold text-success mb-3">Single Room</h5>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Short Term (1mo)</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_single" class="form-control" value="<?= $default_prices['price_single'] ?>" required></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Long Term (6mo+)</label>
                                        <div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_single_long" class="form-control" value="<?= $default_prices['price_single_long'] ?>" required></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4-Bed Dorm -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-sm">
                                <img src="../assets/images/<?= $type_images['4-Bed'] ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="fw-bold text-primary mb-3">4-Bed Dorm</h5>
                                    
                                    <h6 class="small fw-bold text-muted border-bottom pb-1 mb-2">Short Term (1mo)</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6"><label class="small">Upper</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_upper" class="form-control" value="<?= $default_prices['price_4bed_upper'] ?>" required></div></div>
                                        <div class="col-6"><label class="small">Lower</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_lower" class="form-control" value="<?= $default_prices['price_4bed_lower'] ?>" required></div></div>
                                        <div class="col-12"><label class="small">Whole Room</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_whole" class="form-control" value="<?= $default_prices['price_4bed_whole'] ?>" required></div></div>
                                    </div>

                                    <h6 class="small fw-bold text-muted border-bottom pb-1 mb-2">Long Term (6mo+)</h6>
                                    <div class="row g-2">
                                        <div class="col-6"><label class="small">Upper</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_upper_long" class="form-control" value="<?= $default_prices['price_4bed_upper_long'] ?>" required></div></div>
                                        <div class="col-6"><label class="small">Lower</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_lower_long" class="form-control" value="<?= $default_prices['price_4bed_lower_long'] ?>" required></div></div>
                                        <div class="col-12"><label class="small">Whole Room</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_4bed_whole_long" class="form-control" value="<?= $default_prices['price_4bed_whole_long'] ?>" required></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6-Bed Dorm -->
                        <div class="col-lg-4">
                            <div class="card h-100 border shadow-sm">
                                <img src="../assets/images/<?= $type_images['6-Bed'] ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="fw-bold text-warning mb-3">6-Bed Dorm</h5>
                                    
                                    <h6 class="small fw-bold text-muted border-bottom pb-1 mb-2">Short Term (1mo)</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6"><label class="small">Upper</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_upper" class="form-control" value="<?= $default_prices['price_6bed_upper'] ?>" required></div></div>
                                        <div class="col-6"><label class="small">Lower</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_lower" class="form-control" value="<?= $default_prices['price_6bed_lower'] ?>" required></div></div>
                                        <div class="col-12"><label class="small">Whole Room</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_whole" class="form-control" value="<?= $default_prices['price_6bed_whole'] ?>" required></div></div>
                                    </div>

                                    <h6 class="small fw-bold text-muted border-bottom pb-1 mb-2">Long Term (6mo+)</h6>
                                    <div class="row g-2">
                                        <div class="col-6"><label class="small">Upper</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_upper_long" class="form-control" value="<?= $default_prices['price_6bed_upper_long'] ?>" required></div></div>
                                        <div class="col-6"><label class="small">Lower</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_lower_long" class="form-control" value="<?= $default_prices['price_6bed_lower_long'] ?>" required></div></div>
                                        <div class="col-12"><label class="small">Whole Room</label><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" name="price_6bed_whole_long" class="form-control" value="<?= $default_prices['price_6bed_whole_long'] ?>" required></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_prices" class="btn btn-success">Save Prices</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'prices_updated'): ?>
Swal.fire({
    icon: 'success',
    title: 'Updated!',
    text: 'Default room prices updated successfully.',
    timer: 2500,
    showConfirmButton: false
});
// Clean up the URL so the message doesn't persist
const url = new URL(window.location);
url.searchParams.delete('msg');
window.history.replaceState({}, document.title, url);
<?php endif; ?>

<?php if($is_super): ?>
// Initialize Sortable for rooms inside modals
document.querySelectorAll('[id^="room-container-"]').forEach(container => {
    new Sortable(container, {
        animation: 150,
        handle: '.card-room', // The draggable handle
        onEnd: function() {
            const order = [];
            container.querySelectorAll('[data-id]').forEach(el => {
                order.push(el.getAttribute('data-id'));
            });
            
            fetch('admin_rooms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'save_individual_room_order=1&order=' + encodeURIComponent(JSON.stringify(order))
            });
        }
    });
});

// Initialize Sortable for Room Types
const roomContainer = document.getElementById('roomTypesContainer');
if(roomContainer){
    new Sortable(roomContainer, {
        animation: 150,
        handle: '.card-room-summary',
        onEnd: function() {
            const order = [];
            roomContainer.querySelectorAll('[data-type]').forEach(el => {
                order.push(el.getAttribute('data-type'));
            });
            fetch('admin_rooms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'save_room_order=1&order=' + encodeURIComponent(JSON.stringify(order))
            });
        }
    });
}
<?php endif; ?>

function filterModalRooms(select, typeId) {
    const floor = select.value;
    const modal = document.getElementById('modal_' + typeId);
    const items = modal.querySelectorAll('.room-card-item');
    
    items.forEach(item => {
        if(floor === 'all' || item.getAttribute('data-floor') === floor) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function confirmArchive(e, url) {
    e.preventDefault();
    Swal.fire({
        title: 'Archive Room?',
        text: "This room will be moved to the archive and hidden from users.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
            cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive it!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}

function openTypeModal(id) {
    new bootstrap.Modal(document.getElementById('modal_' + id)).show();
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
setInterval(checkUpdates, 3000); // Check every 3 seconds

// Parent Sidebar Badges
document.addEventListener('DOMContentLoaded', function() {
    ['frontDeskSubmenu', 'operationsSubmenu'].forEach(menuId => {
        let menu = document.getElementById(menuId);
        if (menu) {
            let badges = menu.querySelectorAll('.badge');
            let total = 0;
            badges.forEach(b => total += parseInt(b.innerText) || 0);
            if (total > 0) {
                let link = document.querySelector(`[href="#${menuId}"]`);
                if(link) {
                    let icon = link.querySelector('.fa-chevron-down');
                    if(icon) icon.insertAdjacentHTML('beforebegin', `<span class="badge bg-danger rounded-pill me-2 parent-badge">${total}</span>`);
                    link.addEventListener('click', function() { let b = this.querySelector('.parent-badge'); if(b) b.style.setProperty('display', 'none', 'important'); });
                }
            }
        }
    });
});
</script>
</body>
</html>