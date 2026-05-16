<?php 
$pageTitle = 'Manage Flights'; 
require_once __DIR__ . '/includes/admin-header.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM flights WHERE flight_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Flight deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete flight. It might have active bookings.';
    }
    mysqli_stmt_close($stmt);
    redirect(BASE_URL . 'admin/manage-flights.php');
}

$flights = mysqli_query($conn, "SELECT * FROM flights ORDER BY departure_time DESC");
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">All Flights</h5>
        <a href="add-flight.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add New Flight</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Flight #</th><th>Airline</th><th>Route</th><th>Departure</th><th>Seats</th><th>Price</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($f = mysqli_fetch_assoc($flights)): ?>
                    <tr>
                        <td><span class="text-accent fw-bold"><?php echo $f['flight_number']; ?></span></td>
                        <td><?php echo htmlspecialchars($f['airline_name']); ?></td>
                        <td><?php echo htmlspecialchars($f['source'].' → '.$f['destination']); ?></td>
                        <td><?php echo formatDateTime($f['departure_time']); ?></td>
                        <td><?php echo $f['seats_available']; ?> / <?php echo $f['total_seats']; ?></td>
                        <td><?php echo formatPrice($f['price']); ?></td>
                        <td><?php echo statusBadge($f['status']); ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="manage-seats.php?flight_id=<?php echo $f['flight_id']; ?>" class="btn btn-sm btn-outline-info" title="Manage Seats"><i class="bi bi-grid-3x3"></i></a>
                                <a href="?delete=<?php echo $f['flight_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure? This will delete the flight permanentely.')" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
