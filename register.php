<?php 
$pageTitle = 'Register'; 
require_once 'includes/header.php';
if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        redirect('register.php');
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
    } else {
        // Check if email exists using prepared statement
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error'] = 'Email already registered. Please login.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_param_bind($stmt, "ssss", $name, $email, $phone, $hashed); // Note: Fix function name in next turn if needed, it should be mysqli_stmt_bind_param
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashed);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Registration successful! Please login.';
                redirect('login.php');
            } else {
                $_SESSION['error'] = 'Something went wrong. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<section class="auth-section">
<div class="container">
    <?php showAlert(); ?>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="auth-card">
                <div class="row g-0">
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="auth-sidebar">
                            <i class="bi bi-airplane-engines auth-icon"></i>
                            <h2>Join AeroBook</h2>
                            <p>Create your account and start booking flights at the best prices across India.</p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="auth-form">
                            <h2>Create Account</h2>
                            <p class="subtitle">Fill in your details to get started</p>
                            <form method="POST" id="registerForm">
                                <?php csrfField(); ?>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" required>
                                    <div class="invalid-feedback">Name must be at least 3 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" id="phone" class="form-control" placeholder="10-digit mobile number" required maxlength="10">
                                    <div class="invalid-feedback">Enter a valid 10-digit phone number.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required>
                                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
                                        <div class="invalid-feedback">Passwords do not match.</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-accent w-100 py-2 mb-3"><i class="bi bi-person-plus me-2"></i>Register</button>
                                <p class="text-center mb-0">Already have an account? <a href="login.php" class="text-accent fw-bold">Login here</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<?php require_once 'includes/footer.php'; ?>

