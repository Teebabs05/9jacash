<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$wallet = get_wallet((int) $user['id']);

// "Earnings" means credits actually earned through platform activity -
// mining payouts, tasks, ads, spin wins, check-ins and referral bonuses.
// Deposits, withdrawal refunds, admin adjustments and internal
// transfers are real wallet credits too, but not earnings, so they're
// deliberately excluded here.
$earningSources = [
    LEDGER_SOURCE_MINING,
    LEDGER_SOURCE_TASK,
    LEDGER_SOURCE_AD,
    LEDGER_SOURCE_SPIN,
    LEDGER_SOURCE_CHECKIN,
    LEDGER_SOURCE_REFERRAL,
];
$sourcePlaceholders = implode(',', array_fill(0, count($earningSources), '?'));

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders})"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
$totalEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders}) AND created_at >= CURDATE()"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
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

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-2">
    <div>
        <h4 class="fw-bold mb-1">Welcome back, <?= e($user['full_name']) ?> 👋</h4>
        <p style="color:var(--text-muted);" class="mb-0">Here's what's happening with your account today.</p>
    </div>
    <?php require __DIR__ . '/../includes/partials/app-download-badges.php'; ?>
</div>

<div class="row g-4 mt-1">
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-wallet2"></i></div>
            <div class="label">Wallet Balance</div>
            <div class="value"><?= e(money(wallet_total_balance((int) $user['id']))) ?></div>
            <div class="small" style="color:var(--text-muted);">&asymp; <?= e(money_usd(wallet_total_balance((int) $user['id']))) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="label">Total Earnings</div>
            <div class="value"><?= e(money($totalEarnings)) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-sun-fill"></i></div>
            <div class="label">Today's Earnings</div>
            <div class="value"><?= e(money($todayEarnings)) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
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
                            <?php foreach ($recent as $row):
                                $rowDescription = $row['description'] ?: ($sourceLabels[$row['source']] ?? ucfirst($row['source']));
                            ?>
                                <tr data-ledger-row
                                    data-ledger-description="<?= e($rowDescription) ?>"
                                    data-ledger-wallet="<?= e($row['wallet_type']) ?>"
                                    data-ledger-type="<?= e(ucfirst($row['type'])) ?>"
                                    data-ledger-amount="<?= e(($row['type'] === 'credit' ? '+' : '-') . money($row['amount'])) ?>"
                                    data-ledger-balance="<?= e(money($row['balance_after'])) ?>"
                                    data-ledger-status="<?= e(ucfirst($row['status'])) ?>"
                                    data-ledger-reference="<?= e($row['reference'] ?: '-') ?>"
                                    data-ledger-date="<?= e(date('M d, Y H:i', strtotime($row['created_at']))) ?>">
                                    <td><?= e($rowDescription) ?></td>
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
            <div class="input-group mb-2">
                <input type="text" class="form-control form-control-sm" readonly id="refLink" value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
                <button class="btn btn-outline-brand btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); SureCashMining.toast('Referral link copied!');">Copy</button>
            </div>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/referrals.php" class="small fw-semibold" style="color:var(--brand-emerald);">View Referral Stats &amp; Leaderboard <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Quick Access</h5>
            <div class="quick-access-grid">
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-wallet2"></i></span>
                    <span>My Wallet</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/mining/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-cpu-fill"></i></span>
                    <span>Mining</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/tasks/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-list-check"></i></span>
                    <span>Task Center</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/spin/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-disc-fill"></i></span>
                    <span>Spin Wheel</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/ads/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(240,68,56,0.12);color:var(--danger);"><i class="bi bi-play-btn-fill"></i></span>
                    <span>Watch &amp; Earn</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/checkin/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-calendar-check-fill"></i></span>
                    <span>Daily Check-in</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/transaction-detail-modal.php'; ?>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
