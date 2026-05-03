<?php
include '../db.php';
session_start();

// Prevent logged-in tenants from accessing the login page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['night_mode'] = $user['night_mode'] ?? 0;
        
        $redirect = '../index.php';
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $redirect = $_GET['redirect'];
        } elseif (isset($_SESSION['login_redirect']) && !empty($_SESSION['login_redirect'])) {
            $redirect = $_SESSION['login_redirect'];
            unset($_SESSION['login_redirect']);
        }
        
        header("Location: $redirect");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}

$redirect_param = isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="users_CSS/auth.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo">
            <h2>Welcome To Dormitory</h2>
        </div>
        <?php if ($error) { echo "<div class='alert alert-danger py-2 small mb-3'>$error</div>"; } ?>
        <form method="POST" action="login.php<?= $redirect_param ?>">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" id="togglePassword">
                    <i class="fas fa-eye-slash text-muted"></i>
                </span>
            </div>
            <button type="submit" name="login" class="btn btn-custom mb-3">Login</button>
            <div class="text-center">
                <a href="forgot_password.php" class="text-muted small text-decoration-none">Forgot Password?</a>
            </div>
        </form>
        <div class="auth-footer">
            <p class="text-muted mb-1">Don't have an account? <a href="register.php<?= $redirect_param ?>">Create One</a></p>
            <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
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
