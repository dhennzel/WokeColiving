<?php
session_start();
include("../db.php");

$message = "";
$error = "";

// Define a recovery code (You can also store this in the database)
$RECOVERY_CODE = "admin123"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $code = $_POST['recovery_code'];
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    if ($code !== $RECOVERY_CODE) {
        $error = "Invalid Recovery Code.";
    } elseif (!preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error = "Password must contain at least one letter and one number.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if admin exists
        $check = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $sql = "UPDATE admin SET password='$new_password' WHERE username='$username'";
            if (mysqli_query($conn, $sql)) {
                $message = "Password reset successfully. <a href='admin_login.php'>Login here</a>";
            } else {
                $error = "Database error.";
            }
        } else {
            $error = "Admin username not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_CSS/admin_login.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <style>
        <?php
        $theme = get_theme_colors($conn);
        $bg_url = '../assets/images/hero.jpg';
        $q_bg = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='login_bg'");
        if($row_bg = mysqli_fetch_assoc($q_bg)){
            if(!empty($row_bg['setting_value']) && file_exists("../assets/images/" . $row_bg['setting_value'])){
                $bg_url = "../assets/images/" . $row_bg['setting_value'];
            }
        }
        ?>
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
    <h2 class="mb-4">Reset Password</h2>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <?php if ($message) { echo "<div class='alert alert-success py-2 small'>$message</div>"; } ?>
    <form method="POST" class="text-start">
        <input type="text" name="username" class="form-control" placeholder="Admin Username" required>
        <input type="password" name="recovery_code" class="form-control" placeholder="Recovery Code" required>
        <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
        <button type="submit" class="btn btn-custom mt-2">Reset Password</button>
    </form>
    <div class="mt-3 text-center">
        <a href="admin_login.php" class="text-muted small text-decoration-none">&larr; Back to Login</a>
    </div>
</div>
</body>
</html>