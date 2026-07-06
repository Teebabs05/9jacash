<!doctype html>
<html lang="en">
<head>
<?php require APP_PATH . '/views/partials/head.php'; ?>
</head>
<body>
<div class="auth-shell">
    <div class="auth-card glass-card p-5 text-center">
        <?php require APP_PATH . '/views/partials/logo.php'; ?>
        <h3 class="fw-bold mt-4">We'll be right back</h3>
        <p class="text-muted-soft"><?= e(setting('site_name', '9JACASH')) ?> is currently undergoing scheduled maintenance. Please check back shortly.</p>
    </div>
</div>
<?php require APP_PATH . '/views/partials/scripts.php'; ?>
</body>
</html>
