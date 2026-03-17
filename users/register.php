<?php
include '../db.php';

$error = "";

$lname = "";
$fname = "";
$mname = "";
$gender = "";
$email = "";
$phone = "";

if (isset($_POST['register'])) {
    $lname = trim($_POST['lname']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = mysqli_real_escape_string($conn, $lname);
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $raw_pass = $_POST['password'];

    // Validate Phone Number (Philippines format: 09xxxxxxxxx or +639xxxxxxxxx)
    if (!preg_match('/^09\d{9}$/', $phone)) {
        $error = "Invalid phone number. Please use a valid 11-digit Philippine mobile number (e.g., 09xxxxxxxxx).";
    } elseif (strlen($raw_pass) > 8) {
        $error = "Password must be maximum 8 characters.";
    } elseif (!preg_match('/[a-zA-Z]/', $raw_pass) || !preg_match('/[0-9]/', $raw_pass)) {
        $error = "Password must contain at least one letter and one number.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'")) > 0) {
        // Check if email already exists
        $error = "Email address is already registered."; 
    } else {
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (last_name, first_name, middle_name, gender, email, phone_number, password) VALUES ('$lname', '$fname', '$mname', '$gender', '$email', '$phone', '$pass')";
        
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
    <link rel="stylesheet" href="users_CSS/auth.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo">
            <h2>Join Our Community</h2>
        </div>
        <?php if ($error) { echo "<div class='alert alert-danger py-2 small mb-3'>$error</div>"; } ?>
        <form method="POST">
            <div class="row g-2 mb-2">
                <div class="col-4"><input type="text" name="lname" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars($lname) ?>"></div>
                <div class="col-4"><input type="text" name="fname" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars($fname) ?>"></div>
                <div class="col-4"><input type="text" name="mname" class="form-control" placeholder="Middle Name" value="<?= htmlspecialchars($mname) ?>"></div>
            </div>
            <div class="mb-2">
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="gender" id="gender_male" value="Male" required <?= $gender == 'Male' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-success" for="gender_male">Male</label>
                    <input type="radio" class="btn-check" name="gender" id="gender_female" value="Female" required <?= $gender == 'Female' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-success" for="gender_female">Female</label>
                </div>
            </div>
            <div class="mb-2">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>">
            </div>
            <div class="mb-2">
                <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09xxxxxxxxx)" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" required value="<?= htmlspecialchars($phone) ?>">
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" name="register" class="btn btn-custom mb-3">Create Account</button>
        </form>
        <div class="auth-footer">
            <p class="text-muted mb-1">Already have an account? <a href="login.php">Login Here</a></p>
            <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>
