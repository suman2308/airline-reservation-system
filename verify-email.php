<?php
$pageTitle = 'Verify Email';
require_once 'includes/header.php';
require_once 'includes/Auth.php';
require_once 'includes/Notifications.php';

$token = trim($_GET['token'] ?? '');
$status = null;
$message = '';

if (empty($token)) {
    $status = 'error';
    $message = 'No verification token provided.';
} else {
    $result = verifyEmailWithToken($token);
    if ($result === true) {
        $status = 'success';
        $message = 'Your email has been verified successfully! You can now book flights.';
        if (isset($_SESSION['user_id'])) {
            AeroNotifications::create($_SESSION['user_id'], 'email_verified', 'Email Verified', 'Your email address has been verified successfully.', 'profile.php');
        }
        logInfo('Email verified via link', ['token' => substr($token, 0, 8) . '...']);
    } elseif ($result === 'already_verified') {
        $status = 'info';
        $message = 'Your email was already verified. No action needed.';
    } else {
        $status = 'error';
        $message = 'Invalid or expired verification link. Please request a new one from your profile.';
    }
}
?>
<section class="auth-section">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="auth-card">
                <div class="auth-form text-center">
                    <span class="kicker kicker-accent d-inline-block mb-3">Email verification</span>
                    <i class="bi <?php echo $status === 'success' ? 'bi-check-circle-fill text-success' : ($status === 'info' ? 'bi-info-circle-fill text-info' : 'bi-x-circle-fill text-danger'); ?> display-3 mb-3 d-block"></i>
                    <h2 class="fw-bold mb-3">
                        <?php echo $status === 'success' ? 'Email Verified!' : ($status === 'info' ? 'Already Verified' : 'Verification Failed'); ?>
                    </h2>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($message); ?></p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-accent px-4"><i class="bi bi-house-door me-2"></i>Home</a>
                    <?php if ($status === 'error'): ?>
                        <a href="<?php echo BASE_URL; ?>profile.php" class="btn btn-outline-accent px-4"><i class="bi bi-send me-2"></i>Resend Verification</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>
