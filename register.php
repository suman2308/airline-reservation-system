<?php
$pageTitle = 'Register';
require_once 'includes/header.php';
require_once 'includes/Validation.php';
require_once 'includes/Security.php';
require_once 'includes/Auth.php';
require_once 'includes/Mailer.php';
require_once 'includes/Notifications.php';

if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!requireCsrfToken()) {
        redirect('register.php');
    }

    // Rate limiting
    if (!checkRateLimit('register', 3, 300)) {
        logSecurity('Registration rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        setFlash('error', 'Too many registration attempts. Please try again later.');
        redirect('register.php');
    }

    $errors = validateRegistration($_POST);
    if (!empty($errors)) {
        setFlash('error', $errors[0]);
        redirect('register.php');
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        setFlash('error', 'Email already registered. Please login.');
        mysqli_stmt_close($stmt);
        redirect('register.php');
    }
    mysqli_stmt_close($stmt);

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction for user creation + verification
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashed);
        mysqli_stmt_execute($stmt);
        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

// Generate verification token and send email
        $token = createEmailVerification($user_id);
        $mailer = new AeroMailer();
        $verifyUrl = BASE_URL . 'verify-email.php?token=' . urlencode($token);
        $mailer->sendVerification($email, $name, $verifyUrl);

        mysqli_commit($conn);

        regenerateSession();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        clearRateLimit('register');
        logInfo('New user registered', ['user_id' => $user_id]);

        if (isEmailVerificationRequired()) {
            setFlash('info', 'Registration successful! Please check your email to verify your account before booking flights.');
            redirect('profile.php');
        } else {
            setFlash('success', 'Registration successful! Welcome to AeroBook.');
            redirect('index.php');
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        logError('Registration failed', ['email' => $email, 'error' => $e->getMessage()]);
        setFlash('error', 'Something went wrong. Please try again.');
        redirect('register.php');
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
                            <div class="mt-4">
                                <div class="d-flex align-items-center gap-2 text-white-50 mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Book flights across India</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-white-50 mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Choose your preferred seats</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-white-50">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Manage bookings anytime</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="auth-form">
                            <h2>Create Account</h2>
                            <p class="subtitle">Fill in your details to get started</p>
                            <form method="POST" id="registerForm" novalidate class="needs-loading">
                                <?php csrfField(); ?>
                                <div class="mb-3">
                                    <label class="form-label" for="regName">Full Name</label>
                                    <input type="text" name="name" id="regName" class="form-control" placeholder="Enter your full name" required minlength="3" maxlength="100">
                                    <div class="invalid-feedback">Name must be at least 3 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="regEmail">Email Address</label>
                                    <input type="email" name="email" id="regEmail" class="form-control" placeholder="Enter your email" required maxlength="100">
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="regPhone">Phone Number</label>
                                    <input type="text" name="phone" id="regPhone" class="form-control" placeholder="10-digit mobile number" required maxlength="10" pattern="[6-9]\d{9}">
                                    <div class="invalid-feedback">Enter a valid 10-digit phone number.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="regPassword">Password</label>
                                        <input type="password" name="password" id="regPassword" class="form-control" placeholder="Min 6 characters" required minlength="6">
                                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="regConfirm">Confirm Password</label>
                                        <input type="password" name="confirm_password" id="regConfirm" class="form-control" placeholder="Repeat password" required minlength="6">
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
