<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $rewardAmount = (float) ($_POST['ad_reward_amount'] ?? 0);
        $dailyLimit = (int) ($_POST['ad_daily_limit'] ?? 0);
        $cooldownSeconds = (int) ($_POST['ad_cooldown_seconds'] ?? 0);
        $watchDuration = (int) ($_POST['ad_watch_duration_seconds'] ?? 0);

        if ($rewardAmount <= 0) {
            $errors[] = 'Reward per ad must be greater than zero.';
        }
        if ($dailyLimit <= 0) {
            $errors[] = 'Daily ad limit must be greater than zero.';
        }
        if ($cooldownSeconds < 0) {
            $errors[] = 'Cooldown cannot be negative.';
        }
        if ($watchDuration <= 0) {
            $errors[] = 'Ad watch duration must be greater than zero.';
        }

        if (!$errors) {
            set_setting('ad_reward_amount', (string) $rewardAmount);
            set_setting('ad_daily_limit', (string) $dailyLimit);
            set_setting('ad_cooldown_seconds', (string) $cooldownSeconds);
            set_setting('ad_watch_duration_seconds', (string) $watchDuration);

            log_activity(null, (int) $admin['id'], 'watch_settings_saved', 'Updated Watch & Earn settings');
            flash('watch', 'Watch & Earn settings updated.', 'success');
            redirect(rtrim(APP_URL, '/') . '/admin/watch-settings.php');
        }
    }
}

$today = db()->query(
    "SELECT COUNT(*) AS watches, COALESCE(SUM(reward_amount), 0) AS paid FROM ads_logs WHERE watched_at >= CURDATE()"
)->fetch();

$recentLogs = db()->query(
    'SELECT al.*, u.username FROM ads_logs al
     INNER JOIN users u ON u.id = al.user_id
     ORDER BY al.watched_at DESC LIMIT 50'
)->fetchAll();

$pageTitle = 'Watch & Earn Settings';
$activeNav = 'watch-settings';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card-surface p-4">
            <div class="small" style="color:var(--text-muted);">Ads Watched Today</div>
            <div class="h3 fw-bold mb-0"><?= number_format((int) $today['watches']) ?></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card-surface p-4">
            <div class="small" style="color:var(--text-muted);">Paid Out Today</div>
            <div class="h3 fw-bold mb-0"><?= e(money($today['paid'])) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Watch &amp; Earn Settings</h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_settings">

                <div class="mb-3">
                    <label class="form-label small">Reward per ad (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="ad_reward_amount" value="<?= e((string) get_setting('ad_reward_amount', 10)) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Daily ad limit per user</label>
                    <input type="number" min="1" class="form-control" name="ad_daily_limit" value="<?= e((string) get_setting('ad_daily_limit', 10)) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Cooldown between ads (seconds)</label>
                    <input type="number" min="0" class="form-control" name="ad_cooldown_seconds" value="<?= e((string) get_setting('ad_cooldown_seconds', 30)) ?>" required>
                    <div class="form-text">How long a user must wait after one ad before starting the next.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Ad watch duration (seconds)</label>
                    <input type="number" min="1" class="form-control" name="ad_watch_duration_seconds" value="<?= e((string) get_setting('ad_watch_duration_seconds', 15)) ?>" required>
                    <div class="form-text">Minimum time a user must wait before the reward can be claimed.</div>
                </div>

                <button type="submit" class="btn btn-brand w-100">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Recent Ad Rewards</h5>
            <?php if (!$recentLogs): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No ads watched yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>User</th><th>Reward</th><th>Watched At</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($log['username']) ?></td>
                                    <td><?= e(money($log['reward_amount'])) ?></td>
                                    <td><?= e(date('M j, Y g:ia', strtotime((string) $log['watched_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
