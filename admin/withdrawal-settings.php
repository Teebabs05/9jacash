<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $minWithdrawal = (float) ($_POST['min_withdrawal'] ?? 0);
    $maxWithdrawal = (float) ($_POST['max_withdrawal'] ?? 0);
    $chargePercent = (float) ($_POST['withdrawal_charge_percent'] ?? 0);
    $dailyLimit = (int) ($_POST['daily_withdrawal_limit'] ?? 1);

    if ($minWithdrawal <= 0 || $maxWithdrawal <= $minWithdrawal) {
        $errors[] = 'Please enter a valid minimum and maximum withdrawal range.';
    }

    if ($chargePercent < 0 || $chargePercent > 100) {
        $errors[] = 'Charge percentage must be between 0 and 100.';
    }

    if ($dailyLimit < 1) {
        $errors[] = 'Daily withdrawal limit must be at least 1.';
    }

    if (!$errors) {
        set_setting('min_withdrawal', (string) $minWithdrawal);
        set_setting('max_withdrawal', (string) $maxWithdrawal);
        set_setting('withdrawal_charge_percent', (string) $chargePercent);
        set_setting('daily_withdrawal_limit', (string) $dailyLimit);

        log_activity(null, (int) $admin['id'], 'withdrawal_settings_updated', 'Updated withdrawal settings');
        flash('withdrawal_settings', 'Withdrawal settings updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/withdrawal-settings.php');
    }
}

$pageTitle = 'Withdrawal Settings';
$activeNav = 'withdrawal-settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card-surface p-4" style="max-width:640px;">
    <h5 class="fw-bold mb-3">Withdrawal Limits &amp; Charges</h5>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Minimum Withdrawal (₦)</label>
                <input type="number" step="0.01" class="form-control" name="min_withdrawal" value="<?= e((string) get_setting('min_withdrawal', 1000)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Maximum Withdrawal (₦)</label>
                <input type="number" step="0.01" class="form-control" name="max_withdrawal" value="<?= e((string) get_setting('max_withdrawal', 500000)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Withdrawal Charge (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="withdrawal_charge_percent" value="<?= e((string) get_setting('withdrawal_charge_percent', 2)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Daily Withdrawal Request Limit</label>
                <input type="number" min="1" class="form-control" name="daily_withdrawal_limit" value="<?= e((string) get_setting('daily_withdrawal_limit', 1)) ?>" required>
                <div class="form-text">Maximum number of withdrawal requests a user can submit per day.</div>
            </div>
        </div>
        <button type="submit" class="btn btn-brand mt-3">Save Settings</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
