<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Auth.php';
require_once 'includes/Avatar.php';
require_once 'includes/Notifications.php';
require_once 'includes/Mailer.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, avatar, email_verified_at, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$tab = $_GET['tab'] ?? 'personal';

// ──────────────────────────────────────────────
// Handle POST actions
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect('profile.php');
    }

    // ─── Delete Account ───
    if (isset($_POST['delete_account'])) {
        clearRememberMe($user_id);
        $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $user_id);
        if (mysqli_stmt_execute($del_stmt)) {
            secureLogout();
            session_start();
            setFlash('success', 'Your account has been deleted successfully.');
            redirect('index.php');
        } else {
            setFlash('error', 'Failed to delete account.');
        }
        mysqli_stmt_close($del_stmt);
        redirect('profile.php');
    }

    // ─── Update Profile ───
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($phone)) {
            setFlash('error', 'Name and Phone are required.');
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($update_stmt, "ssi", $name, $phone, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['user_name'] = $name;
                AeroNotifications::create($user_id, 'profile_updated', 'Profile Updated', 'Your profile information was updated.');
                setFlash('success', 'Profile updated successfully!');
            } else {
                setFlash('error', 'Update failed.');
            }
            mysqli_stmt_close($update_stmt);
        }
        redirect('profile.php');
    }

    // ─── Change Password ───
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Verify current password
        $pwdStmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($pwdStmt, "i", $user_id);
        mysqli_stmt_execute($pwdStmt);
        $pwdResult = mysqli_stmt_get_result($pwdStmt);
        $pwdRow = mysqli_fetch_assoc($pwdResult);
        mysqli_stmt_close($pwdStmt);

        if (!password_verify($current, $pwdRow['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 6) {
            setFlash('error', 'New password must be at least 6 characters.');
        } elseif ($newPass !== $confirm) {
            setFlash('error', 'Passwords do not match.');
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($upd, "si", $hashed, $user_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            AeroNotifications::create($user_id, 'password_changed', 'Password Changed', 'Your password was updated successfully.');
            setFlash('success', 'Password changed successfully!');
            logInfo('Password changed from profile', ['user_id' => $user_id]);
        }
        redirect('profile.php?tab=security');
    }

    // ─── Upload Avatar (via AeroUpload) ───
    if (isset($_POST['upload_avatar'])) {
        $upload = new AeroUpload();
        $filename = $upload->uploadAvatar($_FILES['avatar'] ?? [], $user_id);
        if ($filename) {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET avatar=? WHERE id=?");
            mysqli_stmt_bind_param($update_stmt, "si", $filename, $user_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            setFlash('success', 'Profile picture updated!');
        } else {
            $errs = $upload->getErrors();
            setFlash('error', !empty($errs) ? $errs[0] : 'Failed to upload avatar.');
        }
        redirect('profile.php');
    }

    // ─── Remove Avatar ───
    if (isset($_POST['remove_avatar'])) {
        // Clear avatar from DB
        $update_stmt = mysqli_prepare($conn, "UPDATE users SET avatar=NULL WHERE id=?");
        mysqli_stmt_bind_param($update_stmt, "i", $user_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        // Delete files
        $upload = new AeroUpload();
        $pattern = __DIR__ . '/../uploads/avatars/avatar_' . $user_id . '_*';
        foreach (glob($pattern) as $f) @unlink($f);
        setFlash('success', 'Profile picture removed.');
        redirect('profile.php');
    }

    // ─── Resend Verification ───
    if (isset($_POST['resend_verification'])) {
        resendVerification($user_id);
        setFlash('success', 'Verification email sent! Please check your inbox.');
        redirect('profile.php');
    }

    // ─── Logout Other Sessions ───
    if (isset($_POST['logout_session'])) {
        $sessionId = intval($_POST['session_id'] ?? 0);
        if ($sessionId > 0) {
            invalidateSession($sessionId);
            setFlash('success', 'Session logged out successfully.');
        }
        redirect('profile.php?tab=security');
    }

    // ─── Logout All Sessions ───
    if (isset($_POST['logout_all_sessions'])) {
        // Invalidate all except current
        $currentIdentifier = hash('sha256', session_id() . $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        global $conn;
        $stmt = mysqli_prepare($conn, "UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND session_identifier != ?");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $currentIdentifier);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        setFlash('success', 'All other devices logged out successfully.');
        redirect('profile.php?tab=security');
    }
}

// Fetch login history
$loginHistory = getUserLoginHistory($user_id, 15);

// Fetch active sessions
$sessions = getUserSessions($user_id);

// Get current session identifier
$currentSessionIdentifier = hash('sha256', session_id() . $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-person-gear me-2"></i>My Account</h1>
        <p>Manage your profile, security settings, and account activity</p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <!-- Profile Tabs -->
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="list-group shadow-sm border-0 rounded-3 overflow-hidden mb-4">
                <a href="?tab=personal" class="list-group-item list-group-item-action border-0 py-3 <?php echo $tab === 'personal' ? 'active bg-accent text-white' : ''; ?>">
                    <i class="bi bi-person me-2"></i> Personal Info
                </a>
                <a href="?tab=security" class="list-group-item list-group-item-action border-0 py-3 <?php echo $tab === 'security' ? 'active bg-accent text-white' : ''; ?>">
                    <i class="bi bi-shield-lock me-2"></i> Security
                </a>
                <a href="?tab=sessions" class="list-group-item list-group-item-action border-0 py-3 <?php echo $tab === 'sessions' ? 'active bg-accent text-white' : ''; ?>">
                    <i class="bi bi-devices me-2"></i> Devices & Sessions
                </a>
                <a href="?tab=history" class="list-group-item list-group-item-action border-0 py-3 <?php echo $tab === 'history' ? 'active bg-accent text-white' : ''; ?>">
                    <i class="bi bi-clock-history me-2"></i> Login History
                </a>
            </div>

            <!-- Account Overview Card -->
            <div class="flight-card p-3 text-center">
                <?php
                $avatarUrl = avatarUrl($user_id, $user['name'], $user['avatar']);
                $initial = strtoupper(substr($user['name'], 0, 1));
                ?>
                <?php if ($avatarUrl): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                <?php else: ?>
                    <div class="mx-auto rounded-circle bg-accent text-white d-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                        <?php echo $initial; ?>
                    </div>
                <?php endif; ?>
                <h5 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                <div class="mt-2">
                    <?php if ($user['email_verified_at'] !== null): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if ($tab === 'personal'): ?>
            <!-- Personal Info Tab -->
            <div class="flight-card p-4">
                <h4 class="mb-4"><i class="bi bi-person me-2 text-accent"></i>Personal Information</h4>
                <form method="POST" enctype="multipart/form-data">
                    <?php csrfField(); ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" value="<?php echo isset($user['created_at']) ? formatDate($user['created_at']) : 'N/A'; ?>" disabled>
                        </div>
                    </div>

                    <!-- Avatar Upload -->
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-image me-2 text-accent"></i>Profile Picture</h5>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo htmlspecialchars(avatarUrl($user_id, $user['name'], $user['avatar'])); ?>" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                            <button type="submit" name="remove_avatar" class="btn btn-outline-danger btn-sm">Remove</button>
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 64px; height: 64px;">
                                <i class="bi bi-person text-muted fs-3"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control form-control-sm">
                            <small class="text-muted">JPG, PNG, GIF or WebP. Max 2MB.</small>
                        </div>
                        <button type="submit" name="upload_avatar" class="btn btn-outline-accent btn-sm">Upload</button>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update_profile" class="btn btn-accent px-5">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </button>
                    </div>
                </form>

                <!-- Email Verification -->
                <hr class="my-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-envelope-check me-2 text-accent"></i>Email Verification</h6>
                        <p class="mb-0 text-muted small">
                            <?php if ($user['email_verified_at'] !== null): ?>
                                ✓ Verified on <?php echo formatDateTime($user['email_verified_at']); ?>
                            <?php else: ?>
                                Your email is not yet verified. Some features may be restricted.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($user['email_verified_at'] === null): ?>
                    <form method="POST" class="m-0">
                        <?php csrfField(); ?>
                        <button type="submit" name="resend_verification" class="btn btn-outline-accent btn-sm">
                            <i class="bi bi-send me-1"></i>Resend
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Danger Zone -->
                <hr class="my-4">
                <div class="p-3 border border-danger rounded-3">
                    <h6 class="text-danger mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h6>
                    <p class="text-muted small mb-3">Once you delete your account, there is no going back. All bookings and data will be permanently removed.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
                        <?php csrfField(); ?>
                        <input type="hidden" name="delete_account" value="1">
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-2"></i>Delete My Account</button>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'security'): ?>
            <!-- Security Tab -->
            <div class="flight-card p-4 mb-4">
                <h4 class="mb-4"><i class="bi bi-shield-lock me-2 text-accent"></i>Change Password</h4>
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-accent mt-3 px-5">
                        <i class="bi bi-check-circle me-2"></i>Update Password
                    </button>
                </form>
            </div>

            <div class="flight-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0"><i class="bi bi-devices me-2 text-accent"></i>Active Sessions</h4>
                    <form method="POST" class="m-0">
                        <?php csrfField(); ?>
                        <button type="submit" name="logout_all_sessions" class="btn btn-outline-danger btn-sm" onclick="return confirm('Log out all other devices?');">
                            <i class="bi bi-x-circle me-1"></i>Log Out All Others
                        </button>
                    </form>
                </div>
                <p class="text-muted small mb-3">These are the devices currently logged into your account.</p>
                <?php if (empty($sessions)): ?>
                    <p class="text-muted text-center py-3">No active sessions found.</p>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-laptop fs-4 text-muted"></i>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($s['device_name'] ?? 'Unknown Device'); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($s['ip_address'] ?? ''); ?>
                                    · Last active <?php echo timeSince($s['last_activity']); ?>
                                    <?php if ($s['session_identifier'] === $currentSessionIdentifier): ?>
                                        <span class="badge bg-success ms-2">Current</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($s['session_identifier'] !== $currentSessionIdentifier): ?>
                        <form method="POST" class="m-0">
                            <?php csrfField(); ?>
                            <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="logout_session" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php elseif ($tab === 'sessions'): ?>
            <!-- Full Sessions Tab (same as above for now) -->
            <div class="flight-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0"><i class="bi bi-devices me-2 text-accent"></i>Active Sessions</h4>
                    <form method="POST" class="m-0">
                        <?php csrfField(); ?>
                        <button type="submit" name="logout_all_sessions" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Log Out All Others
                        </button>
                    </form>
                </div>
                <?php if (empty($sessions)): ?>
                    <p class="text-muted text-center py-3">No active sessions found.</p>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                    <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-laptop fs-3 text-muted"></i>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($s['device_name'] ?? 'Unknown Device'); ?></div>
                                <small class="text-muted">
                                    IP: <?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?>
                                </small><br>
                                <small class="text-muted">
                                    Last active: <?php echo timeSince($s['last_activity']); ?>
                                    · Logged in: <?php echo formatDateTime($s['logged_in_at']); ?>
                                    <?php if ($s['session_identifier'] === $currentSessionIdentifier): ?>
                                        <span class="badge bg-success ms-2">Current Session</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($s['session_identifier'] !== $currentSessionIdentifier): ?>
                        <form method="POST" class="m-0">
                            <?php csrfField(); ?>
                            <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="logout_session" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php elseif ($tab === 'history'): ?>
            <!-- Login History Tab -->
            <div class="flight-card p-4">
                <h4 class="mb-4"><i class="bi bi-clock-history me-2 text-accent"></i>Login History</h4>
                <p class="text-muted small mb-3">Recent login activity on your account. If you see something unusual, change your password immediately.</p>
                <?php if (empty($loginHistory)): ?>
                    <p class="text-muted text-center py-4"><i class="bi bi-inbox me-2"></i>No login history recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>Device</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loginHistory as $h): ?>
                                <tr>
                                    <td class="small"><?php echo formatDateTime($h['login_at']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($h['ip_address']); ?></td>
                                    <td class="small">
                                        <?php
                                        $ua = $h['user_agent'] ?? '';
                                        echo htmlspecialchars(getDeviceName($ua));
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($h['success']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Success</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
