<?php
/**
 * AeroBook – Centralized Error Handler
 *
 * Converts PHP errors and exceptions into graceful failures.
 * Never exposes SQL errors, stack traces, or internal paths to users.
 * All errors are logged via the logger.
 */

require_once __DIR__ . '/Logger.php';

// ──────────────────────────────────────────────
// Custom Exception Handler
// ──────────────────────────────────────────────

function aerobookExceptionHandler($exception) {
    $message = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();

    logError('Uncaught exception', [
        'message' => $message,
        'file' => basename($file),
        'line' => $line,
    ]);

    // Show a friendly message without exposing internals
    if (ob_get_length()) ob_clean();

    $isAdmin = defined('IS_ADMIN_PANEL') && IS_ADMIN_PANEL;
    $homeUrl = $isAdmin ? (defined('BASE_URL') ? BASE_URL . 'admin/dashboard.php' : 'dashboard.php') : (defined('BASE_URL') ? BASE_URL . 'index.php' : 'index.php');

    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Something Went Wrong – AeroBook</title>
        <script>
            // Apply saved/system theme before CSS paints to avoid a flash of the wrong theme.
            (function () {
                try { var t = localStorage.getItem('aerobook-theme');
                    if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-theme', t);
                } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
            })();
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <?php if (defined('BASE_URL')): ?><link href="<?php echo htmlspecialchars(BASE_URL . 'css/style.css'); ?>" rel="stylesheet"><?php endif; ?>
    </head>
    <body class="bg-light">
        <div class="container py-5 text-center">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <i class="bi bi-exclamation-triangle text-danger display-1 mb-4 d-block"></i>
                    <h1 class="fw-bold mb-3">Something Went Wrong</h1>
                    <p class="text-muted mb-4">We encountered an unexpected error. Our team has been notified and we're working on it.</p>
                    <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="btn btn-accent btn-lg px-5">Return Home</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ──────────────────────────────────────────────
// Custom Error Handler (PHP errors/warnings/notices)
// ──────────────────────────────────────────────

function aerobookErrorHandler($errno, $errstr, $errfile, $errline) {
    // Don't die on warnings/notices in production
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $level = 'ERROR';
    if ($errno === E_WARNING || $errno === E_USER_WARNING) {
        $level = 'WARN';
    } elseif ($errno === E_NOTICE || $errno === E_USER_NOTICE) {
        $level = 'INFO';
        // Silently log notices, don't halt
        logInfo('PHP Notice', ['message' => $errstr, 'file' => basename($errfile), 'line' => $errline]);
        return false;
    }

    logError("PHP {$level}", [
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline,
    ]);

    // For fatal-level errors, show friendly page
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        $homeUrl = defined('BASE_URL') ? BASE_URL . 'index.php' : 'index.php';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><title>Error – AeroBook</title>
        <script>
            try { var t = localStorage.getItem('aerobook-theme');
                if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <?php if (defined('BASE_URL')): ?><link href="<?php echo htmlspecialchars(BASE_URL . 'css/style.css'); ?>" rel="stylesheet"><?php endif; ?>
        </head>
        <body class="bg-light">
            <div class="container py-5 text-center">
                <h1 class="fw-bold text-danger">System Error</h1>
                <p class="text-muted">Please try again later.</p>
                <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="btn btn-primary">Return Home</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    return false;
}

// ──────────────────────────────────────────────
// Database Error Handler
// ──────────────────────────────────────────────

function handleDbError($context = 'database operation') {
    global $conn;
    $error = mysqli_error($conn);
    $errno = mysqli_errno($conn);

    logError("Database {$context} failed", [
        'errno' => $errno,
        'message' => $error,
    ]);

    // Never expose the actual SQL error to users
    return false;
}

// ──────────────────────────────────────────────
// Initialize Error Handling
// ──────────────────────────────────────────────

function initErrorHandler() {
    set_exception_handler('aerobookExceptionHandler');
    set_error_handler('aerobookErrorHandler');
    error_reporting(E_ALL);

    // In production, don't display errors
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
    } else {
        ini_set('display_errors', '1');
    }
}
