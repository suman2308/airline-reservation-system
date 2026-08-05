<?php
/**
 * AeroBook – Aviationstack Sync Admin Panel
 *
 * Provides administrators with:
 * - API connectivity testing
 * - Per-endpoint synchronization buttons
 * - Sync history log viewing
 * - Record counts for each synced table
 */

$pageTitle = 'External Data Sync';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/../includes/AviationSyncService.php';

$syncService = new AviationSyncService();
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

// Handle sync actions
if ($action === 'test' && isset($_GET['token'])) {
    if (!validateCSRFToken($_GET['token'])) {
        $message = 'Invalid request token. Please try again.';
        $messageType = 'error';
    } else {
        $test = $syncService->testConnection();
        if ($test['success']) {
            $message = 'Aviationstack API: ' . $test['message'];
            $messageType = 'success';
        } else {
            $message = 'Connection failed: ' . $test['message'];
            $messageType = 'error';
        }
    }
}

$validSyncs = ['airports', 'airlines', 'aircraft_types', 'airplanes', 'countries', 'flights'];
if (in_array($action, $validSyncs, true) && isset($_GET['token'])) {
    if (!validateCSRFToken($_GET['token'])) {
        $message = 'Invalid request token. Please try again.';
        $messageType = 'error';
    } else {
        $method = 'sync' . ucfirst($action);
        $report = $syncService->$method();

        if ($report['success']) {
            $message = "{$action} sync completed: "
                . "{$report['added']} added, {$report['updated']} updated, "
                . "{$report['skipped']} skipped, {$report['failed']} failed. "
                . "({$report['duration_ms']}ms)";
            $messageType = 'success';
            logAdminAction($_SESSION['admin_id'], 'aviation_sync',
                "Synced {$action}: {$report['added']} added, {$report['updated']} updated");
        } else {
            $message = "{$action} sync failed: " . ($report['error'] ?? 'Unknown error');
            $messageType = 'error';
        }
    }
}

