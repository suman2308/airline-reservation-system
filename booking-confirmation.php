<?php
$pageTitle = 'Booking Confirmed';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/QRCode.php';
require_once 'includes/ICS.php';
require_once 'includes/Notifications.php';


if (!isLoggedIn()) redirect('login.php');

$refs_param = trim($_GET['refs'] ?? $_GET['ref'] ?? '');
if (empty($refs_param)) redirect('index.php');

$ref_list = array_filter(array_map('trim', explode(',', $refs_param)));
if (empty($ref_list)) redirect('index.php');

$bookings = getBookingsByRefList($ref_list, $_SESSION['user_id']);
if (empty($bookings)) redirect('index.php');

$first = $bookings[0];
$is_multi = count($bookings) > 1;
$userId = $_SESSION['user_id'];

// Calculate total from bookings
$totalFare = 0;
foreach ($bookings as $b) {
    $totalFare += floatval($b['price']);
}

// Create notifications for each booking
foreach ($bookings as $b) {
    AeroNotifications::create($userId, 'booking_confirmed', 'Booking Confirmed!',
        htmlspecialchars($b['airline_name'] . ' ' . $b['flight_number'] . ' · ' . $b['source'] . ' → ' . $b['destination']),
        'booking-confirmation.php?ref=' . $b['booking_ref']
    );
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Success Banner -->
            <div class="text-center mb-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                </div>
                <h1 class="fw-bold mb-2">Booking Confirmed! 🎉</h1>
                <p class="lead text-muted mb-1">Your flight from <strong><?php echo htmlspecialchars($first['source']); ?></strong> to <strong><?php echo htmlspecialchars($first['destination']); ?></strong> is confirmed.</p>

            </div>

            <!-- Trip Timeline -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-accent"></i>Trip Timeline</h5>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Booking Confirmed</div>
                                <small class="text-muted"><?php echo formatDateTime($first['booking_date']); ?></small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Departure</div>
                                <small class="text-muted"><?php echo formatDateTime($first['travel_date'] . ' ' . date('H:i:s', strtotime($first['departure_time']))); ?> · <?php echo htmlspecialchars($first['source']); ?></small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Arrival</div>
                                <small class="text-muted"><?php echo formatDateTime($first['travel_date'] . ' ' . date('H:i:s', strtotime($first['arrival_time']))); ?> · <?php echo htmlspecialchars($first['destination']); ?></small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Trip Completed</div>
                                <small class="text-muted">Estimated arrival time</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>                    <!-- Real QR Code -->
            <?php $qr = new AeroQR(); ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    <div style="display: inline-block; background: #fff; padding: 8px; border-radius: 8px; border: 2px dashed #e2e8f0;">
                        <img src="<?php echo $qr->bookingQR($first['booking_ref'], 130); ?>" alt="QR Code" style="width:130px;height:130px;display:block;margin:0 auto;">
                    </div>
                    <div class="mt-2">
                        <small class="text-muted d-block">Scan QR code at boarding gate</small>
                        <span class="fw-bold text-accent" style="font-family: 'Libre Barcode 39 Text', cursive; font-size: 1.6rem;">*<?php echo htmlspecialchars($first['booking_ref']); ?>*</span>
                    </div>
                </div>
            </div>

                    <!-- Booking Details & Boarding Pass Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Boarding Pass</h5>
                            <p class="text-muted small mb-0"><?php echo $is_multi ? count($bookings) . ' passengers' : '1 passenger'; ?> · <?php echo htmlspecialchars($first['airline_name']); ?></p>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-accent fs-5"><?php echo htmlspecialchars($first['booking_ref']); ?></div>
                            <small class="text-muted">PNR / Reference</small>
                        </div>
                    </div>

                    <!-- Route Display -->
                    <div class="d-flex align-items-center justify-content-between p-4 bg-light rounded-3 mb-4">
                        <div class="text-center">
                            <div class="fw-bold text-dark" style="font-size: 2rem; font-family: var(--font-heading);"><?php echo strtoupper(substr($first['source'], 0, 3)); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($first['source']); ?></small>
                            <div class="fw-bold mt-2"><?php echo formatTime($first['departure_time']); ?></div>
                        </div>
                        <div class="flex-grow-1 mx-4 text-center position-relative">
                            <div class="border-top border-2 border-accent w-100 position-absolute" style="top: 50%;"></div>
                            <i class="bi bi-airplane-fill text-accent fs-3 position-relative bg-light px-2"></i>
                            <div class="small text-muted mt-2"><?php echo calcDuration($first['departure_time'], $first['arrival_time']); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-dark" style="font-size: 2rem; font-family: var(--font-heading);"><?php echo strtoupper(substr($first['destination'], 0, 3)); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($first['destination']); ?></small>
                            <div class="fw-bold mt-2"><?php echo formatTime($first['arrival_time']); ?></div>
                        </div>
                    </div>

                    <!-- Passenger Details Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Passenger</th>
                                    <th>Seat</th>
                                    <th>Class</th>
                                    <th>Booking Ref</th>
                                    <th>Status</th>
                                    <th class="text-end">E-Ticket</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b):
                                    $seatRow = intval(substr($b['seat_number'], 0, -1));
                                    $seatClass = ($seatRow >= 1 && $seatRow <= 2) ? 'Business' : 'Economy';
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($b['passenger_name']); ?></div>
                                        <small class="text-muted"><?php echo $b['gender'] . ', ' . $b['age'] . ' yrs'; ?></small>
                                    </td>
                                    <td><span class="badge bg-accent fs-6 px-3 py-2"><?php echo $b['seat_number']; ?></span></td>
                                    <td><small><?php echo $seatClass; ?></small></td>
                                    <td><strong class="text-accent"><?php echo $b['booking_ref']; ?></strong></td>
                                    <td><?php echo statusBadge($b['booking_status']); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-ticket-perforated me-1"></i>Ticket
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Flight Info Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center p-3">
                            <i class="bi bi-building text-accent fs-4 mb-1 d-block"></i>
                            <small class="text-muted d-block">Airline</small>
                            <span class="fw-bold small"><?php echo htmlspecialchars($first['airline_name']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center p-3">
                            <i class="bi bi-hash text-accent fs-4 mb-1 d-block"></i>
                            <small class="text-muted d-block">Flight</small>
                            <span class="fw-bold small"><?php echo htmlspecialchars($first['flight_number']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center p-3">
                            <i class="bi bi-door-open text-accent fs-4 mb-1 d-block"></i>
                            <small class="text-muted d-block">Gate</small>
                            <span class="fw-bold small">A-12 (T3)</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center p-3">
                            <i class="bi bi-luggage text-accent fs-4 mb-1 d-block"></i>
                            <small class="text-muted d-block">Baggage</small>
                            <span class="fw-bold small">Belt 04</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fare Summary -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-accent"></i>Fare Summary</h5>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Base Fare (<?php echo count($bookings); ?> seat<?php echo count($bookings) > 1 ? 's' : ''; ?>)</span>
                        <span class="fw-bold"><?php echo formatPrice($totalFare); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Taxes & Airport Fee</span>
                        <span class="fw-bold text-success">Included</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="fw-bold fs-5">Total Paid</span>
                        <span class="fw-bold fs-5 text-accent"><?php echo formatPrice($totalFare); ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php $ics = new AeroICS(); $gcalUrl = AeroICS::googleCalLink($first, $first); ?>
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
                <button onclick="window.print()" class="btn btn-accent btn-lg px-4 fw-bold">
                    <i class="bi bi-printer me-2"></i>Print Confirmation
                </button>
                <?php if (!$is_multi): ?>
                <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $first['booking_ref']; ?>" target="_blank" class="btn btn-outline-accent btn-lg px-4 fw-bold">
                    <i class="bi bi-ticket-perforated me-2"></i>View E-Ticket
                </a>
                <?php endif; ?>
                <a href="<?php echo $gcalUrl; ?>" target="_blank" class="btn btn-outline-success btn-lg px-4 fw-bold">
                    <i class="bi bi-calendar-plus me-2"></i>Add to Google Calendar
                </a>
                <a href="<?php echo BASE_URL; ?>my-bookings.php" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="bi bi-journal-bookmark me-2"></i>My Bookings
                </a>
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="bi bi-search me-2"></i>Book Another Flight
                </a>
            </div>

            <p class="text-center text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i>
                Please carry a government-issued photo ID (Aadhaar, Passport, Driver's License) to the airport.
                Report for boarding at least 45 minutes before departure.
            </p>
        </div>
    </div>
</div>

<style>
.timeline { position: relative; padding-left: 30px; }
.timeline::before { content: ''; position: absolute; left: 10px; top: 5px; bottom: 5px; width: 2px; background: #e2e8f0; }
.timeline-item { position: relative; padding-bottom: 1.5rem; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-marker { position: absolute; left: -24px; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #e2e8f0; }
.timeline-content { padding-left: 0.5rem; }
.bg-accent { background-color: var(--accent) !important; color: #fff; }
.btn-outline-accent { color: var(--accent); border-color: var(--accent); }
.btn-outline-accent:hover { background: var(--accent); color: #fff; }
</style>

<?php require_once 'includes/footer.php'; ?>
