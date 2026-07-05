<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$assetBase = rtrim(APP_URL, '/') . '/assets';
$siteName = get_setting('site_name', '9JACASH');
$pageTitle = 'Privacy Policy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<link rel="icon" href="<?= e($assetBase) ?>/images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
</head>
<body>
<div class="container py-5" style="max-width:820px;">
    <h1 class="fw-bold mb-4"><?= e($siteName) ?> — Privacy Policy</h1>
    <p style="color:var(--text-muted);">Last updated: <?= date('F Y') ?></p>

    <h5 class="fw-bold mt-4">1. Information We Collect</h5>
    <p>We collect your name, email, phone number, KYC documents, bank/USDT details and activity data (deposits, withdrawals, mining, tasks) necessary to operate your account.</p>

    <h5 class="fw-bold mt-4">2. How We Use Your Information</h5>
    <p>Your information is used to process transactions, verify your identity, prevent fraud, communicate account activity, and improve the platform.</p>

    <h5 class="fw-bold mt-4">3. Data Security</h5>
    <p>Passwords are hashed using industry-standard algorithms. Sensitive documents are stored outside the public web root and protected from direct access.</p>

    <h5 class="fw-bold mt-4">4. Data Sharing</h5>
    <p>We do not sell your personal information. Data may be shared with payment processors strictly to complete deposits and withdrawals.</p>

    <h5 class="fw-bold mt-4">5. Your Rights</h5>
    <p>You may request account deletion or data export by contacting support through the details published on the platform.</p>

    <h5 class="fw-bold mt-4">6. Cookies</h5>
    <p>We use strictly necessary session cookies for authentication and an optional "remember me" cookie to keep you signed in.</p>

    <a href="javascript:history.back()" class="btn btn-outline-brand mt-4">Back</a>
</div>
</body>
</html>
