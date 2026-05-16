<?php 
require_once 'includes/config.php'; 
require_once 'includes/functions.php';

if (!isLoggedIn()) redirect('login.php');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect('my-bookings.php');

$user_id = $_SESSION['user_id'];

// Fetch booking safely
$stmt = mysqli_prepare($conn, "SELECT booking_ref, flight_id FROM bookings WHERE booking_id=? AND user_id=? AND booking_status='Confirmed'");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($b = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    
    // Update booking status
    $upd_stmt = mysqli_prepare($conn, "UPDATE bookings SET booking_status='Cancelled' WHERE booking_id=?");
    mysqli_stmt_bind_param($upd_stmt, "i", $id);
    mysqli_stmt_execute($upd_stmt);
    mysqli_stmt_close($upd_stmt);
    
    // Restore seat
    $seat_stmt = mysqli_prepare($conn, "UPDATE flights SET seats_available = seats_available + 1 WHERE flight_id=?");
    mysqli_stmt_bind_param($seat_stmt, "i", $b['flight_id']);
    mysqli_stmt_execute($seat_stmt);
    mysqli_stmt_close($seat_stmt);
    
    $_SESSION['success'] = 'Booking ' . $b['booking_ref'] . ' has been cancelled successfully.';
} else {
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = 'Unable to cancel this booking.';
}
redirect('my-bookings.php');
?>

