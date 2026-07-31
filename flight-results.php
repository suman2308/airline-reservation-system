<?php
$pageTitle = 'Flight Search Results';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

$source = trim($_GET['source'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$travel_date = trim($_GET['travel_date'] ?? '');
$sort = trim($_GET['sort'] ?? 'time_asc');
$region = trim($_GET['region'] ?? '');

if (empty($source) || empty($destination)) {
    setFlash('error', 'Please select origin and destination.');
    redirect('search-flights.php' . ($region ? '?region=' . urlencode($region) : ''));
}
if ($source === $destination) {
    setFlash('error', 'Origin and destination cannot be the same.');
    redirect('search-flights.php' . ($region ? '?region=' . urlencode($region) : ''));
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
        <a href="<?php echo BASE_URL; ?>search-flights.php<?php echo $region ? '?region=' . urlencode($region) : ''; ?>" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
            <i class="bi bi-sliders me-2"></i>Modify Search
        </a>
        <div class="btn-group shadow-sm rounded-pill p-1 bg-white border" role="group">
            <span class="btn btn-sm btn-light border-0 text-muted fw-bold pe-2 d-none d-md-inline">Sort By:</span>
            <a href="flight-results.php?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&sort=time_asc<?php echo $region ? '&region=' . urlencode($region) : ''; ?>"
               class="btn btn-sm rounded-pill fw-bold <?php echo ($sort === 'time_asc') ? 'btn-accent' : 'btn-light text-secondary'; ?>">
               <i class="bi bi-clock me-1"></i>Departure Time
            </a>
            <a href="flight-results.php?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&sort=price_asc<?php echo $region ? '&region=' . urlencode($region) : ''; ?>"
               class="btn btn-sm rounded-pill fw-bold <?php echo ($sort === 'price_asc') ? 'btn-accent' : 'btn-light text-secondary'; ?>">
               <i class="bi bi-arrow-down-short me-1"></i>Cheapest First
            </a>
        </div>
    </div>

    <?php
    $flights = getFlightsByRouteRegion($source, $destination, $display_date, $region, $order_clause);

    if (!empty($flights)):
        $count = count($flights);
        echo '<p class="text-muted mb-4 fw-semibold"><i class="bi bi-check-circle-fill text-success me-1"></i><strong>' . $count . '</strong> verified flight(s) available for booking</p>';
        foreach ($flights as $f):
            $bookingUrl = BASE_URL . 'booking.php?flight_id=' . $f['flight_id'] . '&date=' . $display_date;
            renderFlightCard($f, $bookingUrl, 'Select Seats');
        endforeach;
    else: ?>
    <div class="empty-state py-5 text-center">
        <i class="bi bi-emoji-frown text-muted display-1"></i>
        <h4 class="mt-3 fw-bold">No flights found for this route</h4>
        <p class="text-muted">There are no direct flights scheduled for <strong><?php echo htmlspecialchars("$source → $destination"); ?></strong> on <strong><?php echo formatDate($display_date); ?></strong>.</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php<?php echo $region ? '?region=' . urlencode($region) : ''; ?>" class="btn btn-accent btn-lg px-4 mt-2 fw-bold">Try Another Date or Route</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
