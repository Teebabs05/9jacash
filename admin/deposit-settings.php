<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $minDeposit = (float) ($_POST['min_deposit'] ?? 0);
    $maxDeposit = (float) ($_POST['max_deposit'] ?? 0);

    if ($minDeposit <= 0 || $maxDeposit <= $minDeposit) {
        $errors[] = 'Please enter a valid minimum and maximum deposit range.';
    }

    if (!$errors) {
        set_setting('min_deposit', (string) $minDeposit);
        set_setting('max_deposit', (string) $maxDeposit);
        set_setting('deposit_bank_name', clean($_POST['deposit_bank_name'] ?? ''));
        set_setting('deposit_bank_account_number', clean($_POST['deposit_bank_account_number'] ?? ''));
        set_setting('deposit_bank_account_name', clean($_POST['deposit_bank_account_name'] ?? ''));
        set_setting('deposit_usdt_address', clean($_POST['deposit_usdt_address'] ?? ''));
        set_setting('deposit_usdt_network', clean($_POST['deposit_usdt_network'] ?? 'TRC20'));
        set_setting('payvessel_enabled', isset($_POST['payvessel_enabled']) ? '1' : '0');
        set_setting('payvessel_public_key', clean($_POST['payvessel_public_key'] ?? ''));
        set_setting('payvessel_secret_key', clean($_POST['payvessel_secret_key'] ?? ''));
        set_setting('payvessel_business_id', clean($_POST['payvessel_business_id'] ?? ''));
        set_setting('payvessel_bank_codes', clean($_POST['payvessel_bank_codes'] ?? '120001'));

        log_activity(null, (int) $admin['id'], 'deposit_settings_updated', 'Updated deposit settings');
        flash('deposit_settings', 'Deposit settings updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/deposit-settings.php');
    }
}

$pageTitle = 'Deposit Settings';
$activeNav = 'deposit-settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <?= csrf_field() ?>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Deposit Limits</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Minimum Deposit (₦)</label>
                <input type="number" step="0.01" class="form-control" name="min_deposit" value="<?= e((string) get_setting('min_deposit', 500)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Maximum Deposit (₦)</label>
                <input type="number" step="0.01" class="form-control" name="max_deposit" value="<?= e((string) get_setting('max_deposit', 1000000)) ?>" required>
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">Manual Bank Transfer Details</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small">Bank Name</label>
                <input type="text" class="form-control" name="deposit_bank_name" value="<?= e((string) get_setting('deposit_bank_name', '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Account Number</label>
                <input type="text" class="form-control" name="deposit_bank_account_number" value="<?= e((string) get_setting('deposit_bank_account_number', '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Account Name</label>
                <input type="text" class="form-control" name="deposit_bank_account_name" value="<?= e((string) get_setting('deposit_bank_account_name', '')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">USDT Deposit Details</h5>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small">Wallet Address</label>
                <input type="text" class="form-control" name="deposit_usdt_address" value="<?= e((string) get_setting('deposit_usdt_address', '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Network</label>
                <input type="text" class="form-control" name="deposit_usdt_network" value="<?= e((string) get_setting('deposit_usdt_network', 'TRC20')) ?>">
            </div>
        </div>
    </div>

    <div class="card-surface p-4 mb-4">
        <h5 class="fw-bold mb-3">PayVessel (Automatic Bank Transfer)</h5>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="payvessel_enabled" name="payvessel_enabled" <?= get_setting('payvessel_enabled', false) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="payvessel_enabled">Enable automatic PayVessel deposits</label>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">API Key (public)</label>
                <input type="text" class="form-control" name="payvessel_public_key" value="<?= e((string) get_setting('payvessel_public_key', '')) ?>" placeholder="PVKEY-...">
            </div>
            <div class="col-md-6">
                <label class="form-label small">API Secret</label>
                <input type="password" class="form-control" name="payvessel_secret_key" value="<?= e((string) get_setting('payvessel_secret_key', '')) ?>" placeholder="PVSECRET-...">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Business ID</label>
                <input type="text" class="form-control" name="payvessel_business_id" value="<?= e((string) get_setting('payvessel_business_id', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Bank Codes (comma-separated)</label>
                <input type="text" class="form-control" name="payvessel_bank_codes" value="<?= e((string) get_setting('payvessel_bank_codes', '120001')) ?>">
            </div>
        </div>
        <div class="form-text mt-2">
            Webhook URL to configure in your PayVessel dashboard:
            <code><?= e(rtrim(APP_URL, '/')) ?>/api/payvessel-webhook.php</code>
        </div>
    </div>

    <button type="submit" class="btn btn-brand">Save Settings</button>
</form>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
