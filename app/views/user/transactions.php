<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">Transaction History</h6>
        <form class="d-flex gap-2" method="get">
            <select name="wallet_type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Wallets</option>
                <?php foreach (['main','bonus','referral','mining','task'] as $w): ?>
                    <option value="<?= $w ?>" <?= $walletType === $w ? 'selected' : '' ?>><?= ucfirst($w) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Description</th><th>Wallet</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Reference</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $tx): ?>
                <tr>
                    <td><?= e($tx['description'] ?: ucfirst(str_replace('_',' ',$tx['category']))) ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis text-capitalize"><?= e($tx['wallet_type']) ?></span></td>
                    <td><span class="badge <?= $tx['type'] === 'credit' ? 'badge-status-approved' : 'badge-status-rejected' ?> text-capitalize"><?= e($tx['type']) ?></span></td>
                    <td class="<?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?> fw-semibold"><?= $tx['type'] === 'credit' ? '+' : '-' ?><?= money($tx['amount']) ?></td>
                    <td><?= money($tx['balance_after']) ?></td>
                    <td class="small text-muted-soft"><?= e($tx['reference'] ?: '-') ?></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($tx['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted-soft py-4">No transactions found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('transactions') . ($walletType ? '?wallet_type=' . urlencode($walletType) : ''); ?>
    <?php require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
