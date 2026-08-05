<?php
$pageTitle = 'Support Queries';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Paginator.php';

if (isset($_GET['delete'])) {
    if (!isset($_GET['token']) || !validateDeleteToken($_GET['token'])) {
        setFlash('error', 'Invalid request token.');
        redirect(BASE_URL . 'admin/manage-contacts.php');
    }
    $del_id = intval($_GET['delete']);
    deleteById('contacts', $del_id);
    logAdminAction($_SESSION['admin_id'], 'delete_contact', "Deleted contact query ID $del_id");
    setFlash('success', 'Query deleted.');
    redirect(BASE_URL . 'admin/manage-contacts.php');
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];
$types = '';
if ($search) { $s = "%$search%"; $sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)"; $params = [$s, $s, $s, $s]; $types .= 'ssss'; }
$sql .= " ORDER BY created_at DESC";

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
$queries = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-headset me-2 text-primary"></i>Support Queries</h5>
        <a href="reports.php?export=1&type=contacts&from=<?php echo date('Y-m-d', strtotime('-1 year')); ?>&to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</a>
    </div>
    <div class="card-body p-3 bg-light border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, subject, message..." value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-2"><a href="manage-contacts.php" class="btn btn-outline-secondary btn-sm w-100">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 contacts-table">
                <thead class="table-light">
                    <tr><th>Date</th><th>From</th><th>Subject</th><th>Message</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($queries) === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No queries found.</td></tr>
                    <?php else: while($q = mysqli_fetch_assoc($queries)): ?>
                    <tr>
                        <td class="small"><?php echo formatDateTime($q['created_at']); ?></td>
                        <td><strong><?php echo htmlspecialchars($q['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($q['email']); ?></small></td>
                        <td><span class="fw-semibold"><?php echo htmlspecialchars($q['subject']); ?></span></td>
                        <td><div class="contact-message text-truncate" style="max-width:300px; font-size:0.85rem;" title="<?php echo htmlspecialchars($q['message']); ?>"><?php echo htmlspecialchars($q['message']); ?></div></td>
                        <td>
                            <a href="<?php echo deleteLink('admin/manage-contacts.php', 'delete', $q['id'], 'Delete'); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this query?')" aria-label="Delete query from <?php echo htmlspecialchars($q['name']); ?>"><i class="bi bi-trash"></i></a>
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
