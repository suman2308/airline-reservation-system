<?php 
$pageTitle = 'Login'; 
require_once 'includes/header.php';
if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        redirect('login.php');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
    } else {
        // Use Prepared Statements to prevent SQL Injection
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $db_id, $db_name, $db_email, $db_password);

        if (mysqli_stmt_fetch($stmt)) {
            if (password_verify($password, $db_password)) {
                $_SESSION['user_id'] = $db_id;
                $_SESSION['user_name'] = $db_name;
                $_SESSION['user_email'] = $db_email;
                $_SESSION['success'] = 'Welcome back, ' . $db_name . '!';
                redirect('index.php');
            } else {
                $_SESSION['error'] = 'Invalid password. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'No account found with that email.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<section class="auth-section">
<div class="container">
    <?php showAlert(); ?>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="auth-card">
                <div class="row g-0">
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="auth-sidebar">
                            <i class="bi bi-box-arrow-in-right auth-icon"></i>
                            <h2>Welcome Back!</h2>
                            <p>Login to your AeroBook account to manage your bookings and search for flights.</p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="auth-form">
                            <h2>Login</h2>
                            <p class="subtitle">Enter your credentials to access your account</p>
                            <form method="POST" id="loginForm">
                                <?php csrfField(); ?>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                                </div>
                                <button type="submit" class="btn btn-accent w-100 py-2 mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
                                <p class="text-center mb-0">Don't have an account? <a href="register.php" class="text-accent fw-bold">Register here</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<?php require_once 'includes/footer.php'; ?>

