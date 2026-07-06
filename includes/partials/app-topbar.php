<?php
/**
 * Authenticated topbar: sidebar toggle, page title, notifications, user menu.
 */
$unreadCount = 0;

if (!empty($authUser['id'])) {
    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0');
    $stmt->execute([$authUser['id']]);
    $unreadCount = (int) $stmt->fetch()['c'];
}

$initials = '';
if (!empty($authUser['full_name'])) {
    $parts = preg_split('/\s+/', trim($authUser['full_name']));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
?>
<header class="app-topbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Toggle menu"><i class="bi bi-list"></i></button>
        <h5 class="mb-0 fw-bold"><?= e($pageTitle) ?></h5>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="theme-toggle-btn" style="color:var(--text);border-color:var(--border);" data-theme-toggle data-theme-icon><i class="bi bi-moon-stars"></i></button>

        <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/notifications.php" class="theme-toggle-btn position-relative" style="color:var(--text);border-color:var(--border);">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>

        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:var(--surface-alt);border-radius:30px;padding:6px 14px 6px 6px;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--brand-emerald);color:#fff;font-size:.8rem;"><?= e($initials ?: 'U') ?></span>
                <span class="small fw-semibold d-none d-sm-inline" style="color:var(--text);"><?= e($authUser['full_name'] ?? 'User') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= e(rtrim(APP_URL, '/')) ?>/user/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/index.php"><i class="bi bi-wallet2 me-2"></i>Wallet</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= e(rtrim(APP_URL, '/')) ?>/user/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
