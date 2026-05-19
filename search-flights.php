<?php $pageTitle = 'Search Flights'; require_once 'includes/header.php'; ?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-search me-2"></i>Search Flights</h1>
        <p>Find the best flights across India at unbeatable prices</p>
    </div>
</div>
<div class="container py-5">
    <?php showAlert(); ?>
    <div class="search-panel" style="margin-top:0;">
        <form action="<?php echo BASE_URL; ?>flight-results.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-geo-alt me-1"></i>From</label>
                    <select name="source" class="form-select" required>
                        <option value="">Select Origin</option>
                        <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                        <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-geo-alt-fill me-1"></i>To</label>
                    <select name="destination" class="form-select" required>
                        <option value="">Select Destination</option>
                        <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                        <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-calendar3 me-1"></i>Travel Date</label>
                    <input type="date" name="travel_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-search w-100"><i class="bi bi-search me-2"></i>Search</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Available Flights Today -->
    <?php 
    $today_day = date('l'); // e.g. "Wednesday"
    $today_date = date('Y-m-d');
    ?>
    <h3 class="mt-5 mb-3"><i class="bi bi-airplane me-2 text-accent"></i>Available Flights Today (<?php echo $today_day; ?>)</h3>
    <?php
    $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time) = DAYOFWEEK(?) ORDER BY TIME(departure_time) ASC");
    mysqli_stmt_bind_param($stmt, "s", $today_date);
    mysqli_stmt_execute($stmt);
    $flights = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($flights) > 0):
        while ($f = mysqli_fetch_assoc($flights)): ?>
    <div class="flight-card">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo"><?php echo strtoupper(substr($f['airline_name'],0,2)); ?></div>
                    <div>
                        <div class="airline-name"><?php echo $f['airline_name']; ?></div>
                        <div class="flight-number"><?php echo $f['flight_number']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="flight-route">
                    <div class="route-point">
                        <div class="route-time"><?php echo formatTime($f['departure_time']); ?></div>
                        <div class="route-city"><?php echo $f['source']; ?></div>
                    </div>
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point">
                        <div class="route-time"><?php echo formatTime($f['arrival_time']); ?></div>
                        <div class="route-city"><?php echo $f['destination']; ?></div>
                    </div>
                </div>
                <div class="route-duration"><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?> · <?php echo $f['seats_available']; ?> seats left</div>
            </div>
            <div class="col-md-2 text-center">
                <div class="flight-price"><?php echo formatPrice($f['price']); ?></div>
                <small class="text-muted">per person</small>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?php echo BASE_URL; ?>flight-details.php?id=<?php echo $f['flight_id']; ?>&date=<?php echo $today_date; ?>" class="btn btn-accent w-100">View Details</a>
            </div>
        </div>
    </div>
    <?php endwhile; mysqli_stmt_close($stmt); else: mysqli_stmt_close($stmt); ?>
    <div class="empty-state">
        <i class="bi bi-airplane"></i>
        <h4>No flights available</h4>
        <p>Please check back later for new flights.</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>

