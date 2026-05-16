<?php
/**
 * AeroBook – Helper Functions
 * 
 * Contains reusable utility functions used across the application.
 */

/**
 * Sanitize user input to prevent XSS attacks
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return mysqli_real_escape_string($conn, $data);
}

function redirect($url) {
    if (ob_get_length()) ob_clean();
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get base URL for assets
 */
function asset($path) {
    return BASE_URL . ltrim($path, '/');
}

/**
 * CSRF Token Generation
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Validation
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF Hidden Input Field
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Generate a unique booking reference (e.g., AB-7X3K9M)
 */
function generateBookingRef() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $ref = 'AB-';
    for ($i = 0; $i < 6; $i++) {
        $ref .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $ref;
}

/**
 * Generate a seat number (e.g., 12A, 5C)
 */
function generateSeatNumber() {
    $row = rand(1, 30);
    $col = chr(rand(65, 70)); // A to F
    return $row . $col;
}

/**
 * Format price in Indian Rupees
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format time for display
 */
function formatTime($datetime) {
    return date('h:i A', strtotime($datetime));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Calculate flight duration
 */
function calcDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $diff = $dep->diff($arr);
    return $diff->h . 'h ' . $diff->i . 'm';
}

/**
 * Display alert messages stored in session
 */
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

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $classes = [
        'Confirmed' => 'bg-success',
        'Cancelled' => 'bg-danger',
        'Scheduled' => 'bg-primary',
        'Delayed'   => 'bg-warning text-dark',
        'Completed' => 'bg-secondary',
    ];
    $class = $classes[$status] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . $status . '</span>';
}
?>

