<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Support Tickets</h6>
        <a href="<?= base_url('support/new') ?>" class="btn btn-brand btn-sm rounded-pill px-3"><i class="fa-solid fa-plus me-1"></i>New Ticket</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr class="cursor-pointer" onclick="location.href='<?= base_url('support/' . $t['id']) ?>'" style="cursor:pointer;">
                    <td><?= e($t['subject']) ?></td>
                    <td class="text-capitalize"><?= e($t['category']) ?></td>
                    <td class="text-capitalize"><?= e($t['priority']) ?></td>
                    <td><span class="badge badge-status-<?= $t['status'] === 'closed' ? 'suspended' : ($t['status'] === 'answered' ? 'approved' : 'pending') ?> text-capitalize"><?= e($t['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= time_ago($t['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?><tr><td colspan="5" class="text-center text-muted-soft py-4">No tickets yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
