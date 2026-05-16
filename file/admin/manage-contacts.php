<?php 
$pageTitle = 'Manage Contact Queries'; 
require_once __DIR__ . '/includes/admin-header.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM contacts WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['success'] = 'Query deleted.';
    redirect(BASE_URL . 'admin/manage-contacts.php');
}

$queries = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC");
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom">
        <h5 class="mb-0 fw-bold">Support Queries</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>From</th><th>Subject</th><th>Message</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($q = mysqli_fetch_assoc($queries)): ?>
                    <tr>
                        <td class="small"><?php echo formatDateTime($q['created_at']); ?></td>
                        <td><strong><?php echo htmlspecialchars($q['name']); ?></strong><br><small><?php echo htmlspecialchars($q['email']); ?></small></td>
                        <td><?php echo htmlspecialchars($q['subject']); ?></td>
                        <td><div style="max-width:300px; font-size: 0.85rem;" class="text-truncate" title="<?php echo htmlspecialchars($q['message']); ?>"><?php echo htmlspecialchars($q['message']); ?></div></td>
                        <td>
                            <a href="?delete=<?php echo $q['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this query?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
