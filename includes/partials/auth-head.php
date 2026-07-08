<?php
/**
 * Shared <head> for every authentication page.
 * Expects an optional $pageTitle variable to be set before include.
 */
$pageTitle = $pageTitle ?? 'Welcome';
$siteName = get_setting('site_name', 'SURECASH MINING');
$assetBase = rtrim(APP_URL, '/') . '/assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>
(function(){
    var t = localStorage.getItem('surecash_theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
})();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<?= favicon_link_html() ?>
<link rel="preload" href="<?= e($assetBase) ?>/fonts/poppins/poppins-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e($assetBase) ?>/fonts/poppins/poppins-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e(asset_url('css/theme.css')) ?>">
</head>
<body>
<div class="auth-mobile-brand">
    <div class="d-flex align-items-center gap-2">
        <?= brand_mark_html(36) ?>
        <span><?= e($siteName) ?></span>
    </div>
    <a href="<?= e(rtrim(APP_URL, '/')) ?>/index.php" class="auth-home-link"><i class="bi bi-arrow-left"></i> Home</a>
</div>
<div class="auth-hero-mark"><?= brand_mark_html(104) ?></div>
