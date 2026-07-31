<?php
$pageTitle = 'Search Flights';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

$region = trim($_GET['region'] ?? 'domestic');
$isDomestic = ($region === 'domestic');
$regionLabel = $isDomestic ? 'Across India' : 'Worldwide';
$regionIcon = $isDomestic ? '🇮🇳' : '🌍';
?>

<div class="page-header text-center">
    <div class="container">
        <span class="badge bg-primary-subtle text-accent mb-2 px-3 py-1 border border-accent rounded-pill">
            <i class="bi bi-airplane-fill me-1"></i>Easy Online Booking
        </span>
        <h1 class="fw-bold"><?php echo $regionIcon; ?> Search <?php echo $isDomestic ? 'Domestic' : 'International'; ?> Flights</h1>
        <p class="text-muted">Compare prices, select seats, and book tickets with instant confirmation</p>
        <div class="d-flex gap-2 justify-content-center mt-2">
            <a href="?region=domestic" class="btn btn-sm <?php echo $isDomestic ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-bold rounded-pill px-4">🇮🇳 Domestic</a>
            <a href="?region=international" class="btn btn-sm <?php echo !$isDomestic ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-bold rounded-pill px-4">🌍 International</a>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <div class="search-panel shadow-lg rounded-4 p-4 mb-5" style="margin-top:0; background: var(--surface-card);">
        <form action="<?php echo BASE_URL; ?>fare-results.php" method="GET" class="needs-loading">
            <input type="hidden" name="region" value="<?php echo htmlspecialchars($region); ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-geo-alt me-1 text-accent"></i>Origin City</label>
                    <select name="source" class="form-select form-select-lg fw-semibold" required>
                        <option value="">Select Origin</option>
                        <?php cityOptions(null, $region); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-geo-alt-fill me-1 text-accent"></i>Destination City</label>
                    <select name="destination" class="form-select form-select-lg fw-semibold" required>
                        <option value="">Select Destination</option>
                        <?php cityOptions(null, $region); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-calendar3 me-1 text-accent"></i>Travel Date</label>
                    <input type="date" name="travel_date" class="form-control form-control-lg fw-semibold" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-accent btn-lg w-100 fw-bold py-3"><i class="bi bi-graph-up-arrow me-2"></i>Smart Search</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-warning text-dark px-3 py-2 fw-bold rounded-pill"><i class="bi bi-lightning-fill me-1"></i>Smart Fare Engine</span>
                        <small class="text-muted">Finds direct + connecting flights · Compares prices · Recommends best value</small>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php
    $today_date = date('Y-m-d');
    $today_day = date('l');
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-broadcast me-2 text-accent"></i><?php echo $isDomestic ? 'Domestic' : 'International'; ?> Flights Today (<?php echo $today_day; ?>)</h3>
        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold">Live Schedule Active</span>
    </div>

    <?php
    $flights = getTodaysFlightsRegion($today_date, $region);
    if (!empty($flights)): foreach ($flights as $f): ?>
    <div class="flight-card hover-lift p-4 mb-3 border rounded-4 bg-white shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo bg-primary text-white rounded-circle fw-extrabold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <?php echo airlineInitials($f['airline_name']); ?>
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
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point">
                        <div class="route-time fw-extrabold fs-4 text-dark"><?php echo formatTime($f['arrival_time']); ?></div>
                        <div class="route-city text-muted small fw-semibold"><?php echo htmlspecialchars($f['destination']); ?></div>
                    </div>
                </div>
                <div class="route-duration text-center text-muted small fw-semibold mt-1">
                    <i class="bi bi-clock me-1"></i><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?> ·
                    <span class="text-success"><i class="bi bi-ticket-perforated me-1"></i><?php echo $f['seats_available']; ?> seats remaining</span>
                </div>
            </div>
            <div class="col-md-2 text-center my-2 my-md-0">
                <div class="flight-price text-accent fw-extrabold fs-3"><?php echo formatPrice($f['price']); ?></div>
                <small class="text-muted d-block fw-semibold">per adult seat</small>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?php echo BASE_URL; ?>booking.php?flight_id=<?php echo $f['flight_id']; ?>&date=<?php echo $today_date; ?>" class="btn btn-accent btn-lg w-100 fw-bold shadow-sm mb-2">Book Flight</a>
                <a href="<?php echo BASE_URL; ?>flight-details.php?id=<?php echo $f['flight_id']; ?>&date=<?php echo $today_date; ?>" class="btn btn-outline-secondary btn-sm w-100 fw-bold">View Schedule</a>
            </div>
        </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state py-5 text-center">
        <i class="bi bi-airplane text-muted display-1"></i>
        <h4 class="mt-3 fw-bold">No <?php echo $isDomestic ? 'domestic' : 'international'; ?> flights available</h4>
        <p class="text-muted">Please check back later for newly scheduled routes, or try the <?php echo $isDomestic ? 'international' : 'domestic'; ?> portal.</p>
        <a href="?region=<?php echo $isDomestic ? 'international' : 'domestic'; ?>" class="btn btn-accent mt-2">Switch to <?php echo $isDomestic ? 'International' : 'Domestic'; ?> Portal</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
