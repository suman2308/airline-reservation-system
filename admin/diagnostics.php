<?php
/**
 * AeroBook – Application Diagnostics (Admin Only)
 *
 * Provides system diagnostics for administrators.
 * Returns JSON when accessed via ?format=json, HTML otherwise.
 */

$pageTitle = 'System Diagnostics';
define('IS_ADMIN_PANEL', true);
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/helpers.php';

$format = $_GET['format'] ?? 'html';

// Collect diagnostics
$diagnostics = [];

// PHP Info
$diagnostics['php'] = [
    'version' => phpversion(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

// Database Info
$dbStatus = mysqli_ping($conn);
$queryCount = 0;
$dbSize = 0;
if ($dbStatus) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $queryCount = (int)$row['t'];
    }
    $result = mysqli_query($conn, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $dbSize = (float)$row['size_mb'];
    }
}

$diagnostics['database'] = [
    'connected' => $dbStatus ? 'Yes' : 'No',
    'name' => DB_NAME,
    'host' => DB_HOST,
    'tables' => $queryCount,
    'size_mb' => $dbSize,
];

// Application Stats
$diagnostics['application'] = [
    'environment' => IS_PRODUCTION ? 'Production' : 'Localhost',
    'base_url' => BASE_URL,
    'timezone' => date_default_timezone_get(),
    'server_time' => date('Y-m-d H:i:s'),
    'total_users' => countWhere('users'),
    'total_flights' => countWhere('flights'),
    'total_bookings' => countWhere('bookings'),
    'total_contacts' => countWhere('contacts'),
];

// Session Info (safe only)
$diagnostics['session'] = [
    'session_name' => session_name(),
    'session_save_path' => session_save_path() ?: 'default',
    'active_users' => isset($_SESSION['user_id']) ? 'Yes (User #' . $_SESSION['user_id'] . ')' : 'No',
    'active_admin' => isset($_SESSION['admin_id']) ? 'Yes (Admin #' . $_SESSION['admin_id'] . ')' : 'No',
];

// Server Info (safe only)
$diagnostics['server'] = [
    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
];

// Recent Logs
$logEntries = [];
$logFile = __DIR__ . '/../logs/' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    // Get last 10 entries
    $logEntries = array_slice($lines, -10);
}
$diagnostics['recent_logs'] = $logEntries;

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
    require_once __DIR__ . '/includes/admin-footer.php';
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-primary"></i>System Diagnostics</h5>
                <a href="?format=json" class="btn btn-sm btn-outline-secondary"><i class="bi bi-code-slash me-1"></i>View as JSON</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- PHP Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-code-square me-2 text-primary"></i>PHP Configuration</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php foreach ($diagnostics['php'] as $key => $value): ?>
                    <tr><td class="fw-semibold text-muted small"><?php echo htmlspecialchars($key); ?></td><td><?php echo htmlspecialchars($value); ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Database Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-database me-2 text-success"></i>Database</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php foreach ($diagnostics['database'] as $key => $value): ?>
                    <tr><td class="fw-semibold text-muted small"><?php echo htmlspecialchars($key); ?></td><td><?php echo htmlspecialchars((string)$value); ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Application Stats -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-info"></i>Application Stats</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php foreach ($diagnostics['application'] as $key => $value): ?>
                    <tr><td class="fw-semibold text-muted small"><?php echo htmlspecialchars($key); ?></td><td><?php echo htmlspecialchars((string)$value); ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Session Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Session</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php foreach ($diagnostics['session'] as $key => $value): ?>
                    <tr><td class="fw-semibold text-muted small"><?php echo htmlspecialchars($key); ?></td><td><?php echo htmlspecialchars((string)$value); ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Data Quality Issues -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-shield-exclamation me-2"></i>Data Quality Check</h6>
            </div>
            <div class="card-body p-0">
                <?php $issues = getDataQualityIssues(); ?>
                <?php if (!empty($issues)): ?>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Severity</th><th>Issue</th></tr></thead>
                    <tbody><?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><span class="badge bg-<?php echo $issue['type'] === 'critical' ? 'danger' : ($issue['type'] === 'error' ? 'warning' : 'info'); ?>"><?php echo $issue['type']; ?></span></td>
                        <td><small><?php echo htmlspecialchars($issue['message']); ?></small></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
                <?php else: ?><p class="text-muted p-3 mb-0 small"><i class="bi bi-check-circle text-success me-1"></i>All data quality checks passed.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="col-12 mt-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-secondary"></i>Recent Log Entries (Today)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($logEntries)): ?>
                <pre class="mb-0 p-3" style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;"><?php foreach ($logEntries as $line): ?><?php echo htmlspecialchars($line); ?><?php endforeach; ?></pre>
                <?php else: ?>
                <p class="text-muted p-3 mb-0">No log entries for today.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2 flex-wrap">
    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    <a href="?format=json" class="btn btn-outline-primary"><i class="bi bi-code-slash me-1"></i>Export as JSON</a>
    <a href="activity-log.php" class="btn btn-outline-info"><i class="bi bi-clock-history me-1"></i>Admin Activity Log</a>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
