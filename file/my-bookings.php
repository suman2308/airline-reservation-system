<?php $pageTitle = 'My Bookings'; require_once 'includes/header.php';
if (!isLoggedIn()) { $_SESSION['error'] = 'Please login first.'; redirect('login.php'); }
?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-journal-bookmark me-2"></i>My Bookings</h1>
        <p>View and manage all your flight reservations</p>
    </div>
</div>
<div class="container py-5">
    <?php showAlert(); ?>
    <?php
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.user_id=? ORDER BY b.booking_date DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $bookings = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($bookings) > 0): ?>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booking Ref</th><th>Flight</th><th>Route</th><th>Passenger</th><th>Seat</th><th>Travel Date</th><th>Amount</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td><strong class="text-accent"><?php echo $b['booking_ref']; ?></strong></td>
                        <td><?php echo htmlspecialchars($b['airline_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($b['flight_number']); ?></small></td>
                        <td><?php echo htmlspecialchars($b['source'].' → '.$b['destination']); ?></td>
                        <td><?php echo htmlspecialchars($b['passenger_name']); ?><br><small class="text-muted"><?php echo $b['gender'].', '.$b['age'].'y'; ?></small></td>
                        <td><?php echo $b['seat_number']; ?></td>
                        <td><?php echo formatDate($b['travel_date']); ?></td>
                        <td><strong><?php echo formatPrice($b['price']); ?></strong></td>
                        <td><?php echo statusBadge($b['booking_status']); ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if ($b['booking_status'] === 'Confirmed'): ?>
                                    <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="bi bi-ticket-perforated"></i> Ticket</a>
                                    <a href="<?php echo BASE_URL; ?>cancel-booking.php?id=<?php echo $b['booking_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this booking?')"><i class="bi bi-x-circle"></i> Cancel</a>
                                <?php else: ?>
                                    <span class="text-muted small">Cancelled</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-ticket-perforated"></i>
        <h4>No Bookings Yet</h4>
        <p>You haven't made any bookings. Start by searching for flights!</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent mt-3"><i class="bi bi-search me-2"></i>Search Flights</a>
    </div>
    <?php endif; mysqli_stmt_close($stmt); ?>
</div>
<?php require_once 'includes/footer.php'; ?>

