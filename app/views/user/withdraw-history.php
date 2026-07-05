<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Withdrawal History</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Reference</th><th>Amount</th><th>Charge</th><th>Net</th><th>Bank</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $w): ?>
                <tr>
                    <td class="small"><?= e($w['reference']) ?></td>
                    <td><?= money($w['amount']) ?></td>
                    <td><?= money($w['charge']) ?></td>
                    <td class="fw-semibold"><?= money($w['net_amount']) ?></td>
                    <td class="small"><?= e($w['bank_name']) ?> - <?= e($w['account_number']) ?></td>
                    <td><span class="badge badge-status-<?= e($w['status']) ?> text-capitalize"><?= e($w['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($w['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted-soft py-4">No withdrawals yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('withdraw/history'); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
