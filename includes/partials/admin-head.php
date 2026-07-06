<?php
/**
 * Shared <head> + app-shell opening markup for every admin page.
 * Expects $pageTitle and optionally $activeNav to be set before include.
 */
$pageTitle = $pageTitle ?? 'Admin';
$siteName = get_setting('site_name', '9JACASH');
$assetBase = rtrim(APP_URL, '/') . '/assets';
$authAdmin = current_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>(function(){var t=localStorage.getItem('9jacash_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?> Admin</title>
<link rel="icon" href="<?= e($assetBase) ?>/images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/app.css">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-toggle></div>
    <?php require __DIR__ . '/admin-sidebar.php'; ?>
    <div class="app-main">
        <?php require __DIR__ . '/admin-topbar.php'; ?>
        <div class="app-content">
        <?php require __DIR__ . '/flash-messages.php'; ?>
