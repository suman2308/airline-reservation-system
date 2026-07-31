<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Security.php';

logAdminAction($_SESSION['admin_id'] ?? 0, 'admin_logout', 'Admin logged out');
logInfo('Admin logged out', ['admin_id' => $_SESSION['admin_id'] ?? 'unknown']);
secureLogout();
redirect(BASE_URL . 'admin/login.php');
