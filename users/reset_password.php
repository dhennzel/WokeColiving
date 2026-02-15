<?php
session_start();
include('../db.php');

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$error = "";

if (isset($_POST['reset_password'])) {
    $code = trim($_POST['code']);
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email=? AND reset_token=? AND reset_expiry > NOW()");
        mysqli_stmt_bind_param($stmt, "ss", $email, $code);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($res)) {
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);
            $uid = $row['user_id'];
            
            mysqli_query($conn, "UPDATE users SET password='$new_hash', reset_token=NULL, reset_expiry=NULL WHERE user_id=$uid");
            
            unset($_SESSION['reset_email']);
            echo "<script>alert('Password reset successfully! Please login.'); window.location='login.php';</script>";
            exit;
        } else {
            $error = "Invalid or expired verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        <?php
        $bg_url = '../assets/images/hero.jpg';
        $q_bg = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='login_bg'");
        if($row_bg = mysqli_fetch_assoc($q_bg)){
            if(!empty($row_bg['setting_value']) && file_exists("../assets/images/" . $row_bg['setting_value'])){
                $bg_url = "../assets/images/" . $row_bg['setting_value'];
            }
        }
        ?>
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
            background: linear-gradient(to bottom right, rgba(27, 94, 32, 0.8), rgba(27, 94, 32, 0.6));
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
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .form-control { border-radius: 50px; padding: 12px 20px; }
        .btn-custom { background-color: #FBC02D; color: #1B5E20; font-weight: 700; border-radius: 50px; padding: 12px; width: 100%; border: none; }
        .btn-custom:hover { background-color: #F9A825; }
    </style>
</head>
<body>
<div class="overlay"></div>
<div class="login-card text-center">
    <h3 class="mb-4 fw-bold" style="color: #1B5E20;">Set New Password</h3>
    <p class="small text-muted">A verification code has been sent to <strong><?= htmlspecialchars($email) ?></strong></p>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <form method="POST" class="text-start">
        <div class="mb-3">
            <input type="text" name="code" class="form-control" placeholder="Verification Code" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="New Password" required>
        </div>
        <div class="mb-4">
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
        </div>
        <button type="submit" name="reset_password" class="btn btn-custom mb-3">Update Password</button>
    </form>
</div>
</body>
</html>