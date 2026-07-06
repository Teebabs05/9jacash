<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $result = checkin_perform((int) $user['id']);
    flash('checkin', $result['message'], $result['success'] ? 'success' : 'error');
    redirect(rtrim(APP_URL, '/') . '/checkin/index.php');
}

$alreadyCheckedIn = checkin_has_checked_in_today((int) $user['id']);
$nextStreakDay = checkin_next_streak_day((int) $user['id']);
$previewReward = checkin_reward_for_day($nextStreakDay);

$stmt = db()->prepare('SELECT * FROM daily_checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 10');
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();

$currentStreakDay = $alreadyCheckedIn ? $nextStreakDay : $nextStreakDay - 1;
if ($currentStreakDay < 0) {
    $currentStreakDay = 0;
}

$pageTitle = 'Daily Check-in';
$activeNav = 'checkin';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4 text-center">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:72px;height:72px;background:rgba(15,81,50,0.12);color:var(--brand-emerald);font-size:1.8rem;">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <h5 class="fw-bold">Day <?= $nextStreakDay ?> of <?= CHECKIN_CYCLE_DAYS ?></h5>
            <p class="small" style="color:var(--text-muted);">Current streak: <?= $currentStreakDay ?> day<?= $currentStreakDay === 1 ? '' : 's' ?></p>

            <div class="progress-thin mb-4"><span style="width:<?= (int) round($nextStreakDay / CHECKIN_CYCLE_DAYS * 100) ?>%;"></span></div>

            <?php if ($nextStreakDay === 7 || $nextStreakDay === CHECKIN_CYCLE_DAYS): ?>
                <div class="alert alert-warning small py-2 px-3 mb-3"><i class="bi bi-star-fill me-1"></i> Milestone day! Bonus reward multiplier applies today.</div>
            <?php endif; ?>

            <?php if ($alreadyCheckedIn): ?>
                <button type="button" class="btn btn-outline-brand w-100" disabled><i class="bi bi-check-circle-fill me-1"></i> Checked In Today</button>
            <?php else: ?>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-brand w-100">Check In &amp; Earn <?= e(money($previewReward)) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Check-in History</h5>
            <?php if (!$history): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No check-ins yet. Start your streak today!</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Day</th><th>Reward</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td>Day <?= (int) $h['streak_day'] ?></td>
                                    <td class="text-success fw-semibold">+<?= e(money($h['reward_amount'])) ?></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(date('M d, Y', strtotime($h['checkin_date']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
