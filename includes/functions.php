<?php
/**
 * AeroBook – Helper Functions
 *
 * Reusable utility functions used across the application.
 * Database-aware helpers live in includes/helpers.php.
 */

// ──────────────────────────────────────────────
// URL & Redirection
// ──────────────────────────────────────────────

function redirect($url) {
    if (ob_get_length()) ob_clean();
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    }
    echo "<script>window.location.href='$url';</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
    exit();
}

function asset($path) {
    return BASE_URL . ltrim($path, '/');
}

// ──────────────────────────────────────────────
// Session & Authentication
// ──────────────────────────────────────────────

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect(BASE_URL . 'login.php');
    }
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . 'admin/login.php');
    }
}

// ──────────────────────────────────────────────
// CSRF Protection
// ──────────────────────────────────────────────

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function requireCsrfToken() {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurity('CSRF validation failed', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        setFlash('error', 'Invalid request. Please try again.');
        return false;
    }
    return true;
}

// ──────────────────────────────────────────────
// Reference & Seat Generators
// ──────────────────────────────────────────────

function generateBookingRef() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $ref = 'AB-';
    for ($i = 0; $i < 6; $i++) {
        $ref .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $ref;
}

// ──────────────────────────────────────────────
// Formatting Utilities
// ──────────────────────────────────────────────

function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatTime($datetime) {
    return date('h:i A', strtotime($datetime));
}

function formatDateTime($datetime) {
    return date('d M Y, h:i A', strtotime($datetime));
}

function calcDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $diff = $dep->diff($arr);
    return $diff->h . 'h ' . $diff->i . 'm';
}

// ──────────────────────────────────────────────
// UI Components
// ──────────────────────────────────────────────

function showAlert() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>' . $_SESSION['success'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $_SESSION['error'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['error']);
    }
}

function setFlash($type, $message) {
    $_SESSION[$type] = $message;
}

function statusBadge($status) {
    $classes = [
        'Confirmed' => 'badge-status bg-success',
        'Cancelled' => 'badge-status bg-danger',
        'Scheduled' => 'badge-status bg-primary',
        'Delayed'   => 'badge-status bg-warning',
        'Completed' => 'badge-status bg-secondary',
        'Checked-in' => 'badge-status bg-info',
    ];
    $class = $classes[$status] ?? 'badge-status bg-secondary';
    return '<span class="' . $class . '">' . $status . '</span>';
}

function airlineInitials($name) {
    return strtoupper(substr($name, 0, 2));
}

function cityOptions($selected = null, $region = '') {
    // Core cities matching the flights table source/destination columns.
    // Value stays as city name for backward compatibility with queries.
    // Display text enhanced with IATA codes from aviation_airports if available.
    global $conn;

    // Try to load IATA codes from imported Aviationstack data
    static $cityIataMap = null;
    static $intlCityIataMap = null;
    if ($cityIataMap === null) {
        $cityIataMap = [
            'Delhi' => 'DEL',
            'Mumbai' => 'BOM',
            'Bangalore' => 'BLR',
            'Kolkata' => 'CCU',
            'Chennai' => 'MAA',
            'Hyderabad' => 'HYD',
            'Pune' => 'PNQ',
        ];
        // Override with imported data if available.
        // NOTE: The @ operator does NOT suppress mysqli_sql_exception in PHP 8,
        // so wrap in try/catch and fall back to hardcoded IATA codes if the
        // aviation_airports table has not been imported yet.
        try {
            $r = mysqli_query($conn, "SELECT DISTINCT iata_code, city_iata_code FROM aviation_airports WHERE iata_code IN ('DEL','BOM','BLR','CCU','MAA','HYD','PNQ') AND iata_code != ''");
        } catch (mysqli_sql_exception $e) {
            $r = false;
        }
        if ($r && mysqli_num_rows($r) > 0) {
            $cityAirports = [];
            while ($row = mysqli_fetch_assoc($r)) {
                $cityCode = strtoupper(trim($row['city_iata_code']));
                if (!isset($cityAirports[$cityCode])) {
                    $cityAirports[$cityCode] = $row['iata_code'];
                }
            }
            $cityToCode = ['Delhi' => 'DEL', 'Mumbai' => 'BOM', 'Bangalore' => 'BLR', 'Kolkata' => 'CCU', 'Chennai' => 'MAA', 'Hyderabad' => 'HYD', 'Pune' => 'PNQ'];
            foreach ($cityIataMap as $city => $defaultIata) {
                $code = $cityToCode[$city] ?? strtoupper(substr(str_replace(' ', '', $city), 0, 3));
                if (isset($cityAirports[$code])) {
                    $cityIataMap[$city] = $cityAirports[$code];
                }
            }
        }
    }

    if ($region === 'international') {
        if ($intlCityIataMap === null) {
            $intlCityIataMap = [
                'Delhi' => 'DEL',
                'Mumbai' => 'BOM',
                'Bangalore' => 'BLR',
                'Kolkata' => 'CCU',
                'Chennai' => 'MAA',
                'Hyderabad' => 'HYD',
                'Pune' => 'PNQ',
                'Dubai' => 'DXB',
                'Abu Dhabi' => 'AUH',
                'Singapore' => 'SIN',
                'Bangkok' => 'BKK',
                'Kuala Lumpur' => 'KUL',
                'Doha' => 'DOH',
                'London' => 'LHR',
                'Paris' => 'CDG',
                'Frankfurt' => 'FRA',
                'New York' => 'JFK',
                'Tokyo' => 'NRT',
                'Sydney' => 'SYD',
                'Hong Kong' => 'HKG',
                'Kathmandu' => 'KTM',
                'Colombo' => 'CMB',
                'Dhaka' => 'DAC',
            ];
            // Override with imported aviation data if available (non-Indian cities)
            try {
                $r = mysqli_query($conn, "SELECT DISTINCT city_name, iata_code FROM aviation_airports WHERE iata_code IN ('DXB','AUH','SIN','BKK','KUL','DOH','LHR','CDG','FRA','JFK','NRT','SYD','HKG','KTM','CMB','DAC') AND iata_code != '' AND city_name != ''");
            } catch (mysqli_sql_exception $e) {
                $r = false;
            }
            if ($r && mysqli_num_rows($r) > 0) {
                $airportCity = [];
                while ($row = mysqli_fetch_assoc($r)) {
                    $airportCity[$row['iata_code']] = $row['city_name'];
                }
                foreach ($intlCityIataMap as $city => $iata) {
                    if (isset($airportCity[$iata])) {
                        $intlCityIataMap[$city] = $iata; // keep IATA, city display stays as canonical name
                    }
                }
            }
        }
        $map = $intlCityIataMap;
    } else {
        $map = $cityIataMap;
    }

    foreach ($map as $city => $iata) {
        $display = $city . ' (' . $iata . ')';
        $sel = ($selected === $city) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($city) . '"' . $sel . '>' . htmlspecialchars($display) . '</option>';
    }
}

