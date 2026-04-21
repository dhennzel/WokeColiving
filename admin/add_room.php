<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$is_super = ($_SESSION['admin_role'] ?? 'Admin') == 'Super Admin';

// Ensure new price columns exist
$check_cols = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'price_upper'");
if(mysqli_num_rows($check_cols) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN price_upper DECIMAL(10,2) DEFAULT 0.00");
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN price_lower DECIMAL(10,2) DEFAULT 0.00");
}

$error = "";

if(isset($_POST['add_room'])){
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    $floor = (int) $_POST['floor'];
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $price_upper = isset($_POST['price_upper']) ? (float) $_POST['price_upper'] : 0;
    $price_lower = isset($_POST['price_lower']) ? (float) $_POST['price_lower'] : 0;
    $price_whole = isset($_POST['price_whole']) ? (float) $_POST['price_whole'] : 0;
    $lt_upper = isset($_POST['long_term_price_upper']) ? (float) $_POST['long_term_price_upper'] : 0;
    $lt_lower = isset($_POST['long_term_price_lower']) ? (float) $_POST['long_term_price_lower'] : 0;
    $lt_whole = isset($_POST['long_term_price_whole']) ? (float) $_POST['long_term_price_whole'] : 0;
    $beds = (int) $_POST['beds'];
    $availability = "Available";

    // Auto-set room name based on type, consistent with edit_room.php
    $room_name = ($room_type == 'Single') ? '1 Bed' : (($room_type == '4-Bed') ? '4 Beds' : '6 Beds');

    // If shared room, use lower price as the base 'total_price' for display purposes
    if($room_type != 'Single'){
        $price = $price_lower;
    }

    // Check for duplicate room number before proceeding
    $check_stmt = mysqli_prepare($conn, "SELECT room_id FROM rooms WHERE room_number = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $room_number);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error = "A room with number '$room_number' already exists (it may be in the archive). Please use a unique room number.";
        mysqli_stmt_close($check_stmt);
    } else {
        mysqli_stmt_close($check_stmt); // Close statement before continuing

        // Image Upload
        $image = $_FILES['image']['name'];
        $target_dir = "../assets/images/";

        // Create directory if it does not exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target = $target_dir . basename($image);

        // Ensure the assets/images directory exists or create it manually if needed
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
            $stmt = mysqli_prepare($conn, "INSERT INTO rooms (room_name, room_number, room_type, floor, total_price, price_upper, price_lower, price_whole, long_term_price_upper, long_term_price_lower, long_term_price_whole, total_beds, image, availability) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssidddddddiss", $room_name, $room_number, $room_type, $floor, $price, $price_upper, $price_lower, $price_whole, $lt_upper, $lt_lower, $lt_whole, $beds, $image, $availability);

            try {
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_close($stmt);
                    trigger_update($conn);
                    header("Location: admin_rooms.php");
                    exit;
                } else {
                    $error = "Database error: " . mysqli_stmt_error($stmt);
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Failed to upload image. Ensure 'assets/images' folder exists.";
        }
    }
}

// Fetch Pending Counts for Sidebar
$pending_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status='Pending'"))['c'];
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);

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

