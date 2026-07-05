<div class="surface-card p-4">
    <h6 class="fw-bold mb-3">Mining History</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Plan</th><th>Invested</th><th>Daily Profit</th><th>Progress</th><th>Total Earned</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
                <tr>
                    <td><?= e($p['plan_name']) ?></td>
                    <td><?= money($p['amount_invested']) ?></td>
                    <td><?= money($p['daily_profit']) ?></td>
                    <td><?= (int) $p['days_completed'] ?> / <?= (int) $p['duration_days'] ?> days</td>
                    <td class="fw-semibold text-success"><?= money($p['total_earned']) ?></td>
                    <td><span class="badge badge-status-<?= e($p['status']) ?> text-capitalize"><?= e($p['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No mining history yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
