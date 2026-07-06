<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$userId = (int) $user['id'];

$stmt = db()->prepare('SELECT level, COUNT(*) AS c FROM referrals WHERE user_id = ? GROUP BY level');
$stmt->execute([$userId]);
$levelCounts = [1 => 0, 2 => 0, 3 => 0];
foreach ($stmt->fetchAll() as $row) {
    $levelCounts[(int) $row['level']] = (int) $row['c'];
}

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM referral_earnings WHERE user_id = ?');
$stmt->execute([$userId]);
$totalEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare(
    'SELECT u.username, u.full_name, u.created_at, u.status
     FROM referrals r INNER JOIN users u ON u.id = r.referred_id
     WHERE r.user_id = ? AND r.level = 1
     ORDER BY u.created_at DESC LIMIT 25'
);
$stmt->execute([$userId]);
$directReferrals = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT re.*, u.username FROM referral_earnings re INNER JOIN users u ON u.id = re.from_user_id
     WHERE re.user_id = ? ORDER BY re.created_at DESC LIMIT 15'
);
$stmt->execute([$userId]);
$earningsLog = $stmt->fetchAll();

$leaderboard = db()->query(
    'SELECT u.username, u.full_name, COALESCE(SUM(re.amount), 0) AS total
     FROM users u INNER JOIN referral_earnings re ON re.user_id = u.id
     GROUP BY u.id ORDER BY total DESC LIMIT 10'
)->fetchAll();

$levelPercents = [
    1 => (float) get_setting('referral_level_1_percent', 5),
    2 => (float) get_setting('referral_level_2_percent', 2),
    3 => (float) get_setting('referral_level_3_percent', 1),
];

$pageTitle = 'Referral Program';
$activeNav = 'referrals';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="card-surface p-4 mb-4">
    <h5 class="fw-bold mb-1">Your Referral Link</h5>
    <p class="small mb-3" style="color:var(--text-muted);">Earn <?= e((string) $levelPercents[1]) ?>% / <?= e((string) $levelPercents[2]) ?>% / <?= e((string) $levelPercents[3]) ?>% (levels 1/2/3) whenever the people you invite fund their wallet.</p>
    <div class="input-group">
        <input type="text" class="form-control" readonly id="refLink" value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
        <button class="btn btn-outline-brand" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); SureCashMining.toast('Referral link copied!');">Copy</button>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-people-fill"></i></div>
            <div class="label">Level 1 Referrals</div>
            <div class="value"><?= number_format($levelCounts[1]) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-diagram-3-fill"></i></div>
            <div class="label">Level 2 Referrals</div>
            <div class="value"><?= number_format($levelCounts[2]) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-share-fill"></i></div>
            <div class="label">Level 3 Referrals</div>
            <div class="value"><?= number_format($levelCounts[3]) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-cash-stack"></i></div>
            <div class="label">Total Referral Earnings</div>
            <div class="value"><?= e(money($totalEarnings)) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card-surface p-4 mb-4">
            <h5 class="fw-bold mb-3">Your Direct Referrals</h5>
            <?php if (!$directReferrals): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">No referrals yet. Share your link to start earning!</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>User</th><th>Status</th><th>Joined</th></tr></thead>
                        <tbody>
                            <?php foreach ($directReferrals as $r): ?>
                                <tr>
                                    <td><?= e($r['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($r['username']) ?></div></td>
                                    <td><span class="pill pill-<?= $r['status'] === 'active' ? 'active' : 'rejected' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(time_ago($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Recent Referral Earnings</h5>
            <?php if (!$earningsLog): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">No referral earnings yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <tbody>
                            <?php foreach ($earningsLog as $e): ?>
                                <tr>
                                    <td>Level <?= (int) $e['level'] ?> — from @<?= e($e['username']) ?></td>
                                    <td class="text-success fw-semibold text-end">+<?= e(money($e['amount'])) ?></td>
                                    <td class="small text-end" style="color:var(--text-muted);"><?= e(time_ago($e['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-trophy-fill me-1" style="color:var(--brand-gold-dark);"></i> Referral Leaderboard</h5>
            <?php if (!$leaderboard): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">No referral earnings recorded yet — be the first!</div>
            <?php else: ?>
                <?php foreach ($leaderboard as $i => $entry): ?>
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold" style="width:24px;color:<?= $i < 3 ? 'var(--brand-gold-dark)' : 'var(--text-muted)' ?>;">#<?= $i + 1 ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($entry['full_name']) ?></div>
                                <div class="small" style="color:var(--text-muted);">@<?= e($entry['username']) ?></div>
                            </div>
                        </div>
                        <strong class="text-success"><?= e(money($entry['total'])) ?></strong>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
