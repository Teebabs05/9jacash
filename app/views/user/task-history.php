<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Task Submission History</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Task</th><th>Reward</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= money($s['reward_amount']) ?></td>
                    <td><span class="badge badge-status-<?= e($s['status']) ?> text-capitalize"><?= e($s['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($s['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($submissions)): ?><tr><td colspan="4" class="text-center text-muted-soft py-4">No submissions yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
