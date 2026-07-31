<?php
/**
 * AeroBook – Health Check Endpoint
 *
 * Returns JSON with system status for monitoring tools.
 * Does NOT require authentication.
 * Does NOT expose sensitive information.
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$status = 'ok';
$httpCode = 200;
$checks = [];

// Database check
$dbCheck = false;
try {
    $dbCheck = mysqli_ping($conn);
} catch (Exception $e) {
    $dbCheck = false;
}
$checks['database'] = $dbCheck ? 'connected' : 'failed';
if (!$dbCheck) {
    $status = 'degraded';
    $httpCode = 503;
}

// Flight count check (quick sanity)
$flightCount = 0;
if ($dbCheck) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $flightCount = (int)$row['c'];
    }
}
$checks['flights_count'] = $flightCount;

// Build response
$response = [
    'status' => $status,
    'timestamp' => date('c'),
    'application' => 'AeroBook',
    'version' => '1.0.0',
    'checks' => $checks,
];

http_response_code($httpCode);
echo json_encode($response, JSON_PRETTY_PRINT);
