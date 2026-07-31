<?php
$pageTitle = 'Live Flight Status';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

$flight_query = trim($_GET['flight_no'] ?? '');
$route_source = trim($_GET['source'] ?? '');
$route_dest = trim($_GET['destination'] ?? '');

$flight_status_data = null;
$searched = false;

if (!empty($flight_query) || (!empty($route_source) && !empty($route_dest))) {
    $searched = true;

    if (!empty($flight_query)) {
        $param = '%' . $flight_query . '%';
        $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_number LIKE ? OR airline_name LIKE ? ORDER BY departure_time ASC LIMIT 5");
        mysqli_stmt_bind_param($stmt, "ss", $param, $param);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE source=? AND destination=? ORDER BY departure_time ASC LIMIT 5");
        mysqli_stmt_bind_param($stmt, "ss", $route_source, $route_dest);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $flight_status_data = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

$terminals = ['T1 (Gate A12)', 'T2 (Gate B08)', 'T3 (Gate C22)'];
$belts = ['Baggage Belt 04', 'Baggage Belt 07', 'Baggage Belt 02'];
?>

<div class="page-header text-center">
    <div class="container">
        <span class="badge bg-primary-subtle text-accent mb-2 px-3 py-2 border border-accent rounded-pill">
            <i class="bi bi-broadcast me-1 text-danger spinner-grow spinner-grow-sm" style="width: 8px; height: 8px;"></i> Live Flight Operations Radar
        </span>
        <h1 class="fw-bold"><i class="bi bi-radar me-2 text-accent"></i>Flight Status Tracker</h1>
        <p class="text-muted">Track real-time flight schedules, terminal gates, baggage belts, and flight updates</p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <!-- Search Form Bar -->
    <div class="card border-0 shadow-lg rounded-4 p-4 mb-5" style="background: var(--surface-card);">
        <ul class="nav nav-pills mb-4" id="statusTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="by-flight-tab" data-bs-toggle="pill" data-bs-target="#by-flight" type="button"><i class="bi bi-airplane-engines me-2"></i>Search by Flight Number</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="by-route-tab" data-bs-toggle="pill" data-bs-target="#by-route" type="button"><i class="bi bi-geo-alt me-2"></i>Search by Route</button>
            </li>
        </ul>

        <div class="tab-content" id="statusTabContent">
            <div class="tab-pane fade show active" id="by-flight" role="tabpanel">
                <form action="flight-status.php" method="GET" class="row g-3 align-items-center needs-loading">
                    <div class="col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="flight_no" class="form-control border-start-0 ps-0" placeholder="e.g. AI-204, 6E-512, UK-810 or IndiGo" value="<?php echo htmlspecialchars($flight_query); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-accent btn-lg w-100 fw-bold"><i class="bi bi-crosshair me-2"></i>Track Flight</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="by-route" role="tabpanel">
                <form action="flight-status.php" method="GET" class="row g-3 align-items-center needs-loading">
                    <div class="col-md-4">
                        <select name="source" class="form-select form-select-lg" required>
                            <option value="">Origin Airport</option>
                            <?php cityOptions($route_source); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="destination" class="form-select form-select-lg" required>
                            <option value="">Destination Airport</option>
                            <?php cityOptions($route_dest); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-accent btn-lg w-100 fw-bold"><i class="bi bi-geo-fill me-2"></i>Find Route Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($searched): ?>
        <?php if (!empty($flight_status_data)): ?>
            <h4 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-accent"></i> Real-time Flight Status Results (<?php echo count($flight_status_data); ?>)
            </h4>

            <?php foreach ($flight_status_data as $flight):
                $random_t = $terminals[$flight['flight_id'] % 3];
                $random_b = $belts[$flight['flight_id'] % 3];
                $st = $flight['status'];
                $status_class = ($st === 'Delayed') ? 'bg-warning text-dark' : (($st === 'Cancelled') ? 'bg-danger' : 'bg-success');
            ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="airline-logo bg-white text-primary rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.1rem;">
                            <?php echo airlineInitials($flight['airline_name']); ?>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($flight['airline_name']); ?> (<?php echo htmlspecialchars($flight['flight_number']); ?>)</h5>
                            <small class="text-white-50">Airbus A320neo · Live Tracking Active</small>
                        </div>
                    </div>
                    <span class="badge <?php echo $status_class; ?> px-3 py-2 fs-6 rounded-pill fw-bold">
                        <i class="bi bi-check-circle me-1"></i><?php echo strtoupper($st); ?>
                    </span>
                </div>

                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                            <span class="badge bg-light text-muted border px-2 py-1 mb-1">Departure</span>
                            <h2 class="fw-extrabold mb-0"><?php echo formatTime($flight['departure_time']); ?></h2>
                            <h5 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($flight['source']); ?></h5>
                            <small class="text-muted"><i class="bi bi-building me-1"></i><?php echo $random_t; ?></small>
                        </div>

                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <small class="text-muted fw-bold d-block mb-1"><?php echo calcDuration($flight['departure_time'], $flight['arrival_time']); ?> (Non-stop)</small>
                            <div class="position-relative d-flex align-items-center justify-content-center">
                                <div style="width: 100%; height: 3px; background: linear-gradient(90deg, var(--accent), #10b981);"></div>
                                <i class="bi bi-airplane-fill position-absolute text-accent fs-4" style="background: white; padding: 0 8px;"></i>
                            </div>
                            <small class="text-success fw-semibold mt-1 d-block"><i class="bi bi-shield-check me-1"></i>Cruising Altitude 35,000 ft</small>
                        </div>

                        <div class="col-md-4 text-center text-md-end">
                            <span class="badge bg-light text-muted border px-2 py-1 mb-1">Arrival</span>
                            <h2 class="fw-extrabold mb-0"><?php echo formatTime($flight['arrival_time']); ?></h2>
                            <h5 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($flight['destination']); ?></h5>
                            <small class="text-muted"><i class="bi bi-bag-check me-1"></i><?php echo $random_b; ?></small>
                        </div>
                    </div>

                    <div class="row g-3 pt-3 border-top bg-light rounded-3 p-3 text-center">
                        <div class="col-6 col-md-3 border-end">
                            <small class="text-muted d-block uppercase-label">Available Seats</small>
                            <span class="fw-bold text-accent fs-6"><?php echo $flight['seats_available']; ?> / <?php echo $flight['total_seats']; ?></span>
                        </div>
                        <div class="col-6 col-md-3 border-end">
                            <small class="text-muted d-block uppercase-label">Weather at Destination</small>
                            <span class="fw-bold text-dark fs-6"><i class="bi bi-sun text-warning me-1"></i>28°C Clear</span>
                        </div>
                        <div class="col-6 col-md-3 border-end">
                            <small class="text-muted d-block uppercase-label">On-Time Performance</small>
                            <span class="fw-bold text-success fs-6">98.4% Exceptional</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block uppercase-label">Action</small>
                            <a href="flight-details.php?id=<?php echo $flight['flight_id']; ?>" class="btn btn-sm btn-accent fw-bold px-3">Book Seat</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-airplane text-muted display-1"></i>
                <h4 class="mt-3 text-secondary">No flight status records found</h4>
                <p class="text-muted">Please double check the flight number or route cities and try searching again.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h4 class="fw-bold mb-4"><i class="bi bi-lightning-charge me-2 text-accent"></i>Live Operations Overview Today</h4>
        <div class="row g-4">
            <?php
            $sample_flights = [
                ['IndiGo', '6E-502', 'Delhi (DEL)', 'Mumbai (BOM)', '08:30 AM', '10:45 AM', 'ON TIME', 'bg-success-subtle text-success border-success', 'Terminal 3 (Gate A4)', 'Belt B-02'],
                ['Air India', 'AI-204', 'Mumbai (BOM)', 'Bangalore (BLR)', '11:15 AM', '01:00 PM', 'BOARDING', 'bg-success-subtle text-success border-success', 'Terminal 2 (Gate B12)', 'Belt B-05'],
                ['Vistara', 'UK-810', 'Kolkata (CCU)', 'Delhi (DEL)', '02:00 PM', '04:25 PM', 'IN AIR', 'bg-info-subtle text-info border-info', 'Terminal 1 (Gate C01)', 'Belt B-01'],
            ];
            foreach ($sample_flights as $sf): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 hover-lift">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary text-white"><?php echo $sf[0]; ?> · <?php echo $sf[1]; ?></span>
                        <span class="badge <?php echo $sf[5]; ?> fw-bold"><?php echo $sf[4]; ?></span>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo $sf[2]; ?> → <?php echo $sf[3]; ?></h5>
                    <p class="text-muted small mb-3"><i class="bi bi-clock me-1"></i>Dep: <?php echo $sf[6]; ?> | Arr: <?php echo $sf[7]; ?></p>
                    <div class="d-flex justify-content-between align-items-center small border-top pt-2 text-muted">
                        <span><?php echo $sf[8]; ?></span>
                        <span><?php echo $sf[9]; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
