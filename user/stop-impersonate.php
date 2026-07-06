<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['impersonating_admin_id'])) {
    redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
}

$adminId = (int) $_SESSION['impersonating_admin_id'];
$impersonatedUserId = $_SESSION['user_id'] ?? null;

unset($_SESSION['impersonating_admin_id'], $_SESSION['user_id'], $_SESSION['username']);

if ($impersonatedUserId) {
    log_activity((int) $impersonatedUserId, $adminId, 'admin_login_as_ended', 'Admin returned from impersonation');
}

$_SESSION['admin_id'] = $adminId;

redirect(rtrim(APP_URL, '/') . '/admin/users.php');
