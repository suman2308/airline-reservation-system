<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/helpers.php';
require_once 'includes/Notifications.php';

if (!isLoggedIn()) redirect('login.php');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect('my-bookings.php');

$userId = $_SESSION['user_id'];
$ref = cancelBooking($id, $userId);

if ($ref) {
    // Create cancellation notification
    AeroNotifications::create($userId, 'booking_cancelled', 'Booking Cancelled', "Ref: {$ref}", 'my-bookings.php');

setFlash('success', 'Booking ' . $ref . ' has been cancelled successfully.');
} else {
    setFlash('error', 'Unable to cancel this booking.');
}
redirect('my-bookings.php');
