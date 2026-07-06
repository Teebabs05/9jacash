<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$wallet = get_wallet((int) $user['id']);

$stmt = db()->prepare('SELECT * FROM wallet_ledger WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();

$sourceLabels = [
    'deposit' => 'Deposit',
    'withdrawal' => 'Withdrawal',
    'mining' => 'Mining',
    'task' => 'Task Reward',
    'ad' => 'Ad Reward',
    'spin' => 'Spin Wheel',
    'checkin' => 'Daily Check-in',
    'referral' => 'Referral Bonus',
    'admin_adjustment' => 'Admin Adjustment',
    'transfer' => 'Transfer',
];

$pageTitle = 'Wallet';
$activeNav = 'wallet';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row g-4">
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-wallet2"></i></div>
            <div class="label">Main Wallet</div>
            <div class="value"><?= e(money($wallet['main_balance'])) ?></div>
            <div class="small" style="color:var(--text-muted);">&asymp; <?= e(money_usd((float) $wallet['main_balance'])) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-gift-fill"></i></div>
            <div class="label">Bonus Wallet</div>
            <div class="value"><?= e(money($wallet['bonus_balance'])) ?></div>
            <div class="small" style="color:var(--text-muted);">&asymp; <?= e(money_usd((float) $wallet['bonus_balance'])) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-people-fill"></i></div>
            <div class="label">Referral Wallet</div>
            <div class="value"><?= e(money($wallet['referral_balance'])) ?></div>
            <div class="small" style="color:var(--text-muted);">&asymp; <?= e(money_usd((float) $wallet['referral_balance'])) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-cpu-fill"></i></div>
            <div class="label">Mining Wallet</div>
            <div class="value"><?= e(money($wallet['mining_balance'])) ?></div>
            <div class="small" style="color:var(--text-muted);">&asymp; <?= e(money_usd((float) $wallet['mining_balance'])) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Transactions</h5>
                <a href="history.php" class="small fw-semibold" style="color:var(--brand-emerald);">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if (!$recent): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">
                    <i class="bi bi-receipt" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">No transactions yet. Start earning to see activity here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead>
                            <tr><th>Description</th><th>Wallet</th><th>Amount</th><th>Date</th></tr>
                        </thead>
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
                                    <td>
                                        <?= e($rowDescription) ?>
                                        <span class="pill pill-<?= $row['type'] === 'credit' ? 'credit' : 'debit' ?> ms-1"><?= e(ucfirst($row['type'])) ?></span>
                                    </td>
                                    <td class="text-capitalize"><?= e($row['wallet_type']) ?></td>
                                    <td class="fw-semibold <?= $row['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                        <?= $row['type'] === 'credit' ? '+' : '-' ?><?= e(money($row['amount'])) ?>
                                    </td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(time_ago($row['created_at'])) ?></td>
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
            <h5 class="fw-bold mb-3">Quick Actions</h5>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/payments/deposit.php" class="btn btn-brand w-100 mb-2">
                <i class="bi bi-arrow-down-circle me-1"></i> Deposit Funds
            </a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/withdraw.php" class="btn btn-outline-brand w-100">
                <i class="bi bi-arrow-up-circle me-1"></i> Withdraw Funds
            </a>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Your Referral Link</h5>
            <div class="input-group mb-2">
                <input type="text" class="form-control form-control-sm" readonly id="refLink" value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
                <button class="btn btn-outline-brand btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); SureCashMining.toast('Referral link copied!');">Copy</button>
            </div>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/referrals.php" class="small fw-semibold" style="color:var(--brand-emerald);">View Referral Stats &amp; Leaderboard <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/transaction-detail-modal.php'; ?>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
