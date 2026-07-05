<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Two-Factor Authentication</h6>

            <?php if ($enabled): ?>
                <p class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Two-factor authentication is currently <strong>enabled</strong> on your account.</p>
                <form action="<?= base_url('profile/2fa/disable') ?>" method="post" onsubmit="return confirm('Disable two-factor authentication?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger rounded-pill px-4" type="submit">Disable 2FA</button>
                </form>
            <?php else: ?>
                <p class="text-muted-soft small">Scan-free setup: open your authenticator app (Google Authenticator, Authy, etc.), choose "Enter a setup key" and input the details below.</p>
                <div class="p-3 rounded-3 mb-3" style="background:var(--bg-surface-alt);">
                    <div class="small text-muted-soft">Account</div>
                    <div class="fw-semibold mb-2"><?= e(setting('site_name', '9JACASH')) ?>:<?= e(current_user()['email']) ?></div>
                    <div class="small text-muted-soft">Secret Key</div>
                    <div class="fw-bold fs-5" style="letter-spacing:2px;"><?= e($secret) ?></div>
                </div>
                <form method="post" action="<?= base_url('profile/2fa') ?>">
                    <?= csrf_field() ?>
                    <label class="form-label small fw-semibold">Enter the 6-digit code from your app to confirm</label>
                    <input type="text" name="code" class="form-control form-control-lg text-center mb-3" style="letter-spacing:8px;" maxlength="6" required>
                    <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Enable 2FA</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
