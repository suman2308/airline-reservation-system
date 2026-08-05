<?php
$pageTitle = 'My Bookings';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login first.');
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Fetch counts
$totalCount = countUserBookings($user_id);
$activeCount = countUserBookings($user_id, 'Confirmed');
$cancelledCount = countUserBookings($user_id, 'Cancelled');

// Fetch filtered bookings
$bookings = null;
if ($filter === 'upcoming') {
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price
                                    FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? AND b.booking_status = 'Confirmed' AND f.departure_time >= NOW()
                                    ORDER BY f.departure_time ASC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $bookings = mysqli_stmt_get_result($stmt);
} elseif ($filter === 'completed') {
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price
                                    FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? AND b.booking_status = 'Confirmed' AND f.departure_time < NOW()
                                    ORDER BY f.departure_time DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $bookings = mysqli_stmt_get_result($stmt);
} elseif ($filter === 'cancelled') {
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price
                                    FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                    WHERE b.user_id = ? AND b.booking_status = 'Cancelled'
                                    ORDER BY b.booking_date DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $bookings = mysqli_stmt_get_result($stmt);
} else {
    $bookings = getUserBookings($user_id);
}
?>
<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Reservations</span>
        <h1>My <span class="dim">Bookings</span></h1>
        <p class="mb-0">Manage all your flight reservations in one place</p>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <span class="badge-status bg-primary"><?php echo $totalCount; ?> Total</span>
            <span class="badge-status bg-success"><?php echo $activeCount; ?> Active</span>
            <span class="badge-status bg-danger"><?php echo $cancelledCount; ?> Cancelled</span>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <!-- Search & Filters -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex gap-1 flex-wrap">
            <a href="?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-semibold">All</a>
            <a href="?filter=upcoming" class="btn btn-sm <?php echo $filter === 'upcoming' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-semibold">Upcoming</a>
            <a href="?filter=completed" class="btn btn-sm <?php echo $filter === 'completed' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-semibold">Completed</a>
            <a href="?filter=cancelled" class="btn btn-sm <?php echo $filter === 'cancelled' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-semibold">Cancelled</a>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by ref, flight, route..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
            <button type="submit" class="btn btn-accent rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; padding: 0; flex-shrink: 0;" aria-label="Search"><i class="bi bi-search text-white small"></i></button>
        </form>
    </div>

    <?php
        // getUserBookings() returns array, filtered queries return mysqli_result
        if (is_array($bookings)) {
            $bookingsCount = count($bookings);
            $usingResult = false;
        } else {
            $bookingsCount = $bookings ? mysqli_num_rows($bookings) : 0;
            $usingResult = true;
        }
        if ($bookings && $bookingsCount > 0):
            $displayed = 0;
            while ($b = $usingResult ? mysqli_fetch_assoc($bookings) : array_shift($bookings)):
            // Apply search filter client-side for simplicity
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $match = strpos(strtolower($b['booking_ref']), $searchLower) !== false
                      || strpos(strtolower($b['flight_number']), $searchLower) !== false
                      || strpos(strtolower($b['airline_name']), $searchLower) !== false
                      || strpos(strtolower($b['source']), $searchLower) !== false
                      || strpos(strtolower($b['destination']), $searchLower) !== false;
                if (!$match) continue;
            }
            $displayed++;
            $isUpcoming = strtotime($b['departure_time']) > time() && $b['booking_status'] === 'Confirmed';
    ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3 booking-row <?php echo $b['booking_status'] === 'Cancelled' ? 'opacity-75' : ''; ?>">
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center">
                <div class="col-md-3 col-6 mb-2 mb-md-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-airplane text-primary small"></i>
                        </div>
                        <div>
                            <div class="fw-bold small"><?php echo htmlspecialchars($b['airline_name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($b['flight_number']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2 mb-md-0">
                    <div class="fw-semibold small"><?php echo htmlspecialchars($b['source']); ?> → <?php echo htmlspecialchars($b['destination']); ?></div>
                    <small class="text-muted">
                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($b['passenger_name']); ?>
                        · <i class="bi bi-card-heading ms-1 me-1"></i><?php echo $b['seat_number']; ?>
                    </small>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="small">
                        <i class="bi bi-calendar me-1"></i><?php echo formatDate($b['travel_date']); ?>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i><?php echo formatTime($b['departure_time']); ?>
                    </small>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0 text-center">
                    <div><strong class="text-accent"><?php echo $b['booking_ref']; ?></strong></div>
                    <?php echo statusBadge($b['booking_status']); ?>
                    <?php if ($isUpcoming): ?>
                        <span class="badge bg-info text-white mt-1"><i class="bi bi-arrow-up me-1"></i>Upcoming</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-md-end mt-2 mt-md-0">
                    <div class="d-flex gap-1 justify-content-md-end flex-wrap">
                        <a href="booking-confirmation.php?ref=<?php echo $b['booking_ref']; ?>" class="btn btn-sm btn-outline-accent" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="E-Ticket">
                            <i class="bi bi-ticket-perforated"></i>
                        </a>
                        <?php if ($b['booking_status'] === 'Confirmed'): ?>
                            <a href="<?php echo BASE_URL; ?>cancel-booking.php?id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking? Seats will be released.')" title="Cancel Booking">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        endwhile;
        if ($displayed === 0):
    ?>
    <div class="empty-state bg-white rounded-4 shadow-sm border p-5 text-center">
        <i class="bi bi-search text-muted display-5 mb-3 d-block"></i>
        <h4 class="fw-bold">No Matching Bookings</h4>
        <p class="text-muted mb-3">No bookings match your search "<?php echo htmlspecialchars($search); ?>".</p>
        <a href="?filter=<?php echo htmlspecialchars($filter); ?>" class="btn btn-accent btn-sm">Clear Search</a>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="empty-state bg-white rounded-4 shadow-sm border p-5 text-center">
        <i class="bi bi-ticket-perforated text-muted display-5 mb-3 d-block"></i>
        <h4 class="fw-bold">No Bookings <?php echo $filter !== 'all' ? 'in this category' : 'Yet'; ?></h4>
        <p class="text-muted mb-3">
            <?php if ($filter === 'upcoming'): ?>
                You don't have any upcoming flights. Time to plan a trip!
            <?php elseif ($filter === 'completed'): ?>
                No completed trips yet. Your next booking will appear here.
            <?php elseif ($filter === 'cancelled'): ?>
                No cancelled bookings.
            <?php else: ?>
                You haven't made any bookings yet. Start by searching for flights!
            <?php endif; ?>
        </p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent px-4"><i class="bi bi-search me-2"></i>Search Flights</a>
    </div>
    <?php endif; ?>
</div>

<style>
.booking-row { transition: var(--transition); }
.booking-row:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
