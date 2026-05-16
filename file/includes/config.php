<?php
ob_start();
/**
 * AeroBook – Database & Environment Configuration
 */

// Environment Detection
$is_localhost = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($is_localhost) {
    // Localhost XAMPP Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'aerobook_db');
    define('BASE_URL', 'http://localhost/airline-reservation-system/');
} else {
    // InfinityFree Production Settings
    define('DB_HOST', 'sql313.infinityfree.com');
    define('DB_USER', 'if0_41933848');
    define('DB_PASS', 'Suman2308');
    define('DB_NAME', 'if0_41933848_flight');
    define('BASE_URL', 'https://aerobook.rf.gd/');
}

// Site configuration
define('SITE_NAME', 'AeroBook');
define('SITE_TAGLINE', 'Smart, Fast and Easy Flight Booking Platform');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Database Connection failed. Please check your credentials.");
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id();
    $_SESSION['initiated'] = true;
}
?>

