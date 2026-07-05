<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Webhook Logs</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Provider</th><th>Reference</th><th>Signature</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $w): ?>
                <tr>
                    <td class="text-capitalize"><?= e($w['provider']) ?></td>
                    <td class="small"><?= e($w['reference'] ?: '-') ?></td>
                    <td><span class="badge <?= $w['signature_valid'] ? 'badge-status-approved' : 'badge-status-rejected' ?>"><?= $w['signature_valid'] ? 'Valid' : 'Invalid' ?></span></td>
                    <td class="small"><?= e($w['status']) ?></td>
                    <td class="small text-muted-soft"><?= time_ago($w['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted-soft py-4">No webhook activity yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/webhook-logs'); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
