<?php
$pageTitle = 'Add Flight';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
        redirect(BASE_URL . 'admin/add-flight.php');
    }

    $flight_number = trim($_POST['flight_number']);
    $airline_name = trim($_POST['airline_name']);
    $source = $_POST['source'];
    $destination = $_POST['destination'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $total_seats = intval($_POST['total_seats']);
    $price = floatval($_POST['price']);

    if (empty($flight_number) || empty($airline_name) || $source === $destination) {
        setFlash('error', 'Please fill all fields correctly. Source and destination cannot be the same.');
    } elseif (flightNumberExists($flight_number)) {
        setFlash('error', 'Flight number already exists.');
    } else {
        dbInsert(
            "INSERT INTO flights (flight_number, airline_name, source, destination, departure_time, arrival_time, total_seats, seats_available, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "ssssssiid",
            $flight_number, $airline_name, $source, $destination, $departure_time, $arrival_time, $total_seats, $total_seats, $price
        );
        logAdminAction($_SESSION['admin_id'], 'create_flight', "Added flight $flight_number ($source→$destination)");
        setFlash('success', 'Flight ' . $flight_number . ' added successfully!');
        redirect(BASE_URL . 'admin/manage-flights.php');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Flight</h5>
            </div>
            <div class="card-body p-4">
                <?php showAlert(); ?>
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flight Number</label>
                            <input type="text" name="flight_number" class="form-control" placeholder="e.g. AI-102" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Airline Name</label>
                            <input type="text" name="airline_name" class="form-control" placeholder="e.g. Air India" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Source City</label>
                            <select name="source" class="form-select" required>
                                <option value="">Select Origin</option>
                                <?php cityOptions(); ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination City</label>
                            <select name="destination" class="form-select" required>
                                <option value="">Select Destination</option>
                                <?php cityOptions(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departure Time</label>
                            <input type="datetime-local" name="departure_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Seats</label>
                            <input type="number" name="total_seats" class="form-control" min="1" max="500" value="180" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ticket Price (₹)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Add Flight to Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