$statuses = $syncService->getAllSyncStatus();
$csrfToken = generateCSRFToken();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-down me-2 text-primary"></i>External Data Synchronization</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?php echo defined('AVIATIONSTACK_API_KEY') && AVIATIONSTACK_API_KEY ? 'success' : 'danger'; ?> fs-6 px-3 py-2">
                        <i class="bi bi-key me-1"></i>
                        API Key: <?php echo defined('AVIATIONSTACK_API_KEY') && AVIATIONSTACK_API_KEY ? 'Configured' : 'Not Configured'; ?>
                    </span>
                    <a href="?action=test&token=<?php echo urlencode($csrfToken); ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-wifi me-1"></i>Test Connection
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>-fill me-2"></i>
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Provider Status -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white p-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-accent"></i>Provider Status</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data Source</th>
                        <th class="text-end">Records</th>
                        <th>Last Sync</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $syncEndpoints = [
                        'airports' => ['label' => 'Airports', 'icon' => 'bi-building', 'source' => 'Aviationstack /v1/airports'],
                        'airlines' => ['label' => 'Airlines', 'icon' => 'bi-airplane', 'source' => 'Aviationstack /v1/airlines'],
                        'aircraft_types' => ['label' => 'Aircraft Types', 'icon' => 'bi-gear', 'source' => 'Aviationstack /v1/aircraft_types'],
                        'airplanes' => ['label' => 'Aircraft Registry', 'icon' => 'bi-motherboard', 'source' => 'Aviationstack /v1/airplanes'],
                        'countries' => ['label' => 'Countries', 'icon' => 'bi-globe2', 'source' => 'Aviationstack /v1/countries'],
                        'flights' => ['label' => 'Live Flights', 'icon' => 'bi-crosshair', 'source' => 'Aviationstack /v1/flights'],
                    ];

                    foreach ($syncEndpoints as $ep => $info):
                        $status = $statuses[$ep] ?? null;
                        $lastSync = $status['last_sync'] ?? null;
                        $recordCount = $status['record_count'] ?? 0;
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?php echo $info['icon']; ?> text-accent"></i>
                                <div>
                                    <div class="fw-semibold"><?php echo $info['label']; ?></div>
                                    <small class="text-muted"><?php echo $info['source']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold"><?php echo number_format($recordCount); ?></span>
                        </td>
                        <td>
                            <?php if ($lastSync): ?>
                                <small><?php echo formatDateTime($lastSync['created_at']); ?></small>
                                <br>
                                <small class="text-muted"><?php echo $lastSync['duration_ms']; ?>ms</small>
                            <?php else: ?>
                                <span class="text-muted small">Never synced</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($lastSync): ?>
                                <?php if ($lastSync['status'] === 'success'): ?>
                                    <span class="badge-status bg-success">Success</span>
                                <?php else: ?>
                                    <span class="badge-status bg-danger">Failed</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">
                                    +<?php echo $lastSync['records_added']; ?>
                                    ~<?php echo $lastSync['records_updated']; ?>
                                    ⚡<?php echo $lastSync['records_skipped']; ?>
                                </small>
                            <?php else: ?>
                                <span class="badge-status bg-secondary">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="?action=<?php echo $ep; ?>&token=<?php echo urlencode($csrfToken); ?>"
                               class="btn btn-accent btn-sm"
                               onclick="return confirm('Start syncing <?php echo $ep; ?> from Aviationstack?')">
                                <i class="bi bi-arrow-repeat me-1"></i>Sync Now
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sync History -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-accent"></i>Recent Sync Activity</h6>
        <small class="text-muted">Last 20 entries</small>
    </div>
    <div class="card-body p-0">
        <?php
        global $conn;
        // api_sync_logs only exists after importing database/aviationstack.sql.
        // PHP 8.1 mysqli throws rather than returning false for missing tables,
        // so fall back to the empty state instead of crashing the page.
        try {
            $logResult = mysqli_query($conn,
                "SELECT * FROM api_sync_logs ORDER BY created_at DESC LIMIT 20"
            );
        } catch (mysqli_sql_exception $e) {
            $logResult = false;
        }
        ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th class="text-end">Added</th>
                        <th class="text-end">Updated</th>
                        <th class="text-end">Skipped</th>
                        <th class="text-end">Duration</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logResult && mysqli_num_rows($logResult) > 0):
                        while ($log = mysqli_fetch_assoc($logResult)): ?>
                    <tr>
                        <td class="small"><?php echo formatDateTime($log['created_at']); ?></td>
                        <td><span class="fw-semibold"><?php echo htmlspecialchars($log['endpoint']); ?></span></td>
                        <td>
                            <?php if ($log['status'] === 'success'): ?>
                                <span class="badge-status bg-success">Success</span>
                            <?php else: ?>
                                <span class="badge-status bg-danger">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?php echo number_format($log['records_added']); ?></td>
                        <td class="text-end"><?php echo number_format($log['records_updated']); ?></td>
                        <td class="text-end"><?php echo number_format($log['records_skipped']); ?></td>
                        <td class="text-end small text-muted"><?php echo $log['duration_ms']; ?>ms</td>
                        <td>
                            <?php if ($log['error_message']): ?>
                                <small class="text-danger" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                    <?php echo htmlspecialchars(substr($log['error_message'], 0, 50)); ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile;
                    else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-2"></i>No sync activity yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-info d-flex align-items-center gap-2 py-2" role="alert">
        <i class="bi bi-info-circle-fill me-1"></i>
        <small>
            <strong>Free Plan Note:</strong> Aviationstack's free plan provides limited data.
            Imported data enriches AeroBook's database — it never replaces existing flight schedules,
            bookings, or passenger information. AeroBook remains fully functional even if
            Aviationstack is offline.
        </small>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
