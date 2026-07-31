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

    if (mysqli_stmt_fetch($stmt)) {
        if (password_verify($password, $db_password)) {
            regenerateSession();
            $_SESSION['admin_id'] = $db_admin_id;
            $_SESSION['admin_username'] = $db_username;
            clearRateLimit('admin_login');
            logAdminAction($db_admin_id, 'admin_login', "Admin login successful");
            logInfo('Admin login successful', ['admin_id' => $db_admin_id]);
            setFlash('success', 'Welcome to Admin Panel!');
            redirect(BASE_URL . 'admin/dashboard.php');
        }
    }
    mysqli_stmt_close($stmt);

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
        // Apply saved/system theme before CSS paints to avoid a flash of the wrong theme.
        (function () {
            try { var t = localStorage.getItem('aerobook-theme');
                if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="position-fixed top-0 end-0 m-3">
        <button type="button" class="theme-toggle theme-toggle-light" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode" aria-pressed="false">
            <i class="bi bi-moon-stars-fill"></i>
        </button>
    </div>
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-4">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white p-4 text-center border-0">
                        <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Admin Login</h4>
                        <small class="opacity-50">AeroBook Administration</small>
                    </div>
                    <div class="card-body p-4">
                        <?php showAlert(); ?>
                        <form method="POST" novalidate>
                            <?php csrfField(); ?>
                            <div class="mb-3">
                                <label class="form-label" for="adminUser">Username</label>
                                <input type="text" name="username" id="adminUser" class="form-control py-2" placeholder="Enter username" required maxlength="50">
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="adminPass">Password</label>
                                <input type="password" name="password" id="adminPass" class="form-control py-2" placeholder="Enter password" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-2"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Dashboard</button>
                        </form>
                    </div>
                    <div class="card-footer bg-white p-3 text-center border-0">
                        <a href="<?php echo BASE_URL; ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Website</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/script.js'); ?>"></script>
</body></html>
