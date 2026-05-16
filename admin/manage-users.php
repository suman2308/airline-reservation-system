<?php 
$pageTitle = 'Manage Users'; 
require_once __DIR__ . '/includes/admin-header.php';

// Handle user deletion
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Check if user has bookings
    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE user_id=$del_id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = 'Cannot delete user with active bookings.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $del_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'User deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete user.';
        }
        mysqli_stmt_close($stmt);
    }
    redirect(BASE_URL . 'admin/manage-users.php');
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom">
        <h5 class="mb-0 fw-bold">System Users</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td><?php echo formatDate($u['created_at']); ?></td>
                        <td>
                            <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user? All their data will be lost.')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
