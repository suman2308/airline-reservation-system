<?php
/**
 * AeroBook – Online Check-in
 *
 * Allows passengers to check in for upcoming flights within the check-in window
 * (48 hours to 2 hours before departure). Updates boarding pass via generate-ticket.php.
 * Notifies the user and integrates with Travel Hub.
 */

$pageTitle = 'Online Check-in';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Notifications.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to check in.');
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get upcoming bookings that are eligible for check-in
$stmt = mysqli_prepare($conn,
    "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination,
            f.departure_time, f.arrival_time, f.price
     FROM bookings b
     JOIN flights f ON b.flight_id = f.flight_id
     WHERE b.user_id = ?
       AND b.booking_status = 'Confirmed'
       AND f.departure_time >= NOW()
       AND f.departure_time <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
       AND f.departure_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
     ORDER BY f.departure_time ASC"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$upcoming = [];
while ($row = mysqli_fetch_assoc($result)) $upcoming[] = $row;
mysqli_stmt_close($stmt);

// Get already checked-in bookings
$stmt2 = mysqli_prepare($conn,
    "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination,
            f.departure_time, f.arrival_time
     FROM bookings b
     JOIN flights f ON b.flight_id = f.flight_id
     WHERE b.user_id = ?
       AND b.booking_status = 'Checked-in'
     ORDER BY f.departure_time DESC LIMIT 5"
);
mysqli_stmt_bind_param($stmt2, "i", $userId);
mysqli_stmt_execute($stmt2);
$checkedIn = mysqli_stmt_get_result($stmt2);
mysqli_stmt_close($stmt2);

// Handle check-in action
$checkInMessage = '';
$checkInSuccess = false;
$ref = trim($_GET['checkin'] ?? '');
if (!empty($ref)) {
    if (!isset($_GET['token']) || !validateCSRFToken($_GET['token'])) {
        setFlash('error', 'Invalid request token.');
        redirect('check-in.php');
    }

    // Verify the booking belongs to this user and is eligible
    $stmt3 = mysqli_prepare($conn,
        "SELECT b.booking_id, b.booking_ref, f.departure_time
         FROM bookings b
         JOIN flights f ON b.flight_id = f.flight_id
         WHERE b.booking_ref = ? AND b.user_id = ?
           AND b.booking_status = 'Confirmed'
           AND f.departure_time BETWEEN DATE_SUB(NOW(), INTERVAL 48 HOUR) AND DATE_SUB(NOW(), INTERVAL 2 HOUR)"
    );
    mysqli_stmt_bind_param($stmt3, "si", $ref, $userId);
    mysqli_stmt_execute($stmt3);
    $checkResult = mysqli_stmt_get_result($stmt3);
    $checkBooking = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($stmt3);

    if ($checkBooking) {
        // Update booking status to Checked-in
        $updateStmt = mysqli_prepare($conn, "UPDATE bookings SET booking_status = 'Checked-in' WHERE booking_id = ?");
        mysqli_stmt_bind_param($updateStmt, "i", $checkBooking['booking_id']);
        if (mysqli_stmt_execute($updateStmt)) {
            $checkInSuccess = true;
            $checkInMessage = 'Check-in successful for booking ' . htmlspecialchars($ref) . '!';
            logInfo('Online check-in completed', ['booking_ref' => $ref, 'user_id' => $userId]);

            // Create notification
            AeroNotifications::create($userId, 'checkin_confirmed', 'Check-in Complete!',
                'You\'ve checked in for ' . htmlspecialchars($ref),
                'generate-ticket.php?ref=' . urlencode($ref)
            );

            // Get booking details for the display
            $detailStmt = mysqli_prepare($conn,
                "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination,
                        f.departure_time, f.arrival_time
                 FROM bookings b
                 JOIN flights f ON b.flight_id = f.flight_id
                 WHERE b.booking_ref = ?"
            );
            mysqli_stmt_bind_param($detailStmt, "s", $ref);
            mysqli_stmt_execute($detailStmt);
            $checkedBooking = mysqli_fetch_assoc(mysqli_stmt_get_result($detailStmt));
            mysqli_stmt_close($detailStmt);
        } else {
            $checkInMessage = 'Check-in failed. Please try again.';
        }
        mysqli_stmt_close($updateStmt);
    } else {
        $checkInMessage = 'This booking is not eligible for check-in. Check-in opens 48 hours before departure and closes 2 hours before.';
    }
}

// Check window helper
function checkinWindow($departureTime) {
    $now = time();
    $dep = strtotime($departureTime);
    $hoursUntil = ($dep - $now) / 3600;
    if ($hoursUntil > 48) return ['status' => 'too_early', 'label' => 'Check-in opens ' . floor($hoursUntil - 48) . 'h from now'];
    if ($hoursUntil >= 2 && $hoursUntil <= 48) return ['status' => 'open', 'label' => 'Check-in available'];
    if ($hoursUntil >= 0 && $hoursUntil < 2) return ['status' => 'closing_soon', 'label' => 'Check-in closing soon'];
    return ['status' => 'closed', 'label' => 'Check-in closed'];
}
?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-door-open me-2 text-accent"></i>Online Check-in</h1>
        <p class="text-muted mb-0">Check in for your upcoming flights and download your boarding pass</p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <?php if ($checkInSuccess && isset($checkedBooking)): ?>
    <!-- Success Banner -->
    <div class="alert alert-success alert-dismissible fade show text-center py-4" role="alert">
        <i class="bi bi-check-circle-fill display-6 text-success mb-2 d-block"></i>
        <h4 class="fw-bold mb-2">Check-in Successful! ✅</h4>
        <p class="mb-3">Your boarding pass is ready for <strong><?php echo htmlspecialchars($checkedBooking['airline_name'] . ' ' . $checkedBooking['flight_number']); ?></strong><br>
        <?php echo htmlspecialchars($checkedBooking['source'] . ' → ' . $checkedBooking['destination']); ?> · Seat <strong><?php echo $checkedBooking['seat_number']; ?></strong></p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo urlencode($checkedBooking['booking_ref']); ?>" target="_blank" class="btn btn-accent btn-lg fw-bold px-4">
                <i class="bi bi-ticket-perforated me-2"></i>Download Boarding Pass
            </a>
            <a href="<?php echo BASE_URL; ?>my-bookings.php" class="btn btn-outline-secondary btn-lg px-4 fw-bold">
                <i class="bi bi-journal-bookmark me-2"></i>My Bookings
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($upcoming)): ?>
    <h4 class="fw-bold mb-3"><i class="bi bi-airplane-engines me-2 text-accent"></i>Eligible for Check-in</h4>
    <div class="row g-3 mb-5">
        <?php foreach ($upcoming as $b):
            $window = checkinWindow($b['departure_time']);
        ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($b['airline_name'] . ' ' . $b['flight_number']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($b['source']); ?> → <?php echo htmlspecialchars($b['destination']); ?></small>
                        </div>
                        <?php
                            if ($window['status'] === 'open') echo '<span class="badge-status bg-success">Check-in Open</span>';
                            elseif ($window['status'] === 'closing_soon') echo '<span class="badge-status bg-warning">Closing Soon</span>';
                            else echo '<span class="badge-status bg-secondary">Not Yet</span>';
                        ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 small">
                        <span><i class="bi bi-calendar me-1"></i><?php echo formatDate($b['travel_date']); ?></span>
                        <span><i class="bi bi-clock me-1"></i><?php echo formatTime($b['departure_time']); ?></span>
                        <span><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($b['passenger_name']); ?></span>
                        <span><i class="bi bi-card-heading me-1"></i><?php echo $b['seat_number']; ?></span>
                    </div>
                    <?php if ($window['status'] === 'open'): ?>
                    <a href="?checkin=<?php echo urlencode($b['booking_ref']); ?>&token=<?php echo urlencode(generateCSRFToken()); ?>"
                       class="btn btn-accent w-100 fw-bold"
                       onclick="return confirm('Check in for <?php echo htmlspecialchars($b['airline_name'] . ' ' . $b['flight_number']); ?>?')">
                        <i class="bi bi-check-circle me-2"></i>Check In Now
                    </a>
                    <?php else: ?>
                    <button class="btn btn-outline-secondary w-100 fw-bold" disabled>
                        <i class="bi bi-clock me-2"></i><?php echo $window['label']; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state bg-white rounded-4 shadow-sm border p-5 text-center mb-5">
        <i class="bi bi-door-closed text-muted display-5 mb-3 d-block"></i>
        <h4 class="fw-bold">No Upcoming Flights for Check-in</h4>
        <p class="text-muted mb-3">Check-in is available 48 hours to 2 hours before departure. <br>You don't have any flights departing within this window.</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent px-4 fw-bold">
            <i class="bi bi-search me-2"></i>Book a Flight
        </a>
    </div>
    <?php endif; ?>

    <!-- Already Checked In -->
    <?php if ($checkedIn && mysqli_num_rows($checkedIn) > 0): ?>
    <h4 class="fw-bold mb-3"><i class="bi bi-check-all me-2 text-success"></i>Already Checked In</h4>
    <div class="row g-3 mb-4">
        <?php while ($b = mysqli_fetch_assoc($checkedIn)): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block"><?php echo htmlspecialchars($b['airline_name'] . ' ' . $b['flight_number']); ?></strong>
                        <small class="text-muted"><?php echo htmlspecialchars($b['source'] . ' → ' . $b['destination']); ?> · Seat <?php echo $b['seat_number']; ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge-status bg-success">Checked In</span>
                        <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo urlencode($b['booking_ref']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-ticket-perforated"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Check-in Info Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Check-in Information</h5>
                    <ul class="mb-0 small" style="list-style: none; padding-left: 0;">
                        <li class="mb-1"><i class="bi bi-check-circle me-2"></i>Online check-in opens <strong>48 hours</strong> before departure</li>
                        <li class="mb-1"><i class="bi bi-check-circle me-2"></i>Check-in closes <strong>2 hours</strong> before departure</li>
                        <li class="mb-1"><i class="bi bi-check-circle me-2"></i>Carry a government-issued photo ID to the airport</li>
                        <li><i class="bi bi-check-circle me-2"></i>Report to boarding gate at least <strong>45 minutes</strong> before departure</li>
                    </ul>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <i class="bi bi-door-open" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Travel Hub Integration -->
    <div class="mt-4 text-center">
        <a href="<?php echo BASE_URL; ?>user-dashboard.php" class="btn btn-outline-accent btn-sm fw-bold">
            <i class="bi bi-compass me-2"></i>Go to Travel Hub
        </a>
        <a href="<?php echo BASE_URL; ?>my-bookings.php" class="btn btn-outline-accent btn-sm fw-bold ms-2">
            <i class="bi bi-journal-bookmark me-2"></i>My Bookings
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
