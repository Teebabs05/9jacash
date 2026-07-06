<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!empty($_FILES['site_logo']['name'])) {
        // SVG is intentionally not accepted here: it can embed <script>,
        // which would execute if anyone ever navigates directly to the
        // uploaded file's URL (browsers treat a directly-loaded SVG as an
        // HTML-like document, not a flat image) - not worth the risk for
        // a logo when PNG/JPG/WEBP cover the same need.
        $error = validate_upload($_FILES['site_logo'], ['image/png', 'image/jpeg', 'image/webp'], 1 * 1024 * 1024);
        if ($error) {
            $errors[] = $error;
        } else {
            set_setting('site_logo', store_upload($_FILES['site_logo'], 'branding'));
        }
    }

    if (!empty($_FILES['site_banner']['name'])) {
        $error = validate_upload($_FILES['site_banner'], ['image/png', 'image/jpeg', 'image/webp'], 3 * 1024 * 1024);
        if ($error) {
            $errors[] = $error;
        } else {
            set_setting('site_banner', store_upload($_FILES['site_banner'], 'branding'));
        }
    }

    if (!$errors) {
        $fields = [
            'site_name', 'site_tagline', 'currency', 'currency_symbol', 'timezone', 'usd_exchange_rate',
            'contact_email', 'contact_phone', 'whatsapp_number',
            'facebook_url', 'twitter_url', 'instagram_url', 'telegram_url', 'whatsapp_url',
            'playstore_url', 'appstore_url',
            'google_analytics_id', 'facebook_pixel_id',
            'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_name', 'mail_from_address',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                set_setting($field, clean($_POST[$field]));
            }
        }

        if (!empty($_POST['mail_password'])) {
            set_setting('mail_password', $_POST['mail_password']);
        }

        set_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        set_setting('maintenance_message', clean($_POST['maintenance_message'] ?? ''));

        log_activity(null, (int) $admin['id'], 'site_settings_updated', 'Updated general site settings');
        flash('settings', 'Settings updated successfully.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/settings.php');
    }
}

