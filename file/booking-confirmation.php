<?php $pageTitle = 'Booking Confirmed'; require_once 'includes/header.php';
$ref = trim($_GET['ref'] ?? '');
if (empty($ref)) redirect('index.php');

$stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id = f.flight_id WHERE b.booking_ref = ? AND b.user_id = ?");
mysqli_stmt_bind_param($stmt, "si", $ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) redirect('index.php');
$b = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>
<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="confirmation-card p-5 bg-white border rounded-4 shadow-sm mt-5">
                <div class="success-icon mb-4" style="font-size: 5rem; color: #198754;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h1 class="fw-bold mb-3">Booking Confirmed!</h1>
                <p class="lead text-muted mb-4">Pack your bags! Your flight from <strong><?php echo htmlspecialchars($b['source']); ?></strong> to <strong><?php echo htmlspecialchars($b['destination']); ?></strong> is confirmed.</p>
                
                <div class="booking-ref-box p-4 bg-light rounded-3 mb-4">
                    <p class="mb-1 text-muted small text-uppercase fw-bold">Booking Reference</p>
                    <h2 class="text-accent fw-bold mb-0"><?php echo $b['booking_ref']; ?></h2>
                </div>

                <div class="row text-start g-4 mb-5">
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted small">Passenger</p>
                        <h6 class="fw-bold"><?php echo htmlspecialchars($b['passenger_name']); ?></h6>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted small">Flight</p>
                        <h6 class="fw-bold"><?php echo htmlspecialchars($b['airline_name'] . ' (' . $b['flight_number'] . ')'); ?></h6>
                    </div>
                </div>

                <div class="d-grid gap-3">
                    <a href="<?php echo BASE_URL; ?>generate-ticket.php?ref=<?php echo $b['booking_ref']; ?>" target="_blank" class="btn btn-accent btn-lg py-3 fw-bold">
                        <i class="bi bi-printer me-2"></i>Print E-Ticket
                    </a>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>my-bookings.php" class="btn btn-outline-secondary w-100 py-2">My Bookings</a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline-secondary w-100 py-2">Go Home</a>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-muted mt-4 small">A confirmation email has been sent to your registered email address.</p>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
