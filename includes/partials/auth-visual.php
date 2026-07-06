<?php
/**
 * Left branding panel shown on auth pages.
 * Expects $visualTitle and $visualText to be set before include.
 */
$visualTitle = $visualTitle ?? 'Mine. Earn. Grow your wealth daily.';
$visualText = $visualText ?? 'Join thousands of Nigerians earning daily through mining, tasks, referrals and more.';
?>
<div class="auth-visual">
    <div class="brand">
        <?= brand_mark_html() ?>
        <span><?= e($siteName ?? get_setting('site_name', 'SURECASH MINING')) ?></span>
    </div>
    <div class="pitch">
        <h1><?= e($visualTitle) ?></h1>
        <p><?= e($visualText) ?></p>
    </div>
    <div class="stat-strip">
        <div class="stat"><b>50K+</b><span>Active Users</span></div>
        <div class="stat"><b>₦120M+</b><span>Paid Out</span></div>
        <div class="stat"><b>24/7</b><span>Support</span></div>
    </div>
</div>
