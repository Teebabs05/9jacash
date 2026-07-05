<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$wallet = get_wallet((int) $user['id']);

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger WHERE user_id = ? AND type = ?');
$stmt->execute([$user['id'], LEDGER_CREDIT]);
$totalEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger WHERE user_id = ? AND type = ? AND created_at >= CURDATE()');
$stmt->execute([$user['id'], LEDGER_CREDIT]);
$todayEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare('SELECT COUNT(*) AS c FROM referrals WHERE user_id = ? AND level = 1');
$stmt->execute([$user['id']]);
$directReferrals = (int) $stmt->fetch()['c'];

$stmt = db()->prepare('SELECT * FROM wallet_ledger WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();

$sourceLabels = [
    'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'mining' => 'Mining',
    'task' => 'Task Reward', 'ad' => 'Ad Reward', 'spin' => 'Spin Wheel',
    'checkin' => 'Daily Check-in', 'referral' => 'Referral Bonus',
    'admin_adjustment' => 'Admin Adjustment', 'transfer' => 'Transfer',
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<h4 class="fw-bold mb-1">Welcome back, <?= e($user['full_name']) ?> 👋</h4>
<p style="color:var(--text-muted);">Here's what's happening with your account today.</p>

<div class="row g-4 mt-1">
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-wallet2"></i></div>
            <div class="label">Wallet Balance</div>
            <div class="value"><?= e(money(wallet_total_balance((int) $user['id']))) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="label">Total Earnings</div>
            <div class="value"><?= e(money($totalEarnings)) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-sun-fill"></i></div>
            <div class="label">Today's Earnings</div>
            <div class="value"><?= e(money($todayEarnings)) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-people-fill"></i></div>
            <div class="label">Direct Referrals</div>
            <div class="value"><?= number_format($directReferrals) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Activity</h5>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/history.php" class="small fw-semibold" style="color:var(--brand-emerald);">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if (!$recent): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">
                    <i class="bi bi-receipt" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">No activity yet. Explore the earning tools to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <tbody>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td><?= e($row['description'] ?: ($sourceLabels[$row['source']] ?? ucfirst($row['source']))) ?></td>
                                    <td class="fw-semibold <?= $row['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                        <?= $row['type'] === 'credit' ? '+' : '-' ?><?= e(money($row['amount'])) ?>
                                    </td>
                                    <td class="small text-end" style="color:var(--text-muted);"><?= e(time_ago($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Your Referral Link</h5>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" readonly id="refLink" value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
                <button class="btn btn-outline-brand btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); NineJaCash.toast('Referral link copied!');">Copy</button>
            </div>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Quick Access</h5>
            <div class="d-grid gap-2">
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-wallet2 me-2"></i>My Wallet</a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/mining/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-cpu-fill me-2"></i>Mining</a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/tasks/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-list-check me-2"></i>Task Center</a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/spin/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-disc-fill me-2"></i>Spin Wheel</a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/ads/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-play-btn-fill me-2"></i>Watch &amp; Earn</a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/checkin/index.php" class="btn btn-outline-brand btn-sm text-start"><i class="bi bi-calendar-check-fill me-2"></i>Daily Check-in</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
