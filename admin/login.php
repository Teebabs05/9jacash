<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireGuest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $username = clean($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter your username and password.';
    } else {
        $result = AdminAuth::attemptLogin($username, $password);

        if ($result['success']) {
            redirect(rtrim(APP_URL, '/') . '/admin/index.php');
        }

        $errors[] = $result['message'];
    }
}

$assetBase = rtrim(APP_URL, '/') . '/assets';
$pageTitle = 'Admin Login';
$siteName = get_setting('site_name', 'SURECASH MINING');
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
</head>
<body>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="brand"><?= brand_mark_html() ?><span><?= e($siteName) ?> ADMIN</span></div>
        <div class="pitch">
            <h1>Full control over your platform.</h1>
            <p>Manage users, deposits, withdrawals, mining plans, tasks and every setting from one secure dashboard.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <h2>Administrator Login</h2>
            <p class="sub">Restricted area. Authorized personnel only.</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="input-group-text" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-brand w-100">Log In</button>
            </form>
        </div>
    </div>
</div>
<script src="<?= e($assetBase) ?>/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script src="<?= e($assetBase) ?>/js/main.js"></script>
</body>
</html>
