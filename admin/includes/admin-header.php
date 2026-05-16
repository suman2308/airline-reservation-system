<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
if (!isAdminLoggedIn()) { redirect(BASE_URL . 'admin/login.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | Admin Panel' : 'Admin Panel'; ?> – AeroBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <!-- Admin Sidebar Mobile Toggle -->
    <button class="btn btn-primary d-lg-none position-fixed bottom-0 end-0 m-3 z-3 shadow" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
        <i class="bi bi-list"></i>
    </button>

    <aside class="admin-sidebar collapse d-lg-block" id="adminSidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-airplane-engines me-2" style="color: #00d4ff;"></i><span class="text-white">Aero</span><span style="color: #00d4ff;">Book</span></h4>
            <small>Admin Panel</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/add-flight.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='add-flight.php'?'active':''; ?>"><i class="bi bi-plus-circle"></i>Add Flight</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-flights.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-flights.php'?'active':''; ?>"><i class="bi bi-airplane"></i>Manage Flights</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-bookings.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-bookings.php'?'active':''; ?>"><i class="bi bi-journal-bookmark"></i>Manage Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-users.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-users.php'?'active':''; ?>"><i class="bi bi-people"></i>Manage Users</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-contacts.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-contacts.php'?'active':''; ?>"><i class="bi bi-headset"></i>Support Queries</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/manage-seats.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='manage-seats.php'?'active':''; ?>"><i class="bi bi-grid-3x3"></i>Seat Availability</a></li>
            <li><hr class="text-muted opacity-25"></li>

            <li><a href="<?php echo BASE_URL; ?>index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i>View Website</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/logout.php" class="text-danger"><i class="bi bi-box-arrow-left"></i>Logout</a></li>
        </ul>
    </aside>
    <div class="admin-content">

