<?php
session_start();
include("../db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_full_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
        $_SESSION['admin_logged_in'] = true; // session flag
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <link rel="stylesheet" href="admin_CSS/admin_login.css">
    <?php $theme = get_theme_colors($conn); ?>
    <?php
    $bg_url = '../assets/images/hero.jpg';
    $q_bg = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='login_bg'");
    if($row_bg = mysqli_fetch_assoc($q_bg)){
        if(!empty($row_bg['setting_value']) && file_exists("../assets/images/" . $row_bg['setting_value'])){
            $bg_path = "../assets/images/" . $row_bg['setting_value'];
            $bg_url = $bg_path . "?v=" . filemtime($bg_path);
        }
    }
    ?>
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: url('<?= htmlspecialchars($bg_url, ENT_QUOTES) ?>') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="overlay"></div>
<div class="login-card text-center">
    <div class="mb-4">
        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 4px solid var(--accent-yellow); padding: 2px; background: white;">
    </div>
    <h2 class="mb-4">Woke Coliving Reservation And Management System</h2>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <form method="POST" class="text-start">
        <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="mb-3 position-relative">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
            <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" id="togglePassword">
                <i class="fas fa-eye-slash text-muted"></i>
            </span>
        </div>
        <button type="submit" class="btn btn-custom mt-2">Login</button>
    </form>
    <div class="text-center mt-3">
        <a href="admin_forgot_password.php" class="text-muted small text-decoration-none">Forgot Password?</a>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>
