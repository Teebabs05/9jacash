<?php
/**
 * Shared <head> + app-shell opening markup for every authenticated page.
 * Expects $pageTitle and optionally $activeNav to be set before include.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$siteName = get_setting('site_name', 'SURECASH MINING');
$assetBase = rtrim(APP_URL, '/') . '/assets';
$authUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>(function(){var t=localStorage.getItem('surecash_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<?= favicon_link_html() ?>
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/app.css">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-toggle></div>
    <?php require __DIR__ . '/app-sidebar.php'; ?>
    <div class="app-main">
        <?php if (!empty($_SESSION['impersonating_admin_id'])): ?>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 small" style="background:var(--brand-gold);color:var(--brand-navy);font-weight:600;">
                <span><i class="bi bi-incognito me-1"></i> You are viewing as <?= e($authUser['full_name'] ?? '') ?> (@<?= e($authUser['username'] ?? '') ?>)</span>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/stop-impersonate.php" class="btn btn-sm btn-dark">Return to Admin</a>
            </div>
        <?php endif; ?>
        <?php require __DIR__ . '/app-topbar.php'; ?>
        <div class="app-content">
        <?php require __DIR__ . '/flash-messages.php'; ?>
