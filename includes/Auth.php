<?php
/**
 * AeroBook – Authentication & Account Management System
 *
 * Provides: email verification, password reset, remember-me tokens,
 * login history tracking, session management, account locking, profile pictures.
 */

// ──────────────────────────────────────────────
// Token Generation
// ──────────────────────────────────────────────

function generateAuthToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// ──────────────────────────────────────────────
// Email Verification
// ──────────────────────────────────────────────

/**
 * Create an email verification token for a user.
 * Invalidates any previous unused tokens for that user.
 */
function createEmailVerification($userId) {
    global $conn;

    // Invalidate old tokens
    $clean = mysqli_prepare($conn, "UPDATE email_verifications SET used = 1 WHERE user_id = ? AND used = 0");
    mysqli_stmt_bind_param($clean, "i", $userId);
    mysqli_stmt_execute($clean);
    mysqli_stmt_close($clean);

    // Create new token
    $token = generateAuthToken();
    $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

    $stmt = mysqli_prepare($conn, "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $userId, $token, $expires);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $token;
}

/**
 * Verify an email using a token.
 * Returns true on success, false on failure.
 */
function verifyEmailWithToken($token) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT v.id, v.user_id, v.expires_at, u.email_verified_at
                                    FROM email_verifications v
                                    JOIN users u ON v.user_id = u.id
                                    WHERE v.token = ? AND v.used = 0 AND v.expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $vid, $userId, $expiresAt, $verifiedAt);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        return false;
    }

    // Already verified
    if ($verifiedAt !== null) {
        // Mark token as used anyway
        $used = mysqli_prepare($conn, "UPDATE email_verifications SET used = 1 WHERE id = ?");
        mysqli_stmt_bind_param($used, "i", $vid);
        mysqli_stmt_execute($used);
        mysqli_stmt_close($used);
        return 'already_verified';
    }

    // Mark as verified
    $now = date('Y-m-d H:i:s');
    $updUser = mysqli_prepare($conn, "UPDATE users SET email_verified_at = ? WHERE id = ?");
    mysqli_stmt_bind_param($updUser, "si", $now, $userId);
    mysqli_stmt_execute($updUser);
    mysqli_stmt_close($updUser);

    // Mark token as used
    $updToken = mysqli_prepare($conn, "UPDATE email_verifications SET used = 1 WHERE id = ?");
    mysqli_stmt_bind_param($updToken, "i", $vid);
    mysqli_stmt_execute($updToken);
    mysqli_stmt_close($updToken);

    logInfo('Email verified', ['user_id' => $userId]);
    return true;
}

/**
/**
 * Resend verification email.
 */
function resendVerification($userId) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) return false;

    $token = createEmailVerification($userId);
    $mailer = new AeroMailer();
    $verifyUrl = (defined('BASE_URL') ? BASE_URL : '') . 'verify-email.php?token=' . urlencode($token);
    $mailer->sendVerification($user['email'], $user['name'], $verifyUrl);
    return true;
}

/**
 * Check if a user's email is verified.
 */
function isEmailVerified($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT email_verified_at FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $verifiedAt);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $found && $verifiedAt !== null;
}

// ──────────────────────────────────────────────
// Password Reset
// ──────────────────────────────────────────────

/**
 * Create a password reset token.
 */
function createPasswordReset($email) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId, $userName);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        // Don't reveal whether the email exists
        return true;
    }

    // Invalidate old tokens
    $clean = mysqli_prepare($conn, "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
    mysqli_stmt_bind_param($clean, "i", $userId);
    mysqli_stmt_execute($clean);
    mysqli_stmt_close($clean);

    // Create new token
    $token = generateAuthToken();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $stmt = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $userId, $token, $expires);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

// Send reset email via AeroMailer
    $mailer = new AeroMailer();
    $resetUrl = (defined('BASE_URL') ? BASE_URL : '') . 'reset-password.php?token=' . urlencode($token);
    $mailer->sendPasswordReset($email, $userName, $resetUrl);

    logInfo('Password reset requested', ['user_id' => $userId]);
    return true;
}

/**
 * Validate a password reset token and return the associated user ID.
 * Returns user_id on success, false on failure.
 */
function validatePasswordResetToken($token) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $found ? $userId : false;
}

/**
 * Reset a user's password using a valid token.
 */