function passwordStrength($password) {
    $score = 0;
    if (strlen($password) >= 8) $score++;
    if (strlen($password) >= 12) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;
    
    if ($score <= 2) return 'weak';
    if ($score <= 4) return 'medium';
    return 'strong';
}

function passwordStrengthLabel($password) {
    $strength = passwordStrength($password);
    $labels = [
        'weak' => ['label' => 'Weak', 'class' => 'bg-danger'],
        'medium' => ['label' => 'Medium', 'class' => 'bg-warning text-dark'],
        'strong' => ['label' => 'Strong', 'class' => 'bg-success'],
    ];
    $info = $labels[$strength];
    return '<span class="badge ' . $info['class'] . '">' . $info['label'] . '</span>';
}

function statusOptions($selected = null) {
    $statuses = ['Scheduled', 'Delayed', 'Cancelled', 'Completed'];
    foreach ($statuses as $s) {
        $sel = ($selected === $s) ? ' selected' : '';
        echo "<option value=\"{$s}\"{$sel}>{$s}</option>";
    }
}

/**
 * Known Indian cities used to determine domestic vs international routes.
 * Declared as a constant so both PHP helpers and SQL IN-clauses can use it.
 */
if (!defined('INDIAN_CITIES')) {
    define('INDIAN_CITIES', [
        'Delhi', 'Mumbai', 'Bangalore', 'Bengaluru', 'Kolkata', 'Chennai',
        'Hyderabad', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow', 'Chandigarh',
        'Goa', 'Kochi', 'Thiruvananthapuram', 'Bhubaneswar', 'Guwahati',
        'Nagpur', 'Indore', 'Coimbatore', 'Visakhapatnam', 'Patna', 'Varanasi',
        'Agra', 'Amritsar', 'Srinagar', 'Mangalore', 'Vadodara', 'Bhopal',
        'Raipur', 'Ranchi', 'Dehradun', 'Bagdogra', 'Udaipur', 'Jodhpur',
    ]);
}

/**
 * Check if a route is domestic (both cities are in India).
 */
function isDomesticRoute($source, $destination) {
    $src = trim($source);
    $dst = trim($destination);
    return in_array($src, INDIAN_CITIES) && in_array($dst, INDIAN_CITIES);
}

/**
 * Filter an array of flights by region.
 * domestic: both cities must be Indian. international: at least one non-Indian.
 * Empty region returns flights unchanged (backward compatible).
 */
function filterFlightsByRegion($flights, $region) {
    if ($region === 'domestic') {
        return array_values(array_filter($flights, function($f) {
            return isDomesticRoute($f['source'], $f['destination']);
        }));
    }
    if ($region === 'international') {
        return array_values(array_filter($flights, function($f) {
            return !isDomesticRoute($f['source'], $f['destination']);
        }));
    }
    return $flights;
}

/**
 * Get flights by route with optional region filtering.
 * Reuses getFlightsByRoute() then applies the region filter.
 */
function getFlightsByRouteRegion($source, $destination, $date, $region = '', $orderClause = '') {
    return filterFlightsByRegion(getFlightsByRoute($source, $destination, $date, $orderClause), $region);
}

/**
 * Get today's flights with optional region filtering.
 * Reuses getTodaysFlights() then applies the region filter.
 */
function getTodaysFlightsRegion($date, $region = '') {
    return filterFlightsByRegion(getTodaysFlights($date), $region);
}
