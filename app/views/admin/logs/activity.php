<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Activity Logs</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Device</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $l): ?>
                <tr>
                    <td><?= e($l['username'] ?? 'System') ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e(str_replace('_',' ',$l['action'])) ?></span></td>
                    <td class="small"><?= e($l['description']) ?></td>
                    <td class="small text-muted-soft"><?= e($l['ip_address']) ?></td>
                    <td class="small text-muted-soft"><?= e($l['device']) ?></td>
                    <td class="small text-muted-soft"><?= time_ago($l['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No activity yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/activity-logs'); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
