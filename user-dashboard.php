<?php
$pageTitle = 'Travel Hub';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Auth.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];

// ─── Handle POST/GET actions BEFORE any output ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect('user-dashboard.php');
    }
    if (isset($_POST['save_route'])) {
        $src = trim($_POST['source']); $dst = trim($_POST['destination']);
        if ($src && $dst && $src !== $dst) { saveRoute($user_id, $src, $dst); setFlash('success', 'Route saved!'); }
        else { setFlash('error', 'Invalid route.'); }
        redirect('user-dashboard.php');
    }
    if (isset($_POST['add_price_watch'])) {
        $src = trim($_POST['pw_source']); $dst = trim($_POST['pw_destination']);
        $maxFare = floatval($_POST['max_fare'] ?? 0);
        $prefMonth = trim($_POST['preferred_month'] ?? '');
        if ($src && $dst && $src !== $dst) { addPriceWatch($user_id, $src, $dst, $maxFare > 0 ? $maxFare : null, $prefMonth ?: null); setFlash('success', 'Price watch added!'); }
        else { setFlash('error', 'Invalid route.'); }
        redirect('user-dashboard.php');
    }
}
if (isset($_GET['delete_route'])) { deleteSavedRoute(intval($_GET['delete_route']), $user_id); setFlash('success', 'Route removed.'); redirect('user-dashboard.php'); }
if (isset($_GET['delete_watch'])) { deletePriceWatch(intval($_GET['delete_watch']), $user_id); setFlash('success', 'Price watch removed.'); redirect('user-dashboard.php'); }

