<?php 
$pageTitle = 'Flight Search Results'; 
require_once 'includes/header.php';

$source = trim($_GET['source'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$travel_date = trim($_GET['travel_date'] ?? '');
$sort = trim($_GET['sort'] ?? 'time_asc');

if (empty($source) || empty($destination)) { 
    $_SESSION['error'] = 'Please select origin and destination.'; 
    redirect('search-flights.php'); 
}
if ($source === $destination) { 
    $_SESSION['error'] = 'Origin and destination cannot be the same.'; 
    redirect('search-flights.php'); 
}

$display_date = !empty($travel_date) ? $travel_date : date('Y-m-d');
$display_day = date('l', strtotime($display_date));

$order_clause = "TIME(departure_time) ASC";
if ($sort === 'price_asc') {
    $order_clause = "price ASC";
} elseif ($sort === 'price_desc') {
    $order_clause = "price DESC";
}
?>

<div class="page-header">
    <div class="container">
        <span class="badge bg-primary-subtle text-accent mb-2 px-3 py-1 border border-accent rounded-pill">
            <i class="bi bi-airplane-fill me-1"></i>Direct Non-Stop Flights
        </span>
        <h1 class="fw-bold"><i class="bi bi-geo-alt me-2 text-accent"></i><?php echo htmlspecialchars("$source → $destination"); ?></h1>
        <p class="mb-0 text-muted"><i class="bi bi-calendar-event me-1"></i><?php echo formatDate($display_date) . ' (' . $display_day . ')'; ?></p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
            <i class="bi bi-sliders me-2"></i>Modify Search
        </a>

        <!-- Sorting Buttons -->
        <div class="btn-group shadow-sm rounded-pill p-1 bg-white border" role="group">
            <span class="btn btn-sm btn-light border-0 text-muted fw-bold pe-2 d-none d-md-inline">Sort By:</span>
            <a href="flight-results.php?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&sort=time_asc" 
               class="btn btn-sm rounded-pill fw-bold <?php echo ($sort === 'time_asc') ? 'btn-accent' : 'btn-light text-secondary'; ?>">
               <i class="bi bi-clock me-1"></i>Departure Time
            </a>
            <a href="flight-results.php?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&sort=price_asc" 
               class="btn btn-sm rounded-pill fw-bold <?php echo ($sort === 'price_asc') ? 'btn-accent' : 'btn-light text-secondary'; ?>">
               <i class="bi bi-arrow-down-short me-1"></i>Cheapest First
            </a>
        </div>
    </div>

    <?php
    $sql = "SELECT * FROM flights WHERE source=? AND destination=? AND status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time) = DAYOFWEEK(?) ORDER BY {$order_clause}";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $source, $destination, $display_date);
    mysqli_stmt_execute($stmt);
    $flights = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($flights) > 0):
        echo '<p class="text-muted mb-4 fw-semibold"><i class="bi bi-check-circle-fill text-success me-1"></i><strong>' . mysqli_num_rows($flights) . '</strong> verified flight(s) available for booking</p>';
        while ($f = mysqli_fetch_assoc($flights)): ?>
    <div class="flight-card hover-lift p-4 mb-3 border rounded-4 shadow-sm bg-white">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo bg-primary text-white rounded-circle fw-extrabold d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <?php echo strtoupper(substr($f['airline_name'],0,2)); ?>
                    </div>
                    <div>
                        <div class="airline-name fw-bold text-dark fs-6"><?php echo htmlspecialchars($f['airline_name']); ?></div>
                        <div class="flight-number text-muted small"><i class="bi bi-hash"></i><?php echo htmlspecialchars($f['flight_number']); ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="flight-route">
                    <div class="route-point">
                        <div class="route-time fw-extrabold fs-4 text-dark"><?php echo formatTime($f['departure_time']); ?></div>
                        <div class="route-city text-muted small fw-semibold"><?php echo htmlspecialchars($f['source']); ?></div>
                    </div>
                    <div class="route-line">
                        <i class="bi bi-airplane-fill"></i>
                    </div>
                    <div class="route-point">
                        <div class="route-time fw-extrabold fs-4 text-dark"><?php echo formatTime($f['arrival_time']); ?></div>
                        <div class="route-city text-muted small fw-semibold"><?php echo htmlspecialchars($f['destination']); ?></div>
                    </div>
                </div>
                <div class="route-duration text-center text-muted small fw-semibold mt-1">
                    <i class="bi bi-clock me-1"></i><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?> · 
                    <span class="text-success"><i class="bi bi-ticket-perforated me-1"></i><?php echo $f['seats_available']; ?> seats left</span>
                </div>
            </div>

            <div class="col-md-2 text-center my-3 my-md-0">
                <div class="flight-price text-accent fw-extrabold fs-3"><?php echo formatPrice($f['price']); ?></div>
                <small class="text-muted d-block fw-semibold">per adult seat</small>
            </div>

            <div class="col-md-2 text-end">
                <a href="<?php echo BASE_URL; ?>booking.php?flight_id=<?php echo $f['flight_id']; ?>&date=<?php echo $display_date; ?>" class="btn btn-accent btn-lg w-100 fw-bold shadow-sm">
                    Select Seats <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endwhile; mysqli_stmt_close($stmt); else: mysqli_stmt_close($stmt); ?>
    <div class="empty-state py-5 text-center">
        <i class="bi bi-emoji-frown text-muted display-1"></i>
        <h4 class="mt-3 fw-bold">No flights found for this route</h4>
        <p class="text-muted">There are no direct flights scheduled for <strong><?php echo htmlspecialchars("$source → $destination"); ?></strong> on <strong><?php echo formatDate($display_date); ?></strong>.</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent btn-lg px-4 mt-2 fw-bold">Try Another Date or Route</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
