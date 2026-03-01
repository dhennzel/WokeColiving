<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

$error = "";
$success = "";

// Fetch Users
$users = mysqli_query($conn, "SELECT user_id, full_name, email FROM users ORDER BY full_name ASC");

// Ensure gender column exists in users table
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL");
}

// Ensure role column exists
$check_role = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if(mysqli_num_rows($check_role) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
}

// Ensure is_walkin column exists
$check_walkin = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_walkin'");
if(mysqli_num_rows($check_walkin) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_walkin TINYINT(1) DEFAULT 0");
}

// Fetch Room Prices for JS
$room_prices_js = [];
$price_query = mysqli_query($conn, "SELECT room_type, total_price, price_upper, price_lower FROM rooms GROUP BY room_type");
while($row = mysqli_fetch_assoc($price_query)){
    $room_prices_js[$row['room_type']] = [
        'base' => $row['total_price'],
        'upper' => $row['price_upper'],
        'lower' => $row['price_lower']
    ];
}

// Check for pre-selected room type
$pre_room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';

if(isset($_POST['add_reservation'])){
    $user_type = $_POST['user_type'];
    $user_id = 0;
    $account_msg = "";

    if($user_type == 'new'){
        // Create New User
        $lname = trim($_POST['new_lname']);
        $fname = trim($_POST['new_fname']);
        $mname = trim($_POST['new_mname']);
        $name = $lname . ', ' . $fname . ' ' . $mname;
        $email = trim($_POST['new_email']);
        $phone = trim($_POST['new_phone']);
        $gender = $_POST['new_gender'];
        $raw_pass = !empty($_POST['new_password']) ? $_POST['new_password'] : '123456';
        $password = password_hash($raw_pass, PASSWORD_DEFAULT);

        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
        if(mysqli_num_rows($check) > 0){
            $error = "Email address already registered.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone_number, gender, password, role, is_walkin) VALUES (?, ?, ?, ?, ?, 'user', 1)");
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $gender, $password);
            if(mysqli_stmt_execute($stmt)){
                $user_id = mysqli_insert_id($conn);
                $account_msg = "Account created for $name (Pass: $raw_pass). ";
                log_activity($conn, $user_id, "Account Created", "Walk-in account created by Admin");
            } else {
                $error = "Failed to create user account.";
            }
        }
    } else {
        $user_id = (int)$_POST['user_id'];
    }

    if(!$error && $user_id > 0){
        $room_type = $_POST['room_type'];
        $bed_preference = $_POST['bed_preference'] ?? 'Any';
        $cin = $_POST['cin'];
        $cout = $_POST['cout'];
        
        // Calculate duration
        $d1 = new DateTime($cin);
        $d2 = new DateTime($cout);
        $interval = $d1->diff($d2);
        
        // Calculate accurate billing components (Months + Remaining Days)
        $calc_months = ($interval->y * 12) + $interval->m;
        $calc_days = $interval->d;
        
        $days_total = $d1->diff($d2)->days;
        $months = max(1, round($days_total / 30));

        // Find available room
        $found_room = null;
        $r_sql = "SELECT room_id, total_beds, total_price, price_upper, price_lower FROM rooms WHERE room_type = ? AND availability = 'Available' AND is_archived=0";
        $r_stmt = $conn->prepare($r_sql);
        $r_stmt->bind_param("s", $room_type);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();

        while($room = $r_res->fetch_assoc()) {
            $rid = $room['room_id'];
            $total_capacity = $room['total_beds'];
            
            // Get counts for specific dates
            $q_counts = "SELECT bed_preference, COUNT(*) as cnt FROM reservations WHERE room_id = $rid AND status IN ('Pending','Approved') AND start_date < '$cout' AND end_date > '$cin' GROUP BY bed_preference";
            $res_counts = mysqli_query($conn, $q_counts);
            
            $occ_upper = 0; $occ_lower = 0; $occ_any = 0; $total_taken = 0;
            
            while($row_c = mysqli_fetch_assoc($res_counts)){
                $total_taken += $row_c['cnt'];
                if($row_c['bed_preference'] == 'Upper Bunk') $occ_upper += $row_c['cnt'];
                elseif($row_c['bed_preference'] == 'Lower Bunk') $occ_lower += $row_c['cnt'];
                else $occ_any += $row_c['cnt'];
            }
            
            // If totally full, skip
            if($total_taken >= $total_capacity) continue;

            // Check specific bed availability
            if(($room_type == '4-Bed' || $room_type == '6-Bed')){
                $cap_upper = floor($total_capacity / 2);
                $cap_lower = ceil($total_capacity / 2);
                
                $avail_upper = max(0, $cap_upper - $occ_upper);
                $avail_lower = max(0, $cap_lower - $occ_lower);

                if($occ_any > 0) {
                    $fill_lower = min($avail_lower, $occ_any);
                    $avail_lower -= $fill_lower;
                    $occ_any -= $fill_lower;
                    
                    $avail_upper -= $occ_any;
                    $avail_upper = max(0, $avail_upper);
                }
                
                if($bed_preference == 'Upper Bunk'){
                    if($avail_upper > 0) { $found_room = $room; break; }
                } elseif($bed_preference == 'Lower Bunk'){
                    if($avail_lower > 0) { $found_room = $room; break; }
                } else {
                    $found_room = $room; break;
                }
            } else {
                $found_room = $room; break;
            }
        }

        if($found_room){
            $room_id = $found_room['room_id'];
            
            // Calculate Price
            $monthly_price = $found_room['total_price'];
            if ($room_type != 'Single' && $bed_preference == 'Upper Bunk') {
                $monthly_price = ($found_room['price_upper'] > 0) ? $found_room['price_upper'] : $found_room['total_price'];
            } elseif ($room_type != 'Single' && $bed_preference == 'Lower Bunk') {
                $monthly_price = ($found_room['price_lower'] > 0) ? $found_room['price_lower'] : $found_room['total_price'];
            }
            // Accurate Calculation: (Months * Price) + (Remaining Days * Daily Rate)
            $totalAmount = ($calc_months * $monthly_price) + ($calc_days * ($monthly_price / 30));

            // Insert
            $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price, status, bed_preference) VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?)");
            $stmt->bind_param("iissids", $user_id, $room_id, $cin, $cout, $months, $totalAmount, $bed_preference);
            
            if($stmt->execute()){
                $res_id = $conn->insert_id;
                // Add Payment Record (Unpaid)
                mysqli_query($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date) VALUES ($res_id, $totalAmount, 'Cash', 'Unpaid', NOW())");
                
                log_activity($conn, $user_id, "Walk-in Booking", "Reservation #$res_id created by Admin");
                
                $success = $account_msg . "Reservation created successfully!";
                $new_reservation_id = $res_id; // For JS Print Popup
            } else {
                $error = "Database Error: " . $conn->error;
            }
        } else {
            $error = "No available rooms of type $room_type for these dates.";
        }
    }
}
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Reservation | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .card-form { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 40px; background: white; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: bold; border-radius: 50px; border: none; }
        .btn-custom:hover { filter: brightness(90%); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-form">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: #1B5E20;">Create Manual Reservation</h3>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill">&larr; Back</a>
                </div>

                <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Guest Type</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" id="type_existing" value="existing" checked onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_existing">Existing User</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" id="type_new" value="new" onchange="toggleUserSection()">
                                <label class="form-check-label" for="type_new">New Walk-in Guest</label>
                            </div>
                        </div>
                    </div>

                    <div id="existing_user_section" class="mb-3">
                        <label class="form-label fw-bold">Select User</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- Choose User --</option>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= $u['full_name'] ?> (<?= $u['email'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="new_user_section" class="mb-3 p-3 border rounded bg-light" style="display:none;">
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-user-plus me-2"></i>Guest Details</h6>
                        <div class="row g-2">
                            <div class="col-md-4"><label class="small fw-bold">Last Name</label><input type="text" name="new_lname" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">First Name</label><input type="text" name="new_fname" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">Middle Name</label><input type="text" name="new_mname" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">Email</label><input type="email" name="new_email" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">Phone</label><input type="text" name="new_phone" class="form-control"></div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Gender</label>
                                <select name="new_gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">Password</label><input type="text" name="new_password" class="form-control" placeholder="Default: 123456"></div>
                        </div>
                        <small class="text-muted d-block mt-2">A new account will be created. If password is left blank, it will be <strong>123456</strong>.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Room Type</label>
                            <select name="room_type" id="room_type" class="form-select" required onchange="updateRoomOptions(); calculateTotal(); checkAvailability()">
                                <option value="Single" <?= $pre_room_type == 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="4-Bed" <?= $pre_room_type == '4-Bed' ? 'selected' : '' ?>>4-Bed</option>
                                <option value="6-Bed" <?= $pre_room_type == '6-Bed' ? 'selected' : '' ?>>6-Bed</option>
                            </select>
                            <small id="availability_status" class="fw-bold mt-1 d-block"></small>
                        </div>
                        <div class="col-md-6 mb-3" id="bed_pref_div" style="display:none;">
                            <label class="form-label fw-bold">Bed Preference</label>
                            <select name="bed_preference" class="form-select" onchange="calculateTotal(); checkAvailability()">
                                <option value="Any">Any</option>
                                <option value="Lower Bunk">Lower Bunk</option>
                                <option value="Upper Bunk">Upper Bunk</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Duration</label>
                        <select id="duration_select" class="form-select" onchange="updateCheckoutDate()">
                            <option value="custom">Custom Dates</option>
                            <option value="1">1 Month</option>
                            <option value="6">6 Months</option>
                            <option value="12">1 Year</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-in</label>
                            <input type="date" name="cin" id="cin" class="form-control" required onchange="updateCheckoutDate(); calculateTotal(); checkAvailability()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Check-out</label>
                            <input type="date" name="cout" id="cout" class="form-control" required onchange="calculateTotal(); checkAvailability()">
                        </div>
                    </div>

                    <div class="alert alert-light border text-end">
                        <span class="h5">Total: </span><span class="h4 text-success fw-bold">₱<span id="totalAmount">0.00</span></span>
                    </div>

                    <button type="submit" name="add_reservation" class="btn btn-custom w-100 py-2">Create Reservation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const roomPrices = <?= json_encode($room_prices_js) ?>;

function toggleUserSection() {
    if(document.getElementById('type_new').checked) {
        document.getElementById('existing_user_section').style.display = 'none';
        document.getElementById('new_user_section').style.display = 'block';
        document.querySelector('select[name="user_id"]').required = false;
        document.querySelector('input[name="new_lname"]').required = true;
        document.querySelector('input[name="new_fname"]').required = true;
        document.querySelector('input[name="new_email"]').required = true;
    } else {
        document.getElementById('existing_user_section').style.display = 'block';
        document.getElementById('new_user_section').style.display = 'none';
        document.querySelector('select[name="user_id"]').required = true;
        document.querySelector('input[name="new_lname"]').required = false;
        document.querySelector('input[name="new_fname"]').required = false;
        document.querySelector('input[name="new_email"]').required = false;
    }
}

function updateRoomOptions() {
    let room = document.getElementById('room_type').value;
    let prefDiv = document.getElementById('bed_pref_div');
    if (room && room.includes('Bed')) {
        prefDiv.style.display = 'block';
    } else {
        prefDiv.style.display = 'none';
        document.querySelector('select[name="bed_preference"]').value = 'Any';
    }
}

function updateCheckoutDate() {
    let duration = document.getElementById('duration_select').value;
    let cinInput = document.getElementById('cin');
    let coutInput = document.getElementById('cout');
    
    // Auto-set Check-in to today if empty when selecting duration
    if (!cinInput.value && duration !== 'custom') {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        cinInput.value = `${yyyy}-${mm}-${dd}`;
    }

    if(duration !== 'custom' && cinInput.value) {
        let d = new Date(cinInput.value);
        let originalDay = d.getDate();
        d.setMonth(d.getMonth() + parseInt(duration));
        // Handle month overflow (e.g. Jan 31 + 1 month -> Feb 28/29)
        if (d.getDate() !== originalDay) {
            d.setDate(0);
        }
        coutInput.value = d.toISOString().split('T')[0];
    }
    calculateTotal();
    checkAvailability();
}

function calculateTotal() {
    let room = document.getElementById('room_type').value;
    let cin = document.getElementById('cin').value;
    let cout = document.getElementById('cout').value;
    let bedPref = document.querySelector('select[name="bed_preference"]').value;

    if(room && cin && cout) {
        let d1 = new Date(cin);
        let d2 = new Date(cout);
        
        // Calculate exact months and remaining days for pricing
        let months = (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth());
        if (d2.getDate() < d1.getDate()) {
            months--;
        }
        
        let tempDate = new Date(d1);
        tempDate.setMonth(tempDate.getMonth() + months);
        let daysDiff = Math.ceil((d2 - tempDate) / (1000 * 3600 * 24));

        let priceData = roomPrices[room] || {};
        let price = (room !== 'Single' && bedPref === 'Upper Bunk') ? (priceData.upper || priceData.base) : (room !== 'Single' && bedPref === 'Lower Bunk' ? (priceData.lower || priceData.base) : priceData.base);
        
        let total = (months * parseFloat(price)) + (daysDiff * (parseFloat(price) / 30));
        document.getElementById('totalAmount').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        document.getElementById('totalAmount').innerText = "0.00";
    }
}

function checkAvailability() {
    let room = document.getElementById('room_type').value;
    let cin = document.getElementById('cin').value;
    let cout = document.getElementById('cout').value;
    let bedPref = document.querySelector('select[name="bed_preference"]').value;
    let statusSpan = document.getElementById('availability_status');

    if(room && cin && cout) {
        statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
        statusSpan.className = 'fw-bold mt-1 d-block text-muted';

        fetch(`../users/get_rooms.php?checkin=${cin}&checkout=${cout}`)
            .then(response => response.json())
            .then(data => {
                let available = data.some(r => {
                    if (r.room_type !== room) return false;
                    if (bedPref === 'Lower Bunk') return r.avail_lower > 0;
                    if (bedPref === 'Upper Bunk') return r.avail_upper > 0;
                    return r.available_beds > 0;
                });
                if(available) {
                    statusSpan.innerHTML = '<i class="fas fa-check-circle"></i> Available';
                    statusSpan.className = 'fw-bold mt-1 d-block text-success';
                } else {
                    statusSpan.innerHTML = '<i class="fas fa-times-circle"></i> Fully Booked';
                    statusSpan.className = 'fw-bold mt-1 d-block text-danger';
                }
            });
    } else {
        statusSpan.innerHTML = '';
    }
}

<?php if(isset($new_reservation_id)): ?>
Swal.fire({
    title: 'Success!',
    html: '<?= $account_msg ?>Reservation created successfully.<br><br>Do you want to print the receipt?',
    icon: 'success',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-print"></i> Print Receipt',
    cancelButtonText: 'Close'
}).then((result) => {
    if (result.isConfirmed) {
        window.open('view_receipt.php?id=<?= $new_reservation_id ?>', '_blank');
    }
});
<?php endif; ?>

// Initialize on load if room type is pre-selected
window.addEventListener('DOMContentLoaded', (event) => {
    if(document.getElementById('room_type').value) {
        updateRoomOptions();
    }
});
</script>
</body>
</html>