<div class="surface-card p-4">
    <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy text-warning me-2"></i>Top Earners Leaderboard</h6>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>User</th><th>Referrals</th><th>Total Earned</th></tr></thead>
            <tbody>
            <?php foreach ($leaders as $i => $l): ?>
                <tr>
                    <td class="fw-bold"><?= $i + 1 ?><?= $i < 3 ? ' 🏆' : '' ?></td>
                    <td class="d-flex align-items-center gap-2">
                        <img src="<?= e(user_avatar_url($l)) ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;">
                        <?= e($l['username']) ?>
                    </td>
                    <td><?= (int) $l['referral_count'] ?></td>
                    <td class="fw-semibold text-success"><?= money($l['total_earned']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($leaders)): ?><tr><td colspan="4" class="text-center text-muted-soft py-4">No data yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
