<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}

$current_role = $_SESSION['admin_role'] ?? 'Admin';
$message = "";
$error = "";

// Add Admin
if(isset($_POST['add_admin'])){
    if($current_role !== 'Super Admin'){
        $error = "Only Super Admins can add new admins.";
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $raw_pass = !empty($_POST['password']) ? $_POST['password'] : '12345678';
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        if(!empty($_POST['password']) && (!preg_match('/[a-zA-Z]/', $raw_pass) || !preg_match('/[0-9]/', $raw_pass))){
            $error = "Password must contain at least one letter and one number.";
        }
        
        $name_regex = "/^[a-zA-Z\sñÑ]+$/";
        if (!preg_match($name_regex, $first_name) || !preg_match($name_regex, $last_name)) {
            $error = "Names should only contain letters and spaces. Signs and numbers are not allowed.";
        }

        if(empty($error)){
            $password = mysqli_real_escape_string($conn, $raw_pass);
            $check = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
            if(mysqli_num_rows($check) > 0){
                $error = "Username already exists.";
            } else {
                mysqli_query($conn, "INSERT INTO admin (username, password, role, first_name, last_name, email, phone_number) VALUES ('$username', '$password', '$role', '$first_name', '$last_name', '$email', '$phone')");
                $message = "New admin added successfully.";
            }
        }
    }
}

// Delete Admin
if(isset($_GET['delete'])){
    if($current_role !== 'Super Admin'){
        $error = "Only Super Admins can delete admins.";
    } else {
        $id = (int)$_GET['delete'];
        $me = $_SESSION['admin_username'];
        $check_me = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id AND username='$me'");
        if(mysqli_num_rows($check_me) > 0){
            $error = "You cannot delete your own account.";
        } else {
            mysqli_query($conn, "DELETE FROM admin WHERE id=$id");
            $message = "Admin deleted successfully.";
        }
    }
}

$admins = mysqli_query($conn, "SELECT * FROM admin");
$theme = get_theme_colors($conn);

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Roles | Woke Coliving INC</title>
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
                <h1>Manage Admin Roles</h1>
            </div>
            <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-custom p-4 mb-4">
                        <h5 class="fw-bold mb-3">Add New Admin</h5>
                        <form method="POST">
                            <div class="row g-2 mb-3">
                                <div class="col-6"><label class="form-label small fw-bold">First Name</label><input type="text" name="first_name" class="form-control" required oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')"></div>
                                <div class="col-6"><label class="form-label small fw-bold">Last Name</label><input type="text" name="last_name" class="form-control" required oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')"></div>
                            </div>
                            <div class="mb-3"><label class="form-label small fw-bold">Email</label><input type="email" name="email" class="form-control"></div>
                            <div class="mb-3"><label class="form-label small fw-bold">Phone Number</label><input type="text" name="phone_number" class="form-control" placeholder="09xxxxxxxxx" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
                            <div class="mb-3"><label class="form-label small fw-bold">Login Username</label><input type="text" name="username" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label small fw-bold">Login Password</label><input type="password" name="password" class="form-control" placeholder="Default: 12345678"></div>
                            <div class="mb-3"><label class="form-label small fw-bold">Role Access</label><select name="role" class="form-select"><option value="Admin">Admin</option><option value="Super Admin">Super Admin</option></select></div>
                            <button type="submit" name="add_admin" class="btn btn-custom w-100">Add Admin</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card card-custom p-4">
                        <h5 class="fw-bold mb-3">Existing Admins</h5>
                        <table class="table table-hover">
                            <thead><tr><th>Name / Contact</th><th>Username</th><th>Role</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($admins)): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])) ?: '<span class="text-muted fst-italic">Not Set</span>' ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><span class="badge <?= $row['role'] == 'Super Admin' ? 'bg-danger' : 'bg-primary' ?>"><?= $row['role'] ?></span></td>
                                    <td><?php if($current_role == 'Super Admin' && $row['username'] != $_SESSION['admin_username']): ?><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this admin?')">Delete</a><?php endif; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) { lastUpdate = t; } 
        else if (t > lastUpdate) { sessionStorage.setItem('playNotifSound', 'true'); location.reload(); }
    });
}
setInterval(checkUpdates, 3000);

if(sessionStorage.getItem('playNotifSound') === 'true') {
    let audio = new Audio('../assets/sounds/notification.mp3');
    audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
    audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
    sessionStorage.removeItem('playNotifSound');
}
</script>
</body>
</html>