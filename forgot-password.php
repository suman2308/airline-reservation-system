<?php
$pageTitle = 'Forgot Password';
require_once 'includes/header.php';
require_once 'includes/Auth.php';
require_once 'includes/Validation.php';

if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!requireCsrfToken()) {
        redirect('forgot-password.php');
    }

    $errors = validateForgotPassword($_POST);
    if (!empty($errors)) {
        setFlash('error', $errors[0]);
        redirect('forgot-password.php');
    }

    if (!checkRateLimit('forgot_password', 3, 300)) {
        logSecurity('Password reset rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        setFlash('error', 'Too many requests. Please try again later.');
        redirect('forgot-password.php');
    }

    $email = trim($_POST['email']);
    createPasswordReset($email);
    clearRateLimit('forgot_password');
    setFlash('success', 'If an account exists with that email, a password reset link has been sent.');
    redirect('forgot-password.php');
}
?>
<section class="auth-section">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="flight-card p-5 border-0 shadow-sm rounded-4">
                <div class="text-center mb-4">
                    <i class="bi bi-key text-accent display-4 mb-3 d-block"></i>
                    <h2 class="fw-bold">Forgot Password</h2>
                    <p class="text-muted">Enter your email and we'll send you a reset link.</p>
                </div>
                <?php showAlert(); ?>
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required maxlength="100">
                    </div>
                    <button type="submit" class="btn btn-accent w-100 py-2 mb-3"><i class="bi bi-send me-2"></i>Send Reset Link</button>
                    <p class="text-center mb-0"><a href="login.php" class="text-accent fw-bold">Back to Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>
</section>
<?php require_once 'includes/footer.php'; ?>
