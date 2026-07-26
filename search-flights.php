<?php 
$pageTitle = 'Search Flights'; 
require_once 'includes/header.php'; 
?>

<div class="page-header text-center">
    <div class="container">
        <span class="badge bg-primary-subtle text-accent mb-2 px-3 py-1 border border-accent rounded-pill">
            <i class="bi bi-airplane-fill me-1"></i>Easy Online Booking
        </span>
        <h1 class="fw-bold"><i class="bi bi-search me-2 text-accent"></i>Search Flights Across India</h1>
        <p class="text-muted">Compare prices, select seats, and book tickets with instant confirmation</p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <div class="search-panel shadow-lg rounded-4 p-4 mb-5" style="margin-top:0; background: var(--surface-card);">
        <form action="<?php echo BASE_URL; ?>flight-results.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-geo-alt me-1 text-accent"></i>Origin City</label>
                    <select name="source" class="form-select form-select-lg fw-semibold" required>
                        <option value="">Select Origin</option>
                        <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                        <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-geo-alt-fill me-1 text-accent"></i>Destination City</label>
                    <select name="destination" class="form-select form-select-lg fw-semibold" required>
                        <option value="">Select Destination</option>
                        <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                        <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label uppercase-label text-muted mb-2"><i class="bi bi-calendar3 me-1 text-accent"></i>Travel Date</label>
                    <input type="date" name="travel_date" class="form-control form-control-lg fw-semibold" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-accent btn-lg w-100 fw-bold py-3"><i class="bi bi-search me-2"></i>Find Flights</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Available Flights Today -->
    <?php 
    $today_day = date('l');
    $today_date = date('Y-m-d');
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-broadcast me-2 text-accent"></i>Scheduled Flights Today (<?php echo $today_day; ?>)</h3>
        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold">Live Schedule Active</span>
    </div>

    <?php
    $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time) = DAYOFWEEK(?) ORDER BY TIME(departure_time) ASC");
    mysqli_stmt_bind_param($stmt, "s", $today_date);
    mysqli_stmt_execute($stmt);
    $flights = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($flights) > 0):
        while ($f = mysqli_fetch_assoc($flights)): ?>
    <div class="flight-card hover-lift p-4 mb-3 border rounded-4 bg-white shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo bg-primary text-white rounded-circle fw-extrabold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
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
    <?php endwhile; mysqli_stmt_close($stmt); else: mysqli_stmt_close($stmt); ?>
    <div class="empty-state py-5 text-center">
        <i class="bi bi-airplane text-muted display-1"></i>
        <h4 class="mt-3 fw-bold">No flights available right now</h4>
        <p class="text-muted">Please check back later for newly scheduled routes.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
