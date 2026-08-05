<?php
$isSubDir = true;
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Validation.php';
require_once __DIR__ . '/../includes/Security.php';

if (isAdminLoggedIn()) redirect(BASE_URL . 'admin/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!requireCsrfToken()) {
        redirect(BASE_URL . 'admin/login.php');
    }

    $errors = validateAdminLogin($_POST);
    if (!empty($errors)) {
        setFlash('error', $errors[0]);
        redirect(BASE_URL . 'admin/login.php');
    }

    if (!checkRateLimit('admin_login', 5, 300)) {
        logSecurity('Admin login rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        setFlash('error', 'Too many login attempts. Please try again later.');
        redirect(BASE_URL . 'admin/login.php');
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT admin_id, username, password FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_admin_id, $db_username, $db_password);

    // Capture the auth result, then close the statement BEFORE running any
    // other query on $conn (logAdminAction below) — mysqli throws
    // "Commands out of sync" if a new statement runs while this one is open.
    $authenticated = false;
    if (mysqli_stmt_fetch($stmt)) {
        $authenticated = password_verify($password, $db_password);
    }
    mysqli_stmt_close($stmt);

    if ($authenticated) {
        regenerateSession();
        $_SESSION['admin_id'] = $db_admin_id;
        $_SESSION['admin_username'] = $db_username;
        clearRateLimit('admin_login');
        logAdminAction($db_admin_id, 'admin_login', "Admin login successful");
        logInfo('Admin login successful', ['admin_id' => $db_admin_id]);
        setFlash('success', 'Welcome to Admin Panel!');
        redirect(BASE_URL . 'admin/dashboard.php');
    }

    logSecurity('Admin login failed', ['username' => $username]);
    setFlash('error', 'Invalid username or password.');
    redirect(BASE_URL . 'admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | AeroBook</title>
    <script>
        // Dark mode is the only theme — pin it before CSS paints (no flash).
        document.documentElement.classList.add('js');
        document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $cssVer = filemtime(__DIR__ . '/../css/aerobook.css'); ?>
    <link href="<?php echo asset('css/style.css') . '?v=' . filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/aerobook.css') . '?v=' . $cssVer; ?>" rel="stylesheet">
    <style>
        /* Admin login box keeps the light-mode look (white card, dark text)
           even though the rest of the site is dark-only. */
        .admin-login-card { background: #fff !important; color: #1F2937; border-color: #E5E7EB !important; }
        .admin-login-card .card-body { color: #1F2937; }
        .admin-login-card .card-body h5 { color: #111827; }
        .admin-login-card .kicker { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }
        .admin-login-card .form-label { color: #374151; }
        /* Typed text stays black even with dark color-scheme / browser autofill */
        .admin-login-card .form-control { background: #fff; color: #111827 !important; border-color: #D1D5DB; caret-color: #111827; -webkit-text-fill-color: #111827; }
        .admin-login-card .form-control:focus { background: #fff; border-color: #7342E2; }
        .admin-login-card .form-control:-webkit-autofill { -webkit-text-fill-color: #111827 !important; }
        .admin-login-card .card-footer { background: #fff !important; }
        .admin-login-card .card-footer a { color: #6B7280; }
        /* Mobile: keep the navy header visible (global CSS hides .auth-sidebar < 992px) */
        @media (max-width: 992px) {
            .admin-login-card .auth-sidebar { display: flex; padding: 1.75rem 1.25rem; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden admin-login-card">
                    <div class="auth-sidebar text-center" style="border-radius: 0; padding: 2.5rem 2rem;">
                        <i class="bi bi-shield-lock mb-3 d-block" style="font-size: 2rem;"></i>
                        <h4 class="mb-0">Admin Login</h4>
                        <small class="opacity-75">AeroBook Administration</small>
                    </div>
                    <div class="card-body p-4 pb-2">
                        <?php showAlert(); ?>
                        <span class="kicker">Operations</span>
                        <h5 class="fw-bold mb-3">Sign in to continue</h5>
                        <form method="POST" novalidate>
                            <?php csrfField(); ?>
                            <div class="mb-3">
                                <label class="form-label" for="adminUser">Username</label>
                                <input type="text" name="username" id="adminUser" class="form-control py-2" placeholder="Enter username" required maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="adminPass">Password</label>
                                <input type="password" name="password" id="adminPass" class="form-control py-2" placeholder="Enter password" required>
                            </div>
                            <button type="submit" class="btn btn-accent w-100 py-2 rounded-pill"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
                        </form>
                    </div>
                    <div class="card-footer bg-white py-1 text-center border-0">
                        <a href="<?php echo BASE_URL; ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Website</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/script.js'); ?>"></script>
</body></html>
