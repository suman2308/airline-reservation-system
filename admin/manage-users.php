<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Paginator.php';

if (isset($_GET['delete'])) {
    if (!isset($_GET['token']) || !validateDeleteToken($_GET['token'])) {
        setFlash('error', 'Invalid request token.');
        redirect(BASE_URL . 'admin/manage-users.php');
    }
    $del_id = intval($_GET['delete']);
    if (countWhere('bookings', 'user_id', (string)$del_id) > 0) {
        setFlash('error', 'Cannot delete user with active bookings.');
    } else {
        if (deleteById('users', $del_id)) {
            logAdminAction($_SESSION['admin_id'], 'delete_user', "Deleted user ID $del_id");
            setFlash('success', 'User deleted successfully.');
        } else {
            setFlash('error', 'Failed to delete user.');
        }
    }
    redirect(BASE_URL . 'admin/manage-users.php');
}

$search = trim($_GET['search'] ?? '');
$baseSql = "SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.booking_status='Confirmed') as booking_count,
        (SELECT COALESCE(SUM(f.price), 0) FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.user_id=u.id AND b.booking_status='Confirmed') as total_spent
        FROM users u WHERE 1=1";
$params = [];
$types = '';
if ($search) { $s = "%$search%"; $baseSql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)"; $params = [$s, $s, $s]; $types = 'sss'; }
$baseSql .= " ORDER BY u.created_at DESC";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as c FROM ($baseSql) as sub";
$countStmt = mysqli_prepare($conn, $countSql);
if (!empty($params)) mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'];
mysqli_stmt_close($countStmt);

$paginator = new Paginator($totalRows, 20);
$sql = $baseSql . " LIMIT {$paginator->perPage()} OFFSET {$paginator->offset()}";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>System Users</h5>
        <span class="badge bg-secondary fs-6"><?php echo countWhere('users'); ?> Total</span>
    </div>
    <div class="card-body p-3 bg-light border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, phone..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-2"><a href="manage-users.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Last Active</th><th class="text-end">Bookings</th><th class="text-end">Spent</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($users) === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php else: while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong><?php if ($u['email_verified_at']): ?><i class="bi bi-check-circle-fill text-success ms-1 small"></i><?php endif; ?></td>
                        <td><small><?php echo htmlspecialchars($u['email']); ?></small></td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td><?php echo formatDate($u['created_at']); ?></td>
                        <td><small class="text-muted"><?php echo $u['last_login_at'] ? formatDate($u['last_login_at']) : 'Never'; ?></small></td>
                        <td class="text-end"><span class="badge bg-primary"><?php echo $u['booking_count']; ?></span></td>
                        <td class="text-end fw-bold text-accent"><?php echo formatPrice($u['total_spent']); ?></td>
                        <td>
                            <?php if ($u['booking_count'] == 0): ?>
                            <a href="<?php echo deleteLink('admin/manage-users.php', 'delete', $u['id'], 'Delete'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')" aria-label="Delete user <?php echo htmlspecialchars($u['name']); ?>"><i class="bi bi-trash"></i></a>
                            <?php else: ?>
                            <span class="text-muted small">Has bookings</span>
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
