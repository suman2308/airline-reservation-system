<?php
/**
 * AeroBook – Security Helpers
 *
 * Centralized security functions: headers, session management, rate limiting.
 */

// ──────────────────────────────────────────────
// Security Headers
// ──────────────────────────────────────────────

function emitSecurityHeaders() {
    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');

    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy (restrict browser features)
    header("Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()");

    // Content Security Policy
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://db.onlinewebfonts.com; "
         . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com https://db.onlinewebfonts.com; "
         . "img-src 'self' data:; "
         . "media-src 'self' https://d8j0ntlcm91z4.cloudfront.net; "
         . "connect-src 'self'; "
         . "frame-ancestors 'self'; "
         . "form-action 'self'; "
         . "base-uri 'self'";
    header("Content-Security-Policy: {$csp}");
}

// ──────────────────────────────────────────────
// Session Hardening
// ──────────────────────────────────────────────

function configureSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure cookie parameters before session start
        $isHttps = isSecureRequest();

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

function regenerateSession() {
    // Preserve session data while getting a new ID
    session_regenerate_id(true);
}

function validateSessionTimeout($timeoutMinutes = 30) {
    $timeout = $timeoutMinutes * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function secureLogout() {
    // Clear all session data
    $_SESSION = [];

    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 86400,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// ──────────────────────────────────────────────
// Rate Limiting (simple file-based)
// ──────────────────────────────────────────────

function checkRateLimit($action, $maxAttempts = 5, $windowSeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'ratelimit_' . md5($action . '_' . $ip);

    $attempts = $_SESSION[$key]['attempts'] ?? 0;
    $firstAttempt = $_SESSION[$key]['first_attempt'] ?? 0;

    // Reset if window has expired
    if ($firstAttempt > 0 && (time() - $firstAttempt) > $windowSeconds) {
        $attempts = 0;
        $firstAttempt = time();
    }

    $attempts++;

    $_SESSION[$key] = [
        'attempts' => $attempts,
        'first_attempt' => $firstAttempt ?: time(),
    ];

    return $attempts <= $maxAttempts;
}

function getRemainingAttempts($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'ratelimit_' . md5($action . '_' . $ip);
    $attempts = $_SESSION[$key]['attempts'] ?? 0;
    return max(0, 5 - $attempts);
}

function clearRateLimit($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'ratelimit_' . md5($action . '_' . $ip);
    unset($_SESSION[$key]);
}

// ──────────────────────────────────────────────
// Input Validation Helpers
// ──────────────────────────────────────────────

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateIndianPhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

function validateBookingRef($ref) {
    return preg_match('/^AB-[A-Z0-9]{6}$/', $ref) === 1;
}

function validateSeatNumber($seat) {
    return preg_match('/^\d{1,2}[A-F]$/', strtoupper($seat)) === 1;
}

function validatePositiveInt($value, $max = null) {
    $val = intval($value);
    if ($val <= 0) return false;
    if ($max !== null && $val > $max) return false;
    return true;
}

function validateCardNumber($number) {
    return preg_match('/^\d{16}$/', $number) === 1;
}

function validateCVV($cvv) {
    return preg_match('/^\d{3}$/', $cvv) === 1;
}
