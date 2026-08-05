<?php
$isSubDir = true;
if (!defined('IS_ADMIN_PANEL')) define('IS_ADMIN_PANEL', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Security.php';

emitSecurityHeaders();
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Admin Panel' : 'Admin Panel'; ?> – AeroBook</title>
    <script>
        // Dark mode is the only theme — pin it before CSS paints (no flash).
        document.documentElement.classList.add('js');
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://db.onlinewebfonts.com/c/04e6981992c0e2e7642af2074ebe3901?family=Helvetica+Now+Display+Bold" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php $cssVer = filemtime(__DIR__ . '/../../css/aerobook.css'); ?>
    <link href="<?php echo asset('css/style.css') . '?v=' . filemtime(__DIR__ . '/../../css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/aerobook.css') . '?v=' . $cssVer; ?>" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <button class="btn btn-primary d-lg-none position-fixed bottom-0 end-0 m-3 z-3 shadow" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
        <i class="bi bi-list"></i>
    </button>
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>
    <aside class="admin-sidebar collapse d-lg-block" id="adminSidebar">
        <div class="sidebar-pinned">
            <div class="sidebar-brand d-flex justify-content-between align-items-start">
                <div>
                    <h4><i class="bi bi-airplane-engines me-2" style="color: #F4CEFF;"></i><span class="text-white">Aero</span><span style="color: #F4CEFF;">Book</span></h4>
                    <small>Operations Center</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light d-lg-none sidebar-close" data-bs-toggle="collapse" data-bs-target="#adminSidebar" aria-label="Close sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="pinned-dashboard-link <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>"><i class="bi bi-speedometer2"></i>Operations Dashboard</a>
            <hr class="text-muted opacity-25">
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-label">Flight Operations</li>
            <li><a href="<?php echo BASE_URL; ?>admin/add-flight.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='add-flight.php'?'active':''; ?>"><i class="bi bi-plus-circle"></i>Add Flight</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-flights.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-flights.php'?'active':''; ?>"><i class="bi bi-airplane"></i>Manage Flights</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-seats.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-seats.php'?'active':''; ?>"><i class="bi bi-grid-3x3"></i>Seat Availability</a></li>
            <li><hr class="text-muted opacity-25"></li>
            <li class="sidebar-label">Analytics & Reports</li>
            <li><a href="<?php echo BASE_URL; ?>admin/analytics.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='analytics.php'?'active':''; ?>"><i class="bi bi-graph-up"></i>Airline Analytics</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/route-analytics.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='route-analytics.php'?'active':''; ?>"><i class="bi bi-signpost-2"></i>Route Analytics</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='reports.php'?'active':''; ?>"><i class="bi bi-download"></i>Download Reports</a></li>
            <li><hr class="text-muted opacity-25"></li>
            <li class="sidebar-label">Management</li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-bookings.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-bookings.php'?'active':''; ?>"><i class="bi bi-journal-bookmark"></i>Manage Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-users.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-users.php'?'active':''; ?>"><i class="bi bi-people"></i>Manage Users</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-contacts.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-contacts.php'?'active':''; ?>"><i class="bi bi-headset"></i>Support Queries</a></li>
            <li><hr class="text-muted opacity-25"></li>
            <li class="sidebar-label">System</li>
            <li><a href="<?php echo BASE_URL; ?>admin/activity-log.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='activity-log.php'?'active':''; ?>"><i class="bi bi-clock-history"></i>Activity Log</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/diagnostics.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='diagnostics.php'?'active':''; ?>"><i class="bi bi-activity"></i>System Status</a></li>
            <li><hr class="text-muted opacity-25"></li>
            <li class="sidebar-label">External Data</li>
            <li><a href="<?php echo BASE_URL; ?>admin/aviation-sync.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='aviation-sync.php'?'active':''; ?>"><i class="bi bi-cloud-arrow-down"></i>Data Synchronization</a></li>
            <li><hr class="text-muted opacity-25"></li>
            <li><a href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i>View Website</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/logout.php" class="text-danger"><i class="bi bi-box-arrow-left"></i>Logout</a></li>
        </ul>
    </aside>
    <div class="admin-content">
