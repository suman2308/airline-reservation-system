<?php
$pageTitle = 'Travel Documents';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price
                                FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                WHERE b.user_id = ? AND b.booking_status = 'Confirmed'
                                ORDER BY b.travel_date DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>
<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Documents</span>
        <h1>Travel <span class="dim">Documents</span></h1>
        <p>Access your boarding passes, e-tickets, and trip summaries</p>
    </div>
</div>
<div class="container py-5">
    <?php if (mysqli_num_rows($bookings) > 0): ?>
    <div class="row g-4">
        <?php while($b = mysqli_fetch_assoc($bookings)): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-accent bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-ticket-perforated text-accent"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($b['airline_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($b['flight_number']); ?></small>
                        </div>
                    </div>
                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($b['source']); ?> → <?php echo htmlspecialchars($b['destination']); ?></p>
                    <small class="text-muted d-block mb-3"><?php echo formatDate($b['travel_date']); ?> · <?php echo formatTime($b['departure_time']); ?> · Seat <?php echo $b['seat_number']; ?></small>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-sm btn-accent"><i class="bi bi-ticket-perforated me-1"></i>Boarding Pass</a>
                        <a href="<?php echo BASE_URL; ?>booking-confirmation.php?ref=<?php echo $b['booking_ref']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Details</a>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 px-4 py-2">
                    <small class="text-muted">Ref: <span class="text-accent fw-bold"><?php echo $b['booking_ref']; ?></span></small>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state bg-white rounded-4 shadow-sm border p-5 text-center">
        <i class="bi bi-folder-open text-muted display-5 mb-3 d-block"></i>
        <h4 class="fw-bold">No Documents Yet</h4>
        <p class="text-muted mb-3">Your boarding passes and e-tickets will appear here after you book a flight.</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent px-4"><i class="bi bi-search me-2"></i>Search Flights</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
