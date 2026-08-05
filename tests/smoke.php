<?php
/**
 * AeroBook – Smoke Test Suite
 * ============================
 * Dependency-free automated tests that exercise the app over HTTP.
 * Covers: page availability, auth guards, admin + user login,
 * registration, flight search, booking validation, and DB integrity.
 *
 * Usage (server must be running, e.g. `php -S 127.0.0.1:8080`):
 *   php tests/smoke.php [http://127.0.0.1:8080]
 *
 * Exit code 0 = all green, 1 = failures.
 */

$BASE = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$JAR  = sys_get_temp_dir() . '/aerobook-smoke-' . getmypid() . '.txt';
$TEST_EMAIL = '';  // set once a user is registered; guaranteed cleanup below

// Guarantee the test user + cookie jars are removed even on fatal error.
register_shutdown_function(function () use ($JAR) {
    global $TEST_EMAIL;
    $env = [];
    if (is_file(__DIR__ . '/../.env')) {
        foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
                [$k, $v] = explode('=', $line, 2); $env[trim($k)] = trim($v);
            }
        }
    }
    if ($TEST_EMAIL !== '') {
        $dbh = @mysqli_connect($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'aerobook_db');
        if ($dbh) {
            mysqli_query($dbh, "DELETE FROM users WHERE email = '" . mysqli_real_escape_string($dbh, $TEST_EMAIL) . "'");
            mysqli_close($dbh);
        }
    }
    foreach (glob(sys_get_temp_dir() . '/aerobook-smoke-' . getmypid() . '*') as $f) @unlink($f);
});

$pass = 0; $fail = 0; $failures = [];
function check($label, $ok, $detail = '') {
    global $pass, $fail, $failures;
    if ($ok) { $pass++; echo "  \033[32mPASS\033[0m  $label\n"; }
    else     { $fail++; $failures[] = $label; echo "  \033[31mFAIL\033[0m  $label" . ($detail ? " — $detail" : '') . "\n"; }
}

function parseResponse($raw) {
    if ($raw === false) return ['status' => 0, 'headers' => '', 'body' => ''];
    // Split into header blocks separated by blank lines. The first block that
    // carries an HTTP status line is the real response head; everything after
    // it (possibly other blocks for 100-continue bodies) is the body.
    $blocks = explode("\r\n\r\n", $raw);
    $statusIdx = -1;
    foreach ($blocks as $i => $b) {
        if (preg_match('#^HTTP/[\d.]+\s+(\d{3})#m', $b)) { $statusIdx = $i; break; }
    }
    if ($statusIdx === -1) return ['status' => 0, 'headers' => $raw, 'body' => ''];
    preg_match('#^HTTP/[\d.]+\s+(\d{3})#m', $blocks[$statusIdx], $m);
    $status = (int)$m[1];
    $body = implode("\r\n\r\n", array_slice($blocks, $statusIdx + 1));
    return ['status' => $status, 'headers' => $blocks[$statusIdx], 'body' => $body];
}

function httpGet($url, $jar = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    if ($jar) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    return parseResponse(curl_exec($ch));
}

function httpPost($url, $fields, $jar = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    if ($jar) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    return parseResponse(curl_exec($ch));
}

function csrf($url, $jar) {
    $page = httpGet($url, $jar);
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $page['body'], $m)) return $m[1];
    if (preg_match('/value="([^"]+)"\s+name="csrf_token"/', $page['body'], $m)) return $m[1];
    return '';
}

echo "\nAeroBook Smoke Tests\n" . str_repeat('=', 50) . "\nTarget: $BASE\n\n";

// ─── 1. Public pages → 200 ───
echo "[1] Public pages return 200\n";
$public = ['', 'about.php', 'contact.php', 'search-flights.php', 'flight-status.php',
           'login.php', 'register.php', 'forgot-password.php', 'health.php', 'admin/login.php'];
foreach ($public as $p) {
    $r = httpGet("$BASE/$p");
    check(($p ?: 'home') . " → {$r['status']}", $r['status'] === 200);
}

// ─── 2. Auth guards: logged-out → 302 ───
echo "\n[2] Protected pages redirect when logged out\n";
$guarded = ['my-bookings.php', 'user-dashboard.php', 'profile.php', 'travel-calendar.php',
            'travel-documents.php', 'check-in.php', 'notifications.php',
            'admin/dashboard.php', 'admin/manage-flights.php', 'admin/manage-users.php'];
foreach ($guarded as $p) {
    $r = httpGet("$BASE/$p");
    check("$p → {$r['status']}", $r['status'] === 302);
}

// ─── 3. Admin login flow ───
echo "\n[3] Admin authentication\n";
$adminJar = $JAR . '-admin';
$tok = csrf("$BASE/admin/login.php", $adminJar);
check('login page has CSRF token', $tok !== '');
$r = httpPost("$BASE/admin/login.php", ['csrf_token' => $tok, 'username' => 'admin', 'password' => 'admin123'], $adminJar);
check('admin/admin123 → redirect (302)', $r['status'] === 302, "got {$r['status']}");
$r = httpGet("$BASE/admin/dashboard.php", $adminJar);
check('admin dashboard → 200', $r['status'] === 200, "got {$r['status']}");
$r = httpGet("$BASE/admin/logout.php", $adminJar);
check('admin logout → 302 (no 500)', in_array($r['status'], [302, 200], true), "got {$r['status']}");
// Re-login for the remaining admin checks
$tokR = csrf("$BASE/admin/login.php", $adminJar);
$r = httpPost("$BASE/admin/login.php", ['csrf_token' => $tokR, 'username' => 'admin', 'password' => 'admin123'], $adminJar);
check('admin re-login after logout → 302', $r['status'] === 302, "got {$r['status']}");
$r = httpGet("$BASE/admin/manage-flights.php", $adminJar);
check('admin manage-flights → 200', $r['status'] === 200, "got {$r['status']}");
$tok2 = csrf("$BASE/admin/login.php", $JAR . '-bad');
$r = httpPost("$BASE/admin/login.php", ['csrf_token' => $tok2, 'username' => 'admin', 'password' => 'wrongpass'], $JAR . '-bad');
check('wrong password rejected (302)', $r['status'] === 302, "got {$r['status']}");

