<?php
$pageTitle = 'Airline Analytics';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../../includes/helpers.php';

$revenueByMonth = getRevenueByMonth(12);
$revenueByRoute = getRevenueByRoute();
$revenueByAirline = getRevenueByAirline();
$occupancy = getOccupancyAnalysis();
$topCustomers = getTopCustomers(10);
$bookingTrends = getBookingTrends();
$cancelTrends = getCancellationTrends();
$repeatCustomers = getRepeatCustomers();

$totalRev = array_sum(array_column($revenueByMonth, 'revenue'));
$totalBk = array_sum(array_column($revenueByMonth, 'bookings'));
$bestRoute = $revenueByRoute[0] ?? null;
$bestAirline = $revenueByAirline[0] ?? null;
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Airline Analytics Dashboard</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-primary"><?php echo formatPrice($totalRev); ?></span><span class="stat-label">Total Revenue</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-success"><?php echo $totalBk; ?></span><span class="stat-label">Total Bookings</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-info"><?php echo $bestRoute ? htmlspecialchars($bestRoute['source'] . '→' . $bestRoute['destination']) : 'N/A'; ?></span><span class="stat-label">Top Route</span></div></div>
    <div class="col-md-3 col-6"><div class="stat-card-sm"><span class="stat-value text-accent"><?php echo $bestAirline ? htmlspecialchars($bestAirline['airline_name']) : 'N/A'; ?></span><span class="stat-label">Top Airline</span></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Revenue by Month</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Month</th><th class="text-end">Bookings</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody><?php foreach ($revenueByMonth as $m): ?><tr>
                        <td><?php echo date('M Y', strtotime($m['month'] . '-01')); ?></td>
                        <td class="text-end"><?php echo $m['bookings']; ?></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($m['revenue']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Revenue by Route</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Route</th><th class="text-end">Bookings</th><th class="text-end">Avg Fare</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody><?php foreach ($revenueByRoute as $rt): ?><tr>
                        <td><?php echo htmlspecialchars($rt['source'] . ' → ' . $rt['destination']); ?></td>
                        <td class="text-end"><?php echo $rt['bookings']; ?></td>
                        <td class="text-end"><?php echo formatPrice($rt['avg_price']); ?></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($rt['revenue']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Revenue by Airline</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Airline</th><th class="text-end">Bookings</th><th class="text-end">Avg Fare</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody><?php foreach ($revenueByAirline as $al): ?><tr>
                        <td><?php echo htmlspecialchars($al['airline_name']); ?></td>
                        <td class="text-end"><?php echo $al['bookings']; ?></td>
                        <td class="text-end"><?php echo formatPrice($al['avg_price']); ?></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($al['revenue']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Occupancy Analysis</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Flight</th><th>Route</th><th class="text-end">Occupancy</th><th>Bar</th></tr></thead>
                    <tbody><?php foreach (array_slice($occupancy, 0, 15) as $o): $pct = $o['occupancy_pct']; ?><tr>
                        <td><small><?php echo htmlspecialchars($o['flight_number']); ?></small></td>
                        <td><small><?php echo htmlspecialchars($o['source'] . '→' . $o['destination']); ?></small></td>
                        <td class="text-end fw-bold <?php echo $pct >= 80 ? 'text-success' : ($pct >= 50 ? 'text-warning' : 'text-danger'); ?>"><?php echo $pct; ?>%</td>
                        <td style="min-width:80px;"><div class="progress" style="height:6px;">
                            <div class="progress-bar <?php echo $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger'); ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Top Customers</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Customer</th><th class="text-end">Bookings</th><th class="text-end">Total Spent</th><th>Last Booking</th></tr></thead>
                    <tbody><?php foreach ($topCustomers as $c): ?><tr>
                        <td><small><?php echo htmlspecialchars($c['name']); ?></small></td>
                        <td class="text-end"><?php echo $c['bookings']; ?></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($c['total_spent']); ?></td>
                        <td><small class="text-muted"><?php echo formatDate($c['last_booking']); ?></small></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Cancellation Trends (6 months)</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Month</th><th class="text-end">Total</th><th class="text-end">Cancelled</th><th class="text-end">Rate</th></tr></thead>
                    <tbody><?php foreach ($cancelTrends as $ct): ?><tr>
                        <td><?php echo date('M Y', strtotime($ct['month'] . '-01')); ?></td>
                        <td class="text-end"><?php echo $ct['total']; ?></td>
                        <td class="text-end text-danger"><?php echo $ct['cancelled']; ?></td>
                        <td class="text-end fw-bold <?php echo $ct['rate'] > 20 ? 'text-danger' : 'text-success'; ?>"><?php echo $ct['rate']; ?>%</td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Bookings per Day (Last 30 Days)</h6></div>
            <div class="card-body p-3">
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Date</th><th class="text-end">Bookings</th><th>Bar</th></tr></thead>
                    <tbody><?php 
                    $maxBk = !empty($bookingTrends) ? max(array_column($bookingTrends, 'count')) : 1;
                    foreach ($bookingTrends as $bt): 
                    ?><tr>
                        <td><small><?php echo formatDate($bt['date']); ?></small></td>
                        <td class="text-end fw-bold"><?php echo $bt['count']; ?></td>
                        <td style="min-width:80px;"><div class="progress" style="height:6px;"><div class="progress-bar bg-accent" style="width:<?php echo $bt['count']/$maxBk*100; ?>%"></div></div></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom"><h6 class="mb-0 fw-bold">Repeat Customers</h6></div>
            <div class="card-body p-3">
                <?php if (!empty($repeatCustomers)): ?>
                <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Customer</th><th>Email</th><th class="text-end">Bookings</th><th class="text-end">Total Spent</th></tr></thead>
                    <tbody><?php foreach ($repeatCustomers as $rc): ?><tr>
                        <td><?php echo htmlspecialchars($rc['name']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($rc['email']); ?></small></td>
                        <td class="text-end"><?php echo $rc['booking_count']; ?>x</td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($rc['total_spent']); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
                <?php else: ?><p class="text-muted mb-0 small">No repeat customers yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
