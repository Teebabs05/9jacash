<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';
    $positionId = (int) ($_POST['position_id'] ?? 0);

    if (in_array($action, ['pause', 'resume'], true) && $positionId > 0) {
        $result = mining_toggle_status($positionId, (int) $user['id'], $action);
        flash('mining', $result['message'], $result['success'] ? 'success' : 'error');
    }

    redirect(rtrim(APP_URL, '/') . '/mining/index.php');
}

$plans = db()->query("SELECT * FROM mining_plans WHERE status = 'active' ORDER BY price ASC")->fetchAll();

$stmt = db()->prepare(
    'SELECT um.*, mp.name AS plan_name, mp.daily_return AS plan_daily_return, mp.duration_days AS plan_duration_days
     FROM user_mining um
     INNER JOIN mining_plans mp ON mp.id = um.plan_id
     WHERE um.user_id = ?
     ORDER BY um.created_at DESC'
);
$stmt->execute([$user['id']]);
$positions = $stmt->fetchAll();

$wallet = get_wallet((int) $user['id']);

$pageTitle = 'Mining';
$activeNav = 'mining';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <p class="mb-0" style="color:var(--text-muted);">Invest in a mining plan and earn automatic daily returns.</p>
    <div class="card-surface px-3 py-2 small">Main Wallet Balance: <strong><?= e(money($wallet['main_balance'])) ?></strong></div>
</div>

<h5 class="fw-bold mb-3">Available Mining Plans</h5>
<div class="row g-4">
    <?php if (!$plans): ?>
        <div class="col-12 text-center py-4" style="color:var(--text-muted);">No mining plans are available right now. Please check back later.</div>
    <?php endif; ?>
    <?php foreach ($plans as $plan): ?>
        <div class="col-xl-3 col-md-6">
            <div class="mining-plan-card">
                <h5 class="fw-bold mb-1"><?= e($plan['name']) ?></h5>
                <div class="price"><?= e(money($plan['price'])) ?></div>
                <div class="small" style="color:var(--text-muted);"><?= e($plan['description']) ?></div>
                <ul>
                    <li><i class="bi bi-check-circle-fill text-success"></i> <?= e(money($plan['daily_return'])) ?> / day</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> <?= (int) $plan['duration_days'] ?> day cycle</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> Total: <?= e(money($plan['daily_return'] * $plan['duration_days'])) ?></li>
                </ul>
                <a href="invest.php?plan=<?= (int) $plan['id'] ?>" class="btn btn-brand w-100">Invest Now</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h5 class="fw-bold mb-3 mt-5">My Mining Positions</h5>
<div class="card-surface p-4">
    <?php if (!$positions): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-cpu" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">You have no mining positions yet. Choose a plan above to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead>
                    <tr><th>Plan</th><th>Invested</th><th>Total Earned</th><th>Progress</th><th>Next Payout</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $p):
                        $totalDuration = strtotime($p['ends_at']) - strtotime($p['started_at']);
                        $elapsed = time() - strtotime($p['started_at']);
                        $progress = $totalDuration > 0 ? min(100, max(0, (int) round(($elapsed / $totalDuration) * 100))) : 100;
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= e($p['plan_name']) ?></td>
                            <td><?= e(money($p['amount_invested'])) ?></td>
                            <td class="text-success fw-semibold">+<?= e(money($p['total_earned'])) ?></td>
                            <td style="min-width:140px;">
                                <div class="progress-thin"><span style="width:<?= $progress ?>%;"></span></div>
                                <div class="small mt-1" style="color:var(--text-muted);"><?= $progress ?>% complete</div>
                            </td>
                            <td>
                                <?php if ($p['status'] === MINING_STATUS_ACTIVE): ?>
                                    <span class="countdown-chip" data-countdown data-target="<?= e(date(DATE_ATOM, strtotime($p['next_payout_at']))) ?>">--</span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="pill pill-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                            <td class="text-end">
                                <?php if ($p['status'] === MINING_STATUS_ACTIVE): ?>
                                    <form method="POST" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="pause">
                                        <input type="hidden" name="position_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-outline-brand btn-sm">Pause</button>
                                    </form>
                                <?php elseif ($p['status'] === MINING_STATUS_PAUSED): ?>
                                    <form method="POST" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="resume">
                                        <input type="hidden" name="position_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-brand btn-sm">Resume</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="<?= e($assetBase) ?>/js/countdown.js"></script>
<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