$allowed_types = ['Single', '4-Bed', '6-Bed'];
$locked_type = (isset($_GET['type']) && in_array($_GET['type'], $allowed_types)) ? $_GET['type'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Add New Room</h1>
                <a href="admin_rooms.php" class="btn btn-outline-secondary rounded-pill">&larr; Back</a>
            </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-white p-4 shadow-sm border-0 rounded-4">
                <?php if($error){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3"><label class="form-label fw-bold">Room Number</label><input type="text" name="room_number" class="form-control" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Floor Level</label>
                            <select name="floor" class="form-select" required>
                                <?php for($i=2; $i<=7; $i++): 
                                    $suffix = ($i == 2) ? 'nd' : (($i == 3) ? 'rd' : 'th'); ?>
                                    <option value="<?= $i ?>"><?= $i . $suffix ?> Floor</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Room Type</label>
                        <?php if($locked_type): ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($locked_type) ?>" readonly>
                            <input type="hidden" name="room_type" id="room_type" value="<?= htmlspecialchars($locked_type) ?>">
                        <?php else: ?>
                            <select name="room_type" id="room_type" class="form-select" required onchange="togglePriceFields()">
                                <option value="Single">Single</option>
                                <option value="4-Bed">4-Bed</option>
                                <option value="6-Bed">6-Bed</option>
                            </select>
                        <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3" id="gender_div">
                            <label class="form-label fw-bold">Gender Restrict</label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="Any">Any / Mixed</option>
                                <option value="Male" selected>Male Only</option>
                                <option value="Female">Female Only</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="single_price_div"><label class="form-label fw-bold">Short Term Price (₱)</label><input type="number" name="price" class="form-control" step="0.01" value="<?= $default_prices['price_single'] ?>" readonly></div>
                        <div class="col-md-6 mb-3" id="single_price_long_div" style="display:none;"><label class="form-label fw-bold">Long Term Price (₱)</label><input type="number" name="long_term_price_whole" id="lt_whole" class="form-control" step="0.01"></div>
                        
                        <div class="col-md-3 mb-3" id="upper_price_div" style="display:none;"><label class="form-label fw-bold">ST Upper Bed (₱)</label><input type="number" name="price_upper" class="form-control" step="0.01"></div>
                        <div class="col-md-3 mb-3" id="lower_price_div" style="display:none;"><label class="form-label fw-bold">ST Lower Bed (₱)</label><input type="number" name="price_lower" class="form-control" step="0.01"></div>
                        <div class="col-md-3 mb-3" id="upper_price_long_div" style="display:none;"><label class="form-label fw-bold">LT Upper Bed (₱)</label><input type="number" name="long_term_price_upper" class="form-control" step="0.01"></div>
                        <div class="col-md-3 mb-3" id="lower_price_long_div" style="display:none;"><label class="form-label fw-bold">LT Lower Bed (₱)</label><input type="number" name="long_term_price_lower" class="form-control" step="0.01"></div>
                        
                        <div class="col-md-6 mb-3" id="whole_price_div" style="display:none;"><label class="form-label fw-bold">Whole Room Price (ST)</label><input type="number" name="price_whole" class="form-control" step="0.01"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Total Beds</label><input type="number" name="beds" id="beds" class="form-control" value="1" readonly></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Image</label><input type="file" name="image" class="form-control" accept="image/*" required></div>
                    </div>
                    <div class="d-grid gap-2 mt-4"><button type="submit" name="add_room" class="btn btn-custom btn-lg">Add Room</button><a href="admin_rooms.php" class="btn btn-outline-secondary rounded-pill">Cancel</a></div>
                </form>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function togglePriceFields() {
    var type = document.getElementById("room_type").value;
    var singleDiv = document.getElementById("single_price_div");
    var singleLongDiv = document.getElementById("single_price_long_div");
    var upperDiv = document.getElementById("upper_price_div");
    var lowerDiv = document.getElementById("lower_price_div");
    var upperLongDiv = document.getElementById("upper_price_long_div");
    var lowerLongDiv = document.getElementById("lower_price_long_div");
    var wholeDiv = document.getElementById("whole_price_div");
    var genderDiv = document.getElementById("gender_div");
    var genderSelect = document.getElementById("gender");
    
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
        if(genderDiv) genderDiv.style.display = "none";
        if(genderSelect) genderSelect.value = "Any";
        
        priceInput.required = true;
        upperInput.required = false;
        lowerInput.required = false;
        
        priceInput.value = <?= $default_prices['price_single'] ?>;
        ltWholeInput.value = <?= $default_prices['price_single_long'] ?>;
        bedsInput.value = 1;
    } else {
        singleDiv.style.display = "none";
        singleLongDiv.style.display = "none";
        upperDiv.style.display = "block";
        lowerDiv.style.display = "block";
        upperLongDiv.style.display = "block";
        lowerLongDiv.style.display = "block";
        wholeDiv.style.display = "block";
        if(genderDiv) genderDiv.style.display = "block";
        
        priceInput.required = false;
        upperInput.required = true;
        lowerInput.required = true;
        
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
// Initialize
togglePriceFields();
</script>
</body>
</html>