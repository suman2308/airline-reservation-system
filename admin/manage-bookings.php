<?php
$pageTitle = 'Manage Bookings';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Paginator.php';

if (isset($_GET['cancel'])) {
    if (!isset($_GET['token']) || !validateDeleteToken($_GET['token'])) {
        setFlash('error', 'Invalid request token.');
        redirect(BASE_URL . 'admin/manage-bookings.php');
    }
    $cancel_id = intval($_GET['cancel']);
    if (cancelBooking($cancel_id)) {
        logAdminAction($_SESSION['admin_id'], 'cancel_booking', "Cancelled booking ID $cancel_id");
        setFlash('success', 'Booking cancelled and seat restored.');
    } else {
        setFlash('error', 'Unable to cancel this booking.');
    }
    redirect(BASE_URL . 'admin/manage-bookings.php');
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$routeFilter = trim($_GET['route'] ?? '');

$sql = "SELECT b.*, f.flight_number, f.airline_name, f.source, f.destination, f.price, u.email as user_email FROM bookings b JOIN flights f ON b.flight_id=f.flight_id JOIN users u ON b.user_id=u.id WHERE 1=1";
$params = [];
$types = '';
if ($search) { $s = "%$search%"; $sql .= " AND (b.booking_ref LIKE ? OR b.passenger_name LIKE ? OR f.flight_number LIKE ? OR u.email LIKE ?)"; $params = [$s, $s, $s, $s]; $types = 'ssss'; }
if ($statusFilter) { $sql .= " AND b.booking_status = ?"; $params[] = $statusFilter; $types .= 's'; }
$sql .= " ORDER BY b.booking_date DESC";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as c FROM ($sql) as sub";
$countStmt = mysqli_prepare($conn, $countSql);
if (!empty($params)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'];
mysqli_stmt_close($countStmt);

$paginator = new Paginator($totalRows, 20);
$sql .= " LIMIT {$paginator->perPage()} OFFSET {$paginator->offset()}";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$totalConfirmed = countWhere('bookings', 'booking_status', 'Confirmed');
$totalCancelled = countWhere('bookings', 'booking_status', 'Cancelled');
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-bookmark me-2 text-primary"></i>All Bookings</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-success fs-6"><?php echo $totalConfirmed; ?> Confirmed</span>
            <span class="badge bg-danger fs-6"><?php echo $totalCancelled; ?> Cancelled</span>
            <a href="reports.php?export=1&type=bookings" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</a>
        </div>
    </div>
    <div class="card-body p-3 bg-light border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search by ref, passenger, flight, email..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-2"><a href="manage-bookings.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Ref</th><th>Passenger</th><th>User Account</th><th>Flight</th><th>Seat</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($bookings) === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No bookings found.</td></tr>
                    <?php else: while($b = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td><span class="text-accent fw-bold"><?php echo $b['booking_ref']; ?></span></td>
                        <td><?php echo htmlspecialchars($b['passenger_name']); ?><br><small class="text-muted"><?php echo $b['gender'] . ', ' . $b['age']; ?></small></td>
                        <td><small><?php echo htmlspecialchars($b['user_email']); ?></small></td>
                        <td><?php echo $b['airline_name']; ?><br><small><?php echo $b['flight_number']; ?> · <?php echo $b['source']; ?>→<?php echo $b['destination']; ?></small></td>
                        <td><?php echo $b['seat_number']; ?></td>
                        <td><?php echo formatDate($b['travel_date']); ?></td>
                        <td class="fw-bold text-accent"><?php echo formatPrice($b['price']); ?></td>
                        <td><?php echo statusBadge($b['booking_status']); ?></td>
                        <td>
                            <?php if ($b['booking_status'] === 'Confirmed'): ?>
                                <a href="<?php echo deleteLink('admin/manage-bookings.php', 'cancel', $b['booking_id'], 'Cancel'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')" aria-label="Cancel booking <?php echo htmlspecialchars($b['booking_ref']); ?>">Cancel</a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo $paginator->render(); ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
