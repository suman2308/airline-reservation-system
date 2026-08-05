<?php
$pageTitle = 'Login';
require_once 'includes/header.php';
require_once 'includes/Validation.php';
require_once 'includes/Security.php';
require_once 'includes/Auth.php';

if (isLoggedIn()) redirect('index.php');

// Try remember me auto-login
loginViaRememberMe();
if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!requireCsrfToken()) {
        redirect('login.php');
    }

    $errors = validateLogin($_POST);
    if (!empty($errors)) {
        setFlash('error', $errors[0]);
        redirect('login.php');
    }

    // Rate limiting
    if (!checkRateLimit('login', 5, 300)) {
        logSecurity('Login rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        setFlash('error', 'Too many login attempts. Please try again in 5 minutes.');
        redirect('login.php');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']) ? true : false;

    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, email_verified_at, failed_logins, locked_until FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_id, $db_name, $db_email, $db_password, $verifiedAt, $failedLogins, $lockedUntil);
    $userFound = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$userFound) {
        // Don't reveal whether email exists
        recordFailedLogin($email);
        logSecurity('Login failed - invalid email', ['email' => $email]);
        setFlash('error', 'Invalid email or password.');
        redirect('login.php');
    }

    // Check if account is locked
    $lockRemaining = isAccountLocked($db_id);
    if ($lockRemaining !== false) {
        logSecurity('Login blocked - account locked', ['user_id' => $db_id]);
        setFlash('error', "Account temporarily locked due to multiple failed attempts. Please try again in {$lockRemaining} minutes.");
        redirect('login.php');
    }

    // Verify password
    if (password_verify($password, $db_password)) {
        // Check email verification if required
        if (isEmailVerificationRequired() && $verifiedAt === null) {
            // Resend verification
            resendVerification($db_id);
            setFlash('warning', 'Please verify your email address before logging in. A new verification link has been sent to your email.');
            redirect('login.php');
        }

        // Successful login
        regenerateSession();
        $_SESSION['user_id'] = $db_id;
        $_SESSION['user_name'] = $db_name;
        $_SESSION['user_email'] = $db_email;
        $_SESSION['login_time'] = time();

        // Reset failed login counter
        $resetStmt = mysqli_prepare($conn, "UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        mysqli_stmt_bind_param($resetStmt, "si", $ip, $db_id);
        mysqli_stmt_execute($resetStmt);
        mysqli_stmt_close($resetStmt);

        // Record login history
        recordLoginHistory($db_id, true);

        // Record session
        recordUserSession($db_id);

        // Remember Me
        if ($remember) {
            createRememberMe($db_id);
        }

        clearRateLimit('login');
        logInfo('User login successful', ['user_id' => $db_id]);
        require_once 'includes/Notifications.php';
        AeroNotifications::create($db_id, 'login', 'Welcome Back!', 'You logged in successfully.', 'user-dashboard.php');
        setFlash('success', 'Welcome back, ' . htmlspecialchars($db_name) . '!');
        redirect('index.php');
    } else {
        // Failed password
        recordFailedLogin($email, $db_id);
        logSecurity('Login failed - wrong password', ['user_id' => $db_id]);
        setFlash('error', 'Invalid email or password.');
        redirect('login.php');
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
                            <i class="bi bi-box-arrow-in-right auth-icon"></i>
                            <h2>Welcome Back!</h2>
                            <p>Login to your AeroBook account to manage your bookings and search for flights.</p>
                            <div class="mt-4">
                                <div class="d-flex align-items-center gap-2 text-white-50 mb-2">
                                    <i class="bi bi-shield-check text-info"></i>
                                    <span>Secure account access</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-white-50 mb-2">
                                    <i class="bi bi-clock-history text-info"></i>
                                    <span>Track your login history</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 text-white-50">
                                    <i class="bi bi-devices text-info"></i>
                                    <span>Manage your devices</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="auth-form">
                            <span class="kicker">Account access</span>
                            <h2>Login</h2>
                            <p class="subtitle">Enter your credentials to access your account</p>
                            <form method="POST" id="loginForm" novalidate class="needs-loading">
                                <?php csrfField(); ?>
                                <div class="mb-3">
                                    <label class="form-label" for="loginEmail">Email Address</label>
                                    <input type="email" name="email" id="loginEmail" class="form-control" placeholder="Enter your email" required maxlength="100">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="loginPassword">Password</label>
                                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required minlength="6">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="remember_me" id="rememberMe" class="form-check-input">
                                        <label class="form-check-label small" for="rememberMe">Remember me</label>
                                    </div>
                                    <a href="forgot-password.php" class="small text-accent fw-semibold">Forgot password?</a>
                                </div>
                                <button type="submit" class="btn btn-accent w-100 py-2 mb-3 rounded-pill"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
                                <p class="text-center mb-0">Don't have an account? <a href="register.php" class="text-accent fw-bold">Register here</a></p>
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
