<?php
$pageTitle = 'Notifications';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Notifications.php';

if (!isLoggedIn()) redirect('login.php');

$userId = $_SESSION['user_id'];

// Handle mark read actions
if (isset($_GET['mark_read'])) {
    AeroNotifications::markRead(intval($_GET['mark_read']), $userId);
    redirect('notifications.php');
}
if (isset($_GET['mark_all_read'])) {
    AeroNotifications::markAllRead($userId);
    redirect('notifications.php');
}

$notifications = AeroNotifications::getRecent($userId, 50);
$unreadCount = AeroNotifications::countUnread($userId);
?>

<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Inbox</span>
        <h1>Noti<span class="dim">fications</span></h1>
        <p class="mb-0">Stay updated with your bookings and account activity</p>
        <?php if ($unreadCount > 0): ?>
        <div class="mt-3"><a href="?mark_all_read=1" class="btn btn-outline-accent btn-sm rounded-pill px-3"><i class="bi bi-check2-all me-1"></i>Mark All Read</a></div>
        <?php endif; ?>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <?php if ($unreadCount > 0): ?>
    <div class="alert alert-info d-flex align-items-center justify-content-between py-2">
        <div class="small"><i class="bi bi-info-circle-fill me-2"></i>You have <strong><?php echo $unreadCount; ?></strong> unread notification<?php echo $unreadCount > 1 ? 's' : ''; ?>.</div>
        <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">Mark all read</a>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash text-muted display-4 mb-3 d-block"></i>
                <p class="text-muted mb-0">No notifications yet.</p>
            </div>
            <?php else: foreach ($notifications as $n): 
                $icon = [
                    'booking_confirmed' => 'bi-check-circle-fill text-success',
                    'booking_cancelled' => 'bi-x-circle-fill text-danger',
                    'price_match' => 'bi-graph-down-arrow text-accent',
                    'password_changed' => 'bi-shield-check text-info',
                    'email_verified' => 'bi-envelope-check-fill text-success',
                ][$n['type']] ?? 'bi-bell-fill text-accent';
            ?>
            <div class="d-flex align-items-start gap-3 p-3 border-bottom <?php echo !$n['is_read'] ? 'bg-light' : ''; ?>">
                <i class="bi <?php echo $icon; ?> fs-4 mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold <?php echo !$n['is_read'] ? '' : 'text-muted'; ?>"><?php echo htmlspecialchars($n['title']); ?></div>
                    <?php if ($n['message']): ?>
                    <div class="small text-muted"><?php echo htmlspecialchars($n['message']); ?></div>
                    <?php endif; ?>
                    <small class="text-muted" style="font-size:0.7rem;"><?php echo timeSince($n['created_at']); ?></small>
                </div>
                <div class="d-flex gap-1 align-items-center">
                    <?php if (!$n['is_read']): ?>
                    <a href="?mark_read=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-accent" title="Mark as read"><i class="bi bi-check2"></i></a>
                    <?php endif; ?>
                    <?php if ($n['link']): ?>
                    <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-sm btn-outline-primary" title="View details"><i class="bi bi-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
