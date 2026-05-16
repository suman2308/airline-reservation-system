<?php 
$pageTitle = 'Edit Flight'; 
require_once __DIR__ . '/includes/admin-header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect(BASE_URL . 'admin/manage-flights.php');

// Fetch flight details safely
$stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = 'Flight not found.';
    redirect(BASE_URL . 'admin/manage-flights.php');
}
$f = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request.';
    } else {
        $airline_name = trim($_POST['airline_name']);
        $source = $_POST['source'];
        $destination = $_POST['destination'];
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $total_seats = intval($_POST['total_seats']);
        $seats_available = intval($_POST['seats_available']);
        $price = floatval($_POST['price']);
        $status = $_POST['status'];

        if (empty($airline_name) || $source === $destination) {
            $_SESSION['error'] = 'Please fill all fields correctly. Source and destination cannot be the same.';
        } else {
            $upd_stmt = mysqli_prepare($conn, "UPDATE flights SET airline_name = ?, source = ?, destination = ?, departure_time = ?, arrival_time = ?, total_seats = ?, seats_available = ?, price = ?, status = ? WHERE flight_id = ?");
            mysqli_stmt_bind_param($upd_stmt, "sssssiidsi", $airline_name, $source, $destination, $departure_time, $arrival_time, $total_seats, $seats_available, $price, $status, $id);
            
            if (mysqli_stmt_execute($upd_stmt)) {
                $_SESSION['success'] = 'Flight updated successfully!';
                redirect(BASE_URL . 'admin/manage-flights.php');
            } else {
                $_SESSION['error'] = 'Failed to update flight.';
            }
            mysqli_stmt_close($upd_stmt);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Flight: <?php echo $f['flight_number']; ?></h5>
                <a href="manage-flights.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body p-4">
                <?php showAlert(); ?>
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flight Number</label>
                            <input type="text" class="form-control bg-light" value="<?php echo $f['flight_number']; ?>" disabled>
                            <small class="text-muted">Flight number cannot be changed.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Airline Name</label>
                            <input type="text" name="airline_name" class="form-control" value="<?php echo htmlspecialchars($f['airline_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Source City</label>
                            <select name="source" class="form-select" required>
                                <?php foreach(['Delhi','Mumbai','Bangalore','Kolkata','Chennai','Hyderabad','Pune'] as $city): ?>
                                    <option value="<?php echo $city; ?>" <?php echo $f['source'] == $city ? 'selected' : ''; ?>><?php echo $city; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination City</label>
                            <select name="destination" class="form-select" required>
                                <?php foreach(['Delhi','Mumbai','Bangalore','Kolkata','Chennai','Hyderabad','Pune'] as $city): ?>
                                    <option value="<?php echo $city; ?>" <?php echo $f['destination'] == $city ? 'selected' : ''; ?>><?php echo $city; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departure Time</label>
                            <input type="datetime-local" name="departure_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($f['departure_time'])); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($f['arrival_time'])); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Seats</label>
                            <input type="number" name="total_seats" class="form-control" value="<?php echo $f['total_seats']; ?>" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Available Seats</label>
                            <input type="number" name="seats_available" class="form-control" value="<?php echo $f['seats_available']; ?>" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" name="price" class="form-control" value="<?php echo $f['price']; ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Flight Status</label>
                        <select name="status" class="form-select">
                            <?php foreach(['Scheduled','Delayed','Cancelled','Completed'] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $f['status'] == $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Update Flight Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