function resetPasswordWithToken($token, $newPassword) {
    global $conn;

    $userId = validatePasswordResetToken($token);
    if (!$userId) {
        return false;
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, "si", $hashed, $userId);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    // Mark all tokens as used
    $used = mysqli_prepare($conn, "UPDATE password_resets SET used = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($used, "i", $userId);
    mysqli_stmt_execute($used);
    mysqli_stmt_close($used);

    // Invalidate all sessions for this user (force re-login)
    invalidateUserSessions($userId);

    logInfo('Password reset completed', ['user_id' => $userId]);
    return true;
}

// ──────────────────────────────────────────────
// Remember Me (Persistent Login)
// ──────────────────────────────────────────────

/**
 * Create a "Remember Me" token and set the cookie.
 */
function createRememberMe($userId) {
    global $conn;

    $token = generateAuthToken();
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 2592000); // 30 days

    $stmt = mysqli_prepare($conn, "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $userId, $tokenHash, $expires);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Set cookie (valid 30 days)
    $cookieValue = $userId . ':' . $token;
    $cookieExpires = time() + 2592000;
    $isHttps = isSecureRequest();

    setcookie(
        'remember_me',
        $cookieValue,
        $cookieExpires,
        '/',
        '',
        $isHttps,
        true // httponly
    );

    logInfo('Remember me token created', ['user_id' => $userId]);
}

/**
 * Attempt to login via "Remember Me" cookie.
 * Returns true and sets session on success.
 */
function loginViaRememberMe() {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }

    global $conn;

    $parts = explode(':', $_COOKIE['remember_me'], 2);
    if (count($parts) !== 2) {
        clearRememberMe();
        return false;
    }

    $userId = intval($parts[0]);
    $token = $parts[1];
    $tokenHash = hash('sha256', $token);

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM remember_tokens
                                    WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "is", $userId, $tokenHash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $valid = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if (!$valid) {
        clearRememberMe();
        return false;
    }

    // Fetch user data
    $stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        clearRememberMe();
        return false;
    }

    // Log in: regenerate session, rotate remember-me token
    regenerateSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    // Delete old token and create a new one (token rotation)
    deleteRememberMeTokens($userId);
    createRememberMe($userId);

    // Record login
    recordLoginHistory($userId, true);
    recordUserSession($userId);

    logInfo('Login via remember me', ['user_id' => $userId]);
    return true;
}

/**
 * Clear all remember-me tokens for a user and delete the cookie.
 */
