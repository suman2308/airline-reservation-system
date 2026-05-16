<?php 
$pageTitle = 'My Profile'; 
require_once 'includes/header.php';
if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request.';
        redirect('profile.php');
    }

    if (isset($_POST['delete_account'])) {
        $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $user_id);
        if (mysqli_stmt_execute($del_stmt)) {
            session_destroy();
            session_start();
            $_SESSION['success'] = 'Your account has been deleted successfully.';
            redirect('index.php');
        } else {
            $_SESSION['error'] = 'Failed to delete account.';
            redirect('profile.php');
        }
        mysqli_stmt_close($del_stmt);
    }

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['new_password'];

    if (empty($name) || empty($phone)) {
        $_SESSION['error'] = 'Name and Phone are required.';
    } else {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['error'] = 'New password must be at least 6 characters.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($update_stmt, "sssi", $name, $phone, $hashed, $user_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success'] = 'Profile and password updated!';
                    $_SESSION['user_name'] = $name;
                } else {
                    $_SESSION['error'] = 'Update failed.';
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($update_stmt, "ssi", $name, $phone, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success'] = 'Profile updated successfully!';
                $_SESSION['user_name'] = $name;
            } else {
                $_SESSION['error'] = 'Update failed.';
            }
            mysqli_stmt_close($update_stmt);
        }
        redirect('profile.php');
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-person-gear me-2"></i>Manage Profile</h1>
        <p>Keep your contact details up to date</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <?php showAlert(); ?>
            <div class="flight-card">
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small class="text-muted">Email cannot be changed.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required maxlength="10">
                    </div>
                    <hr class="my-4">
                    <h5 class="mb-3">Change Password</h5>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
                    </div>
                    <button type="submit" class="btn btn-accent w-100 py-2 mt-2"><i class="bi bi-check-circle me-2"></i>Update Profile</button>
                </form>
                
                <hr class="my-4">
                <div class="text-center mt-4">
                    <h5 class="text-danger mb-3">Danger Zone</h5>
                    <p class="text-muted small">Once you delete your account, there is no going back. Please be certain.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to completely delete your account? This action cannot be undone.');">
                        <?php csrfField(); ?>
                        <input type="hidden" name="delete_account" value="1">
                        <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-trash me-2"></i>Delete My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
