<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">Support Tickets</h6>
        <form class="d-flex gap-2" method="get">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach (['open','answered','closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $t): ?>
                <tr class="cursor-pointer" onclick="location.href='<?= base_url('admin/support/' . $t['id']) ?>'" style="cursor:pointer;">
                    <td><?= e($t['username']) ?></td>
                    <td><?= e($t['subject']) ?></td>
                    <td class="text-capitalize"><?= e($t['category']) ?></td>
                    <td class="text-capitalize"><?= e($t['priority']) ?></td>
                    <td><span class="badge badge-status-<?= $t['status'] === 'closed' ? 'suspended' : ($t['status'] === 'answered' ? 'approved' : 'pending') ?> text-capitalize"><?= e($t['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= time_ago($t['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No tickets found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/support') . ($status ? '?status=' . $status : ''); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
