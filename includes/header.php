<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AeroBook – Smart, Fast and Easy Flight Booking Platform. Search flights, book tickets, and manage your reservations seamlessly.">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' – ' . SITE_TAGLINE; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
                <i class="bi bi-airplane-engines me-2 brand-icon" style="color: #00d4ff;"></i>
                <span class="brand-text text-white">Aero<span style="color: #00d4ff;">Book</span></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>search-flights.php">
                            <i class="bi bi-search me-1"></i>Search Flights
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>about.php">
                            <i class="bi bi-info-circle me-1"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>contact.php">
                            <i class="bi bi-envelope me-1"></i>Contact
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <div class="d-flex align-items-center gap-3">
                            <a href="<?php echo BASE_URL; ?>my-bookings.php" class="text-white text-decoration-none d-none d-md-block">
                                <i class="bi bi-ticket-perforated me-1"></i> My Bookings
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-link p-0 border-0 text-white d-flex align-items-center gap-2 dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown">
                                    <div class="profile-avatar bg-info text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                    </div>
                                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                                    <li class="px-3 py-2 border-bottom mb-2">
                                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>user-dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i>My Bookings</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>profile.php"><i class="bi bi-person-gear me-2"></i>Profile Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-secondary btn-sm fw-bold">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-accent btn-sm">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->

