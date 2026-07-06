<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$assetBase = rtrim(APP_URL, '/') . '/assets';
$siteName = get_setting('site_name', 'SURECASH MINING');
$pageTitle = 'Terms of Service';
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
<?php require __DIR__ . '/includes/partials/whatsapp-widget.php'; ?>
<div class="container py-5" style="max-width:820px;">
    <h1 class="fw-bold mb-4"><?= e($siteName) ?> — Terms of Service</h1>
    <p style="color:var(--text-muted);">Last updated: <?= date('F Y') ?></p>

    <h5 class="fw-bold mt-4">1. Acceptance of Terms</h5>
    <p>By creating an account on <?= e($siteName) ?>, you agree to be bound by these Terms of Service and all applicable laws and regulations of the Federal Republic of Nigeria.</p>

    <h5 class="fw-bold mt-4">2. Account Eligibility</h5>
    <p>You must be at least 18 years old and provide accurate registration information to use this platform.</p>

    <h5 class="fw-bold mt-4">3. Earnings &amp; Withdrawals</h5>
    <p>Earnings accrued through mining, tasks, referrals, spin wheel, daily check-ins and other activities are subject to the limits, charges and processing times published on the platform and may change at the sole discretion of the administrator.</p>

    <h5 class="fw-bold mt-4">4. Prohibited Conduct</h5>
    <p>Creating multiple/duplicate accounts, using automated bots, submitting fraudulent task proof, or attempting to exploit the referral or bonus system will result in account suspension and forfeiture of funds.</p>

    <h5 class="fw-bold mt-4">5. Account Suspension</h5>
    <p>We reserve the right to suspend or terminate any account found in violation of these terms or engaged in fraudulent activity.</p>

    <h5 class="fw-bold mt-4">6. Limitation of Liability</h5>
    <p><?= e($siteName) ?> is provided "as is". We are not liable for indirect or consequential damages arising from the use of this platform.</p>

    <h5 class="fw-bold mt-4">7. Changes to Terms</h5>
    <p>These terms may be updated periodically. Continued use of the platform constitutes acceptance of the revised terms.</p>

    <button type="button" onclick="history.back()" class="btn btn-outline-brand mt-4">Back</button>
</div>
</body>
</html>
