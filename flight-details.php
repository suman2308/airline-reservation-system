<?php $pageTitle = 'Flight Details'; require_once 'includes/header.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { $_SESSION['error'] = 'Invalid flight.'; redirect('search-flights.php'); }

$stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) { 
    $_SESSION['error'] = 'Flight not found.'; 
    mysqli_stmt_close($stmt);
    redirect('search-flights.php'); 
}
$f = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$selected_date = $_GET['date'] ?? '';
if (!empty($selected_date)) {
    $d = DateTime::createFromFormat('Y-m-d', $selected_date);
    if (!$d || $d->format('Y-m-d') !== $selected_date) {
        $selected_date = date('Y-m-d', strtotime($f['departure_time']));
    }
} else {
    $selected_date = date('Y-m-d', strtotime($f['departure_time']));
}
?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-airplane me-2"></i><?php echo htmlspecialchars($f['airline_name'] . ' – ' . $f['flight_number']); ?></h1>
        <p><?php echo htmlspecialchars($f['source'] . ' → ' . $f['destination']); ?></p>
    </div>
</div>
<div class="container py-5">
    <?php showAlert(); ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="flight-card">
                <div class="flight-airline">
                    <div class="airline-logo" style="width:56px;height:56px;font-size:1.1rem;"><?php echo strtoupper(substr($f['airline_name'],0,2)); ?></div>
                    <div><div class="airline-name" style="font-size:1.2rem;"><?php echo htmlspecialchars($f['airline_name']); ?></div><div class="flight-number"><?php echo htmlspecialchars($f['flight_number']); ?> · <?php echo statusBadge($f['status']); ?></div></div>
                </div>
                <hr>
                <div class="flight-route py-3">
                    <div class="route-point">
                        <div class="route-time" style="font-size:1.8rem;"><?php echo formatTime($f['departure_time']); ?></div>
                        <div class="route-city" style="font-size:1rem;"><?php echo htmlspecialchars($f['source']); ?></div>
                        <small class="text-muted"><?php echo formatDate($selected_date); ?></small>
                    </div>
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point">
                        <div class="route-time" style="font-size:1.8rem;"><?php echo formatTime($f['arrival_time']); ?></div>
                        <div class="route-city" style="font-size:1rem;"><?php echo htmlspecialchars($f['destination']); ?></div>
                        <small class="text-muted"><?php echo formatDate($selected_date); ?></small>
                    </div>
                </div>
                <hr>
                <div class="row text-center py-2">
                    <div class="col-4"><p class="mb-0 text-muted small">Duration</p><strong><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?></strong></div>
                    <div class="col-4"><p class="mb-0 text-muted small">Total Seats</p><strong><?php echo $f['total_seats']; ?></strong></div>
                    <div class="col-4"><p class="mb-0 text-muted small">Available</p><strong class="text-accent"><?php echo $f['seats_available']; ?></strong></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="flight-card text-center">
                <p class="text-muted mb-1">Fare per person</p>
                <div class="flight-price" style="font-size:2.2rem;"><?php echo formatPrice($f['price']); ?></div>
                <hr>
                <?php if ($f['seats_available'] > 0 && $f['status'] === 'Scheduled'): ?>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>booking.php?flight_id=<?php echo $f['flight_id']; ?>&date=<?php echo $selected_date; ?>" class="btn btn-accent w-100 py-2 btn-lg"><i class="bi bi-ticket-perforated me-2"></i>Book This Flight</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-accent w-100 py-2 btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Book</a>
                        <p class="text-muted small mt-2">You need to login to book a flight</p>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 py-2" disabled>Not Available</button>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-arrow-left me-2"></i>Back to Search</a>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

