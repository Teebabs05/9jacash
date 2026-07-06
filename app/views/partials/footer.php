<footer class="py-5 mt-5" style="background:var(--bg-surface-alt); border-top:1px solid var(--border-color);">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <?php require APP_PATH . '/views/partials/logo.php'; ?>
                <p class="text-muted-soft mt-3 small"><?= e(setting('site_tagline', 'Earn. Grow. Cash Out.')) ?> Mining, tasks, referrals and daily rewards — all in one secure wallet.</p>
                <div class="d-flex gap-2 mt-3">
                    <?php foreach (['facebook','twitter','instagram','telegram'] as $net): $link = setting('social_' . $net); if (!$link) continue; ?>
                        <a href="<?= e($link) ?>" class="theme-toggle-btn" target="_blank" rel="noopener"><i class="fa-brands fa-<?= e($net) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-3">Platform</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= base_url('pricing') ?>" class="text-muted-soft">Mining Plans</a></li>
                    <li><a href="<?= base_url('referral-info') ?>" class="text-muted-soft">Referral Program</a></li>
                    <li><a href="<?= base_url('faq') ?>" class="text-muted-soft">FAQ</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-3">Company</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= base_url('about') ?>" class="text-muted-soft">About Us</a></li>
                    <li><a href="<?= base_url('contact') ?>" class="text-muted-soft">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-3">Legal</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= base_url('terms') ?>" class="text-muted-soft">Terms of Service</a></li>
                    <li><a href="<?= base_url('privacy') ?>" class="text-muted-soft">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="fw-bold mb-3">Contact</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 text-muted-soft">
                    <li><i class="fa-solid fa-envelope me-1"></i> <?= e(setting('contact_email', 'support@9jacash.com')) ?></li>
                    <li><i class="fa-solid fa-phone me-1"></i> <?= e(setting('contact_phone', '')) ?></li>
                </ul>
            </div>
        </div>
        <hr class="my-4" style="border-color:var(--border-color)">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted-soft gap-2">
            <span>&copy; <?= date('Y') ?> <?= e(setting('site_name', '9JACASH')) ?>. All rights reserved.</span>
            <span>Investing carries risk. Read our <a href="<?= base_url('terms') ?>">Terms</a> before participating.</span>
        </div>
    </div>
</footer>
