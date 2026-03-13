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
            $bg_url = "../assets/images/" . $row_bg['setting_value'];
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
            background: url('<?= $bg_url ?>') no-repeat center center/cover;
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
    <h2 class="mb-4">Admin Portal</h2>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <form method="POST" class="text-start">
        <input type="text" name="username" class="form-control" placeholder="Username" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <button type="submit" class="btn btn-custom mt-2">Login</button>
    </form>
    <div class="text-center mt-3">
        <a href="admin_forgot_password.php" class="text-muted small text-decoration-none">Forgot Password?</a>
    </div>
</div>

</body>
</html>
