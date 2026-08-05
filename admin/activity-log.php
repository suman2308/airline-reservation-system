<?php
$pageTitle = 'Activity Log';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$actionFilter = $_GET['action'] ?? null;
$dateFrom = $_GET['from'] ?? null;
$dateTo = $_GET['to'] ?? null;

$entries = getAdminActivityLog($limit, $offset, $actionFilter, $dateFrom, $dateTo);

// Get total count for pagination
global $conn;
$countSql = "SELECT COUNT(*) as c FROM admin_activity_log WHERE 1=1";
$params = [];
$types = '';
if ($actionFilter) { $countSql .= ' AND action = ?'; $params[] = $actionFilter; $types .= 's'; }
if ($dateFrom) { $countSql .= ' AND created_at >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo) { $countSql .= ' AND created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; $types .= 's'; }
$stmt = mysqli_prepare($conn, $countSql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$totalEntries = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
mysqli_stmt_close($stmt);
$totalPages = max(1, ceil($totalEntries / $limit));

// Get distinct actions for filter
$actions = mysqli_query($conn, "SELECT DISTINCT action FROM admin_activity_log ORDER BY action ASC");
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Admin Activity Log</h5>
                <span class="small text-muted"><?php echo $totalEntries; ?> total entries</span>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php while ($a = mysqli_fetch_assoc($actions)): ?>
                    <option value="<?php echo htmlspecialchars($a['action']); ?>" <?php echo $actionFilter === $a['action'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['action']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small fw-bold">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dateFrom ?? ''); ?>"></div>
            <div class="col-md-3"><label class="form-label small fw-bold">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dateTo ?? ''); ?>"></div>
            <div class="col-md-3"><button type="submit" class="btn btn-accent btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Time</th><th>Admin</th><th>Action</th><th>Details</th><th>IP</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No activity entries found.</td></tr>
                    <?php else: foreach ($entries as $e): ?>
                    <tr>
                        <td class="small"><?php echo formatDateTime($e['created_at']); ?></td>
                        <td><span class="fw-semibold"><?php echo htmlspecialchars($e['username']); ?></span></td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($e['action']); ?></span></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($e['details'] ?? ''); ?></small></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($e['ip_address'] ?? ''); ?></small></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white p-3">
        <nav><ul class="pagination pagination-sm mb-0 justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?><?php echo $actionFilter ? '&action='.urlencode($actionFilter) : ''; ?><?php echo $dateFrom ? '&from='.urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&to='.urlencode($dateTo) : ''; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
