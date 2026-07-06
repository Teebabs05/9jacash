<?php
/**
 * Shared <head> for every authentication page.
 * Expects an optional $pageTitle variable to be set before include.
 */
$pageTitle = $pageTitle ?? 'Welcome';
$siteName = get_setting('site_name', '9JACASH');
$assetBase = rtrim(APP_URL, '/') . '/assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>
(function(){
    var t = localStorage.getItem('9jacash_theme');
    if (t) document.documentElement.setAttribute('data-theme', t);
})();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<link rel="icon" href="<?= e($assetBase) ?>/images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
</head>
<body>
