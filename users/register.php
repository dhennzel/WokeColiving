<?php
include '../db.php';

$error = "";

$lname = "";
$fname = "";
$mname = "";
$suffix = "";
$gender = "";
$email = "";
$phone = "";

if (isset($_POST['register'])) {
    $lname = mb_convert_case(trim($_POST['lname']), MB_CASE_TITLE, "UTF-8");
    $fname = mb_convert_case(trim($_POST['fname']), MB_CASE_TITLE, "UTF-8");
    $mname = mb_convert_case(trim($_POST['mname']), MB_CASE_TITLE, "UTF-8");
    $suffix = trim($_POST['suffix'] ?? '');
    $lname = mysqli_real_escape_string($conn, $lname);
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $suffix = mysqli_real_escape_string($conn, $suffix);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $raw_pass = $_POST['password'];

    $letter_count = preg_match_all('/[a-zA-Z]/', $raw_pass);
    $digit_count = preg_match_all('/[0-9]/', $raw_pass);
    $name_regex = "/^[a-zA-Z\sñÑ.-]+$/";

    // Validate Phone Number (Philippines format: 09xxxxxxxxx or +639xxxxxxxxx)
    if (!preg_match($name_regex, $fname) || !preg_match($name_regex, $lname) || (!empty($mname) && !preg_match($name_regex, $mname)) || (!empty($suffix) && !preg_match($name_regex, $suffix))) {
        $error = "First, Middle, Last names, and Suffixes should only contain letters, spaces, periods, and hyphens. Numbers and special characters are not allowed.";
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $error = "Invalid phone number. Please use a valid 11-digit Philippine mobile number (e.g., 09xxxxxxxxx).";
    } elseif (strlen($raw_pass) < 6 || strlen($raw_pass) > 8 || $digit_count < 1) {
        $error = "Password must be between 6 to 8 characters and must contain at least one number.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE first_name='$fname' AND last_name='$lname' AND middle_name='$mname' AND suffix='$suffix'")) > 0) {
        $error = "A user with this full name is already registered.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'")) > 0) {
        // Check if email already exists
        $error = "Email address is already registered."; 
    } else {
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (last_name, first_name, middle_name, suffix, gender, email, phone_number, password) VALUES ('$lname', '$fname', '$mname', '$suffix', '$gender', '$email', '$phone', '$pass')";
        
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
                <div class="col-3"><input type="text" name="lname" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars($lname) ?>" oninput="this.value = this.value.replace(/[0-9]/g, '')" style="text-transform: capitalize;"></div>
                <div class="col-3"><input type="text" name="fname" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars($fname) ?>" oninput="this.value = this.value.replace(/[0-9]/g, '')" style="text-transform: capitalize;"></div>
                <div class="col-3"><input type="text" name="mname" class="form-control" placeholder="Middle Name" value="<?= htmlspecialchars($mname) ?>" oninput="this.value = this.value.replace(/[0-9]/g, '')" style="text-transform: capitalize;"></div>
                <div class="col-3">
                    <select name="suffix" class="form-control">
                        <option value="">Suffix</option>
                        <option value="Jr." <?= $suffix == 'Jr.' ? 'selected' : '' ?>>Jr.</option>
                        <option value="Sr." <?= $suffix == 'Sr.' ? 'selected' : '' ?>>Sr.</option>
                        <option value="II" <?= $suffix == 'II' ? 'selected' : '' ?>>II</option>
                        <option value="III" <?= $suffix == 'III' ? 'selected' : '' ?>>III</option>
                        <option value="IV" <?= $suffix == 'IV' ? 'selected' : '' ?>>IV</option>
                        <option value="V" <?= $suffix == 'V' ? 'selected' : '' ?>>V</option>
                    </select>
                </div>
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
                <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09xxxxxxxxx)" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" required value="<?= htmlspecialchars($phone) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password (6-8 chars, with number)" required minlength="6" maxlength="8">
                <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" id="togglePassword">
                    <i class="fas fa-eye-slash text-muted"></i>
                </span>
            </div>
            <button type="submit" name="register" class="btn btn-custom mb-3">Create Account</button>
        </form>
        <div class="auth-footer">
            <p class="text-muted mb-1">Already have an account? <a href="login.php">Login Here</a></p>
            <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function () {
        // toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // toggle the icon
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>
