<?php 
$pageTitle = 'Booking Confirmed'; 
require_once 'includes/header.php';

if (!isLoggedIn()) redirect('login.php');

$refs_param = trim($_GET['refs'] ?? $_GET['ref'] ?? '');
if (empty($refs_param)) redirect('index.php');

$ref_list = array_filter(array_map('trim', explode(',', $refs_param)));
if (empty($ref_list)) redirect('index.php');

// Fetch all matching bookings securely for logged in user
$in_clause = implode(',', array_fill(0, count($ref_list), '?'));
$types = str_repeat('s', count($ref_list)) . 'i';

$sql = "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price 
        FROM bookings b 
        JOIN flights f ON b.flight_id = f.flight_id 
        WHERE b.booking_ref IN ($in_clause) AND b.user_id = ? 
        ORDER BY b.booking_id ASC";

$stmt = mysqli_prepare($conn, $sql);

// Bind parameters dynamically
$bind_params = array_merge($ref_list, [$_SESSION['user_id']]);
mysqli_stmt_bind_param($stmt, $types, ...$bind_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}
mysqli_stmt_close($stmt);

if (empty($bookings)) redirect('index.php');

$first = $bookings[0];
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="confirmation-card p-4 p-md-5 bg-white border rounded-4 shadow-sm mt-3">
                <div class="confirm-icon mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h1 class="fw-bold mb-2">Booking Confirmed!</h1>
                <p class="lead text-muted mb-4">Your flight reservation from <strong><?php echo htmlspecialchars($first['source']); ?></strong> to <strong><?php echo htmlspecialchars($first['destination']); ?></strong> is locked in.</p>
                
                <div class="row g-3 mb-4 text-start">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small text-uppercase d-block fw-bold mb-1">Airline & Flight</span>
                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($first['airline_name'] . ' (' . $first['flight_number'] . ')'); ?></h6>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small text-uppercase d-block fw-bold mb-1">Travel Date</span>
                            <h6 class="fw-bold mb-0 text-dark"><?php echo formatDate($first['travel_date']); ?></h6>
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded-3 mb-4 text-start">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Passenger</th>
                                <th>Seat No.</th>
                                <th>Booking Ref</th>
                                <th>Status</th>
                                <th class="text-end">E-Ticket</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($b['passenger_name']); ?></strong>
                                    <div class="small text-muted"><?php echo $b['gender'] . ', ' . $b['age'] . ' yrs'; ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-card-heading me-1"></i><?php echo $b['seat_number']; ?></span>
                                </td>
                                <td><strong class="text-accent"><?php echo $b['booking_ref']; ?></strong></td>
                                <td><?php echo statusBadge($b['booking_status']); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-printer me-1"></i> Print Ticket
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-grid gap-3 col-md-8 mx-auto">
                    <?php if (count($bookings) === 1): ?>
                        <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $first['booking_ref']; ?>" target="_blank" class="btn btn-accent btn-lg py-3 fw-bold">
                            <i class="bi bi-printer me-2"></i>Print E-Ticket
                        </a>
                    <?php endif; ?>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>my-bookings.php" class="btn btn-outline-secondary w-100 py-2 fw-semibold">My Bookings</a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline-secondary w-100 py-2 fw-semibold">Go Home</a>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-muted mt-4 small"><i class="bi bi-shield-check text-success me-1"></i>A confirmation copy has been sent to your registered email address.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