function clearRememberMe($userId = null) {
    global $conn;

    if ($userId !== null) {
        $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Delete cookie
    if (isset($_COOKIE['remember_me'])) {
        $isHttps = isSecureRequest();

        setcookie('remember_me', '', time() - 86400, '/', '', $isHttps, true);
        unset($_COOKIE['remember_me']);
    }
}

/**
 * Delete remember-me tokens for a user (used during logout).
 */
function deleteRememberMeTokens($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ──────────────────────────────────────────────
// Login History & Tracking
// ──────────────────────────────────────────────

/**
 * Record a login attempt in the login history.
 */
function recordLoginHistory($userId, $success, $email = null) {
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ua = substr($userAgent, 0, 500);

    $stmt = mysqli_prepare($conn, "INSERT INTO login_history (user_id, email, ip_address, user_agent, success) VALUES (?, ?, ?, ?, ?)");
    $successFlag = $success ? 1 : 0; // must be a variable — mysqli_stmt_bind_param binds by reference (PHP 8)
    mysqli_stmt_bind_param($stmt, "issii", $userId, $email, $ip, $ua, $successFlag);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Record failed login attempt with optional user ID.
 */
function recordFailedLogin($email, $userId = null) {
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = mysqli_prepare($conn, "INSERT INTO login_history (user_id, email, ip_address, user_agent, success) VALUES (?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "isss", $userId, $email, $ip, $userAgent);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Increment failed login counter
    if ($userId !== null) {
        // Check if account should be locked
        $stmt = mysqli_prepare($conn, "SELECT failed_logins, locked_until FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $failed, $lockedUntil);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $failed++;

        if ($failed >= 5) {
            // Lock for 30 minutes
            $lockUntil = date('Y-m-d H:i:s', time() + 1800);
            $upd = mysqli_prepare($conn, "UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "isi", $failed, $lockUntil, $userId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            logSecurity('Account locked due to failed logins', ['user_id' => $userId, 'failed' => $failed]);
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET failed_logins = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "ii", $failed, $userId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
    }
}

/**
 * Check if an account is locked.
 * Returns the number of minutes remaining, or false if not locked.
 */
function isAccountLocked($userId) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT failed_logins, locked_until FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $failed, $lockedUntil);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found || $failed < 5) {
        return false;
    }

    // Check if lock has expired
    if ($lockedUntil !== null) {
        $lockTime = strtotime($lockedUntil);
        if ($lockTime > time()) {
            $remaining = ceil(($lockTime - time()) / 60);
            return $remaining; // Returns minutes remaining
        } else {
            // Lock expired, reset counter
            $reset = mysqli_prepare($conn, "UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?");
            mysqli_stmt_bind_param($reset, "i", $userId);
            mysqli_stmt_execute($reset);
            mysqli_stmt_close($reset);
            return false;
        }
    }

    return false;
}

/**
 * Get login history for a user.
 */
function getUserLoginHistory($userId, $limit = 10) {
    global $conn;

    $limit = intval($limit);
    $stmt = mysqli_prepare($conn, "SELECT * FROM login_history WHERE user_id = ? ORDER BY login_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    return $history;
}

// ──────────────────────────────────────────────
// Session Management
// ──────────────────────────────────────────────

/**
 * Record a new user session.
 */
function recordUserSession($userId) {
    global $conn;

    $sessionId = session_id();
    $identifier = hash('sha256', $sessionId . $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceName = getDeviceName($userAgent);

    // Check if this session already exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_sessions WHERE session_identifier = ?");
    mysqli_stmt_bind_param($stmt, "s", $identifier);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conn, "INSERT INTO user_sessions (user_id, session_identifier, ip_address, user_agent, device_name) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $userId, $identifier, $ip, $userAgent, $deviceName);
        mysqli_stmt_execute($stmt);
    } else {
        mysqli_stmt_close($stmt);
        // Update last activity
        $stmt = mysqli_prepare($conn, "UPDATE user_sessions SET last_activity = NOW() WHERE session_identifier = ?");
        mysqli_stmt_bind_param($stmt, "s", $identifier);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

/**
 * Get active sessions for a user.
 */
function getUserSessions($userId) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT * FROM user_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_activity DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $sessions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sessions[] = $row;
    }
    return $sessions;
}

/**
 * Invalidate a specific session by ID.
 */
function invalidateSession($sessionId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE user_sessions SET is_active = 0 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $sessionId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Invalidate all sessions for a user.
 */
function invalidateUserSessions($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE user_sessions SET is_active = 0 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    logInfo('All sessions invalidated', ['user_id' => $userId]);
}

/**
 * Extract a human-readable device name from User-Agent string.
 */
function getDeviceName($userAgent) {
    if (empty($userAgent)) return 'Unknown Device';

    $ua = strtolower($userAgent);

    if (strpos($ua, 'chrome') !== false) $browser = 'Chrome';
    elseif (strpos($ua, 'firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua, 'safari') !== false) $browser = 'Safari';
    elseif (strpos($ua, 'edge') !== false) $browser = 'Edge';
    elseif (strpos($ua, 'opera') !== false || strpos($ua, 'opr') !== false) $browser = 'Opera';
    else $browser = 'Browser';

    if (strpos($ua, 'windows') !== false) $os = 'Windows';
    elseif (strpos($ua, 'mac') !== false) $os = 'macOS';
    elseif (strpos($ua, 'linux') !== false) $os = 'Linux';
    elseif (strpos($ua, 'android') !== false) $os = 'Android';
    elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) $os = 'iOS';
    else $os = 'Unknown OS';

    return "{$browser} on {$os}";
}

// ──────────────────────────────────────────────
// Profile Picture / Avatar
// ──────────────────────────────────────────────

/**
 * Upload a profile picture for a user.
 */
function uploadProfilePicture($userId, $file) {
    global $conn;

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'No file uploaded or upload error occurred.';
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return 'Only JPG, PNG, GIF, and WebP images are allowed.';
    }

    if ($file['size'] > 2097152) { // 2MB
        return 'Image must be under 2MB.';
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/avatars/';

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return 'Failed to upload image. Please try again.';
    }

    // Delete old avatar
    $stmt = mysqli_prepare($conn, "SELECT avatar FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $oldAvatar);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
        @unlink($uploadDir . $oldAvatar);
    }

    // Save new avatar
    $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $filename, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    logInfo('Profile picture uploaded', ['user_id' => $userId]);
    return true;
}

/**
 * Remove a user's profile picture.
 */
function removeProfilePicture($userId) {
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT avatar FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $avatar);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($avatar) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (file_exists($uploadDir . $avatar)) {
            @unlink($uploadDir . $avatar);
        }
    }

    $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    logInfo('Profile picture removed', ['user_id' => $userId]);
    return true;
}

/**
 * Get the avatar URL for a user.
 * Returns a URL to the avatar image or a generated SVG initial.
 */
function avatarUrl($userId, $name, $avatar = null) {
    if ($avatar) {
        return BASE_URL . 'uploads/avatars/' . $avatar;
    }
    // Return initials (SVG data URL is too complex, just return null)
    return null;
}

/**
 * Get the travel stats for a user's dashboard.
 */
function getUserTravelStats($userId) {
    global $conn;

    $stats = [
        'total_bookings' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'total_spent' => 0,
        'unique_routes' => 0,
        'upcoming_trip' => null,
    ];

    // Total bookings
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_bookings'] = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);

    // Confirmed bookings
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND booking_status = 'Confirmed'");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['confirmed'] = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);

    // Cancelled
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND booking_status = 'Cancelled'");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['cancelled'] = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);

    // Total spent (from confirmed bookings, use price from flights)
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(f.price), 0) as total FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.user_id = ? AND b.booking_status = 'Confirmed'");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_spent'] = (float)mysqli_fetch_assoc($result)['total'];
    mysqli_stmt_close($stmt);

    // Unique routes
    $stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT CONCAT(f.source, '-', f.destination)) as c FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['unique_routes'] = (int)mysqli_fetch_assoc($result)['c'];
    mysqli_stmt_close($stmt);

    // Upcoming trip
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time
                                    FROM bookings b
                                    JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? AND b.booking_status = 'Confirmed' AND f.departure_time >= NOW()
                                    ORDER BY f.departure_time ASC
                                    LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['upcoming_trip'] = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $stats;
}

/**
 * Check if email verification is required before login.
 * This setting is configurable.
 */
function isEmailVerificationRequired() {
    return defined('REQUIRE_EMAIL_VERIFICATION') ? REQUIRE_EMAIL_VERIFICATION : false;
}
