<?php
$base = rtrim(APP_URL, '/');
$socials = [
    'facebook' => ['icon' => 'bi-facebook', 'url' => get_setting('facebook_url', '')],
    'twitter' => ['icon' => 'bi-twitter-x', 'url' => get_setting('twitter_url', '')],
    'instagram' => ['icon' => 'bi-instagram', 'url' => get_setting('instagram_url', '')],
    'telegram' => ['icon' => 'bi-telegram', 'url' => get_setting('telegram_url', '')],
    'whatsapp' => ['icon' => 'bi-whatsapp', 'url' => get_setting('whatsapp_url', '')],
];
?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?= brand_mark_html(28) ?>
                    <span class="fw-bold fs-5 text-white"><?= e($siteName ?? get_setting('site_name', 'SURECASH MINING')) ?></span>
                </div>
                <p class="small">Mine, complete tasks, refer friends and grow your wealth daily on Nigeria's premium earning platform.</p>
                <div class="mt-3">
                    <?php foreach ($socials as $key => $social): ?>
                        <a href="<?= e($social['url'] ?: '#') ?>" class="social-icon" aria-label="<?= e(ucfirst($key)) ?>"><i class="bi <?= e($social['icon']) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <h6>Platform</h6>
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#mining-plans">Mining Plans</a>
                <a href="#referral">Referral Program</a>
            </div>
            <div class="col-lg-2 col-6">
                <h6>Account</h6>
                <a href="<?= e($base) ?>/user/login.php">Log In</a>
                <a href="<?= e($base) ?>/user/register.php">Create Account</a>
                <a href="<?= e($base) ?>/user/forgot-password.php">Forgot Password</a>
            </div>
            <div class="col-lg-2 col-6">
                <h6>Legal</h6>
                <a href="<?= e($base) ?>/terms.php">Terms of Service</a>
                <a href="<?= e($base) ?>/privacy.php">Privacy Policy</a>
            </div>
            <div class="col-lg-2 col-6">
                <h6>Contact</h6>
                <a href="mailto:<?= e(get_setting('contact_email', 'support@surecashmining.com')) ?>"><i class="bi bi-envelope me-1"></i><?= e(get_setting('contact_email', 'support@surecashmining.com')) ?></a>
                <a href="tel:<?= e(get_setting('contact_phone', '')) ?>"><i class="bi bi-telephone me-1"></i><?= e(get_setting('contact_phone', '+234 800 000 0000')) ?></a>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'SURECASH MINING')) ?>. All rights reserved.</span>
            <span>Built for Nigerians, by Nigerians. 🇳🇬</span>
        </div>
    </div>
</footer>
