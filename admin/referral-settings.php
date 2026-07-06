<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $level1 = (float) ($_POST['referral_level_1_percent'] ?? 0);
    $level2 = (float) ($_POST['referral_level_2_percent'] ?? 0);
    $level3 = (float) ($_POST['referral_level_3_percent'] ?? 0);
    $maxLevels = (int) ($_POST['referral_max_levels'] ?? 3);
    $registrationBonus = (float) ($_POST['registration_bonus'] ?? 0);

    if ($level1 < 0 || $level2 < 0 || $level3 < 0 || $level1 > 100 || $level2 > 100 || $level3 > 100) {
        $errors[] = 'Percentages must be between 0 and 100.';
    }

    if ($maxLevels < 1 || $maxLevels > 3) {
        $errors[] = 'Maximum referral levels supported is 3.';
    }

    if (!$errors) {
        set_setting('referral_level_1_percent', (string) $level1);
        set_setting('referral_level_2_percent', (string) $level2);
        set_setting('referral_level_3_percent', (string) $level3);
        set_setting('referral_max_levels', (string) $maxLevels);
        set_setting('registration_bonus', (string) $registrationBonus);

        log_activity(null, (int) $admin['id'], 'referral_settings_updated', 'Updated referral program settings');
        flash('referral_settings', 'Referral settings updated.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/referral-settings.php');
    }
}

$stmt = db()->query(
    'SELECT COUNT(*) AS total_referrals, COALESCE(SUM(1), 0) AS c FROM referrals WHERE level = 1'
);
$totalDirectReferrals = (int) $stmt->fetch()['total_referrals'];

$totalPaidOut = (float) db()->query('SELECT COALESCE(SUM(amount), 0) AS c FROM referral_earnings')->fetch()['c'];

$pageTitle = 'Referral Settings';
$activeNav = 'referral-settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-people-fill"></i></div>
            <div class="label">Total Direct Referral Relationships</div>
            <div class="value"><?= number_format($totalDirectReferrals) ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-cash-stack"></i></div>
            <div class="label">Total Referral Bonuses Paid</div>
            <div class="value"><?= e(money($totalPaidOut)) ?></div>
        </div>
    </div>
</div>

<div class="card-surface p-4" style="max-width:640px;">
    <h5 class="fw-bold mb-3">Referral Program Configuration</h5>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small">Level 1 Bonus (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="referral_level_1_percent" value="<?= e((string) get_setting('referral_level_1_percent', 5)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Level 2 Bonus (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="referral_level_2_percent" value="<?= e((string) get_setting('referral_level_2_percent', 2)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Level 3 Bonus (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="referral_level_3_percent" value="<?= e((string) get_setting('referral_level_3_percent', 1)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Referral Levels Tracked</label>
                <input type="number" min="1" max="3" class="form-control" name="referral_max_levels" value="<?= e((string) get_setting('referral_max_levels', 3)) ?>" required>
                <div class="form-text">How many upline levels to record at registration (max 3).</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Registration Bonus (₦)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="registration_bonus" value="<?= e((string) get_setting('registration_bonus', 0)) ?>">
                <div class="form-text">Optional welcome bonus credited on signup (0 disables).</div>
            </div>
        </div>
        <div class="alert alert-info small py-2 px-3 mt-3">
            Bonuses are credited to the referral wallet whenever a referred user's deposit is approved. Percentages apply to the deposit amount at each level of the chain.
        </div>
        <button type="submit" class="btn btn-brand mt-2">Save Settings</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
