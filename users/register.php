<?php
session_start();

// Prevent logged-in tenants from accessing the register page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include '../db.php';

$error = "";
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$redirect_param = !empty($redirect_url) ? '?redirect=' . urlencode($redirect_url) : '';

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
    } elseif (strlen($raw_pass) < 8 || $letter_count < 1 || $digit_count < 1) {
        $error = "Password must be at least 8 characters and contain at least one letter and one number.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE first_name='$fname' AND last_name='$lname' AND middle_name='$mname' AND suffix='$suffix'")) > 0) {
        $error = "A user with this full name is already registered.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'")) > 0) {
        // Check if email already exists
        $error = "Email address is already registered."; 
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE phone_number='$phone'")) > 0) {
        $error = "Phone number is already registered."; 
    } else {
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (last_name, first_name, middle_name, suffix, gender, email, phone_number, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssss", $lname, $fname, $mname, $suffix, $gender, $email, $phone, $pass);
            if(mysqli_stmt_execute($stmt)){
                $new_user_id = mysqli_insert_id($conn);
                
                // Auto-login the newly registered user
                $u_q = mysqli_query($conn, "SELECT role, night_mode FROM users WHERE user_id=$new_user_id");
                if($u_row = mysqli_fetch_assoc($u_q)){
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['role'] = $u_row['role'];
                    $_SESSION['night_mode'] = $u_row['night_mode'] ?? 0;
                }
                
                // Send them straight to the booking form if a redirect is set
                $dest = !empty($redirect_url) ? $redirect_url : '../index.php';
                header("Location: $dest");
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
    <style>
        .name-col {
            transition: flex 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), max-width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), transform 0.3s ease, opacity 0.3s ease;
        }
        .name-col.focused {
            transform: scale(1.02);
            z-index: 10;
        }
        .name-col.focused input, .name-col.focused select {
            box-shadow: 0 4px 15px rgba(52, 184, 117, 0.25);
            border-color: #34B875;
        }
        .name-col input:disabled, .name-col select:disabled {
            background-color: #f1f5f9;
            opacity: 0.6;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo">
            <h2>Join Our Community</h2>
        </div>
        <?php if ($error) { echo "<div class='alert alert-danger py-2 small mb-3'>$error</div>"; } ?>
        <form method="POST" action="register.php<?= $redirect_param ?>">
            <div class="row g-2 mb-2 position-relative" id="dynamic-name-row">
                <div class="name-col col-6 col-md-3 order-1"><input type="text" name="lname" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars($lname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                <div class="name-col col-6 col-md-3 order-1"><input type="text" name="fname" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars($fname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                <div class="name-col col-6 col-md-3 order-1"><input type="text" name="mname" class="form-control" placeholder="Middle Name" value="<?= htmlspecialchars($mname) ?>" oninput="this.value = this.value.replace(/[^a-zA-Z\sñÑ]/g, '')" style="text-transform: capitalize;"></div>
                <div class="name-col col-6 col-md-3 order-1">
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
                <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09xxxxxxxxx)" pattern="^09\d{9}$" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" required value="<?= htmlspecialchars($phone) ?>" oninput="let v = this.value.replace(/[^0-9]/g, ''); if(v.length > 0 && v[0] !== '0') v = '0' + v; if(v.length > 1 && v[1] !== '9') v = '09' + v.substring(2); this.value = v;">
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password (min. 8 chars, with number)" required minlength="8">
                <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" id="togglePassword">
                    <i class="fas fa-eye-slash text-muted"></i>
                </span>
            </div>
            <button type="submit" name="register" class="btn btn-custom mb-3">Create Account</button>
        </form>
        <div class="auth-footer">
            <p class="text-muted mb-1">Already have an account? <a href="login.php<?= $redirect_param ?>">Login Here</a></p>
            <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

<script>
    // Dynamic Name Fields Animation
    document.addEventListener('DOMContentLoaded', function() {
        const nameContainer = document.getElementById('dynamic-name-row');
        const nameCols = document.querySelectorAll('.name-col');
        const nameInputs = document.querySelectorAll('.name-col input, .name-col select');

        const lnameInput = document.querySelector('input[name="lname"]');
        const fnameInput = document.querySelector('input[name="fname"]');
        const mnameInput = document.querySelector('input[name="mname"]');
        const suffixSelect = document.querySelector('select[name="suffix"]');

        // Enforce Step-by-Step Unlocking
        function updateStepByStep() {
            const lFilled = lnameInput.value.trim().length > 0;
            const fFilled = fnameInput.value.trim().length > 0;
            
            fnameInput.disabled = !lFilled;
            mnameInput.disabled = !fFilled;
            suffixSelect.disabled = !fFilled;
        }

        lnameInput.addEventListener('input', updateStepByStep);
        fnameInput.addEventListener('input', updateStepByStep);
        
        // Initialize state on load
        updateStepByStep();

        function updateLayout() {
            let anyInteracted = false;
            nameInputs.forEach(input => {
                if (input === document.activeElement || input.value.trim().length > 0 || (input.tagName === 'SELECT' && input.value !== '')) {
                    anyInteracted = true;
                }
            });

            nameCols.forEach(col => {
                const input = col.querySelector('input, select');
                const hasValue = input.value.trim().length > 0 || (input.tagName === 'SELECT' && input.value !== '');
                const isFocused = (input === document.activeElement);

                if (!anyInteracted) {
                    col.className = 'name-col col-6 col-md-3 order-1 mt-0';
                } else {
                    if (isFocused) {
                        col.className = 'name-col col-12 order-2 mt-2 focused'; // Active drops to bottom
                    } else if (hasValue) {
                        col.className = 'name-col col-12 order-2 mt-2 opacity-100'; // Permanent full size if typed
                    } else {
                        col.className = 'name-col col order-1 mt-0 opacity-75'; // Empties shrink and sit on top
                    }
                }
            });
        }

        nameInputs.forEach(input => {
            input.addEventListener('focus', updateLayout);
            input.addEventListener('blur', updateLayout);
            input.addEventListener('input', updateLayout);
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', updateLayout);
            }
        });
        
        // Run once on load to apply sizes if form was pre-filled
        updateLayout();
    });

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
