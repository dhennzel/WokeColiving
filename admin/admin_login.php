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
            animation: fadeInUp 0.8s ease-out;
        }
        h2 { font-family: 'Playfair Display', serif; color: var(--dark-green); font-weight: bold; }
        .form-control { border-radius: 50px; padding: 12px 20px; border: 1px solid #ddd; background: #f8f9fa; margin-bottom: 15px; }
        .form-control:focus { border-color: var(--primary-green); box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25); background: #fff; }
        .btn-custom { background-color: var(--accent-yellow); color: var(--dark-green); font-weight: 700; border-radius: 50px; padding: 12px; width: 100%; border: none; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-custom:hover { background-color: #F9A825; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(251, 192, 45, 0.4); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
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
