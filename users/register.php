<?php
include '../db.php';

$error = "";

if (isset($_POST['register'])) {
    $lname = trim($_POST['lname']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = mysqli_real_escape_string($conn, $lname);
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validate Phone Number (Philippines format: 09xxxxxxxxx or +639xxxxxxxxx)
    if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
        $error = "Invalid phone number. Please use a valid Philippine mobile number (e.g., 09xxxxxxxxx).";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'")) > 0) {
        // Check if email already exists
        $error = "Email address is already registered."; 
    } else {
        $sql = "INSERT INTO users (last_name, first_name, middle_name, email, phone_number, password) VALUES ('$lname', '$fname', '$mname', '$email', '$phone', '$pass')";
        
        try {
            if(mysqli_query($conn, $sql)){
                header("Location: login.php");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        :root {
            --primary-green: #2E7D32;
            --dark-green: #1B5E20;
            --accent-yellow: #FBC02D;
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
            background: linear-gradient(to bottom right, rgba(27, 94, 32, 0.8), rgba(27, 94, 32, 0.6));
            backdrop-filter: blur(3px);
            z-index: 1;
        }
        .register-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease-out;
        }
        h2 { font-family: 'Playfair Display', serif; color: var(--dark-green); font-weight: bold; }
        .form-control { border-radius: 50px; padding: 12px 20px; border: 1px solid #ddd; background: #f8f9fa; margin-bottom: 15px; }
        .form-control:focus { border-color: var(--primary-green); box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25); background: #fff; }
        .btn-custom {
            background-color: var(--accent-yellow);
            color: var(--dark-green);
            font-weight: 700;
            border-radius: 50px;
            padding: 12px;
            width: 100%;
            border: none;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-custom:hover { background-color: #F9A825; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(251, 192, 45, 0.4); }
        .link-custom { color: var(--primary-green); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .link-custom:hover { color: var(--accent-yellow); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="overlay"></div>
<div class="register-card text-center">
    <div class="mb-3">
        <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 4px solid var(--accent-yellow); padding: 2px; background: white;">
    </div>
    <h2 class="mb-4">Join Our Community</h2>
    <?php if ($error) { echo "<div class='alert alert-danger py-2 small'>$error</div>"; } ?>
    <form method="POST" class="text-start">
        <div class="row g-2 mb-3">
            <div class="col-4"><input type="text" name="lname" class="form-control" placeholder="Last Name" required></div>
            <div class="col-4"><input type="text" name="fname" class="form-control" placeholder="First Name" required></div>
            <div class="col-4"><input type="text" name="mname" class="form-control" placeholder="Middle Name"></div>
        </div>
        <input type="email" name="email" class="form-control" placeholder="Email Address" required>
        <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09xxxxxxxxx)" pattern="^(09|\+639)\d{9}$" title="Please enter a valid Philippine mobile number (e.g., 09xxxxxxxxx)" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <button type="submit" name="register" class="btn btn-custom mt-2 mb-3">Create Account</button>
    </form>
    <div class="mt-2">
        <p class="small text-muted mb-1">Already have an account?</p>
        <a href="login.php" class="link-custom">Login Here</a>
    </div>
    <div class="mt-3">
        <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
</div>

</body>
</html>
