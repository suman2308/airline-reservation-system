<?php
/**
 * AeroBook – Admin Analytics Functions
 *
 * Aggregated queries for the admin operations center: metrics, revenue,
 * occupancy, routes, trends, data quality, and repeat customers.
 */

if (!defined('AEROBOOK_ADMIN_ANALYTICS')) {

require_once __DIR__ . '/Cache.php';

function logAdminAction($adminId, $action, $details = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = mysqli_prepare($conn, "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $adminId, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function getAdminActivityLog($limit = 100, $offset = 0, $actionFilter = null, $dateFrom = null, $dateTo = null) {
    global $conn;
    $sql = "SELECT aal.*, adm.username FROM admin_activity_log aal JOIN admins adm ON aal.admin_id = adm.admin_id WHERE 1=1";
    $params = [];
    $types = '';
    if ($actionFilter) { $sql .= ' AND aal.action = ?'; $params[] = $actionFilter; $types .= 's'; }
    if ($dateFrom) { $sql .= ' AND aal.created_at >= ?'; $params[] = $dateFrom; $types .= 's'; }
    if ($dateTo) { $sql .= ' AND aal.created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; $types .= 's'; }
    $sql .= ' ORDER BY aal.created_at DESC LIMIT ? OFFSET ?';
    $params[] = intval($limit); $params[] = intval($offset); $types .= 'ii';
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) $entries[] = $row;
    mysqli_stmt_close($stmt);
    return $entries;
}

function getTodayOpsMetrics() {
    return AeroCache::remember('today_ops_metrics', 60, function() {
        global $conn;
        $metrics = [];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights WHERE DATE(departure_time) = CURDATE()");
        $metrics['flights_today'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights WHERE DATE(departure_time) = CURDATE() AND TIME(departure_time) BETWEEN TIME(NOW()) AND TIME(DATE_ADD(NOW(), INTERVAL 2 HOUR))");
        $metrics['boarding_soon'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights WHERE status='Delayed'");
        $metrics['delayed'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights WHERE DATE(departure_time) = CURDATE() AND status='Completed'");
        $metrics['completed_today'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE DATE(travel_date) = CURDATE() AND booking_status='Confirmed'");
        $metrics['passengers_today'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE DATE(booking_date) = CURDATE()");
        $metrics['bookings_today'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COALESCE(SUM(f.price), 0) as s FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.booking_status='Confirmed' AND DATE(b.booking_date) = CURDATE()");
        $metrics['revenue_today'] = (float)mysqli_fetch_assoc($r)['s'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE booking_status='Confirmed'");
        $metrics['total_bookings'] = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE DATE(booking_date)=CURDATE() AND booking_status='Cancelled'");
        $cancelled = (int)mysqli_fetch_assoc($r)['c'];
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE DATE(booking_date)=CURDATE()");
        $total = (int)mysqli_fetch_assoc($r)['c'];
        $metrics['cancellation_rate'] = $total > 0 ? round($cancelled / $total * 100, 1) : 0;
        $r = mysqli_query($conn, "SELECT SUM(total_seats) as total, SUM(total_seats - seats_available) as booked FROM flights WHERE status='Scheduled'");
        $row = mysqli_fetch_assoc($r);
        $metrics['overall_occupancy'] = ($row['total'] ?? 0) > 0 ? round(($row['booked'] ?? 0) / $row['total'] * 100, 1) : 0;
        return $metrics;
    });
}

function getRevenueByMonth($months = 12) {
    global $conn;
    $months = intval($months);
    $r = mysqli_query($conn, "SELECT DATE_FORMAT(b.booking_date, '%Y-%m') as month, SUM(f.price) as revenue, COUNT(*) as bookings
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              WHERE b.booking_status='Confirmed' AND b.booking_date >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
                              GROUP BY month ORDER BY month ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getRevenueByRoute() {
    global $conn;
    $r = mysqli_query($conn, "SELECT f.source, f.destination, SUM(f.price) as revenue, COUNT(*) as bookings,
                              ROUND(AVG(f.price), 2) as avg_price
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              WHERE b.booking_status='Confirmed'
                              GROUP BY f.source, f.destination ORDER BY revenue DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getRevenueByAirline() {
    global $conn;
    $r = mysqli_query($conn, "SELECT f.airline_name, SUM(f.price) as revenue, COUNT(*) as bookings,
                              ROUND(AVG(f.price), 2) as avg_price
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              WHERE b.booking_status='Confirmed'
                              GROUP BY f.airline_name ORDER BY revenue DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getTopCustomers($limit = 10) {
    global $conn;
    $limit = intval($limit);
    $r = mysqli_query($conn, "SELECT b.user_id, u.name, u.email, COUNT(*) as bookings, SUM(f.price) as total_spent,
                              MAX(b.booking_date) as last_booking
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              JOIN users u ON b.user_id=u.id
                              WHERE b.booking_status='Confirmed'
                              GROUP BY b.user_id ORDER BY total_spent DESC LIMIT {$limit}");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getOccupancyAnalysis() {
    global $conn;
    $r = mysqli_query($conn, "SELECT f.flight_id, f.flight_number, f.airline_name, f.source, f.destination,
                              f.total_seats, f.seats_available,
                              (f.total_seats - f.seats_available) as booked,
                              ROUND((f.total_seats - f.seats_available) / f.total_seats * 100, 1) as occupancy_pct
                              FROM flights f WHERE f.status IN ('Scheduled','Delayed')
                              ORDER BY occupancy_pct ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getRouteAnalytics() {
    global $conn;
    $r = mysqli_query($conn, "SELECT f.source, f.destination, COUNT(*) as total_bookings,
                              SUM(f.price) as total_revenue, ROUND(AVG(f.price), 2) as avg_fare,
                              ROUND(AVG(f.total_seats - f.seats_available), 1) as avg_occupancy
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              WHERE b.booking_status='Confirmed'
                              GROUP BY f.source, f.destination ORDER BY total_bookings DESC");
    $routes = [];
    while ($row = mysqli_fetch_assoc($r)) $routes[] = $row;
    $r = mysqli_query($conn, "SELECT f.source, f.destination, COUNT(b.booking_id) as bookings
                              FROM flights f LEFT JOIN bookings b ON f.flight_id=b.flight_id AND b.booking_status='Confirmed'
                              WHERE f.status IN ('Scheduled','Delayed')
                              GROUP BY f.source, f.destination HAVING bookings=0");
    $inactive = [];
    while ($row = mysqli_fetch_assoc($r)) $inactive[] = $row;
    return ['routes' => $routes, 'inactive' => $inactive];
}

function getBookingTrends() {
    global $conn;
    $r = mysqli_query($conn, "SELECT DATE_FORMAT(booking_date, '%Y-%m-%d') as date, COUNT(*) as count
                              FROM bookings WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                              GROUP BY DATE(booking_date) ORDER BY date ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getCancellationTrends() {
    global $conn;
    $r = mysqli_query($conn, "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month,
                              SUM(CASE WHEN booking_status='Cancelled' THEN 1 ELSE 0 END) as cancelled,
                              COUNT(*) as total,
                              ROUND(SUM(CASE WHEN booking_status='Cancelled' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as rate
                              FROM bookings WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                              GROUP BY month ORDER BY month ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

function getDataQualityIssues() {
    global $conn;
    $issues = [];
    $r = mysqli_query($conn, "SELECT flight_id, flight_number, source, destination FROM flights WHERE seats_available = 0 AND total_seats > 0 AND status IN ('Scheduled','Delayed')");
    while ($row = mysqli_fetch_assoc($r)) {
        $issues[] = ['type' => 'warning', 'message' => "Flight {$row['flight_number']} ({$row['source']}→{$row['destination']}) has 0 available seats."];
    }
    $r = mysqli_query($conn, "SELECT flight_id, flight_number FROM flights WHERE price <= 0");
    while ($row = mysqli_fetch_assoc($r)) {
        $issues[] = ['type' => 'error', 'message' => "Flight {$row['flight_number']} has zero or negative price."];
    }
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id NOT IN (SELECT id FROM users)");
    $row = mysqli_fetch_assoc($r);
    if ($row['c'] > 0) $issues[] = ['type' => 'error', 'message' => "{$row['c']} bookings reference non-existent users."];
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM flights WHERE seats_available > total_seats");
    $row = mysqli_fetch_assoc($r);
    if ($row['c'] > 0) $issues[] = ['type' => 'error', 'message' => "{$row['c']} flights have seats_available exceeding total_seats."];
    $r = mysqli_query($conn, "SELECT flight_number, COUNT(*) as c FROM flights GROUP BY flight_number HAVING c > 1");
    while ($row = mysqli_fetch_assoc($r)) {
        $issues[] = ['type' => 'warning', 'message' => "Duplicate flight number: {$row['flight_number']} (appears {$row['c']} times)."];
    }
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE flight_id NOT IN (SELECT flight_id FROM flights)");
    $row = mysqli_fetch_assoc($r);
    if ($row['c'] > 0) $issues[] = ['type' => 'critical', 'message' => "{$row['c']} bookings reference non-existent flights."];
    return $issues;
}

function getRepeatCustomers() {
    global $conn;
    $r = mysqli_query($conn, "SELECT b.user_id, u.name, u.email, COUNT(*) as booking_count, SUM(f.price) as total_spent
                              FROM bookings b JOIN flights f ON b.flight_id=f.flight_id
                              JOIN users u ON b.user_id=u.id
                              WHERE b.booking_status='Confirmed'
                              GROUP BY b.user_id HAVING booking_count > 1
                              ORDER BY booking_count DESC LIMIT 20");
    $data = [];
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
    return $data;
}

define('AEROBOOK_ADMIN_ANALYTICS', true);
}