$pageTitle = 'General Settings';
$activeNav = 'settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Branding</h5>
        <div class="row g-3 align-items-center mb-3">
            <div class="col-auto">
                <?php $logo = get_setting('site_logo', ''); ?>
                <?php if ($logo): ?>
                    <img src="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($logo) ?>" alt="Logo" style="width:56px;height:56px;object-fit:contain;border-radius:12px;background:var(--surface-alt);padding:6px;">
                <?php else: ?>
                    <?= brand_mark_html(56) ?>
                <?php endif; ?>
            </div>
            <div class="col">
                <label class="form-label small">Upload New Logo (PNG, JPG or WEBP, max 1MB)</label>
                <input type="file" class="form-control" name="site_logo" accept="image/png,image/jpeg,image/webp">
                <div class="form-text">Also used as the site favicon (browser tab icon).</div>
            </div>
        </div>
        <div class="row g-3 align-items-center mb-3">
            <div class="col-auto">
                <?php $banner = get_setting('site_banner', ''); ?>
                <?php if ($banner): ?>
                    <img src="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($banner) ?>" alt="Banner" style="width:140px;height:56px;object-fit:cover;border-radius:12px;background:var(--surface-alt);">
                <?php else: ?>
                    <div style="width:140px;height:56px;border-radius:12px;background:var(--surface-alt);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:.75rem;">No banner</div>
                <?php endif; ?>
            </div>
            <div class="col">
                <label class="form-label small">Upload Website Banner (PNG, JPG or WEBP, max 3MB)</label>
                <input type="file" class="form-control" name="site_banner" accept="image/png,image/jpeg,image/webp">
                <div class="form-text">Shown on the homepage hero section. Recommended widescreen image, e.g. 1600&times;600.</div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Site Name</label>
                <input type="text" class="form-control" name="site_name" value="<?= e((string) get_setting('site_name', 'SURECASH MINING')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Tagline</label>
                <input type="text" class="form-control" name="site_tagline" value="<?= e((string) get_setting('site_tagline', '')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Regional</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small">Currency Code</label>
                <input type="text" class="form-control" name="currency" value="<?= e((string) get_setting('currency', 'NGN')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Currency Symbol</label>
                <input type="text" class="form-control" name="currency_symbol" value="<?= e((string) get_setting('currency_symbol', '₦')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Timezone</label>
                <select class="form-select" name="timezone">
                    <?php foreach (['Africa/Lagos', 'Africa/Accra', 'Africa/Cairo', 'Africa/Johannesburg', 'Europe/London', 'UTC'] as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= get_setting('timezone', 'Africa/Lagos') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">USD Exchange Rate (₦ per $1)</label>
                <input type="number" step="0.01" min="1" class="form-control" name="usd_exchange_rate" value="<?= e((string) get_setting('usd_exchange_rate', 1500)) ?>">
                <div class="form-text">Powers the NGN &harr; USD converter shown next to deposit/withdrawal amounts and mining plan prices. Update this whenever the real rate moves.</div>
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Contact &amp; Social Links</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Contact Email</label>
                <input type="email" class="form-control" name="contact_email" value="<?= e((string) get_setting('contact_email', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Contact Phone</label>
                <input type="text" class="form-control" name="contact_phone" value="<?= e((string) get_setting('contact_phone', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">WhatsApp Support Number</label>
                <input type="text" class="form-control" name="whatsapp_number" value="<?= e((string) get_setting('whatsapp_number', '')) ?>" placeholder="e.g. 2348012345678">
                <div class="form-text">Full international number, digits only (no + or leading 0). Powers the floating WhatsApp button shown across the site.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Facebook URL</label>
                <input type="text" class="form-control" name="facebook_url" value="<?= e((string) get_setting('facebook_url', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Twitter/X URL</label>
                <input type="text" class="form-control" name="twitter_url" value="<?= e((string) get_setting('twitter_url', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Instagram URL</label>
                <input type="text" class="form-control" name="instagram_url" value="<?= e((string) get_setting('instagram_url', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Telegram URL</label>
                <input type="text" class="form-control" name="telegram_url" value="<?= e((string) get_setting('telegram_url', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">WhatsApp URL</label>
                <input type="text" class="form-control" name="whatsapp_url" value="<?= e((string) get_setting('whatsapp_url', '')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Mobile App Links</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Google Play Store URL</label>
                <input type="text" class="form-control" name="playstore_url" value="<?= e((string) get_setting('playstore_url', '')) ?>" placeholder="https://play.google.com/store/apps/details?id=...">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Apple App Store URL</label>
                <input type="text" class="form-control" name="appstore_url" value="<?= e((string) get_setting('appstore_url', '')) ?>" placeholder="https://apps.apple.com/app/...">
            </div>
            <div class="form-text">Download badges appear on the homepage and at the top of the user dashboard once set. Leave blank to hide a badge.</div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Analytics</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Google Analytics ID</label>
                <input type="text" class="form-control" name="google_analytics_id" value="<?= e((string) get_setting('google_analytics_id', '')) ?>" placeholder="G-XXXXXXX">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Facebook Pixel ID</label>
                <input type="text" class="form-control" name="facebook_pixel_id" value="<?= e((string) get_setting('facebook_pixel_id', '')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">SMTP Email Settings</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">SMTP Host</label>
                <input type="text" class="form-control" name="mail_host" value="<?= e((string) get_setting('mail_host', env('MAIL_HOST', ''))) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Port</label>
                <input type="text" class="form-control" name="mail_port" value="<?= e((string) get_setting('mail_port', env('MAIL_PORT', 587))) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Encryption</label>
                <select class="form-select" name="mail_encryption">
                    <option value="tls" <?= get_setting('mail_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= get_setting('mail_encryption', 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">SMTP Username</label>
                <input type="text" class="form-control" name="mail_username" value="<?= e((string) get_setting('mail_username', env('MAIL_USERNAME', ''))) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">SMTP Password</label>
                <input type="password" class="form-control" name="mail_password" placeholder="Leave blank to keep current password">
            </div>
            <div class="col-md-6">
                <label class="form-label small">From Name</label>
                <input type="text" class="form-control" name="mail_from_name" value="<?= e((string) get_setting('mail_from_name', 'SURECASH MINING')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">From Address</label>
                <input type="email" class="form-control" name="mail_from_address" value="<?= e((string) get_setting('mail_from_address', '')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Maintenance Mode</h5>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= get_setting('maintenance_mode', false) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="maintenance_mode">Enable maintenance mode (blocks all non-admin visitors)</label>
        </div>
        <label class="form-label small">Maintenance Message</label>
        <textarea class="form-control" name="maintenance_message" rows="2"><?= e((string) get_setting('maintenance_message', '')) ?></textarea>
    </div>

    <button type="submit" class="btn btn-brand">Save Settings</button>
</form>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
