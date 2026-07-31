<?php
/**
 * AeroBook – Travel & Passenger Helpers
 *
 * Saved passengers, saved routes, price watches, travel statistics,
 * and travel milestones.
 */

if (!defined('AEROBOOK_TRAVEL_HELPER')) {

// ── Saved Passengers ──

function getSavedPassengers($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM saved_passengers WHERE user_id = ? ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $passengers = [];
    while ($row = mysqli_fetch_assoc($result)) $passengers[] = $row;
    mysqli_stmt_close($stmt);
    return $passengers;
}

function savePassenger($userId, $name, $age, $gender) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO saved_passengers (user_id, name, age, gender) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isis", $userId, $name, $age, $gender);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

function deleteSavedPassenger($id, $userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM saved_passengers WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// ── Saved Routes ──

function getSavedRoutes($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM saved_routes WHERE user_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $routes = [];
    while ($row = mysqli_fetch_assoc($result)) $routes[] = $row;
    mysqli_stmt_close($stmt);
    return $routes;
}

function saveRoute($userId, $source, $destination, $label = null) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO saved_routes (user_id, source, destination, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)");
    mysqli_stmt_bind_param($stmt, "isss", $userId, $source, $destination, $label);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id ?: true;
}

function deleteSavedRoute($id, $userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM saved_routes WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// ── Price Watches ──

function getPriceWatches($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM price_watches WHERE user_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $watches = [];
    while ($row = mysqli_fetch_assoc($result)) $watches[] = $row;
    mysqli_stmt_close($stmt);
    return $watches;
}

function addPriceWatch($userId, $source, $destination, $maxFare = null, $preferredMonth = null) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO price_watches (user_id, source, destination, max_fare, preferred_month) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issds", $userId, $source, $destination, $maxFare, $preferredMonth);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

function deletePriceWatch($id, $userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM price_watches WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// ── Distances Table ──

function getCityDistanceKm($source, $destination) {
    $distances = [
        'Delhi-Mumbai' => 1400, 'Delhi-Bangalore' => 2100, 'Delhi-Kolkata' => 1470,
        'Delhi-Chennai' => 2180, 'Delhi-Hyderabad' => 1550, 'Delhi-Pune' => 1470,
        'Mumbai-Bangalore' => 840, 'Mumbai-Kolkata' => 1960, 'Mumbai-Chennai' => 1330,
        'Mumbai-Hyderabad' => 710, 'Mumbai-Pune' => 150, 'Bangalore-Kolkata' => 1870,
        'Bangalore-Chennai' => 350, 'Bangalore-Hyderabad' => 560, 'Bangalore-Pune' => 830,
        'Kolkata-Chennai' => 1670, 'Kolkata-Hyderabad' => 1450, 'Kolkata-Pune' => 2030,
        'Chennai-Hyderabad' => 630, 'Chennai-Pune' => 1000, 'Hyderabad-Pune' => 560,
    ];
    $key1 = $source . '-' . $destination;
    $key2 = $destination . '-' . $source;
    return $distances[$key1] ?? $distances[$key2] ?? 0;
}

// ── Comprehensive Travel Statistics ──

function getComprehensiveTravelStats($userId) {
    global $conn;

    $stats = [
        'total_trips' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0,
        'total_spent' => 0, 'avg_ticket_price' => 0, 'unique_routes' => 0,
        'unique_cities' => [], 'favorite_airline' => '', 'favorite_route' => '',
        'total_distance' => 0, 'longest_flight' => null, 'shortest_flight' => null,
        'most_active_month' => '', 'avg_trip_duration' => 0,
        'airline_counts' => [], 'route_counts' => [], 'month_counts' => [],
        'first_flight_date' => null, 'total_distance_km' => 0,
    ];

    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price
                                    FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? ORDER BY b.booking_date ASC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $allBookings = [];
    while ($row = mysqli_fetch_assoc($result)) $allBookings[] = $row;
    mysqli_stmt_close($stmt);

    $stats['total_trips'] = count($allBookings);
    if ($stats['total_trips'] === 0) return $stats;

    $totalPrice = 0;
    $durationSum = 0;
    $durationCount = 0;
    $longestDuration = 0;
    $shortestDuration = PHP_FLOAT_MAX;
    $airlines = [];
    $routes = [];
    $months = [];
    $cities = [];
    $firstDate = null;

    foreach ($allBookings as $b) {
        if ($b['booking_status'] === 'Confirmed') {
            if (strtotime($b['departure_time']) > time()) {
                $stats['upcoming']++;
            } else {
                $stats['completed']++;
            }
            $totalPrice += floatval($b['price']);

            $dep = new DateTime($b['departure_time']);
            $arr = new DateTime($b['arrival_time']);
            $durationMinutes = (int)($dep->diff($arr)->h * 60 + $dep->diff($arr)->i);
            if ($durationMinutes > $longestDuration) {
                $longestDuration = $durationMinutes;
                $stats['longest_flight'] = $b;
            }
            if ($durationMinutes < $shortestDuration) {
                $shortestDuration = $durationMinutes;
                $stats['shortest_flight'] = $b;
            }
            $durationSum += $durationMinutes;
            $durationCount++;

            $al = $b['airline_name'];
            $airlines[$al] = ($airlines[$al] ?? 0) + 1;

            $routeKey = $b['source'] . '-' . $b['destination'];
            $routes[$routeKey] = ($routes[$routeKey] ?? 0) + 1;

            $monthKey = date('Y-m', strtotime($b['booking_date']));
            $months[$monthKey] = ($months[$monthKey] ?? 0) + 1;

            $cities[$b['source']] = true;
            $cities[$b['destination']] = true;

            $stats['total_distance_km'] += getCityDistanceKm($b['source'], $b['destination']);

            if ($firstDate === null || $b['booking_date'] < $firstDate) {
                $firstDate = $b['booking_date'];
            }
        } else {
            $stats['cancelled']++;
        }
    }

    $stats['total_spent'] = $totalPrice;
    $stats['unique_routes'] = count($routes);
    $stats['unique_cities'] = array_keys($cities);
    $stats['avg_ticket_price'] = $durationCount > 0 ? $totalPrice / $durationCount : 0;
    $stats['avg_trip_duration'] = $durationCount > 0 ? round($durationSum / $durationCount) : 0;

    arsort($airlines);
    $stats['airline_counts'] = $airlines;
    $stats['favorite_airline'] = key($airlines) ?: 'N/A';

    arsort($routes);
    $stats['route_counts'] = $routes;
    $stats['favorite_route'] = key($routes) ?: 'N/A';

    arsort($months);
    $stats['month_counts'] = $months;
    $stats['most_active_month'] = key($months) ?: 'N/A';

    if ($firstDate) $stats['first_flight_date'] = $firstDate;

    // Upcoming trip
    $stmt2 = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time
                                    FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? AND b.booking_status = 'Confirmed' AND f.departure_time >= NOW()
                                    ORDER BY f.departure_time ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt2, "i", $userId);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $stats['upcoming_trip'] = mysqli_fetch_assoc($result2);
    mysqli_stmt_close($stmt2);

    return $stats;
}

// ── Travel Milestones ──

function getTravelMilestones($userId, $stats) {
    $milestones = [];

    if ($stats['total_trips'] >= 1) {
        $milestones[] = ['icon' => 'bi-star-fill', 'label' => 'First Flight', 'date' => $stats['first_flight_date'] ? formatDate($stats['first_flight_date']) : '', 'class' => 'text-warning'];
    }

    if ($stats['total_trips'] >= 5) $milestones[] = ['icon' => 'bi-award-fill', 'label' => '5 Trips Completed', 'count' => $stats['total_trips'], 'class' => 'text-success'];
    elseif ($stats['total_trips'] >= 3) $milestones[] = ['icon' => 'bi-award', 'label' => '3 Trips Completed', 'count' => $stats['total_trips'], 'class' => 'text-info'];

    if ($stats['total_trips'] >= 10) $milestones[] = ['icon' => 'bi-trophy-fill', 'label' => '10 Trips', 'count' => $stats['total_trips'], 'class' => 'text-warning'];

    $cityCount = count($stats['unique_cities']);
    if ($cityCount >= 5) $milestones[] = ['icon' => 'bi-geo-alt-fill', 'label' => 'Visited ' . $cityCount . ' Cities', 'class' => 'text-accent'];
    elseif ($cityCount >= 3) $milestones[] = ['icon' => 'bi-geo-alt', 'label' => 'Visited ' . $cityCount . ' Cities', 'class' => 'text-accent'];

    // Window Seat Lover
    if ($stats['total_trips'] > 0) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT b.seat_number FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.user_id=? AND b.booking_status='Confirmed' AND (b.seat_number LIKE '%A' OR b.seat_number LIKE '%F') LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $milestones[] = ['icon' => 'bi-window', 'label' => 'Window Seat Lover', 'class' => 'text-info'];
        }
        mysqli_stmt_close($stmt);

        // Business Traveler
        $stmt2 = mysqli_prepare($conn, "SELECT b.seat_number FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.user_id=? AND b.booking_status='Confirmed' AND CAST(SUBSTRING(b.seat_number, 1, LENGTH(b.seat_number)-1) AS UNSIGNED) <= 2 LIMIT 1");
        mysqli_stmt_bind_param($stmt2, "i", $userId);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_store_result($stmt2);
        if (mysqli_stmt_num_rows($stmt2) > 0) {
            $milestones[] = ['icon' => 'bi-briefcase-fill', 'label' => 'Business Traveler', 'class' => 'text-primary'];
        }
        mysqli_stmt_close($stmt2);
    }

    // Early Booker
    if ($stats['total_trips'] > 0) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT b.booking_date, b.travel_date FROM bookings b WHERE b.user_id=? AND b.booking_status='Confirmed' AND DATEDIFF(b.travel_date, b.booking_date) >= 7 LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $milestones[] = ['icon' => 'bi-calendar-check-fill', 'label' => 'Early Booker', 'class' => 'text-success'];
        }
        mysqli_stmt_close($stmt);
    }

    return $milestones;
}

define('AEROBOOK_TRAVEL_HELPER', true);
}
