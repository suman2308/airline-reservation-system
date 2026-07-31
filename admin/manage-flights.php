<?php
$pageTitle = 'Manage Flights';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Paginator.php';

if (isset($_GET['delete'])) {
    if (!isset($_GET['token']) || !validateDeleteToken($_GET['token'])) {
        setFlash('error', 'Invalid request token.');
        redirect(BASE_URL . 'admin/manage-flights.php');
    }
    $del_id = intval($_GET['delete']);
    if (deleteById('flights', $del_id, 'flight_id')) {
        logAdminAction($_SESSION['admin_id'], 'delete_flight', "Deleted flight ID $del_id");
        setFlash('success', 'Flight deleted successfully.');
    } else {
        setFlash('error', 'Failed to delete flight. It might have active bookings.');
    }
    redirect(BASE_URL . 'admin/manage-flights.php');
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$airlineFilter = trim($_GET['airline'] ?? '');
$routeFilter = trim($_GET['route'] ?? '');
$regionFilter = trim($_GET['region'] ?? '');

$sql = "SELECT * FROM flights WHERE 1=1";
$params = [];
$types = '';
if ($search) { $sql .= " AND (flight_number LIKE ? OR airline_name LIKE ? OR source LIKE ? OR destination LIKE ?)"; $s = "%$search%"; $params = array_merge($params, [$s, $s, $s, $s]); $types .= 'ssss'; }
if ($statusFilter) { $sql .= " AND status = ?"; $params[] = $statusFilter; $types .= 's'; }
if ($airlineFilter) { $sql .= " AND airline_name = ?"; $params[] = $airlineFilter; $types .= 's'; }
if ($routeFilter) { $parts = explode('-', $routeFilter); if (count($parts) === 2) { $src = trim($parts[0]); $dst = trim($parts[1]); $sql .= " AND source = ? AND destination = ?"; $params[] = $src; $params[] = $dst; $types .= 'ss'; } }
if ($regionFilter === 'domestic') {
    $inPlaceholders = implode(',', array_fill(0, count(INDIAN_CITIES), '?'));
    $sql .= " AND source IN ({$inPlaceholders}) AND destination IN ({$inPlaceholders})";
    $params = array_merge($params, INDIAN_CITIES, INDIAN_CITIES);
    $types .= str_repeat('s', count(INDIAN_CITIES) * 2);
} elseif ($regionFilter === 'international') {
    $inPlaceholders = implode(',', array_fill(0, count(INDIAN_CITIES), '?'));
    $sql .= " AND (source NOT IN ({$inPlaceholders}) OR destination NOT IN ({$inPlaceholders}))";
    $params = array_merge($params, INDIAN_CITIES, INDIAN_CITIES);
    $types .= str_repeat('s', count(INDIAN_CITIES) * 2);
}
$sql .= " ORDER BY departure_time ASC";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as c FROM ($sql) as sub";
$countStmt = mysqli_prepare($conn, $countSql);
if (!empty($params)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'];
mysqli_stmt_close($countStmt);

$paginator = new Paginator($totalRows, 15);
$sql .= " LIMIT {$paginator->perPage()} OFFSET {$paginator->offset()}";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$flights_result = mysqli_stmt_get_result($stmt);
$flights = [];
while ($f = mysqli_fetch_assoc($flights_result)) $flights[] = $f;
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt);
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-airplane me-2 text-primary"></i>All Flights</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="add-flight.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Flight</a>
            <a href="reports.php?export=1&type=flights" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</a>
        </div>
    </div>
    <div class="card-body p-3 bg-light border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search flight #, airline, route..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php statusOptions($statusFilter); ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="airline" class="form-select form-select-sm">
                    <option value="">All Airlines</option>
                    <?php
                    $airlines = mysqli_query($conn, "SELECT DISTINCT airline_name FROM flights ORDER BY airline_name");
                    while ($a = mysqli_fetch_assoc($airlines)):
                        $sel = $airlineFilter === $a['airline_name'] ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($a['airline_name']) . "\" $sel>" . htmlspecialchars($a['airline_name']) . "</option>";
                    endwhile;
                    ?>
                </select>
            </div>
<div class="col-md-2">
                <select name="region" class="form-select form-select-sm">
                    <option value="">All Regions</option>
                    <option value="domestic" <?php echo $regionFilter === 'domestic' ? 'selected' : ''; ?>>🇮🇳 Domestic</option>
                    <option value="international" <?php echo $regionFilter === 'international' ? 'selected' : ''; ?>>🌍 International</option>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-2"><a href="manage-flights.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Flight #</th><th>Airline</th><th>Route</th><th>Departure</th><th>Occupancy</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($flights)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No flights found matching your criteria.</td></tr>
                    <?php else: foreach($flights as $f):
                        $booked = $f['total_seats'] - $f['seats_available'];
                        $occ = $f['total_seats'] > 0 ? round($booked / $f['total_seats'] * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><span class="text-accent fw-bold"><?php echo htmlspecialchars($f['flight_number']); ?></span></td>
                        <td><?php echo htmlspecialchars($f['airline_name']); ?></td>
                        <td><?php echo htmlspecialchars($f['source'] . ' → ' . $f['destination']); ?></td>
                        <td><?php echo formatDateTime($f['departure_time']); ?></td>
                        <td style="min-width:120px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar <?php echo $occ >= 80 ? 'bg-success' : ($occ >= 50 ? 'bg-warning' : 'bg-danger'); ?>" style="width:<?php echo $occ; ?>%"></div>
                                </div>
                                <small class="fw-bold <?php echo $occ >= 80 ? 'text-success' : ($occ >= 50 ? 'text-warning' : 'text-danger'); ?>"><?php echo $occ; ?>%</small>
                            </div>
                            <small class="text-muted"><?php echo $booked; ?>/<?php echo $f['total_seats']; ?> seats</small>
                        </td>
                        <td><?php echo formatPrice($f['price']); ?></td>
                        <td><?php echo statusBadge($f['status']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit-flight.php?id=<?php echo $f['flight_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit flight" aria-label="Edit flight <?php echo $f['flight_number']; ?>"><i class="bi bi-pencil"></i></a>
                                <a href="manage-seats.php?flight_id=<?php echo $f['flight_id']; ?>" class="btn btn-sm btn-outline-info" title="View seats" aria-label="View seats for <?php echo $f['flight_number']; ?>"><i class="bi bi-grid-3x3"></i></a>
                                <a href="<?php echo deleteLink('admin/manage-flights.php', 'delete', $f['flight_id'], 'Delete'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete flight permanently?')" title="Delete flight" aria-label="Delete flight <?php echo $f['flight_number']; ?>"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo $paginator->render(); ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
