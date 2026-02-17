<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

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
        header("Location: admin_rooms.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        $error = "Cannot archive room: " . $e->getMessage();
    }
}

// Fetch all rooms
$rooms = mysqli_query($conn, "SELECT * FROM rooms WHERE is_archived='0' ORDER BY floor ASC, room_name ASC");
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Woke Coliving INC</title>
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }

        .card-room { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: .3s; overflow: hidden; background: white; }
        .card-room:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-room img { height: 200px; object-fit: cover; width: 100%; }
        .price-tag { font-size: 1.2rem; font-weight: bold; color: var(--primary-green); font-family: 'Playfair Display', serif; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { background-color: #f9a825; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
        }
    </style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link"><i class="fas fa-calendar-check me-2"></i>Bookings</a>
            <a href="admin_rooms.php" class="sidebar-link active"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><i class="fas fa-wrench me-2"></i>Maintenance</a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><i class="fas fa-broom me-2"></i>Housekeeping</a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="settingsSubmenu">
                <span><i class="fas fa-cog me-2"></i>Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
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
            <a href="add_room.php" class="btn btn-custom px-4"><i class="fas fa-plus me-2"></i>Add New Room</a>
        </div>
    </div>

    <div class="row g-4">
        <?php 
        $current_floor = 0;
        while($room = mysqli_fetch_assoc($rooms)) { 
            $floor = $room['floor'] ?? 2; // Default to 2 if null
            // Calculate Dynamic Availability
            $room_id = $room['room_id'];
            $total_beds = $room['total_beds'];
            $room_type = $room['room_type'];
            $is_shared = ($room_type == '4-Bed' || $room_type == '6-Bed');
            
            // Count occupied beds based on active reservations
            $occ_q = mysqli_query($conn, "SELECT bed_preference, count(*) as cnt FROM reservations WHERE room_id=$room_id AND status IN ('Pending','Approved') AND start_date <= CURDATE() AND end_date > CURDATE() GROUP BY bed_preference");
            $occupied_count = 0;
            $taken_upper = 0;
            $taken_lower = 0;
            $taken_any = 0;
            
            while($occ = mysqli_fetch_assoc($occ_q)){
                $occupied_count += $occ['cnt'];
                if($occ['bed_preference'] == 'Upper Bunk') $taken_upper += $occ['cnt'];
                elseif($occ['bed_preference'] == 'Lower Bunk') $taken_lower += $occ['cnt'];
                else $taken_any += $occ['cnt'];
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

            if($floor != $current_floor){
                $current_floor = $floor;
                $suffix = ($floor == 2) ? 'nd' : (($floor == 3) ? 'rd' : 'th');
                echo '<div class="col-12 mt-4"><h5 class="fw-bold text-secondary border-bottom pb-2"><i class="fas fa-layer-group me-2"></i>'.$floor.$suffix.' Floor</h5></div>';
            }
        ?>
        <div class="col-md-4">
            <div class="card card-room h-100">
                <img src="../assets/images/<?= $room['image'] ?>" alt="<?= $room['room_name'] ?>">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title fw-bold text-dark"><?= $room['room_name'] ?></h5>
                        <span class="badge bg-secondary"><?= $room['room_type'] ?></span>
                    </div>
                <p class="price-tag mb-1">₱<?= number_format($room['total_price'],2) ?> <small class="text-muted fs-6">/mo</small></p>
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
        <?php } ?>
    </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
</script>
</body>
</html>
