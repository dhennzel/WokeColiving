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
    <title>Reset Password | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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
        .overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom right, rgba(27, 94, 32, 0.9), rgba(27, 94, 32, 0.7));
            backdrop-filter: blur(3px);
            z-index: 1;
        }
        .login-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        h2 { font-family: 'Playfair Display', serif; color: var(--dark-green); font-weight: bold; }
        .form-control { border-radius: 50px; padding: 12px 20px; border: 1px solid #ddd; background: #f8f9fa; margin-bottom: 15px; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: 700; border-radius: 50px; padding: 12px; width: 100%; border: none; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-custom:hover { background-color: #F9A825; transform: translateY(-3px); }
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