$stmt = mysqli_prepare($conn, "SELECT name, email, phone, avatar, email_verified_at, last_login_at, last_login_ip FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total_bookings = countUserBookings($user_id);
$active_bookings = countUserBookings($user_id, 'Confirmed');
$cancelled_bookings = countUserBookings($user_id, 'Cancelled');
$travelStats = getComprehensiveTravelStats($user_id);
$milestones = getTravelMilestones($user_id, $travelStats);
$recent = getUserBookings($user_id, 5);
$savedRoutes = getSavedRoutes($user_id);
$priceWatches = getPriceWatches($user_id);
$loginHistory = getUserLoginHistory($user_id, 3);
$upcomingTrip = $travelStats['upcoming_trip'] ?? null;

$completion = 0;
if (!empty($user['name'])) $completion += 20;
if (!empty($user['phone'])) $completion += 20;
if ($user['email_verified_at'] !== null) $completion += 20;
if (!empty($user['avatar'])) $completion += 20;
if ($total_bookings > 0) $completion += 20;
?>

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-start">
                <h1 class="mb-1"><i class="bi bi-compass me-2 text-accent"></i>Your Travel Hub</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?> · <?php echo formatDate(date('Y-m-d')); ?></p>
                <?php if ($user['last_login_at']): ?>
                <small class="text-muted">Last login: <?php echo formatDateTime($user['last_login_at']); ?></small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="profile.php" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill"><i class="bi bi-person-gear me-1"></i>Profile</a>
                <a href="travel-documents.php" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill"><i class="bi bi-folder me-1"></i>Documents</a>
                <a href="travel-calendar.php" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill"><i class="bi bi-calendar3 me-1"></i>Calendar</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <?php showAlert(); ?>

    <?php if ($completion < 100): ?>
    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 py-2" role="alert">
        <div class="small"><i class="bi bi-info-circle-fill me-2"></i>Complete your profile! You're <strong><?php echo $completion; ?>%</strong> done.</div>
        <a href="profile.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-right me-1"></i>Complete</a>
    </div>
    <?php endif; ?>

    <!-- Upcoming Journey -->
    <?php if ($upcomingTrip): $trip = $upcomingTrip; ?>
    <div class="upcoming-trip-card p-4 mb-4 rounded-4 position-relative overflow-hidden">
        <div class="row align-items-center position-relative z-1">
            <div class="col-md-8">
                <small class="text-uppercase fw-bold text-white-50 tracking-wider mb-2 d-block"><i class="bi bi-calendar-event me-1"></i> Next Trip</small>
                <h3 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($trip['source']); ?> → <?php echo htmlspecialchars($trip['destination']); ?></h3>
                <p class="text-white-50 mb-0"><?php echo htmlspecialchars($trip['airline_name'] . ' ' . $trip['flight_number']); ?> · <?php echo formatDate($trip['travel_date']); ?></p>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <span class="badge bg-white text-dark bg-opacity-25">Depart: <?php echo formatTime($trip['departure_time']); ?></span>
                    <span class="badge bg-white text-dark bg-opacity-25">Arrive: <?php echo formatTime($trip['arrival_time']); ?></span>
                </div>
                <!-- Trip Timeline (7-stage journey lifecycle) -->
                <?php
                $hoursUntilDeparture = max(0, (strtotime($trip['departure_time']) - time()) / 3600);
                $stages = ['Confirmed', 'Payment', 'Check-in', 'Boarding', 'Departed', 'Landed', 'Completed'];
                $stageClasses = [
                    $hoursUntilDeparture >= 48 ? 'bg-success' : 'bg-secondary',
                    $hoursUntilDeparture >= 48 ? 'bg-success' : 'bg-secondary',
                    $hoursUntilDeparture >= 24 && $hoursUntilDeparture < 48 ? 'bg-success' : ($hoursUntilDeparture < 24 ? 'bg-primary' : 'bg-secondary'),
                    $hoursUntilDeparture >= 2 && $hoursUntilDeparture < 24 ? 'bg-warning' : 'bg-secondary',
                    $hoursUntilDeparture >= 0 && $hoursUntilDeparture < 2 ? 'bg-warning' : 'bg-secondary',
                    $hoursUntilDeparture <= 0 && $hoursUntilDeparture > -24 ? 'bg-warning' : ($hoursUntilDeparture <= -24 ? 'bg-success' : 'bg-secondary'),
                    $hoursUntilDeparture <= -24 ? 'bg-success' : ($hoursUntilDeparture <= 0 && $hoursUntilDeparture > -24 ? 'bg-primary' : 'bg-secondary'),
                ];
                $stageIcons = ['bi-check-circle-fill', 'bi-credit-card-2-front', 'bi-door-open', 'bi-airplane', 'bi-airplane-fill', 'bi-geo-alt-fill', 'bi-check-all'];
                ?>
                <div class="mt-3 d-flex align-items-center gap-1 flex-wrap">
                    <?php foreach ($stages as $idx => $stage): ?>
                    <div class="d-flex align-items-center gap-1">
                        <i class="bi <?php echo $stageIcons[$idx]; ?> text-white-50" style="font-size:0.65rem;"></i>
                        <?php
                        $isActive = strpos($stageClasses[$idx], 'bg-success') !== false
                                 || strpos($stageClasses[$idx], 'bg-primary') !== false
                                 || strpos($stageClasses[$idx], 'bg-warning') !== false;
                        ?>
                        <small class="text-white-50 <?php echo $isActive ? 'fw-bold' : ''; ?>"><?php echo $stage; ?></small>
                    </div>
                    <?php if ($idx < count($stages) - 1): ?><span class="text-white-50 small" style="opacity:0.4;">—</span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="booking-confirmation.php?ref=<?php echo $trip['booking_ref']; ?>" class="btn btn-light btn-lg fw-bold px-3"><i class="bi bi-ticket-perforated me-2"></i>View E-Ticket</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white p-3 rounded-4 shadow-sm mb-4 border d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><h5 class="mb-0"><i class="bi bi-compass text-accent me-2"></i>No upcoming trips</h5><small class="text-muted">Ready for your next adventure?</small></div>
        <a href="search-flights.php" class="btn btn-accent btn-sm px-3"><i class="bi bi-search me-1"></i>Search Flights</a>
    </div>
    <?php endif; ?>

    <!-- Travel Statistics -->
    <div class="mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-graph-up me-2 text-accent"></i>Travel Statistics</h5>
        <div class="row g-2">
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value"><?php echo $travelStats['total_trips']; ?></span><span class="stat-label">Trips</span></div></div>
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value text-success"><?php echo $travelStats['completed']; ?></span><span class="stat-label">Completed</span></div></div>
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value text-info"><?php echo $travelStats['upcoming']; ?></span><span class="stat-label">Upcoming</span></div></div>
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value text-accent"><?php echo count($travelStats['unique_cities']); ?></span><span class="stat-label">Cities</span></div></div>
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value text-warning"><?php echo formatPrice($travelStats['total_spent']); ?></span><span class="stat-label">Spent</span></div></div>
            <div class="col-md-2 col-4"><div class="stat-card-sm"><span class="stat-value text-primary"><?php echo $travelStats['total_distance_km'] > 0 ? number_format($travelStats['total_distance_km']) . 'km' : '—'; ?></span><span class="stat-label">Distance</span></div></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Recent Trips -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-accent"></i>Recent Trips</h5>
                <a href="my-bookings.php" class="btn btn-sm btn-outline-accent">View All</a>
            </div>
            <?php if ($recent && mysqli_num_rows($recent) > 0): while($b = mysqli_fetch_assoc($recent)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-2">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-md-5 col-6">
                            <small class="fw-bold"><?php echo htmlspecialchars($b['airline_name']); ?> (<?php echo htmlspecialchars($b['flight_number']); ?>)</small><br>
                            <small class="text-muted"><?php echo htmlspecialchars($b['source']); ?> → <?php echo htmlspecialchars($b['destination']); ?></small>
                        </div>
                        <div class="col-md-3 col-6"><small class="text-muted"><i class="bi bi-calendar me-1"></i><?php echo formatDate($b['travel_date']); ?></small></div>
                        <div class="col-md-2 col-6 text-center"><?php echo statusBadge($b['booking_status']); ?></div>
                        <div class="col-md-2 col-6 text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="booking-confirmation.php?ref=<?php echo $b['booking_ref']; ?>" class="btn btn-sm btn-outline-accent"><i class="bi bi-eye"></i></a>
                                <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-ticket-perforated"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="bg-white p-4 text-center rounded-4 shadow-sm border mb-3">
                <i class="bi bi-ticket-perforated text-muted display-6 mb-2 d-block"></i>
                <p class="text-muted mb-0 small">No trips yet. <a href="search-flights.php" class="text-accent fw-bold">Book your first flight</a></p>
            </div>
            <?php endif; ?>

            <!-- Saved Routes -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-bookmark-star me-2 text-accent"></i>Saved Routes</h5>
                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal" data-bs-target="#saveRouteModal"><i class="bi bi-plus me-1"></i>Add Route</button>
            </div>
            <?php if (!empty($savedRoutes)): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($savedRoutes as $sr): ?>
                <div class="d-flex align-items-center gap-1 bg-white border rounded-3 px-2 py-1 shadow-sm">
                    <a href="<?php echo BASE_URL; ?>fare-results.php?source=<?php echo urlencode($sr['source']); ?>&destination=<?php echo urlencode($sr['destination']); ?>" class="fw-semibold small text-dark text-decoration-none"><?php echo htmlspecialchars($sr['source']); ?> → <?php echo htmlspecialchars($sr['destination']); ?></a>
                    <a href="?delete_route=<?php echo $sr['id']; ?>" class="text-danger small ms-1" onclick="return confirm('Remove?')"><i class="bi bi-x"></i></a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Save your frequent routes for one-click searching.</p>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Price Watch -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-1 text-accent"></i>Price Watch</h6>
                        <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal" data-bs-target="#priceWatchModal"><i class="bi bi-plus"></i></button>
                    </div>
                    <?php if (!empty($priceWatches)): foreach ($priceWatches as $pw):
                        $cheapest = getCheapestFareForRoute($pw['source'], $pw['destination']);
                        $underBudget = $cheapest !== null && $pw['max_fare'] > 0 && $cheapest <= $pw['max_fare'];
                    ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div>
                            <span><?php echo htmlspecialchars($pw['source']); ?> → <?php echo htmlspecialchars($pw['destination']); ?></span>
                            <?php if (!empty($pw['preferred_month'])): ?>
                                <br><small class="text-muted" style="font-size:0.6rem;"><?php echo date('M Y', strtotime($pw['preferred_month'] . '-01')); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($cheapest): ?><span class="fw-bold text-accent"><?php echo formatPrice($cheapest); ?></span><?php endif; ?>
                            <?php if ($underBudget): ?><span class="badge bg-success text-white" style="font-size:0.55rem;">✓</span><?php endif; ?>
                            <a href="?delete_watch=<?php echo $pw['id']; ?>" class="text-danger" onclick="return confirm('Remove?')"><i class="bi bi-x" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="text-muted small mb-0">Track price changes on your favorite routes.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Milestones -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-award me-1 text-accent"></i>Milestones</h6>
                    <?php if (!empty($milestones)): foreach ($milestones as $m): ?>
                    <div class="d-flex align-items-center gap-2 small py-1 border-bottom">
                        <i class="bi <?php echo $m['icon']; ?> <?php echo $m['class']; ?>"></i>
                        <span class="fw-semibold"><?php echo $m['label']; ?></span>
                        <?php if (isset($m['date'])): ?><small class="text-muted ms-auto"><?php echo $m['date']; ?></small><?php endif; ?>
                        <?php if (isset($m['count'])): ?><small class="text-muted ms-auto"><?php echo $m['count']; ?></small><?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="text-muted small mb-0">Book your first flight to earn milestones.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <?php if (!empty($loginHistory)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shield-check me-1 text-accent"></i>Recent Activity</h6>
                    <?php foreach ($loginHistory as $h): ?>
                    <div class="d-flex align-items-center justify-content-between py-1 border-bottom small">
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($h['success']): ?><i class="bi bi-check-circle-fill text-success" style="font-size:0.7rem;"></i>
                            <?php else: ?><i class="bi bi-x-circle-fill text-danger" style="font-size:0.7rem;"></i><?php endif; ?>
                            <span><?php echo $h['success'] ? 'Login' : 'Failed attempt'; ?></span>
                            <small class="text-muted"><?php echo htmlspecialchars(getDeviceName($h['user_agent'] ?? '')); ?></small>
                        </div>
                        <small class="text-muted"><?php echo timeSince($h['login_at']); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-lightning-charge me-1 text-accent"></i>Quick Actions</h6>
                    <div class="d-flex flex-column gap-1 small">
                        <a href="search-flights.php" class="text-decoration-none py-1"><i class="bi bi-search me-2 text-accent"></i>Search Flights</a>
                        <a href="my-bookings.php" class="text-decoration-none py-1"><i class="bi bi-journal-bookmark me-2 text-accent"></i>My Bookings</a>
                        <a href="travel-documents.php" class="text-decoration-none py-1"><i class="bi bi-folder me-2 text-accent"></i>Travel Documents</a>
                        <a href="travel-calendar.php" class="text-decoration-none py-1"><i class="bi bi-calendar3 me-2 text-accent"></i>Travel Calendar</a>
                        <a href="profile.php?tab=security" class="text-decoration-none py-1"><i class="bi bi-shield-lock me-2 text-accent"></i>Security Settings</a>
                    </div>
                </div>
            </div>

            <!-- Travel Summary -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-bar-chart me-1 text-accent"></i>Travel Summary</h6>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Favorite Airline</span><span class="fw-bold"><?php echo $travelStats['favorite_airline']; ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Favorite Route</span><span class="fw-bold small"><?php echo str_replace('-', ' → ', $travelStats['favorite_route']); ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Average Fare</span><span class="fw-bold"><?php echo formatPrice($travelStats['avg_ticket_price']); ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Avg Duration</span><span class="fw-bold"><?php echo floor($travelStats['avg_trip_duration'] / 60) . 'h ' . ($travelStats['avg_trip_duration'] % 60) . 'm'; ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Best Month</span><span class="fw-bold"><?php echo date('M Y', strtotime($travelStats['most_active_month'] . '-01')); ?></span></div>
                    <div class="d-flex justify-content-between py-1 small"><span class="text-muted">Total Spent</span><span class="fw-bold text-accent"><?php echo formatPrice($travelStats['total_spent']); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Route Modal -->
<div class="modal fade" id="saveRouteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 pb-0"><h5 class="fw-bold">Save a Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><?php csrfField(); ?>
                    <div class="mb-2"><label class="form-label small fw-bold">Origin</label><select name="source" class="form-select" required><?php cityOptions(); ?></select></div>
                    <div class="mb-2"><label class="form-label small fw-bold">Destination</label><select name="destination" class="form-select" required><?php cityOptions(); ?></select></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="save_route" class="btn btn-accent w-100 py-2 fw-bold">Save Route</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Price Watch Modal -->
<div class="modal fade" id="priceWatchModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 pb-0"><h5 class="fw-bold">Watch a Route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><?php csrfField(); ?>
                    <div class="mb-2"><label class="form-label small fw-bold">Origin</label><select name="pw_source" class="form-select" required><?php cityOptions(); ?></select></div>
                    <div class="mb-2"><label class="form-label small fw-bold">Destination</label><select name="pw_destination" class="form-select" required><?php cityOptions(); ?></select></div>
                    <div class="mb-2"><label class="form-label small fw-bold">Max Fare (₹)</label><input type="number" name="max_fare" class="form-control" placeholder="e.g. 4000" step="100"></div>
                    <div class="mb-0"><label class="form-label small fw-bold">Preferred Month</label><input type="month" name="preferred_month" class="form-control" value="<?php echo date('Y-m'); ?>"></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="add_price_watch" class="btn btn-accent w-100 py-2 fw-bold">Start Watching</button></div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-outline-accent { color: var(--accent); border-color: var(--accent); }
.btn-outline-accent:hover { background: var(--accent); color: #fff; }
.stat-card-sm { background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.5rem; text-align: center; }
.stat-value { display: block; font-size: 1.1rem; font-weight: 800; font-family: var(--font-heading); }
.stat-label { font-size: 0.65rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px; }
</style>

<?php require_once 'includes/footer.php'; ?>
