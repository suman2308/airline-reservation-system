<?php
$pageTitle = 'Route Analytics';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';

$routeData = getRouteAnalytics();
$occupancy = getOccupancyAnalysis();
$revenueByRoute = getRevenueByRoute();
$routes = $routeData['routes'];
$inactive = $routeData['inactive'];

// Flights per route
global $conn;
$r = mysqli_query($conn, "SELECT source, destination, COUNT(*) as flight_count FROM flights WHERE status IN ('Scheduled','Delayed') GROUP BY source, destination ORDER BY flight_count DESC");
$flightCounts = [];
while ($row = mysqli_fetch_assoc($r)) $flightCounts[$row['source'] . '-' . $row['destination']] = $row['flight_count'];
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-signpost-2 me-2 text-primary"></i>Route Management</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-primary"><?php echo count($routes); ?></span><span class="stat-label">Active Routes</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-danger"><?php echo count($inactive); ?></span><span class="stat-label">Inactive Routes</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-success"><?php echo $routes ? array_sum(array_column($routes, 'total_bookings')) : 0; ?></span><span class="stat-label">Total Bookings</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-accent"><?php echo $routes ? formatPrice(array_sum(array_column($routes, 'total_revenue'))) : '₹0'; ?></span><span class="stat-label">Total Revenue</span></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Route Performance</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Route</th><th class="text-end">Flights</th><th class="text-end">Bookings</th><th class="text-end">Revenue</th><th class="text-end">Avg Fare</th><th class="text-end">Avg Occ</th>
                    </tr></thead>
                    <tbody><?php foreach ($routes as $rt): 
                        $key = $rt['source'] . '-' . $rt['destination'];
                        $fc = $flightCounts[$key] ?? 0;
                    ?><tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($rt['source'] . ' → ' . $rt['destination']); ?></td>
                        <td class="text-end"><?php echo $fc; ?></td>
                        <td class="text-end"><?php echo $rt['total_bookings']; ?></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($rt['total_revenue']); ?></td>
                        <td class="text-end"><?php echo formatPrice($rt['avg_fare']); ?></td>
                        <td class="text-end"><span class="badge <?php echo $rt['avg_occupancy'] >= 70 ? 'bg-success' : ($rt['avg_occupancy'] >= 40 ? 'bg-warning' : 'bg-danger'); ?>"><?php echo $rt['avg_occupancy']; ?>%</span></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Inactive Routes</h6></div>
            <div class="card-body p-0">
                <?php if (!empty($inactive)): ?>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Route</th><th class="text-end">Flights</th></tr></thead>
                    <tbody><?php foreach ($inactive as $ia): 
                        $key = $ia['source'] . '-' . $ia['destination'];
                        $fc = $flightCounts[$key] ?? 0;
                    ?><tr>
                        <td><?php echo htmlspecialchars($ia['source'] . ' → ' . $ia['destination']); ?></td>
                        <td class="text-end"><?php echo $fc; ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
                <?php else: ?><p class="text-muted p-3 mb-0 small">All routes have at least one booking.</p><?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Occupancy Distribution</h6></div>
            <div class="card-body p-3">
                <?php
                $high = count(array_filter($occupancy, fn($o) => $o['occupancy_pct'] >= 80));
                $mid = count(array_filter($occupancy, fn($o) => $o['occupancy_pct'] >= 50 && $o['occupancy_pct'] < 80));
                $low = count(array_filter($occupancy, fn($o) => $o['occupancy_pct'] < 50 && $o['occupancy_pct'] > 0));
                $empty = count(array_filter($occupancy, fn($o) => $o['occupancy_pct'] == 0));
                ?>
                <div class="d-flex justify-content-between small mb-2"><span class="text-success fw-bold">High (≥80%)</span><span><?php echo $high; ?> flights</span></div>
                <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-success" style="width:<?php echo count($occupancy) > 0 ? $high/count($occupancy)*100 : 0; ?>%"></div></div>
                <div class="d-flex justify-content-between small mb-2"><span class="text-warning fw-bold">Medium (50-79%)</span><span><?php echo $mid; ?> flights</span></div>
                <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-warning" style="width:<?php echo count($occupancy) > 0 ? $mid/count($occupancy)*100 : 0; ?>%"></div></div>
                <div class="d-flex justify-content-between small mb-2"><span class="text-danger fw-bold">Low (<50%)</span><span><?php echo $low; ?> flights</span></div>
                <div class="progress mb-3" style="height:8px"><div class="progress-bar bg-danger" style="width:<?php echo count($occupancy) > 0 ? $low/count($occupancy)*100 : 0; ?>%"></div></div>
                <div class="d-flex justify-content-between small"><span class="text-secondary fw-bold">Empty (0%)</span><span><?php echo $empty; ?> flights</span></div>
                <div class="progress" style="height:8px"><div class="progress-bar bg-secondary" style="width:<?php echo count($occupancy) > 0 ? $empty/count($occupancy)*100 : 0; ?>%"></div></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
