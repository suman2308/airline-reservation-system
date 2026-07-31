<?php
/**
 * AeroBook – Reusable Query & Database Helpers
 *
 * Backward-compatibility shim that loads all focused helper modules.
 *
 * Each module file handles a specific domain:
 *   DatabaseHelper.php   — dbInsert, dbUpdate, deleteById, countWhere
 *   FlightHelper.php     — getFlightById, getAllFlights, getFlightsByRoute, etc.
 *   BookingHelper.php    — getUserBookings, cancelBooking, saveBookingAddons, etc.
 *   TravelHelper.php     — saved passengers, routes, price watches, travel stats
 *   AdminAnalytics.php   — logAdminAction, getTodayOpsMetrics, analytics queries
 *   TokenHelper.php      — generateDeleteToken, validateDeleteToken, deleteLink
 *   RenderHelper.php     — renderFlightCard
 */

if (!defined('AEROBOOK_HELPERS')) {

require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/FlightHelper.php';
require_once __DIR__ . '/BookingHelper.php';
require_once __DIR__ . '/TravelHelper.php';
require_once __DIR__ . '/AdminAnalytics.php';
require_once __DIR__ . '/TokenHelper.php';
require_once __DIR__ . '/RenderHelper.php';

define('AEROBOOK_HELPERS', true);
}
