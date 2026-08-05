<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/Security.php';
require_once 'includes/Auth.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    logInfo('User logged out', ['user_id' => $userId]);
    // Clear remember-me tokens
    clearRememberMe($userId);
    // Invalidate active session in database
    invalidateUserSessions($userId);
}

secureLogout();
redirect('index.php');
