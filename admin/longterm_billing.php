<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// 1. Ensure utility_bills table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS utility_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    bill_date DATE NOT NULL,
    electric_start DECIMAL(10,2) DEFAULT 0,
    electric_end DECIMAL(10,2) DEFAULT 0,
    electric_rate DECIMAL(10,2) DEFAULT 0,
    water_start DECIMAL(10,2) DEFAULT 0,
    water_end DECIMAL(10,2) DEFAULT 0,
    water_rate DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Ensure payments table has description column for bill details
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'description'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN description VARCHAR(255) DEFAULT 'Room Payment'");
}

$message = "";
$error = "";

// Handle Bill Generation
if(isset($_POST['generate_bill'])){
    $res_id = (int)$_POST['reservation_id'];
    $bill_date = $_POST['bill_date'];
    
    // Electricity
    $e_start = (float)$_POST['electric_start'];
    $e_end = (float)$_POST['electric_end'];
    $e_rate = (float)$_POST['electric_rate'];
    $e_usage = max(0, $e_end - $e_start);
    $e_cost = $e_usage * $e_rate;

    // Water
    $w_start = (float)$_POST['water_start'];
    $w_end = (float)$_POST['water_end'];
    $w_rate = (float)$_POST['water_rate'];
    $w_usage = max(0, $w_end - $w_start);
    $w_cost = $w_usage * $w_rate;

    $total = $e_cost + $w_cost;

    if($total > 0){
        // Insert into utility_bills log
        $stmt = mysqli_prepare($conn, "INSERT INTO utility_bills (reservation_id, bill_date, electric_start, electric_end, electric_rate, water_start, water_end, water_rate, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isddddddd", $res_id, $bill_date, $e_start, $e_end, $e_rate, $w_start, $w_end, $w_rate, $total);
        
        if(mysqli_stmt_execute($stmt)){
            // Add to payments as Unpaid Bill
            $desc = "Utility Bill ($bill_date) - Elec: {$e_usage}kw, Water: {$w_usage}m3";
            $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (reservation_id, amount, payment_method, payment_status, payment_date, description) VALUES (?, ?, 'Cash', 'Unpaid', NOW(), ?)");
            mysqli_stmt_bind_param($pay_stmt, "ids", $res_id, $total, $desc);
            mysqli_stmt_execute($pay_stmt);
            
            $message = "Utility bill generated and added to tenant's account.";
            trigger_update($conn);
            
            // Notify User
            $u_q = mysqli_query($conn, "SELECT user_id FROM reservations WHERE reservation_id=$res_id");
            $uid = mysqli_fetch_assoc($u_q)['user_id'];
            send_notification($conn, $uid, "🧾 <strong>New Utility Bill</strong><br>A bill of ₱".number_format($total,2)." has been generated for $bill_date.", "Billing");
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error = "Total bill amount is 0. Please check readings.";
    }
}

// Fetch Long-term Tenants (>= 6 months)
$query = "SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, rm.room_name 
          FROM reservations r 
          JOIN users u ON r.user_id = u.user_id 
          JOIN rooms rm ON r.room_id = rm.room_id 
          WHERE r.status = 'Approved' AND r.months >= 6 
          ORDER BY r.end_date ASC";
$tenants = mysqli_query($conn, $query);

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
    <title>Utility Billing | Woke Coliving INC</title>
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
            <div class="page-header">
                <h1>Utility Billing (Long-term Tenants)</h1>
            </div>

            <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="card card-table p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Room</th>
                                <th>Contract End</th>
                                <th>Last Bill</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($tenants)) { 
                                // Get last bill date
                                $rid = $row['reservation_id'];
                                $lb_q = mysqli_query($conn, "SELECT bill_date, electric_end, water_end FROM utility_bills WHERE reservation_id=$rid ORDER BY bill_date DESC LIMIT 1");
                                $last_bill = mysqli_fetch_assoc($lb_q);
                                $last_date = $last_bill ? $last_bill['bill_date'] : 'None';
                                $prev_e = $last_bill ? $last_bill['electric_end'] : 0;
                                $prev_w = $last_bill ? $last_bill['water_end'] : 0;
                            ?>
                            <tr>
                                <td class="fw-bold"><?= $row['full_name'] ?></td>
                                <td><?= $row['room_name'] ?></td>
                                <td><?= $row['end_date'] ?></td>
                                <td><?= $last_date ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-custom" onclick="openBillModal(<?= $rid ?>, '<?= addslashes($row['full_name']) ?>', <?= $prev_e ?>, <?= $prev_w ?>)">
                                        <i class="fas fa-file-invoice-dollar me-2"></i>Generate Bill
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-1" onclick="openHistoryModal(<?= $rid ?>)">
                                        <i class="fas fa-history me-1"></i> History
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i>Utility Bill History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyModalBody">
                <!-- History content will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Billing Modal -->
<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Generate Utility Bill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Billing for: <strong id="modalTenantName"></strong></p>
                    <input type="hidden" name="reservation_id" id="modalResId">
                    
                    <div class="mb-3">
                        <label class="form-label">Billing Month/Date</label>
                        <input type="date" name="bill_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <h6 class="text-warning fw-bold mt-4"><i class="fas fa-bolt me-2"></i>Electricity</h6>
                    <div class="row g-2">
                        <div class="col-4"><label class="small">Prev Reading</label><input type="number" step="0.01" name="electric_start" id="e_start" class="form-control" required></div>
                        <div class="col-4"><label class="small">Curr Reading</label><input type="number" step="0.01" name="electric_end" class="form-control" required></div>
                        <div class="col-4"><label class="small">Rate (₱/kw)</label><input type="number" step="0.01" name="electric_rate" class="form-control" value="12.00" required></div>
                    </div>

                    <h6 class="text-info fw-bold mt-4"><i class="fas fa-tint me-2"></i>Water</h6>
                    <div class="row g-2">
                        <div class="col-4"><label class="small">Prev Reading</label><input type="number" step="0.01" name="water_start" id="w_start" class="form-control" required></div>
                        <div class="col-4"><label class="small">Curr Reading</label><input type="number" step="0.01" name="water_end" class="form-control" required></div>
                        <div class="col-4"><label class="small">Rate (₱/m3)</label><input type="number" step="0.01" name="water_rate" class="form-control" value="35.00" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_bill" class="btn btn-success fw-bold">Calculate & Save</button>
                </div>
            </form>
        </div>
    </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function openBillModal(id, name, prevE, prevW) {
    document.getElementById('modalResId').value = id;
    document.getElementById('modalTenantName').innerText = name;
    document.getElementById('e_start').value = prevE;
    document.getElementById('w_start').value = prevW;
    new bootstrap.Modal(document.getElementById('billModal')).show();
}

function openHistoryModal(resId) {
    const modalBody = document.getElementById('historyModalBody');
    modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading history...</p></div>';
    
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();
    
    fetch('get_utility_history.php?reservation_id=' + resId)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load history. Please try again.</div>';
        });
}

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