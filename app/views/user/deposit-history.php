<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Deposit History</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $d): ?>
                <tr>
                    <td class="small"><?= e($d['reference']) ?></td>
                    <td class="fw-semibold"><?= money($d['amount']) ?></td>
                    <td class="text-capitalize"><?= e($d['method']) ?></td>
                    <td><span class="badge badge-status-<?= e($d['status']) ?> text-capitalize"><?= e($d['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($d['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted-soft py-4">No deposits yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('deposit/history'); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
