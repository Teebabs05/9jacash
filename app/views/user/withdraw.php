<div class="row g-3">
    <div class="col-lg-7">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-1">Request Withdrawal</h6>
            <p class="small text-muted-soft">Available: <strong><?= money($wallet['main_balance']) ?></strong> &middot; Charge: <?= $chargePercent ?>% &middot; Limit: <?= $todayCount ?>/<?= $dailyLimit ?> used today</p>

            <?php if (empty($bankAccounts)): ?>
                <div class="alert alert-warning small">Please add a bank account below before requesting a withdrawal.</div>
            <?php else: ?>
            <form method="post" action="<?= base_url('withdraw/store') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Amount</label>
                    <input type="number" step="0.01" name="amount" id="wAmount" class="form-control" min="<?= $minWithdrawal ?>" max="<?= min($maxWithdrawal, (float) $wallet['main_balance']) ?>" required>
                    <div class="form-text">You'll receive <strong id="wNet">₦0.00</strong> after <?= $chargePercent ?>% charge.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" class="form-select" required>
                        <?php foreach ($bankAccounts as $acc): ?>
                            <option value="<?= (int) $acc['id'] ?>"><?= e($acc['bank_name']) ?> — <?= e($acc['account_number']) ?> (<?= e($acc['account_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Submit Withdrawal Request</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Add Bank Account</h6>
            <form method="post" action="<?= base_url('withdraw/bank-account') ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-4"><input type="text" name="bank_name" class="form-control" placeholder="Bank Name" required></div>
                    <div class="col-md-4"><input type="text" name="account_number" class="form-control" placeholder="Account Number" required></div>
                    <div class="col-md-4"><input type="text" name="account_name" class="form-control" placeholder="Account Name" required></div>
                </div>
                <button class="btn btn-outline-brand rounded-pill px-4 mt-3" type="submit">Add Account</button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent Withdrawals</h6>
                <a href="<?= base_url('withdraw/history') ?>" class="small">View all</a>
            </div>
            <?php foreach ($recent as $w): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color:var(--border-color) !important;">
                    <div>
                        <div class="fw-semibold"><?= money($w['net_amount']) ?></div>
                        <div class="small text-muted-soft"><?= e($w['bank_name']) ?> &middot; <?= time_ago($w['created_at']) ?></div>
                    </div>
                    <span class="badge badge-status-<?= e($w['status']) ?> text-capitalize"><?= e($w['status']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?><p class="text-muted-soft small text-center py-3">No withdrawals yet.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php push_script('
var amountInput = document.getElementById("wAmount");
if (amountInput) {
    amountInput.addEventListener("input", function () {
        var amt = parseFloat(this.value) || 0;
        var net = amt - (amt * ' . (float) $chargePercent . ' / 100);
        document.getElementById("wNet").textContent = "₦" + net.toFixed(2);
    });
}
'); ?>
