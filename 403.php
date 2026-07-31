<?php
$pageTitle = 'Access Denied';
require_once 'includes/header.php';
?>
<div class="container py-5 text-center" style="min-height: 60vh;">
    <div class="row justify-content-center align-items-center h-100">
        <div class="col-md-6">
            <i class="bi bi-shield-lock text-danger display-1 mb-4 d-block" style="opacity:0.3;"></i>
            <h1 class="display-4 fw-bold mb-3">403</h1>
            <h3 class="fw-bold mb-3">Access Denied</h3>
            <p class="text-muted lead mb-4">You don't have permission to access this area.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-accent btn-lg px-5"><i class="bi bi-house-door me-2"></i>Return Home</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
