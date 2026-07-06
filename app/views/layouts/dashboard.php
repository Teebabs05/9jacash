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
        <?php if (\App\Core\Session::has('_impersonator_id')): ?>
        <div class="alert alert-warning rounded-0 mb-0 py-2 text-center small">
            <i class="fa-solid fa-user-secret me-1"></i> You are viewing as this user.
            <form action="<?= base_url('return-to-admin') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-dark rounded-pill ms-2 py-0 px-2">Return to Admin</button>
            </form>
        </div>
        <?php endif; ?>
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
