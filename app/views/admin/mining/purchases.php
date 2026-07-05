<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Mining Purchases</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Plan</th><th>Invested</th><th>Progress</th><th>Earned</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= e($p['username']) ?></td>
                    <td><?= e($p['plan_name']) ?></td>
                    <td><?= money($p['amount_invested']) ?></td>
                    <td><?= (int) $p['days_completed'] ?>/<?= (int) $p['duration_days'] ?>d</td>
                    <td class="text-success fw-semibold"><?= money($p['total_earned']) ?></td>
                    <td><span class="badge badge-status-<?= e($p['status']) ?> text-capitalize"><?= e($p['status']) ?></span></td>
                    <td>
                        <?php if ($p['status'] === 'active'): ?>
                        <form method="post" action="<?= base_url('admin/mining/purchases/' . $p['id'] . '/force-complete') ?>" onsubmit="return confirm('Force complete this plan?')">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Force Complete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted-soft py-4">No purchases yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/mining/purchases'); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
