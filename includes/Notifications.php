<?php
/**
 * AeroBook – In-App Notification System
 *
 * Tracks user-facing notifications for bookings, prices, and account events.
 * Notifications are stored in the database and displayed in the header/dashboard.
 *
 * Table: notifications (id, user_id, type, title, message, link, is_read, created_at)
 */

class AeroNotifications {

    /**
     * Create a notification for a user.
     */
    public static function create($userId, $type, $title, $message = null, $link = null) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $userId, $type, $title, $message, $link);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return $id;
    }

    /**
     * Get unread notifications for a user.
     */
    public static function getUnread($userId, $limit = 10) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $limit = intval($limit);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) $notifications[] = $row;
        mysqli_stmt_close($stmt);
        return $notifications;
    }

    /**
     * Get recent notifications (read + unread) for a user.
     */
    public static function getRecent($userId, $limit = 20) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $limit = intval($limit);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) $notifications[] = $row;
        mysqli_stmt_close($stmt);
        return $notifications;
    }

    /**
     * Count unread notifications for a user.
     */
    public static function countUnread($userId) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = (int)mysqli_fetch_assoc($result)['c'];
        mysqli_stmt_close($stmt);
        return $count;
    }

    /**
     * Mark a single notification as read.
     */
    public static function markRead($notificationId, $userId) {
        global $conn;
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllRead($userId) {
        global $conn;
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Delete old notifications (cleanup task).
     */
    public static function deleteOld($days = 90) {
        global $conn;
        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $days = intval($days);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Render notification dropdown HTML for the header.
     */
    public static function renderDropdown($userId) {
        $unread = self::countUnread($userId);
        $notifications = self::getUnread($userId, 5);
        $html = '<a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false">';
        $html .= '<i class="bi bi-bell"></i>';
        if ($unread > 0) {
            $html .= '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.55rem;">' . min($unread, 99) . '</span>';
        }
        $html .= '</a>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0" style="width:320px;">';
        $html .= '<li><h6 class="dropdown-header fw-bold">Notifications</h6></li>';
        if (empty($notifications)) {
            $html .= '<li><div class="dropdown-item-text text-muted small text-center py-3">No new notifications</div></li>';
        } else {
            foreach ($notifications as $n) {
                $icon = self::iconForType($n['type']);
                $html .= '<li><a class="dropdown-item py-2" href="' . ($n['link'] ?: '#') . '">';
                $html .= '<div class="d-flex align-items-start gap-2"><i class="bi ' . $icon . ' mt-1 text-accent"></i>';
                $html .= '<div><div class="fw-semibold small">' . htmlspecialchars($n['title']) . '</div>';
                if ($n['message']) $html .= '<small class="text-muted">' . htmlspecialchars($n['message']) . '</small>';
                $html .= '</div></div></a></li>';
            }
            if ($unread > 5) {
                $html .= '<li><hr class="dropdown-divider"></li>';
                $html .= '<li><a class="dropdown-item text-center small text-accent" href="notifications.php">View all</a></li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }

    private static function iconForType($type) {
        $icons = [
            'booking_confirmed' => 'bi-check-circle-fill',
            'booking_cancelled' => 'bi-x-circle-fill',
            'price_match' => 'bi-graph-down-arrow',
            'password_changed' => 'bi-shield-check',
            'email_verified' => 'bi-envelope-check-fill',
            'flight_updated' => 'bi-info-circle-fill',
        ];
        return $icons[$type] ?? 'bi-bell-fill';
    }
}
