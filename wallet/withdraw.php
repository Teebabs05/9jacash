<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$errors = [];

$stmt = db()->prepare('SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);

    if ($bankAccountId <= 0) {
        $errors[] = 'Please select a withdrawal account.';
    }

    if (!$errors) {
        $result = withdrawals_create((int) $user['id'], $bankAccountId, $amount);

        if ($result['success']) {
            flash('withdraw', $result['message'], 'success');
            redirect(rtrim(APP_URL, '/') . '/wallet/withdraw.php');
        }

        $errors[] = $result['message'];
    }
}

$minWithdrawal = (float) get_setting('min_withdrawal', 1000);
$maxWithdrawal = (float) get_setting('max_withdrawal', 500000);
$chargePercent = (float) get_setting('withdrawal_charge_percent', 2);
$dailyLimit = (int) get_setting('daily_withdrawal_limit', 1);
$usedToday = withdrawals_today_count((int) $user['id']);
$availableBalance = wallet_total_balance((int) $user['id']);

$stmt = db()->prepare('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
$stmt->execute([$user['id']]);
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Withdraw Funds';
$activeNav = 'withdraw';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-1">Request Withdrawal</h5>
            <p class="small mb-3" style="color:var(--text-muted);">Available balance: <strong><?= e(money($availableBalance)) ?></strong></p>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if (!$accounts): ?>
                <div class="alert alert-warning small py-2 px-3 mb-3">You need to add a withdrawal account first.</div>
                <a href="bank-accounts.php" class="btn btn-brand w-100">Add Withdrawal Account</a>
            <?php elseif ($usedToday >= $dailyLimit): ?>
                <div class="alert alert-warning small py-2 px-3">You've used today's withdrawal request limit (<?= $dailyLimit ?>). Please try again tomorrow.</div>
            <?php else: ?>
                <form method="POST" action="" data-loading-submit>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small">Withdrawal Account</label>
                        <select class="form-select" name="bank_account_id" required>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= (int) $acc['id'] ?>">
                                    <?= $acc['type'] === 'bank' ? e($acc['bank_name'] . ' - ' . $acc['account_number']) : e('USDT (' . $acc['network'] . ') ' . substr($acc['usdt_address'], 0, 10) . '...') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small">Amount (₦)</label>
                            <input type="number" step="0.01" min="<?= $minWithdrawal ?>" max="<?= min($maxWithdrawal, $availableBalance) ?>" class="form-control" name="amount" id="wdAmount" data-currency-group="wd" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label small">&asymp; USD</label>
                            <input type="number" step="0.01" class="form-control" data-currency-usd="wd" placeholder="0.00">
                        </div>
                        <div class="form-text">Min <?= e(money($minWithdrawal)) ?> — Max <?= e(money($maxWithdrawal)) ?></div>
                    </div>

                    <div class="card-surface p-3 mb-3" style="background:var(--surface-alt);">
                        <div class="d-flex justify-content-between small"><span>Charge (<?= e((string) $chargePercent) ?>%)</span><strong id="wdCharge"><?= e(money(0)) ?></strong></div>
                        <div class="d-flex justify-content-between small mt-1"><span>You will receive</span><strong id="wdNet" class="text-success"><?= e(money(0)) ?></strong></div>
                    </div>

                    <button type="submit" class="btn btn-brand w-100">Request Withdrawal</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Withdrawal History</h5>
            <?php if (!$withdrawals): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No withdrawal requests yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Amount</th><th>Charge</th><th>Net</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td><?= e(money($w['amount'])) ?></td>
                                    <td class="text-danger">-<?= e(money($w['charge'])) ?></td>
                                    <td class="fw-semibold"><?= e(money($w['net_amount'])) ?></td>
                                    <td class="text-uppercase small"><?= e($w['method']) ?></td>
                                    <td><span class="pill pill-<?= e($w['status']) ?>"><?= e(ucfirst($w['status'])) ?></span></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(time_ago($w['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('wdAmount');
    if (!amountInput) return;
    const chargePercent = <?= json_encode($chargePercent) ?>;
    const chargeEl = document.getElementById('wdCharge');
    const netEl = document.getElementById('wdNet');

    amountInput.addEventListener('input', function () {
        const amount = parseFloat(amountInput.value) || 0;
        const charge = amount * chargePercent / 100;
        const net = amount - charge;
        chargeEl.textContent = '₦' + charge.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        netEl.textContent = '₦' + net.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
});
</script>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
