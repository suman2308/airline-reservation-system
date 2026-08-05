<?php
$pageTitle = 'Manage Seats';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seats'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $flight_id = intval($_POST['flight_id']);
        $new_seats = intval($_POST['seats_available']);
        $total_seats = intval($_POST['total_seats']);
        $status = $_POST['status'];

        if ($new_seats < 0 || $new_seats > $total_seats) {
            setFlash('error', 'Available seats cannot exceed total seats or be negative.');
        } else {
            dbUpdate("UPDATE flights SET seats_available = ?, status = ? WHERE flight_id = ?", "isi", $new_seats, $status, $flight_id);
            setFlash('success', 'Flight status and seats updated!');
        }
    }
    redirect(BASE_URL . 'admin/manage-seats.php');
}

$flights = getAllFlights('departure_time ASC');
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="mb-0 fw-bold">Update Seat Availability & Status</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 manage-seats-table">
                        <thead class="table-light">
                            <tr>
                                <th>Flight</th><th>Route</th><th>Departure</th><th>Current Stats</th><th>Update Info</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($flights as $f): $fid = intval($f['flight_id']); ?>
                            <tr>
                                <td><strong><?php echo $f['flight_number']; ?></strong><br><small><?php echo $f['airline_name']; ?></small></td>
                                <td><?php echo $f['source']; ?> → <?php echo $f['destination']; ?></td>
                                <td><?php echo formatDate($f['departure_time']); ?><br><small><?php echo formatTime($f['departure_time']); ?></small></td>
                                <td>
                                    <div class="small">Available: <span class="fw-bold text-accent"><?php echo $f['seats_available']; ?></span></div>
                                    <div class="small">Total: <?php echo $f['total_seats']; ?></div>
                                    <div><?php echo statusBadge($f['status']); ?></div>
                                </td>
                                <td>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="small text-muted">Seats Left</label>
                                            <input type="number" name="seats_available" form="seat-form-<?php echo $fid; ?>" class="form-control form-control-sm" value="<?php echo $f['seats_available']; ?>" min="0" max="<?php echo $f['total_seats']; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted">Status</label>
                                            <select name="status" form="seat-form-<?php echo $fid; ?>" class="form-select form-select-sm">
                                                <?php statusOptions($f['status']); ?>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <button type="submit" form="seat-form-<?php echo $fid; ?>" name="update_seats" class="btn btn-sm btn-accent">Update</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php // Hidden per-row forms — inputs/buttons reference them via the HTML5 `form` attribute
                // (a <form> cannot be a child of <tr>; wrapping <td>s broke row layout on mobile). ?>
                <?php foreach ($flights as $f): $fid = intval($f['flight_id']); ?>
                <form id="seat-form-<?php echo $fid; ?>" method="POST" class="d-none">
                    <?php csrfField(); ?>
                    <input type="hidden" name="flight_id" value="<?php echo $fid; ?>">
                    <input type="hidden" name="total_seats" value="<?php echo $f['total_seats']; ?>">
                </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
