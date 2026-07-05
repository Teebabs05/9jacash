<?php
/** @var callable $content */
$title = $title ?? 'Dashboard';
?>
<!doctype html>
<html lang="en">
<head>
<?php require APP_PATH . '/views/partials/head.php'; ?>
</head>
<body>
<div class="app-shell">
    <?php require APP_PATH . '/views/partials/sidebar-user.php'; ?>
    <div class="app-main">
        <?php require APP_PATH . '/views/partials/topbar.php'; ?>
        <?php require APP_PATH . '/views/partials/announcement.php'; ?>
        <?php require APP_PATH . '/views/partials/flash.php'; ?>
        <div class="container-fluid p-3 p-md-4 fade-in">
            <?php $content(); ?>
        </div>
    </div>
</div>
<?php require APP_PATH . '/views/partials/scripts.php'; ?>
</body>
</html>
