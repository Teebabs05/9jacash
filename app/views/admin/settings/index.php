<div class="surface-card p-4">
    <ul class="nav nav-pills mb-4 flex-wrap gap-1">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general">General</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#mail">SMTP / Email</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#payvessel">PayVessel</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#recaptcha">reCAPTCHA</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#maintenance">Maintenance</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="general">
            <form method="post" action="<?= base_url('admin/settings') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="general">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small">Site Name</label><input type="text" name="site_name" class="form-control" value="<?= e(setting('site_name')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?= e(setting('site_tagline')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?= e(setting('currency_symbol')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Currency Code</label><input type="text" name="currency_code" class="form-control" value="<?= e(setting('currency_code')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?= e(setting('contact_email')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Contact Phone</label><input type="text" name="contact_phone" class="form-control" value="<?= e(setting('contact_phone')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"></div>
                    <div class="col-md-6"><label class="form-label small">Favicon</label><input type="file" name="favicon" class="form-control" accept="image/*"></div>
                    <div class="col-md-3"><label class="form-label small">Facebook URL</label><input type="text" name="social_facebook" class="form-control" value="<?= e(setting('social_facebook')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Twitter/X URL</label><input type="text" name="social_twitter" class="form-control" value="<?= e(setting('social_twitter')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Instagram URL</label><input type="text" name="social_instagram" class="form-control" value="<?= e(setting('social_instagram')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Telegram URL</label><input type="text" name="social_telegram" class="form-control" value="<?= e(setting('social_telegram')) ?>"></div>

                    <div class="col-12"><hr></div>
                    <div class="col-md-3"><label class="form-label small">Signup Bonus</label><input type="number" step="0.01" name="registration_bonus" class="form-control" value="<?= e(setting('registration_bonus')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Min Deposit</label><input type="number" step="0.01" name="min_deposit" class="form-control" value="<?= e(setting('min_deposit')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Max Deposit</label><input type="number" step="0.01" name="max_deposit" class="form-control" value="<?= e(setting('max_deposit')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Withdrawal Charge %</label><input type="number" step="0.01" name="withdrawal_charge_percent" class="form-control" value="<?= e(setting('withdrawal_charge_percent')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Min Withdrawal</label><input type="number" step="0.01" name="min_withdrawal" class="form-control" value="<?= e(setting('min_withdrawal')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Max Withdrawal</label><input type="number" step="0.01" name="max_withdrawal" class="form-control" value="<?= e(setting('max_withdrawal')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Daily Withdrawal Limit (count)</label><input type="number" name="daily_withdrawal_limit" class="form-control" value="<?= e(setting('daily_withdrawal_limit')) ?>"></div>

                    <div class="col-md-6 form-check mt-3"><input class="form-check-input" type="checkbox" name="kyc_required" value="1" <?= setting('kyc_required') === '1' ? 'checked' : '' ?> id="kycReq"><label class="form-check-label" for="kycReq">Require KYC Verification</label></div>
                    <div class="col-md-6 form-check mt-3"><input class="form-check-input" type="checkbox" name="email_verification_required" value="1" <?= setting('email_verification_required') === '1' ? 'checked' : '' ?> id="emailReq"><label class="form-check-label" for="emailReq">Require Email Verification</label></div>
                </div>
                <button class="btn btn-brand rounded-pill px-4 mt-4">Save General Settings</button>
            </form>
        </div>

        <div class="tab-pane fade" id="mail">
            <form method="post" action="<?= base_url('admin/settings') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="mail">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small">SMTP Host</label><input type="text" name="mail_host" class="form-control" value="<?= e(config('mail.host')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Port</label><input type="number" name="mail_port" class="form-control" value="<?= e(config('mail.port')) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Encryption</label>
                        <select name="mail_encryption" class="form-select">
                            <option value="tls" <?= config('mail.encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= config('mail.encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small">SMTP Username</label><input type="text" name="mail_username" class="form-control" value="<?= e(config('mail.username')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">SMTP Password</label><input type="password" name="mail_password" class="form-control" placeholder="Leave blank to keep current"></div>
                    <div class="col-md-6"><label class="form-label small">From Address</label><input type="email" name="mail_from_address" class="form-control" value="<?= e(config('mail.from_address')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">From Name</label><input type="text" name="mail_from_name" class="form-control" value="<?= e(config('mail.from_name')) ?>"></div>
                </div>
                <button class="btn btn-brand rounded-pill px-4 mt-4">Save Email Settings</button>
            </form>
        </div>

        <div class="tab-pane fade" id="payvessel">
            <form method="post" action="<?= base_url('admin/settings') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="payvessel">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small">Public Key</label><input type="text" name="payvessel_public_key" class="form-control" value="<?= e(config('payvessel.public_key')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Secret Key</label><input type="password" name="payvessel_secret_key" class="form-control" placeholder="Leave blank to keep current"></div>
                    <div class="col-md-6"><label class="form-label small">Base URL</label><input type="text" name="payvessel_base_url" class="form-control" value="<?= e(config('payvessel.base_url')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Webhook Secret</label><input type="password" name="payvessel_webhook_secret" class="form-control" placeholder="Leave blank to keep current"></div>
                </div>
                <p class="small text-muted-soft mt-2">Webhook URL to configure in your PayVessel dashboard: <code><?= e(base_url('api/webhook/payvessel')) ?></code></p>
                <button class="btn btn-brand rounded-pill px-4 mt-2">Save PayVessel Settings</button>
            </form>
        </div>

        <div class="tab-pane fade" id="recaptcha">
            <form method="post" action="<?= base_url('admin/settings') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="recaptcha">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small">Site Key</label><input type="text" name="recaptcha_site_key" class="form-control" value="<?= e(config('recaptcha.site_key')) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Secret Key</label><input type="password" name="recaptcha_secret_key" class="form-control" placeholder="Leave blank to keep current"></div>
                    <div class="col-12 form-check"><input class="form-check-input" type="checkbox" name="recaptcha_enabled" value="1" <?= setting('recaptcha_enabled') === '1' ? 'checked' : '' ?> id="recaptchaEnabled"><label class="form-check-label" for="recaptchaEnabled">Enable reCAPTCHA on registration</label></div>
                </div>
                <button class="btn btn-brand rounded-pill px-4 mt-4">Save reCAPTCHA Settings</button>
            </form>
        </div>

        <div class="tab-pane fade" id="maintenance">
            <form method="post" action="<?= base_url('admin/settings') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="maintenance">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?> id="maintMode">
                    <label class="form-check-label" for="maintMode">Enable Maintenance Mode</label>
                </div>
                <p class="small text-muted-soft">When enabled, only administrators can access the site — all other visitors see a maintenance page.</p>
                <button class="btn btn-brand rounded-pill px-4">Save</button>
            </form>
        </div>
    </div>
</div>
