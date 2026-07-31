<?php
$pageTitle = 'Reset Password';
require_once 'includes/header.php';
require_once 'includes/Validation.php';
require_once 'includes/Auth.php';

if (isLoggedIn()) redirect('index.php');

$token = $_GET['token'] ?? '';
$resetComplete = false;
$tokenValid = false;
$userId = false;

// Validate token on page load
if (!empty($token)) {
    $userId = validatePasswordResetToken($token);
    $tokenValid = ($userId !== false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!requireCsrfToken()) {
        redirect('reset-password.php?token=' . urlencode($token));
    }

    $token = $_POST['token'] ?? '';
    $userId = validatePasswordResetToken($token);

    if (!$userId) {
        setFlash('error', 'Invalid or expired reset link. Please request a new one.');
        redirect('forgot-password.php');
    }

    $errors = validatePasswordReset($_POST);
    if (!empty($errors)) {
        setFlash('error', $errors[0]);
        redirect('reset-password.php?token=' . urlencode($token));
    }

    $password = $_POST['password'];
    $result = resetPasswordWithToken($token, $password);

    if ($result) {
        $resetComplete = true;
        logInfo('Password reset completed via page', ['user_id' => $userId]);
    } else {
        setFlash('error', 'Failed to reset password. The link may have expired.');
        redirect('forgot-password.php');
    }
}
?>

<section class="auth-section">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <?php showAlert(); ?>

            <?php if ($resetComplete): ?>
            <div class="auth-card text-center">
                <div class="auth-form" style="padding: 4rem;">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem; display: block; margin-bottom: 1.5rem;"></i>
                    <h2 class="mb-3">Password Reset Complete</h2>
                    <p class="text-muted mb-4">Your password has been changed successfully. You can now login with your new password.</p>
                    <p class="text-muted small mb-4">All your active sessions have been invalidated for security.</p>
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-accent btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login Now
                    </a>
                </div>
            </div>

            <?php elseif (!$tokenValid): ?>
            <div class="auth-card text-center">
                <div class="auth-form" style="padding: 4rem;">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem; display: block; margin-bottom: 1.5rem;"></i>
                    <h2 class="mb-3">Invalid or Expired Link</h2>
                    <p class="text-muted mb-4">This password reset link is invalid or has expired. Password reset links expire after 1 hour.</p>
                    <a href="<?php echo BASE_URL; ?>forgot-password.php" class="btn btn-accent btn-lg px-5">
                        <i class="bi bi-arrow-left me-2"></i>Request New Reset Link
                    </a>
                </div>
            </div>

            <?php else: ?>
            <div class="auth-card">
                <div class="auth-form" style="padding: 3rem;">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-accent" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <h2>Create New Password</h2>
                        <p class="text-muted">Choose a strong password for your account.</p>
                    </div>
                    <form method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="mb-3">
                            <label class="form-label" for="password">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required minlength="6">
                            <div class="invalid-feedback">Passwords do not match.</div>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-2 mb-3">
                            <i class="bi bi-check-circle me-2"></i>Reset Password
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>
