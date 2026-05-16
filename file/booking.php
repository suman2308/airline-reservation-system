<?php 
$pageTitle = 'Book Flight'; 
require_once 'includes/header.php';
if (!isLoggedIn()) { $_SESSION['error'] = 'Please login to book a flight.'; redirect('login.php'); }

$flight_id = intval($_GET['flight_id'] ?? 0);
if ($flight_id <= 0) redirect('search-flights.php');

// Fetch flight details safely
$stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id=? AND status='Scheduled' AND seats_available > 0");
mysqli_stmt_bind_param($stmt, "i", $flight_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) { 
    $_SESSION['error'] = 'Flight not available.'; 
    mysqli_stmt_close($stmt);
    redirect('search-flights.php'); 
}
$f = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        redirect('booking.php?flight_id=' . $flight_id);
    }

    $passenger_name = trim($_POST['passenger_name']);
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $travel_date = $_POST['travel_date'];
    $user_id = $_SESSION['user_id'];

    if (empty($passenger_name) || $age < 1 || empty($gender) || empty($travel_date)) {
        $_SESSION['error'] = 'All fields are required.';
    } else {
        $booking_ref = generateBookingRef();
        $seat = generateSeatNumber();
        
        // Ensure booking_ref is unique
        $check_stmt = mysqli_prepare($conn, "SELECT booking_id FROM bookings WHERE booking_ref=?");
        mysqli_stmt_bind_param($check_stmt, "s", $booking_ref);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        while (mysqli_stmt_num_rows($check_stmt) > 0) {
            $booking_ref = generateBookingRef();
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
        }
        mysqli_stmt_close($check_stmt);

        // Begin Booking
        mysqli_begin_transaction($conn);
        try {
            $book_stmt = mysqli_prepare($conn, "INSERT INTO bookings (booking_ref, user_id, flight_id, passenger_name, age, gender, travel_date, seat_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($book_stmt, "siisisss", $booking_ref, $user_id, $flight_id, $passenger_name, $age, $gender, $travel_date, $seat);
            
            if (mysqli_stmt_execute($book_stmt)) {
                $update_stmt = mysqli_prepare($conn, "UPDATE flights SET seats_available = seats_available - 1 WHERE flight_id=? AND seats_available > 0");
                mysqli_stmt_bind_param($update_stmt, "i", $flight_id);
                mysqli_stmt_execute($update_stmt);
                
                if (mysqli_affected_rows($conn) > 0) {
                    mysqli_commit($conn);
                    $_SESSION['success'] = 'Booking confirmed!';
                    redirect('booking-confirmation.php?ref=' . $booking_ref);
                } else {
                    throw new Exception("Seats no longer available.");
                }
            } else {
                throw new Exception("Booking insertion failed.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = 'Booking failed: ' . $e->getMessage();
        }
    }
}
?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-ticket-perforated me-2"></i>Book Your Flight</h1>
        <p><?php echo htmlspecialchars($f['source'] . ' → ' . $f['destination'] . ' · ' . $f['airline_name']); ?></p>
    </div>
</div>
<div class="container py-5">
    <?php showAlert(); ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="flight-card">
                <h4 class="mb-3"><i class="bi bi-person-lines-fill me-2 text-accent"></i>Passenger Information</h4>
                <form method="POST" id="bookingForm">
                    <?php csrfField(); ?>
                    <input type="hidden" name="travel_date" value="<?php echo date('Y-m-d', strtotime($f['departure_time'])); ?>">
                    <div class="mb-3">
                        <label class="form-label">Passenger Full Name</label>
                        <input type="text" name="passenger_name" id="passenger_name" class="form-control" placeholder="As on ID proof" required>
                        <div class="invalid-feedback">Enter valid name (min 3 characters).</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" id="age" class="form-control" min="1" max="120" placeholder="Enter age" required>
                            <div class="invalid-feedback">Enter valid age.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Confirm Booking</button>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="flight-card">
                <h5 class="mb-3">Flight Summary</h5>
                <div class="booking-details-card" style="margin:0;max-width:100%;">
                    <div class="detail-row"><span class="detail-label">Airline</span><span class="detail-value"><?php echo htmlspecialchars($f['airline_name']); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Flight</span><span class="detail-value"><?php echo htmlspecialchars($f['flight_number']); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Route</span><span class="detail-value"><?php echo htmlspecialchars($f['source'].' → '.$f['destination']); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Departure</span><span class="detail-value"><?php echo formatDateTime($f['departure_time']); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Arrival</span><span class="detail-value"><?php echo formatDateTime($f['arrival_time']); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Price</span><span class="detail-value flight-price" style="font-size:1.2rem;"><?php echo formatPrice($f['price']); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

