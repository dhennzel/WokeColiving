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

// Ensure is_hidden column exists
$check_col_hidden = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'is_hidden'");
if(mysqli_num_rows($check_col_hidden) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
}

// Fetch room details
$query = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$room_id");
if(mysqli_num_rows($query) == 0){
    header("Location: admin_rooms.php");
    exit;
}
$room = mysqli_fetch_assoc($query);

// Fetch default prices
$default_prices = [
    'price_single' => 14000, 'price_single_long' => 13000,
    'price_4bed_upper' => 4200, 'price_4bed_lower' => 4700, 'price_4bed_whole' => 18000,
    'price_4bed_upper_long' => 4000, 'price_4bed_lower_long' => 4500, 'price_4bed_whole_long' => 17000,
    'price_6bed_upper' => 3750, 'price_6bed_lower' => 4500, 'price_6bed_whole' => 25000,
    'price_6bed_upper_long' => 3500, 'price_6bed_lower_long' => 4200, 'price_6bed_whole_long' => 24000
];
$q_prices = mysqli_query($conn, "SELECT * FROM site_settings WHERE setting_key LIKE 'price_%'");
while($row = mysqli_fetch_assoc($q_prices)){ $default_prices[$row['setting_key']] = (float)$row['setting_value']; }

if(isset($_POST['update_room'])){
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    // Auto-set room name based on type
    $room_name = ($room_type == 'Single') ? '1 Bed' : (($room_type == '4-Bed') ? '4 Beds' : '6 Beds');
    $floor = (int) $_POST['floor'];
    $gender = $_POST['gender'] ?? 'Male';
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $price_upper = isset($_POST['price_upper']) ? (float) $_POST['price_upper'] : 0;
    $price_lower = isset($_POST['price_lower']) ? (float) $_POST['price_lower'] : 0;
    $price_whole = isset($_POST['price_whole']) ? (float) $_POST['price_whole'] : 0;
    $lt_upper = isset($_POST['long_term_price_upper']) ? (float) $_POST['long_term_price_upper'] : 0;
    $lt_lower = isset($_POST['long_term_price_lower']) ? (float) $_POST['long_term_price_lower'] : 0;
    $lt_whole = isset($_POST['long_term_price_whole']) ? (float) $_POST['long_term_price_whole'] : 0;
    $beds = (int) $_POST['beds'];
    $availability = $_POST['availability'];
    $is_hidden = isset($_POST['is_hidden']) ? 1 : 0;
    
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

        $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_name=?, room_number=?, room_type=?, floor=?, gender=?, total_price=?, price_upper=?, price_lower=?, price_whole=?, long_term_price_upper=?, long_term_price_lower=?, long_term_price_whole=?, total_beds=?, availability=?, image=?, is_hidden=? WHERE room_id=?");
        mysqli_stmt_bind_param($stmt, "sssisdddddddissii", $room_name, $room_number, $room_type, $floor, $gender, $price, $price_upper, $price_lower, $price_whole, $lt_upper, $lt_lower, $lt_whole, $beds, $availability, $image, $is_hidden, $room_id);
        
        try {
            if(mysqli_stmt_execute($stmt)){
                $success = "Room updated successfully!";
                trigger_update($conn);
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
    <title>Edit Room | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Edit Room</h1>
                <a href="admin_rooms.php" class="btn btn-outline-secondary rounded-pill">&larr; Back</a>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card bg-white p-4 shadow-sm border-0 rounded-4">
                        
                        <?php if($error){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
                        <?php if($success){ echo "<div class='alert alert-success'>$success</div>"; } ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Room Number</label>
                                <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars(!empty($room['room_number']) ? $room['room_number'] : $room['room_name']) ?>" required>
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
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Room Type</label>
                                    <select name="room_type" id="room_type" class="form-select" required onchange="togglePriceFields()">
                                        <option value="Single" <?= $room['room_type'] == 'Single' ? 'selected' : '' ?>>1 Bed</option>
                                        <option value="4-Bed" <?= $room['room_type'] == '4-Bed' ? 'selected' : '' ?>>4 Beds</option>
                                        <option value="6-Bed" <?= $room['room_type'] == '6-Bed' ? 'selected' : '' ?>>6 Beds</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Gender Restrict</label>
                                    <select name="gender" class="form-select" required>
                                        <option value="Male" <?= ($room['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male Only</option>
                                        <option value="Female" <?= ($room['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female Only</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="single_price_div">
                                    <label class="form-label fw-bold">Short Term Price (₱)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" value="<?= $room['total_price'] ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3" id="single_price_long_div" style="display:none;"><label class="form-label fw-bold">Long Term Price (₱)</label><input type="number" name="long_term_price_whole" id="lt_whole" class="form-control" step="0.01" value="<?= $room['long_term_price_whole'] ?>" readonly></div>
                                
                                <div class="col-md-3 mb-3" id="upper_price_div" style="display:none;"><label class="form-label fw-bold">ST Upper Bed (₱)</label><input type="number" name="price_upper" class="form-control" step="0.01" value="<?= $room['price_upper'] ?>" readonly></div>
                                <div class="col-md-3 mb-3" id="lower_price_div" style="display:none;"><label class="form-label fw-bold">ST Lower Bed (₱)</label><input type="number" name="price_lower" class="form-control" step="0.01" value="<?= $room['price_lower'] ?>" readonly></div>
                                <div class="col-md-3 mb-3" id="upper_price_long_div" style="display:none;"><label class="form-label fw-bold">LT Upper Bed (₱)</label><input type="number" name="long_term_price_upper" class="form-control" step="0.01" value="<?= $room['long_term_price_upper'] ?>" readonly></div>
                                <div class="col-md-3 mb-3" id="lower_price_long_div" style="display:none;"><label class="form-label fw-bold">LT Lower Bed (₱)</label><input type="number" name="long_term_price_lower" class="form-control" step="0.01" value="<?= $room['long_term_price_lower'] ?>" readonly></div>
                                
                                <div class="col-md-6 mb-3" id="whole_price_div" style="display:none;"><label class="form-label fw-bold">Whole Room Price (ST)</label><input type="number" name="price_whole" class="form-control" step="0.01" value="<?= $room['price_whole'] ?>" readonly></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total Beds</label>
                                    <input type="number" name="beds" id="beds" class="form-control" value="<?= $room['total_beds'] ?>" required readonly>
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
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_hidden" id="is_hidden" value="1" <?= ($room['is_hidden'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="is_hidden">
                                        <i class="fas fa-eye-slash me-1"></i> Hide from Dashboard
                                    </label>
                                    <small class="text-muted d-block">When enabled, this room will not appear in the Admin Dashboard occupancy view.</small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Room Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image.</small><br>
                                <img src="../assets/images/<?= $room['image'] ?>" class="img-thumbnail mt-2" style="max-height: 200px; max-width: 100%; object-fit: cover; border-radius: 12px;">
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
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
let isFirstRun = true;
function togglePriceFields() {
    var type = document.getElementById("room_type").value;
    var singleDiv = document.getElementById("single_price_div");
    var singleLongDiv = document.getElementById("single_price_long_div");
    var upperDiv = document.getElementById("upper_price_div");
    var lowerDiv = document.getElementById("lower_price_div");
    var upperLongDiv = document.getElementById("upper_price_long_div");
    var lowerLongDiv = document.getElementById("lower_price_long_div");
    var wholeDiv = document.getElementById("whole_price_div");
    
    var priceInput = document.querySelector('input[name="price"]');
    var ltWholeInput = document.querySelector('input[name="long_term_price_whole"]');
    var upperInput = document.querySelector('input[name="price_upper"]');
    var lowerInput = document.querySelector('input[name="price_lower"]');
    var wholeInput = document.querySelector('input[name="price_whole"]');
    var ltUpperInput = document.querySelector('input[name="long_term_price_upper"]');
    var ltLowerInput = document.querySelector('input[name="long_term_price_lower"]');
    var bedsInput = document.getElementById("beds");

    if (type === "Single") {
        singleDiv.style.display = "block";
        singleLongDiv.style.display = "block";
        upperDiv.style.display = "none";
        lowerDiv.style.display = "none";
        upperLongDiv.style.display = "none";
        lowerLongDiv.style.display = "none";
        wholeDiv.style.display = "none";
        
        priceInput.required = true;
        upperInput.required = false;
        lowerInput.required = false;
        
        if(!isFirstRun) {
            priceInput.value = <?= $default_prices['price_single'] ?>;
            ltWholeInput.value = <?= $default_prices['price_single_long'] ?>;
            bedsInput.value = 1;
        }
    } else {
        singleDiv.style.display = "none";
        singleLongDiv.style.display = "none";
        upperDiv.style.display = "block";
        lowerDiv.style.display = "block";
        upperLongDiv.style.display = "block";
        lowerLongDiv.style.display = "block";
        wholeDiv.style.display = "block";
        
        priceInput.required = false;
        upperInput.required = true;
        lowerInput.required = true;
        
        if(!isFirstRun) {
            if(type === "4-Bed") {
                upperInput.value = <?= $default_prices['price_4bed_upper'] ?>;
                lowerInput.value = <?= $default_prices['price_4bed_lower'] ?>;
                wholeInput.value = <?= $default_prices['price_4bed_whole'] ?>;
                ltUpperInput.value = <?= $default_prices['price_4bed_upper_long'] ?>;
                ltLowerInput.value = <?= $default_prices['price_4bed_lower_long'] ?>;
                ltWholeInput.value = <?= $default_prices['price_4bed_whole_long'] ?>;
                bedsInput.value = 4;
            } else if(type === "6-Bed") {
                upperInput.value = <?= $default_prices['price_6bed_upper'] ?>;
                lowerInput.value = <?= $default_prices['price_6bed_lower'] ?>;
                wholeInput.value = <?= $default_prices['price_6bed_whole'] ?>;
                ltUpperInput.value = <?= $default_prices['price_6bed_upper_long'] ?>;
                ltLowerInput.value = <?= $default_prices['price_6bed_lower_long'] ?>;
                ltWholeInput.value = <?= $default_prices['price_6bed_whole_long'] ?>;
                bedsInput.value = 6;
            }
        }
    }
    isFirstRun = false;
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
</script>
</body>
</html>