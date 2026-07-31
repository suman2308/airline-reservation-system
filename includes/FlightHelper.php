<?php
/**
 * AeroBook – Flight Query Helpers
 *
 * Database queries specific to flights.
 * All functions return arrays (not raw mysqli_result) for consistency.
 */

if (!defined('AEROBOOK_FLIGHT_HELPER')) {

function getFlightById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $flight = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $flight;
}

function getAllFlights($orderBy = 'departure_time ASC') {
    global $conn;
    $orderBy = preg_replace('/[^a-zA-Z0-9_ ]/', '', $orderBy);
    $r = mysqli_query($conn, "SELECT * FROM flights ORDER BY {$orderBy}");
    $flights = [];
    while ($row = mysqli_fetch_assoc($r)) $flights[] = $row;
    return $flights;
}

function getFlightsByRoute($source, $destination, $date, $orderClause = '') {
    global $conn;
    if (empty($orderClause)) $orderClause = "TIME(departure_time) ASC";
    $sql = "SELECT * FROM flights WHERE source=? AND destination=? AND status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time) = DAYOFWEEK(?) ORDER BY {$orderClause}";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $source, $destination, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $flights = [];
    while ($row = mysqli_fetch_assoc($result)) $flights[] = $row;
    mysqli_stmt_close($stmt);
    return $flights;
}

function getTodaysFlights($date) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time) = DAYOFWEEK(?) ORDER BY TIME(departure_time) ASC");
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $flights = [];
    while ($row = mysqli_fetch_assoc($result)) $flights[] = $row;
    mysqli_stmt_close($stmt);
    return $flights;
}

function flightNumberExists($flightNumber, $excludeId = null) {
    global $conn;
    if ($excludeId !== null) {
        $stmt = mysqli_prepare($conn, "SELECT flight_id FROM flights WHERE flight_number = ? AND flight_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $flightNumber, $excludeId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT flight_id FROM flights WHERE flight_number = ?");
        mysqli_stmt_bind_param($stmt, "s", $flightNumber);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function getCheapestFareForRoute($source, $destination) {
    global $conn;
    $today = date('Y-m-d');
    $stmt = mysqli_prepare($conn, "SELECT MIN(price) as cheapest FROM flights WHERE source=? AND destination=? AND status='Scheduled' AND seats_available > 0 AND departure_time >= ?");
    mysqli_stmt_bind_param($stmt, "sss", $source, $destination, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['cheapest'] ? floatval($row['cheapest']) : null;
}

function validateTravelDate($dateStr, $fallbackDateTime) {
    if (!empty($dateStr)) {
        $d = DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($d && $d->format('Y-m-d') === $dateStr) return $dateStr;
    }
    return date('Y-m-d', strtotime($fallbackDateTime));
}

define('AEROBOOK_FLIGHT_HELPER', true);
}
