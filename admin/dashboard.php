<?php
$pageTitle = 'Operations Center';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';

$adminId = $_SESSION['admin_id'];
$metrics = getTodayOpsMetrics();
$occupancy = getOccupancyAnalysis();
$dataIssues = getDataQualityIssues();

global $conn;
$boardings = mysqli_query($conn, "SELECT * FROM flights WHERE DATE(departure_time) = CURDATE() AND TIME(departure_time) BETWEEN TIME(NOW()) AND TIME(DATE_ADD(NOW(), INTERVAL 2 HOUR)) ORDER BY departure_time ASC");
$departures = mysqli_query($conn, "SELECT * FROM flights WHERE DATE(departure_time) = CURDATE() AND TIME(departure_time) > TIME(DATE_ADD(NOW(), INTERVAL 2 HOUR)) ORDER BY departure_time ASC LIMIT 5");
$recent = getRecentBookings(5);
$topCustomers = getTopCustomers(3);
$alertFlights = mysqli_query($conn, "SELECT flight_number, source, destination, ROUND((total_seats - seats_available) / total_seats * 100, 1) as occ FROM flights WHERE status IN ('Scheduled','Delayed') AND (total_seats - seats_available) / total_seats * 100 < 20 ORDER BY occ ASC LIMIT 5");
?>
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="kicker"><i class="bi bi-speedometer2 me-1"></i> Operations Center</span>
                <h4 class="fw-bold mb-0 mt-1">Today's <span class="text-muted">Operations</span></h4>
                <small class="text-muted"><?php echo date('l, d M Y h:i A'); ?> · Server: <?php echo phpversion(); ?></small>
            </div>
            <div><a href="diagnostics.php" class="btn btn-sm btn-outline-accent rounded-pill px-3"><i class="bi bi-activity me-1"></i>System Status</a></div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-2 mb-4">
    <div class="col-md-3 col-6"><div class="ops-kpi"><i class="bi bi-airplane text-primary"></i><span class="kpi-value"><?php echo $metrics['flights_today']; ?></span><span class="kpi-label">Flights Today</span><small class="text-muted"><?php echo $metrics['boarding_soon']; ?> boarding soon</small></div></div>
    <div class="col-md-3 col-6"><div class="ops-kpi"><i class="bi bi-people text-success"></i><span class="kpi-value"><?php echo $metrics['passengers_today']; ?></span><span class="kpi-label">Passengers Today</span><small class="text-muted"><?php echo $metrics['bookings_today']; ?> bookings today</small></div></div>
    <div class="col-md-3 col-6"><div class="ops-kpi"><i class="bi bi-currency-rupee text-accent"></i><span class="kpi-value"><?php echo formatPrice($metrics['revenue_today']); ?></span><span class="kpi-label">Revenue Today</span><small class="text-muted"><?php echo $metrics['total_bookings']; ?> total bookings</small></div></div>
    <div class="col-md-3 col-6"><div class="ops-kpi"><i class="bi bi-graph-up text-warning"></i><span class="kpi-value"><?php echo $metrics['overall_occupancy']; ?>%</span><span class="kpi-label">Occupancy</span><small class="text-muted"><?php echo $metrics['completed_today']; ?> completed · <?php echo $metrics['delayed']; ?> delayed · cancel <?php echo $metrics['cancellation_rate']; ?>%</small></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Boarding Soon -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock me-2 text-warning"></i>Boarding Soon <span class="badge bg-warning text-dark ms-1"><?php echo $metrics['boarding_soon']; ?></span></h6>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($boardings) > 0): ?>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Flight</th><th>Route</th><th>Time</th><th>Seats</th><th>Status</th></tr></thead>
                    <tbody><?php while($f = mysqli_fetch_assoc($boardings)): ?><tr>
                        <td><span class="fw-bold"><?php echo $f['flight_number']; ?></span><br><small><?php echo $f['airline_name']; ?></small></td>
                        <td><?php echo $f['source']; ?> → <?php echo $f['destination']; ?></td>
                        <td><strong><?php echo formatTime($f['departure_time']); ?></strong><br><small class="text-muted"><?php echo formatDate($f['departure_time']); ?></small></td>
                        <td><?php echo $f['seats_available']; ?>/<?php echo $f['total_seats']; ?></td>
                        <td><?php echo statusBadge($f['status']); ?></td>
                    </tr><?php endwhile; ?></tbody>
                </table></div>
                <?php else: ?><p class="text-muted p-3 mb-0 small">No flights boarding in the next 2 hours.</p><?php endif; ?>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-journal-check me-2 text-primary"></i>Recent Bookings</h6>
                <a href="manage-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Ref</th><th>Passenger</th><th>Flight</th><th>Route</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($recent as $b): ?><tr>
                        <td><span class="text-accent fw-bold"><?php echo $b['booking_ref']; ?></span></td>
                        <td><?php echo htmlspecialchars($b['passenger_name']); ?></td>
                        <td><?php echo $b['flight_number']; ?></td>
                        <td><?php echo $b['source']; ?> → <?php echo $b['destination']; ?></td>
                        <td><?php echo statusBadge($b['booking_status']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>

        <!-- Upcoming Departures -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="bi bi-airplane-fill me-2 text-info"></i>Upcoming Departures</h6></div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($departures) > 0): ?>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Flight</th><th>Route</th><th>Time</th><th>Seats Left</th></tr></thead>
                    <tbody><?php while($f = mysqli_fetch_assoc($departures)): ?><tr>
                        <td><span class="fw-bold"><?php echo $f['flight_number']; ?></span><br><small><?php echo $f['airline_name']; ?></small></td>
                        <td><?php echo $f['source']; ?> → <?php echo $f['destination']; ?></td>
                        <td><?php echo formatTime($f['departure_time']); ?></td>
                        <td><span class="badge <?php echo $f['seats_available'] > 50 ? 'bg-success' : ($f['seats_available'] > 20 ? 'bg-warning' : 'bg-danger'); ?>"><?php echo $f['seats_available']; ?></span></td>
                    </tr><?php endwhile; ?></tbody>
                </table></div>
                <?php else: ?><p class="text-muted p-3 mb-0 small">No more departures today.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Low Occupancy Alerts -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Low Occupancy Flights</h6></div>
            <div class="card-body p-3">
                <?php if (mysqli_num_rows($alertFlights) > 0): ?>
                <?php while($a = mysqli_fetch_assoc($alertFlights)): ?>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                    <span><?php echo $a['flight_number']; ?><br><small class="text-muted"><?php echo $a['source']; ?>→<?php echo $a['destination']; ?></small></span>
                    <span class="badge bg-danger"><?php echo $a['occ']; ?>%</span>
                </div>
                <?php endwhile; ?>
                <?php else: ?><p class="text-muted small mb-0">All flights have healthy occupancy.</p><?php endif; ?>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="bi bi-trophy me-2 text-warning"></i>Top Customers</h6></div>
            <div class="card-body p-3">
                <?php if (!empty($topCustomers)): foreach ($topCustomers as $c): ?>
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="fw-semibold"><?php echo htmlspecialchars($c['name']); ?></span>
                    <span class="text-accent"><?php echo $c['bookings']; ?>x <?php echo formatPrice($c['total_spent']); ?></span>
                </div>
                <?php endforeach; else: ?><p class="text-muted small mb-0">No bookings yet.</p><?php endif; ?>
            </div>
        </div>

        <!-- Data Quality -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-info"></i>Data Quality</h6></div>
            <div class="card-body p-3">
                <?php if (!empty($dataIssues)): foreach ($dataIssues as $issue): ?>
                <div class="d-flex align-items-start gap-2 py-1 border-bottom small">
                    <i class="bi bi-<?php echo $issue['type'] === 'critical' ? 'exclamation-octagon text-danger' : ($issue['type'] === 'error' ? 'x-circle text-danger' : 'exclamation-triangle text-warning'); ?> mt-1"></i>
                    <span><?php echo htmlspecialchars($issue['message']); ?></span>
                </div>
                <?php endforeach; else: ?><p class="text-muted small mb-0"><i class="bi bi-check-circle text-success me-1"></i>No data quality issues found.</p><?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-accent"></i>Platform Overview</h6></div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Total Users</span><span class="fw-bold"><?php echo countWhere('users'); ?></span></div>
                <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Total Flights</span><span class="fw-bold"><?php echo countWhere('flights'); ?></span></div>
                <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Confirmed Bookings</span><span class="fw-bold"><?php echo countWhere('bookings', 'booking_status', 'Confirmed'); ?></span></div>
                <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Cancelled Bookings</span><span class="fw-bold text-danger"><?php echo countWhere('bookings', 'booking_status', 'Cancelled'); ?></span></div>
                <div class="d-flex justify-content-between pt-1 small"><span class="text-muted">Contact Queries</span><span class="fw-bold"><?php echo countWhere('contacts'); ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
