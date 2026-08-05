<?php
/**
 * AeroBook – Lightweight Logger
 *
 * Writes structured log entries to a daily-rotated log file.
 * Never logs passwords, payment card data, or full request bodies.
 */

define('LOG_LEVEL_INFO', 'INFO');
define('LOG_LEVEL_WARN', 'WARN');
define('LOG_LEVEL_ERROR', 'ERROR');
define('LOG_LEVEL_SECURITY', 'SECURITY');

function loggerInit() {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    // Protect the log directory from direct access
    $htaccessPath = $logDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        @file_put_contents($htaccessPath, "Deny from all\n");
    }
}

function loggerWrite($level, $message, array $context = []) {
    static $initialized = false;
    if (!$initialized) {
        loggerInit();
        $initialized = true;
    }

    $logDir = __DIR__ . '/../logs';
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $file = $logDir . "/{$date}.log";

    // Sanitize: remove sensitive fields from context.
    // Keep diagnostic keys (message/file/line) so exceptions are actually traceable.
    $safeKeys = ['user_id', 'booking_ref', 'flight_id', 'ip', 'action', 'duration_ms', 'status', 'affected_rows', 'message', 'file', 'line'];
    $safeContext = [];
    foreach ($safeKeys as $k) {
        if (isset($context[$k])) {
            $safeContext[$k] = $context[$k];
        }
    }

    $contextStr = !empty($safeContext) ? ' ' . json_encode($safeContext) : '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $line = "[{$date} {$time}] {$level} [{$ip}] {$message}{$contextStr}" . PHP_EOL;

    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function logInfo($message, array $context = []) {
    loggerWrite(LOG_LEVEL_INFO, $message, $context);
}

function logWarning($message, array $context = []) {
    loggerWrite(LOG_LEVEL_WARN, $message, $context);
}

function logError($message, array $context = []) {
    loggerWrite(LOG_LEVEL_ERROR, $message, $context);
}

function logSecurity($message, array $context = []) {
    loggerWrite(LOG_LEVEL_SECURITY, $message, $context);
}
