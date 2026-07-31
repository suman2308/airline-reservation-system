<?php
/**
 * AeroBook – Render Helpers
 *
 * Functions that render HTML output. Separated from query helpers to maintain
 * separation of concerns between data access and presentation.
 */

if (!defined('AEROBOOK_RENDER_HELPER')) {

function renderFlightCard($flight, $actionUrl, $actionLabel = 'Book Flight') {
    $source = htmlspecialchars($flight['source']);
    $dest = htmlspecialchars($flight['destination']);
    $airline = htmlspecialchars($flight['airline_name']);
    $flightNum = htmlspecialchars($flight['flight_number']);
    $initials = strtoupper(substr($flight['airline_name'], 0, 2));
    $price = formatPrice($flight['price']);
    $depTime = formatTime($flight['departure_time']);
    $arrTime = formatTime($flight['arrival_time']);
    $duration = calcDuration($flight['departure_time'], $flight['arrival_time']);
    ?>
    <div class="flight-card hover-lift p-4 mb-3 border rounded-4 shadow-sm bg-white">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;"><?php echo $initials; ?></div>
                    <div><div class="airline-name fw-bold"><?php echo $airline; ?></div><div class="flight-number text-muted small"><?php echo $flightNum; ?></div></div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="flight-route">
                    <div class="route-point"><div class="route-time fw-extrabold fs-4 text-dark"><?php echo $depTime; ?></div><div class="route-city text-muted small"><?php echo $source; ?></div></div>
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point"><div class="route-time fw-extrabold fs-4 text-dark"><?php echo $arrTime; ?></div><div class="route-city text-muted small"><?php echo $dest; ?></div></div>
                </div>
                <div class="text-center text-muted small fw-semibold mt-1"><i class="bi bi-clock me-1"></i><?php echo $duration; ?> · <span class="text-success"><i class="bi bi-ticket-perforated me-1"></i><?php echo $flight['seats_available']; ?> seats left</span></div>
            </div>
            <div class="col-md-2 text-center my-3 my-md-0"><div class="flight-price text-accent fw-extrabold fs-3"><?php echo $price; ?></div><small class="text-muted">per adult seat</small></div>
            <div class="col-md-2 text-end"><a href="<?php echo $actionUrl; ?>" class="btn btn-accent btn-lg w-100 fw-bold shadow-sm"><?php echo $actionLabel; ?> <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>
    <?php
}

define('AEROBOOK_RENDER_HELPER', true);
}
