<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">Task Submissions</h6>
        <form class="d-flex gap-2" method="get">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach (['pending','approved','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Task</th><th>Reward</th><th>Proof</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $s): ?>
                <tr>
                    <td><?= e($s['username']) ?></td>
                    <td><?= e($s['title']) ?></td>
                    <td><?= money($s['reward_amount']) ?></td>
                    <td>
                        <?php if ($s['proof_file']): ?><a href="<?= base_url('files/proofs/' . basename($s['proof_file'])) ?>" target="_blank">View</a><?php endif; ?>
                        <?php if ($s['proof_text']): ?><div class="small text-muted-soft"><?= e($s['proof_text']) ?></div><?php endif; ?>
                    </td>
                    <td><span class="badge badge-status-<?= e($s['status']) ?> text-capitalize"><?= e($s['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($s['created_at'])) ?></td>
                    <td>
                        <?php if ($s['status'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <form method="post" action="<?= base_url('admin/tasks-submissions/' . $s['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
                            <form method="post" action="<?= base_url('admin/tasks-submissions/' . $s['id'] . '/reject') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                        </div>
                        <?php else: ?><span class="small text-muted-soft">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted-soft py-4">No submissions found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/tasks-submissions') . ($status ? '?status=' . $status : ''); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
