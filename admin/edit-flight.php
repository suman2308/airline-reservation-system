<?php $pageTitle = 'Edit Flight'; require_once 'includes/admin-header.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect('manage-flights.php');
$result = mysqli_query($conn, "SELECT * FROM flights WHERE flight_id=$id");
if (mysqli_num_rows($result) === 0) { $_SESSION['error'] = 'Flight not found.'; redirect('manage-flights.php'); }
$f = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $airline = sanitize($_POST['airline_name']);
    $source = sanitize($_POST['source']);
    $dest = sanitize($_POST['destination']);
    $dep = sanitize($_POST['departure_time']);
    $arr = sanitize($_POST['arrival_time']);
    $seats = intval($_POST['total_seats']);
    $avail = intval($_POST['seats_available']);
    $price = floatval($_POST['price']);
    $status = sanitize($_POST['status']);

    $sql = "UPDATE flights SET airline_name='$airline', source='$source', destination='$dest', departure_time='$dep', arrival_time='$arr', total_seats=$seats, seats_available=$avail, price=$price, status='$status' WHERE flight_id=$id";
    if (mysqli_query($conn, $sql)) { $_SESSION['success'] = 'Flight updated successfully!'; redirect('manage-flights.php'); }
    else { $_SESSION['error'] = 'Update failed.'; }
}
?>
<div class="admin-topbar"><h4 class="mb-0"><i class="bi bi-pencil-square me-2 text-accent"></i>Edit Flight – <?php echo $f['flight_number']; ?></h4></div>
<?php showAlert(); ?>
<div class="row justify-content-center"><div class="col-lg-8"><div class="flight-card">
    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Airline Name</label><input type="text" name="airline_name" class="form-control" required value="<?php echo $f['airline_name']; ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Flight Number</label><input type="text" class="form-control" value="<?php echo $f['flight_number']; ?>" disabled></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Source</label>
                <select name="source" class="form-select" required><?php foreach(['Delhi','Mumbai','Bangalore','Kolkata','Chennai','Hyderabad','Pune'] as $c): ?><option <?php echo $f['source']===$c?'selected':''; ?>><?php echo $c; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Destination</label>
                <select name="destination" class="form-select" required><?php foreach(['Delhi','Mumbai','Bangalore','Kolkata','Chennai','Hyderabad','Pune'] as $c): ?><option <?php echo $f['destination']===$c?'selected':''; ?>><?php echo $c; ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Departure</label><input type="datetime-local" name="departure_time" class="form-control" required value="<?php echo date('Y-m-d\TH:i', strtotime($f['departure_time'])); ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Arrival</label><input type="datetime-local" name="arrival_time" class="form-control" required value="<?php echo date('Y-m-d\TH:i', strtotime($f['arrival_time'])); ?>"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Total Seats</label><input type="number" name="total_seats" class="form-control" required value="<?php echo $f['total_seats']; ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Available Seats</label><input type="number" name="seats_available" class="form-control" required value="<?php echo $f['seats_available']; ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Price (₹)</label><input type="number" name="price" class="form-control" required step="0.01" value="<?php echo $f['price']; ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select name="status" class="form-select"><?php foreach(['Scheduled','Delayed','Cancelled','Completed'] as $s): ?><option <?php echo $f['status']===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select>
        </div>
        <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Update Flight</button>
    </form>
</div></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
