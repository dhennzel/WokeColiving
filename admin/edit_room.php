<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: admin_rooms.php");
    exit;
}

$room_id = (int)$_GET['id'];
$error = "";
$success = "";

// Ensure new price columns exist
$check_cols = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'price_upper'");
if(mysqli_num_rows($check_cols) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN price_upper DECIMAL(10,2) DEFAULT 0.00");
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN price_lower DECIMAL(10,2) DEFAULT 0.00");
}

// Fetch room details
$query = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$room_id");
if(mysqli_num_rows($query) == 0){
    header("Location: admin_rooms.php");
    exit;
}
$room = mysqli_fetch_assoc($query);

if(isset($_POST['update_room'])){
    $room_name = trim($_POST['room_name']);
    $room_type = trim($_POST['room_type']);
    $floor = (int) $_POST['floor'];
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $price_upper = isset($_POST['price_upper']) ? (float) $_POST['price_upper'] : 0;
    $price_lower = isset($_POST['price_lower']) ? (float) $_POST['price_lower'] : 0;
    $beds = (int) $_POST['beds'];
    $availability = $_POST['availability'];
    
    // Image Upload
    $image = $room['image']; // Default to old image
    if(!empty($_FILES['image']['name'])){
        $new_image = $_FILES['image']['name'];
        $target_dir = "../assets/images/";
        $target = $target_dir . basename($new_image);
        
        // Create directory if it does not exist (safety check)
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
            $image = $new_image;
        } else {
            $error = "Failed to upload new image.";
        }
    }

    if(!$error){
        // If shared room, use lower price as base total_price
        if($room_type != 'Single'){
            $price = $price_lower;
        }

        $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_name=?, room_type=?, floor=?, total_price=?, price_upper=?, price_lower=?, total_beds=?, availability=?, image=? WHERE room_id=?");
        mysqli_stmt_bind_param($stmt, "ssidddissi", $room_name, $room_type, $floor, $price, $price_upper, $price_lower, $beds, $availability, $image, $room_id);
        
        try {
            if(mysqli_stmt_execute($stmt)){
                $success = "Room updated successfully!";
                // Refresh data
                $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$room_id"));
            } else {
                $error = "Database error: " . mysqli_stmt_error($stmt);
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room | Woke Coliving INC</title>
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
        
        .card-form { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 40px; background: white; }
        .current-img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; margin-top: 10px; }
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
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
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
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card card-form">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                                </a>
                                <h3 class="fw-bold mb-0" style="color: var(--dark-green);"><i class="fas fa-edit me-2"></i>Edit Room</h3>
                            </div>
                            <a href="admin_rooms.php" class="btn btn-outline-secondary rounded-pill">&larr; Back</a>
                        </div>
                        
                        <?php if($error){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
                        <?php if($success){ echo "<div class='alert alert-success'>$success</div>"; } ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Room Name</label>
                                <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars($room['room_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Floor Level</label>
                                <select name="floor" class="form-select" required>
                                    <?php for($i=2; $i<=7; $i++): 
                                        $suffix = ($i == 2) ? 'nd' : (($i == 3) ? 'rd' : 'th'); ?>
                                        <option value="<?= $i ?>" <?= ($room['floor'] == $i) ? 'selected' : '' ?>><?= $i . $suffix ?> Floor</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Room Type</label>
                                    <select name="room_type" id="room_type" class="form-select" required onchange="togglePriceFields()">
                                        <option value="Single" <?= $room['room_type'] == 'Single' ? 'selected' : '' ?>>Single</option>
                                        <option value="4-Bed" <?= $room['room_type'] == '4-Bed' ? 'selected' : '' ?>>4-Bed</option>
                                        <option value="6-Bed" <?= $room['room_type'] == '6-Bed' ? 'selected' : '' ?>>6-Bed</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="single_price_div">
                                    <label class="form-label fw-bold">Price (₱)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" value="<?= $room['total_price'] ?>" required>
                                </div>
                                <div class="col-md-3 mb-3" id="upper_price_div" style="display:none;">
                                    <label class="form-label fw-bold">Upper Bed Price (₱)</label>
                                    <input type="number" name="price_upper" class="form-control" step="0.01" value="<?= $room['price_upper'] ?? 0 ?>">
                                </div>
                                <div class="col-md-3 mb-3" id="lower_price_div" style="display:none;">
                                    <label class="form-label fw-bold">Lower Bed Price (₱)</label>
                                    <input type="number" name="price_lower" class="form-control" step="0.01" value="<?= $room['price_lower'] ?? 0 ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total Beds</label>
                                    <input type="number" name="beds" class="form-control" value="<?= $room['total_beds'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Availability Status</label>
                                    <select name="availability" class="form-select">
                                        <option <?= $room['availability'] == 'Available' ? 'selected' : '' ?>>Available</option>
                                        <option <?= $room['availability'] == 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                        <option <?= $room['availability'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Room Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image.</small><br>
                                <img src="../assets/images/<?= $room['image'] ?>" class="current-img">
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="admin_rooms.php?delete_id=<?= $room['room_id'] ?>" class="btn btn-danger" onclick="confirmDelete(event, this.href)">Delete Room</a>
                                <div>
                                    <a href="admin_rooms.php" class="btn btn-outline-secondary me-2 rounded-pill">Cancel</a>
                                    <button type="submit" name="update_room" class="btn btn-custom px-4">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePriceFields() {
    var type = document.getElementById("room_type").value;
    var singleDiv = document.getElementById("single_price_div");
    var upperDiv = document.getElementById("upper_price_div");
    var lowerDiv = document.getElementById("lower_price_div");
    
    var priceInput = document.querySelector('input[name="price"]');
    var upperInput = document.querySelector('input[name="price_upper"]');
    var lowerInput = document.querySelector('input[name="price_lower"]');

    if (type === "Single") {
        singleDiv.style.display = "block";
        upperDiv.style.display = "none";
        lowerDiv.style.display = "none";
        
        priceInput.required = true;
        upperInput.required = false;
        lowerInput.required = false;
    } else {
        singleDiv.style.display = "none";
        upperDiv.style.display = "block";
        lowerDiv.style.display = "block";
        
        priceInput.required = false;
        upperInput.required = true;
        lowerInput.required = true;
    }
}
// Initialize
togglePriceFields();

function confirmDelete(e, url) {
    e.preventDefault();
    Swal.fire({
        title: 'Delete Room?',
        text: "You won't be able to revert this! This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
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