// ─── 4. User registration + login ───
echo "\n[4] User registration & login\n";
$userJar = $JAR . '-user';
$stamp = date('YmdHis');
$email = "smoke$stamp@example.test";
$TEST_EMAIL = $email;
$tok = csrf("$BASE/register.php", $userJar);
$r = httpPost("$BASE/register.php", [
    'csrf_token' => $tok, 'name' => 'Smoke Tester', 'email' => $email,
    'phone' => '9876543210', 'password' => 'TestPass123', 'confirm_password' => 'TestPass123',
], $userJar);
check("register $email → 302", $r['status'] === 302, "got {$r['status']}");
$r = httpGet("$BASE/user-dashboard.php", $userJar);
check('new user reaches dashboard → 200', $r['status'] === 200, "got {$r['status']}");
$r = httpGet("$BASE/logout.php", $userJar);
check('logout → 302 (no 500)', in_array($r['status'], [302, 200], true), "got {$r['status']}");
$r = httpGet("$BASE/my-bookings.php", $userJar);
check('logged-out my-bookings → 302 after logout', $r['status'] === 302, "got {$r['status']}");
httpGet("$BASE/login.php", $userJar);
$tok = csrf("$BASE/login.php", $userJar);
$r = httpPost("$BASE/login.php", ['csrf_token' => $tok, 'email' => $email, 'password' => 'TestPass123'], $userJar);
check("login $email → 302", $r['status'] === 302, "got {$r['status']}");
$r = httpGet("$BASE/my-bookings.php", $userJar);
check('logged-in my-bookings → 200', $r['status'] === 200, "got {$r['status']}");

// ─── 5. Flight search & details (route/flight derived from DB so it survives reseeding) ───
echo "\n[5] Flight search\n";
$env5 = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) { [$k, $v] = explode('=', $line, 2); $env5[trim($k)] = trim($v); }
}
$dbh5 = @mysqli_connect($env5['DB_HOST'] ?? 'localhost', $env5['DB_USER'] ?? 'root', $env5['DB_PASS'] ?? '', $env5['DB_NAME'] ?? 'aerobook_db');
$route = ['Chennai', 'Hyderabad']; $flightId = 1;
if ($dbh5) {
    $res = mysqli_query($dbh5, "SELECT source, destination FROM flights LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) { $route = [$row['source'], $row['destination']]; }
    $res = mysqli_query($dbh5, "SELECT flight_id FROM flights LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) { $flightId = (int)$row['flight_id']; }
    mysqli_close($dbh5);
}
$r = httpGet("$BASE/fare-results.php?source=" . urlencode($route[0]) . "&destination=" . urlencode($route[1]) . "&travel_date=" . date('Y-m-d'));
check("fare-results → {$r['status']}", $r['status'] === 200, "got {$r['status']}");
check('results contain flight card', strpos($r['body'], 'flight-card') !== false || stripos($r['body'], 'Book') !== false);
$r = httpGet("$BASE/flight-details.php?id=$flightId");
check("flight-details → {$r['status']}", in_array($r['status'], [200, 302], true), "got {$r['status']}");

// ─── 6. Booking validation rejects bad add-on amounts ───
echo "\n[6] Booking validation\n";
// The validation fix: float add-on values must pass, invalid amounts must fail.
// validateAddonCosts() returns an array of error strings (empty = valid).
require_once __DIR__ . '/../includes/Validation.php';
if (function_exists('validateAddonCosts')) {
    check('validateAddonCosts(0,0) accepts no add-ons', validateAddonCosts(0, 0) === []);
    check('validateAddonCosts(0.0,0.0) accepts float zeros', validateAddonCosts(0.0, 0.0) === []);
    check('validateAddonCosts(800,350) accepts valid add-ons', validateAddonCosts(800, 350) === []);
    check('validateAddonCosts(999,0) rejects unknown amount', validateAddonCosts(999, 0) !== []);
} else {
    check('validateAddonCosts exists', false, 'function not found');
}

// ─── 7. DB integrity ───
echo "\n[7] Database integrity\n";
$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
        [$k, $v] = explode('=', $line, 2); $env[trim($k)] = trim($v);
    }
}
$dbh = @mysqli_connect($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'aerobook_db');
check('MySQL connection', (bool)$dbh);
if ($dbh) {
    $required = ['users', 'flights', 'bookings', 'admins', 'contacts', 'booking_addons'];
    $have = [];
    $res = mysqli_query($dbh, 'SHOW TABLES');
    while ($row = mysqli_fetch_row($res)) $have[] = strtolower($row[0]);
    foreach ($required as $t) check("table `$t` exists", in_array($t, $have, true));
    $res = mysqli_query($dbh, "SELECT flight_id FROM flights LIMIT 1");
    check('flights table has data', (bool)mysqli_num_rows($res));
    mysqli_close($dbh);
}

// ─── Summary (cleanup handled by shutdown function) ───
echo "\n" . str_repeat('=', 50) . "\n";
echo "Results: $pass passed, $fail failed\n";
if ($fail) { echo "Failures:\n"; foreach ($failures as $f) echo "  - $f\n"; }
exit($fail > 0 ? 1 : 0);
