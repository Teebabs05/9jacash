<?php
/** @var callable $content */
$title = $title ?? 'Welcome';
?>
<!doctype html>
<html lang="en">
<head>
<?php require APP_PATH . '/views/partials/head.php'; ?>
</head>
<body>
<div class="auth-shell">
    <div class="auth-card glass-card p-4 p-md-5 fade-in">
        <div class="text-center mb-4">
            <a href="<?= base_url('/') ?>"><?php require APP_PATH . '/views/partials/logo.php'; ?></a>
        </div>
        <?php require APP_PATH . '/views/partials/flash.php'; ?>
        <?php $content(); ?>
    </div>
</div>
<?php require APP_PATH . '/views/partials/scripts.php'; ?>
</body>
</html>
