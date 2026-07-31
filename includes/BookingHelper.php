<?php
/**
 * AeroBook – Booking Query Helpers
 *
 * Database queries specific to bookings, add-ons, and cancellation.
 * All functions return arrays (not raw mysqli_result) for consistency.
 */

if (!defined('AEROBOOK_BOOKING_HELPER')) {

function getUserBookings($userId, $limit = null) {
    global $conn;
    $sql = "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price 
            FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.user_id = ? ORDER BY b.booking_date DESC";
    if ($limit !== null) { $sql .= " LIMIT " . intval($limit); }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bookings = [];
    while ($row = mysqli_fetch_assoc($result)) $bookings[] = $row;
    mysqli_stmt_close($stmt);
    return $bookings;
}

function getBookingByRef($ref, $userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price, u.name as booked_by FROM bookings b JOIN flights f ON b.flight_id = f.flight_id JOIN users u ON b.user_id = u.id WHERE b.booking_ref = ? AND b.user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $ref, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $booking;
}

function getBookingsByRefList($refList, $userId) {
    global $conn;
    $refList = array_filter(array_map('trim', $refList));
    if (empty($refList)) return [];
    $inClause = implode(',', array_fill(0, count($refList), '?'));
    $types = str_repeat('s', count($refList)) . 'i';
    $sql = "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.booking_ref IN ({$inClause}) AND b.user_id = ? ORDER BY b.booking_id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    $bindParams = array_merge($refList, [$userId]);
    mysqli_stmt_bind_param($stmt, $types, ...$bindParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bookings = [];
    while ($row = mysqli_fetch_assoc($result)) $bookings[] = $row;
    mysqli_stmt_close($stmt);
    return $bookings;
}

function getAllBookings() {
    global $conn;
    $r = mysqli_query($conn, "SELECT b.*, f.flight_number, f.airline_name, u.email as user_email FROM bookings b JOIN flights f ON b.flight_id=f.flight_id JOIN users u ON b.user_id=u.id ORDER BY b.booking_date DESC");
    $bookings = [];
    while ($row = mysqli_fetch_assoc($r)) $bookings[] = $row;
    return $bookings;
}

function getRecentBookings($limit = 5) {
    global $conn;
    $limit = intval($limit);
    $r = mysqli_query($conn, "SELECT b.*, f.flight_number, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id=f.flight_id ORDER BY b.booking_date DESC LIMIT {$limit}");
    $bookings = [];
    while ($row = mysqli_fetch_assoc($r)) $bookings[] = $row;
    return $bookings;
}

function countUserBookings($userId, $status = null) {
    global $conn;
    if ($status !== null) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND booking_status = ?");
        mysqli_stmt_bind_param($stmt, "is", $userId, $status);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int)$row['c'];
}

/**
 * Cancel a booking (for both user and admin).
 * If $userId is provided, only cancels if the booking belongs to that user.
 * Returns the booking_ref on success, or true for admin, or false on failure.
 */
function cancelBooking($bookingId, $userId = null) {
    global $conn;
    if ($userId !== null) {
        $stmt = mysqli_prepare($conn, "SELECT booking_ref, flight_id FROM bookings WHERE booking_id = ? AND user_id = ? AND booking_status = 'Confirmed'");
        mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT booking_ref, flight_id FROM bookings WHERE booking_id = ? AND booking_status = 'Confirmed'");
        mysqli_stmt_bind_param($stmt, "i", $bookingId);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($b = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        dbUpdate("UPDATE bookings SET booking_status='Cancelled' WHERE booking_id=?", "i", $bookingId);
        dbUpdate("UPDATE flights SET seats_available = seats_available + 1 WHERE flight_id=?", "i", $b['flight_id']);
        if ($userId !== null) {
            return $b['booking_ref'];
        }
        return true;
    }
    mysqli_stmt_close($stmt);
    return false;
}

function saveBookingAddons($bookingId, $baggageCost, $mealCost, $baggageOption, $mealOption) {
    global $conn;
    if ($baggageCost > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO booking_addons (booking_id, addon_type, addon_name, amount) VALUES (?, 'baggage', ?, ?)");
        $name = ($baggageCost >= 1400) ? '+20kg Heavy Baggage' : '+10kg Extra Baggage';
        mysqli_stmt_bind_param($stmt, "isd", $bookingId, $name, $baggageCost);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    if ($mealCost > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO booking_addons (booking_id, addon_type, addon_name, amount) VALUES (?, 'meal', ?, ?)");
        $mlabels = ['350' => 'Vegetarian Gourmet Meal', '450' => 'Non-Veg Chicken Feast'];
        $mealLabel = $mlabels[$mealCost] ?? $mealOption;
        mysqli_stmt_bind_param($stmt, "isd", $bookingId, $mealLabel, $mealCost);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function getBookingAddons($bookingId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM booking_addons WHERE booking_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $addons = [];
    while ($row = mysqli_fetch_assoc($result)) $addons[] = $row;
    mysqli_stmt_close($stmt);
    return $addons;
}

define('AEROBOOK_BOOKING_HELPER', true);
}
