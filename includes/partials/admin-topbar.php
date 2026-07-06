<?php
/**
 * Admin topbar: sidebar toggle, page title, admin menu.
 */
$initials = '';
if (!empty($authAdmin['full_name'])) {
    $parts = preg_split('/\s+/', trim($authAdmin['full_name']));
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

        <a href="<?= e(rtrim(APP_URL, '/')) ?>/index.php" target="_blank" class="theme-toggle-btn d-none d-sm-inline-flex" style="color:var(--text);border-color:var(--border);" title="View site"><i class="bi bi-box-arrow-up-right"></i></a>

        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:var(--surface-alt);border-radius:30px;padding:6px 14px 6px 6px;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;background:var(--brand-navy);color:#fff;font-size:.8rem;"><?= e($initials ?: 'A') ?></span>
                <span class="small fw-semibold d-none d-sm-inline" style="color:var(--text);"><?= e($authAdmin['full_name'] ?? 'Admin') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
