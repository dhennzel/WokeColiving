<?php
session_start();
include('../db.php');

$error = "";

if (isset($_POST['reset_request'])) {
    $email = trim($_POST['email']);
    
    $stmt = mysqli_prepare($conn, "SELECT user_id, full_name FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($res)) {
        $token = strtoupper(bin2hex(random_bytes(3))); // 6 char code
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $uid = $row['user_id'];
        
        // Update DB
        mysqli_query($conn, "UPDATE users SET reset_token='$token', reset_expiry='$expiry' WHERE user_id=$uid");
        
        // Send Email
        $msg = "Hello " . $row['full_name'] . ",<br><br>You requested a password reset. Your verification code is: <h2 style='color:#2E7D32;'>$token</h2><br>This code expires in 1 hour.";
        
        send_notification($conn, $uid, $msg, "Password Reset");
        
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $error = "Email address not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Woke Coliving INC</title>
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
    <h3 class="mb-4 fw-bold" style="color: #1B5E20;">Reset Password</h3>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <form method="POST" class="text-start">
        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Enter your email" required></div>
        <button type="submit" name="reset_request" class="btn btn-custom mb-3">Send Code</button>
        <div class="text-center"><a href="login.php" class="text-muted small text-decoration-none">&larr; Back to Login</a></div>
    </form>
</div>
</body>
</html>