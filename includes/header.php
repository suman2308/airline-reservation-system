<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Security.php';

// Emit security headers on every page
emitSecurityHeaders();

// Landing mode: set $landingNav = true before including the header
// (used by index.php to render the AeroBook marketing navigation)
// Bare mode: set $bareNav = true before including the header (used by
// About/Contact) to render the app navbar without the nav links.
$isLanding = !empty($landingNav);
$isBareNav = !empty($bareNav);

/**
 * Compact user menu for logged-in visitors: avatar-only trigger with a
 * dropdown containing exactly My Bookings, Notifications, and My Profile.
 * Rendered identically in the landing navbar and the app navbar.
 */
function renderUserMenuDropdown(): string
{
    $name = htmlspecialchars($_SESSION['user_name'] ?? 'Account');
    $initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
    $base = defined('BASE_URL') ? BASE_URL : '';

    return '
    <div class="dropdown">
        <button class="btn btn-link p-0 border-0 profile-menu-btn d-flex align-items-center gap-2 dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
            <div class="profile-avatar fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; font-size: 0.95rem;">' . $initial . '</div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
            <li><a class="dropdown-item py-2" href="' . $base . 'my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i>My Bookings</a></li>
            <li><a class="dropdown-item py-2" href="' . $base . 'notifications.php"><i class="bi bi-bell me-2"></i>Notifications</a></li>
            <li><a class="dropdown-item py-2" href="' . $base . 'profile.php"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
        </ul>
    </div>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AeroBook – Smart, Fast and Easy Flight Booking Platform. Search flights, book tickets, and manage your reservations seamlessly.">
    <meta name="robots" content="index, follow">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' – ' . SITE_TAGLINE; ?></title>
    <script>
        // Dark mode is the only theme — pin it before CSS paints so there's never
        // a flash of light mode. The .js class enables progressive enhancement.
        document.documentElement.classList.add('js');
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://db.onlinewebfonts.com/c/04e6981992c0e2e7642af2074ebe3901?family=Helvetica+Now+Display+Bold" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php
    // Cache-bust local stylesheets so CSS edits always show on reload.
    $cssVer = filemtime(__DIR__ . '/../css/aerobook.css');
    ?>
    <link href="<?php echo asset('css/style.css') . '?v=' . filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/aerobook.css') . '?v=' . $cssVer; ?>" rel="stylesheet">
</head>
<body class="<?php echo $isLanding ? 'landing-body' : ''; ?>">
    <!-- Skip to main content (accessibility) -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    <?php if ($isLanding): ?>
    <!-- ═══ AeroBook Landing Navigation ═══ -->
    <nav class="landing-nav" id="landingNav" aria-label="Main navigation">
        <div class="landing-nav-inner">
            <a class="landing-brand" href="<?php echo BASE_URL; ?>index.php"><i class="bi bi-airplane-engines landing-brand-icon" aria-hidden="true"></i>Aero<em>Book</em></a>

            <!-- Desktop menu (md+) -->
            <div class="landing-menu">
                <a href="#start">Explore</a>
                <a href="#story">Story</a>
                <a href="#rates">Fares</a>
                <a href="#benefits">Features</a>
                <a href="#faq">FAQ</a>
            </div>

            <!-- Desktop actions (md+) -->
            <div class="landing-actions">
                <?php if (isLoggedIn()): ?>
                    <?php echo renderUserMenuDropdown(); ?>
                <?php else: ?>
                    <div class="landing-auth d-flex align-items-center gap-2">
                        <a class="landing-signin" href="<?php echo BASE_URL; ?>login.php">Sign in</a>
                        <a class="landing-register" href="<?php echo BASE_URL; ?>register.php">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile hamburger -->
            <button class="landing-burger" id="landingBurger" aria-label="Toggle menu" aria-expanded="false" aria-controls="landingMenuPanel">
                <svg id="burgerIconMenu" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>
                </svg>
                <svg id="burgerIconClose" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;">
                    <line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>
                </svg>
            </button>

            <!-- Mobile dropdown -->
            <div class="landing-menu-panel" id="landingMenuPanel">
                <a href="#start">Explore</a>
                <a href="#story">Story</a>
                <a href="#rates">Fares</a>
                <a href="#benefits">Features</a>
                <a href="#faq">FAQ</a>
                <?php if (isLoggedIn()): ?>
                    <div class="landing-auth-links landing-auth-links-loggedin">
                        <a href="<?php echo BASE_URL; ?>profile.php" class="landing-user-chip">
                            <span class="landing-user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?></span>
                        </a>
                        <a class="landing-register" href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="landing-auth-links">
                        <a href="<?php echo BASE_URL; ?>login.php">Sign in</a>
                        <a class="landing-register" href="<?php echo BASE_URL; ?>register.php">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <!-- ═══ AeroBook App Navigation ═══ -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
                <i class="bi bi-airplane-engines me-2 brand-icon"></i>
                <span class="brand-text">Aero<span class="brand-accent">Book</span></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($isBareNav): ?>
                <!-- Bare mode (About/Contact): centered Back to Home keeps the
                     auth buttons anchored to the right of the navbar -->
                <ul class="navbar-nav mx-auto nav-center">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php"><i class="bi bi-arrow-left me-1"></i>Back to Home</a></li>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav mx-auto nav-center">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>search-flights.php"><i class="bi bi-search me-1"></i>Search Flights</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>flight-status.php"><i class="bi bi-radar me-1"></i>Flight Status</a></li>
                </ul>
                <?php endif; ?>
                <div class="d-flex align-items-center gap-2 nav-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php echo renderUserMenuDropdown(); ?>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-accent btn-sm rounded-pill px-3"><i class="bi bi-person-plus me-1"></i>Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main id="main-content">
