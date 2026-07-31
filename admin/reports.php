<?php
// ─── Handle CSV export BEFORE any HTML output ───
if (isset($_GET['export']) && isset($_GET['type'])) {
    $isSubDir = true;
    define('IS_ADMIN_PANEL', true);
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/functions.php';
    require_once __DIR__ . '/../../includes/Security.php';
    require_once __DIR__ . '/../../includes/helpers.php';
    emitSecurityHeaders();
    requireAdmin();

    $type = preg_match('/^(bookings|flights|revenue|passengers|routes)$/', $_GET['type']) ? $_GET['type'] : 'bookings';
    $rawFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-1 year'));
    $rawTo = $_GET['to'] ?? date('Y-m-d');
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-d', strtotime('-1 year'));
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo) ? $rawTo : date('Y-m-d');
    $fromEsc = mysqli_real_escape_string($conn, $from);
    $toEsc = mysqli_real_escape_string($conn, $to);

    if (ob_get_length()) ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aerobook-' . $type . '-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($type === 'bookings') {
        fputcsv($output, ['Ref', 'Passenger', 'Age', 'Gender', 'Flight', 'Airline', 'Route', 'Seat', 'Travel Date', 'Status', 'Booking Date']);
        $r = mysqli_query($conn, "SELECT b.*, f.flight_number, f.airline_name, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE DATE(b.booking_date) BETWEEN '$fromEsc' AND '$toEsc' ORDER BY b.booking_date DESC");
        while ($row = mysqli_fetch_assoc($r)) fputcsv($output, [$row['booking_ref'], $row['passenger_name'], $row['age'], $row['gender'], $row['flight_number'], $row['airline_name'], $row['source'].'->'.$row['destination'], $row['seat_number'], $row['travel_date'], $row['booking_status'], $row['booking_date']]);
    } elseif ($type === 'flights') {
        fputcsv($output, ['Flight #', 'Airline', 'Route', 'Departure', 'Arrival', 'Total Seats', 'Available', 'Price', 'Status']);
        $r = mysqli_query($conn, "SELECT * FROM flights WHERE DATE(departure_time) BETWEEN '$fromEsc' AND '$toEsc' ORDER BY departure_time ASC");
        while ($row = mysqli_fetch_assoc($r)) fputcsv($output, [$row['flight_number'], $row['airline_name'], $row['source'].'->'.$row['destination'], $row['departure_time'], $row['arrival_time'], $row['total_seats'], $row['seats_available'], $row['price'], $row['status']]);
    } elseif ($type === 'revenue') {
        fputcsv($output, ['Month', 'Bookings', 'Revenue']);
        $r = mysqli_query($conn, "SELECT DATE_FORMAT(b.booking_date, '%Y-%m') as month, COUNT(*) as bookings, SUM(f.price) as revenue FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.booking_status='Confirmed' AND DATE(b.booking_date) BETWEEN '$fromEsc' AND '$toEsc' GROUP BY month ORDER BY month");
        while ($row = mysqli_fetch_assoc($r)) fputcsv($output, [$row['month'], $row['bookings'], $row['revenue']]);
    } elseif ($type === 'passengers') {
        fputcsv($output, ['Ref', 'Passenger', 'Age', 'Gender', 'Flight', 'Route', 'Seat', 'Travel Date', 'Status']);
        $r = mysqli_query($conn, "SELECT b.*, f.flight_number, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE DATE(b.travel_date) BETWEEN '$fromEsc' AND '$toEsc' ORDER BY b.travel_date DESC");
        while ($row = mysqli_fetch_assoc($r)) fputcsv($output, [$row['booking_ref'], $row['passenger_name'], $row['age'], $row['gender'], $row['flight_number'], $row['source'].'->'.$row['destination'], $row['seat_number'], $row['travel_date'], $row['booking_status']]);
    } elseif ($type === 'routes') {
        fputcsv($output, ['Route', 'Bookings', 'Revenue', 'Avg Fare']);
        $r = mysqli_query($conn, "SELECT f.source, f.destination, COUNT(*) as bookings, SUM(f.price) as revenue, ROUND(AVG(f.price),2) as avg_fare FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.booking_status='Confirmed' AND DATE(b.booking_date) BETWEEN '$fromEsc' AND '$toEsc' GROUP BY f.source, f.destination ORDER BY revenue DESC");
        while ($row = mysqli_fetch_assoc($r)) fputcsv($output, [$row['source'].'->'.$row['destination'], $row['bookings'], $row['revenue'], $row['avg_fare']]);
    }
    fclose($output);
    logAdminAction($_SESSION['admin_id'], 'export_report', "Exported $type report ($from to $to)");
    exit;
}

// ─── Normal page rendering ───
$pageTitle = 'Downloadable Reports';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../../includes/helpers.php';
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="mb-0 fw-bold"><i class="bi bi-download me-2 text-primary"></i>Downloadable Reports</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php
    $reportTypes = [
        'bookings' => ['icon' => 'bi-journal-bookmark', 'label' => 'Bookings Report', 'desc' => 'All booking records with passenger, flight, and status details.'],
        'flights' => ['icon' => 'bi-airplane', 'label' => 'Flights Report', 'desc' => 'Flight schedule information including capacity, pricing and status.'],
        'revenue' => ['icon' => 'bi-currency-rupee', 'label' => 'Revenue Report', 'desc' => 'Monthly revenue breakdown with booking counts.'],
        'passengers' => ['icon' => 'bi-people', 'label' => 'Passengers Report', 'desc' => 'All passenger details with flight and seat information.'],
        'routes' => ['icon' => 'bi-signpost-2', 'label' => 'Routes Report', 'desc' => 'Route performance data with booking and revenue totals.'],
    ];
    foreach ($reportTypes as $key => $rt):
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-light p-3 d-flex align-items-center justify-content-center" style="width:50px;height:50px;"><i class="bi <?php echo $rt['icon']; ?> text-primary fs-4"></i></div>
                    <div><h6 class="fw-bold mb-0"><?php echo $rt['label']; ?></h6><small class="text-muted">CSV format</small></div>
                </div>
                <p class="small text-muted mb-3"><?php echo $rt['desc']; ?></p>
                <form method="GET" class="row g-2" action="reports.php">
                    <input type="hidden" name="export" value="1">
                    <input type="hidden" name="type" value="<?php echo $key; ?>">
                    <div class="col-5"><input type="date" name="from" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('-1 year')); ?>"></div>
                    <div class="col-5"><input type="date" name="to" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="col-2"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-download"></i></button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
