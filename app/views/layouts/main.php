<?php
/** @var callable $content */
$title = $title ?? setting('site_name', '9JACASH');
$siteName = setting('site_name', '9JACASH');
?>
<!doctype html>
<html lang="en">
<head>
<?php require APP_PATH . '/views/partials/head.php'; ?>
</head>
<body>
<?php require APP_PATH . '/views/partials/navbar-guest.php'; ?>

<?php require APP_PATH . '/views/partials/flash.php'; ?>

<main><?php $content(); ?></main>

<?php require APP_PATH . '/views/partials/footer.php'; ?>
<?php require APP_PATH . '/views/partials/scripts.php'; ?>
</body>
</html>
