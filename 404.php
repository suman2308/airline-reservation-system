<?php
$pageTitle = 'Page Not Found';
require_once 'includes/header.php';
?>
<div class="container py-5 text-center" style="min-height: 60vh;">
    <div class="row justify-content-center align-items-center h-100">
        <div class="col-md-6">
            <i class="bi bi-compass text-muted display-1 mb-4 d-block" style="opacity:0.3;"></i>
            <h1 class="display-4 fw-bold mb-3">404</h1>
            <h3 class="fw-bold mb-3">Page Not Found</h3>
            <p class="text-muted lead mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-accent btn-lg px-5"><i class="bi bi-house-door me-2"></i>Return Home</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
