<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isAdminLoggedIn()) redirect('../admin/login.php');
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    mysqli_query($conn, "DELETE FROM flights WHERE flight_id=$id");
    $_SESSION['success'] = 'Flight deleted successfully.';
} else {
    $_SESSION['error'] = 'Invalid flight.';
}
redirect('manage-flights.php');
?>
