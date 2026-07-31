<?php
/**
 * AeroBook – Database & Environment Configuration
 *
 * Loads configuration from environment variables with fallback to defaults.
 * Supports .env file in the project root for local development.
 * Supports PHP environment variables (getenv) for Docker/hosting.
 * Bootstraps the error handler and session.
 */

// ──────────────────────────────────────────────
// 1. Bootstrap Error Handler (before everything)
// ──────────────────────────────────────────────
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/Logger.php';
initErrorHandler();

// ──────────────────────────────────────────────
// 2. Load .env file (if exists) — for local dev / shared hosting
// ──────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip optional surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            // Only set if not already an environment variable (env var takes priority)
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * Helper: get a config value from environment, $_ENV, or a default.
 * Used so all constants below can be overridden via .env or Docker env.
 */
if (!function_exists('env')) {
function env($key, $default = null) {
    $value = getenv($key);
    if ($value !== false) return $value;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    return $default;
}
}

// ──────────────────────────────────────────────
// 3. Environment Detection
// ──────────────────────────────────────────────
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$serverAddr  = $_SERVER['SERVER_ADDR'] ?? '';
$isLocalhost = ($serverName === 'localhost' || $serverAddr === '127.0.0.1' || $serverAddr === '::1');
$isDocker = (env('IS_DOCKER', 'false') === 'true');
define('IS_PRODUCTION', !$isLocalhost && !$isDocker);

// ──────────────────────────────────────────────
// 4. Configuration Constants (env → hardcoded fallback)
// ──────────────────────────────────────────────
// All database/BASE_URL values MUST be set via .env or environment variables.
// The hardcoded defaults below are safe development-only placeholders.
// Copy .env.example to .env and configure your credentials before deploying.
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'aerobook_db'));

// ──────────────────────────────────────────────
// BASE_URL — auto-detected from the current request.
// This keeps assets working on any host/port/install path
// (local dev, XAMPP subfolder, shared hosting, Docker)
// while still allowing an explicit BASE_URL override via .env.
// ──────────────────────────────────────────────
$baseUrlOverride = env('BASE_URL', '');
if ($baseUrlOverride !== '') {
    define('BASE_URL', $baseUrlOverride);
} else {
    // Compute the app's base path relative to the document root.
    // Example: project in htdocs/airline-reservation-system → "/airline-reservation-system"
    $basePath = '';
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $projectRoot = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
    if ($docRoot !== '') {
        // Normalize both to real paths so symlinked installs (e.g. public_html -> app)
        // still compute the correct base path.
        $resolvedDoc = realpath($docRoot) ?: $docRoot;
        $resolvedDoc = str_replace('\\', '/', rtrim($resolvedDoc, '/\\'));
        $resolvedProject = str_replace('\\', '/', rtrim($projectRoot, '/\\'));
        if ($resolvedProject !== $resolvedDoc && str_starts_with($resolvedProject . '/', $resolvedDoc . '/')) {
            $basePath = substr($resolvedProject, strlen($resolvedDoc));
        }
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . $basePath . '/');
}

define('SITE_NAME', env('SITE_NAME', 'AeroBook'));
define('SITE_TAGLINE', env('SITE_TAGLINE', 'Smart, Fast and Easy Flight Booking Platform'));
define('BASE_PATH', __DIR__ . '/..');
define('SESSION_TIMEOUT_MINUTES', (int)env('SESSION_TIMEOUT_MINUTES', 30));
define('REQUIRE_EMAIL_VERIFICATION', env('REQUIRE_EMAIL_VERIFICATION', 'false') === 'true');
define('ALLOWED_AVATAR_EXTENSIONS', env('ALLOWED_AVATAR_EXTENSIONS', 'jpg,jpeg,png,gif,webp'));
define('MAX_AVATAR_SIZE', (int)env('MAX_AVATAR_SIZE', 2097152));

// ──────────────────────────────────────────────
// Aviationstack API Configuration
// ──────────────────────────────────────────────
define('AVIATIONSTACK_API_KEY', env('AVIATIONSTACK_API_KEY', ''));
define('AVIATIONSTACK_ENABLED', env('AVIATIONSTACK_ENABLED', 'false') === 'true');

// ──────────────────────────────────────────────
// Mail Configuration
// ──────────────────────────────────────────────
define('MAIL_MODE', env('MAIL_MODE', 'log'));
define('MAIL_FROM', env('MAIL_FROM', 'noreply@aerobook.in'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', SITE_NAME));
define('MAIL_HOST', env('MAIL_HOST', ''));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_USER', env('MAIL_USER', ''));
define('MAIL_PASS', env('MAIL_PASS', ''));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));

// ──────────────────────────────────────────────
// 5. Maintenance Mode
// ──────────────────────────────────────────────
define('MAINTENANCE_MODE', env('MAINTENANCE_MODE', 'false') === 'true');

// ──────────────────────────────────────────────
// 6. Secure Session Configuration
// ──────────────────────────────────────────────
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session timeout
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if ($lastActivity > 0 && (time() - $lastActivity) > (SESSION_TIMEOUT_MINUTES * 60)) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// ──────────────────────────────────────────────
// 7. Database Connection
// ──────────────────────────────────────────────
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    logError('Database connection failed');
    if (IS_PRODUCTION) {
        die('Service temporarily unavailable. Please try again later.');
    }
    die('Database Connection failed. Please check your credentials.');
}

// ──────────────────────────────────────────────
// 8. Maintenance Mode Check
// ──────────────────────────────────────────────
if (MAINTENANCE_MODE) {
    $allowedIps = ['127.0.0.1', '::1'];
    $maintenanceExempt = defined('ADMIN_MAINTENANCE_OVERRIDE') && ADMIN_MAINTENANCE_OVERRIDE;
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps) && !$maintenanceExempt) {
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><title>Under Maintenance – AeroBook</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center vh-100">
            <div class="container text-center">
                <i class="bi bi-tools display-1 text-warning mb-4 d-block"></i>
                <h1 class="fw-bold mb-3">Under Maintenance</h1>
                <p class="text-muted lead">We're performing scheduled updates. We'll be back shortly.</p>
                <p class="text-muted small">Estimated completion time: 15 minutes</p>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// ──────────────────────────────────────────────
// 9. Timezone
// ──────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');
