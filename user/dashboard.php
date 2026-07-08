<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$wallet = get_wallet((int) $user['id']);

// Admin-sent push notifications this user hasn't acknowledged yet -
// shown as a pop-up below, one at a time, on this page only.
$stmt = db()->prepare(
    "SELECT id, title, message FROM notifications WHERE user_id = ? AND type = 'broadcast' AND is_read = 0 ORDER BY created_at ASC"
);
$stmt->execute([$user['id']]);
$pendingBroadcasts = $stmt->fetchAll();

// "Earnings" means credits actually earned through platform activity -
// mining payouts, tasks, ads, spin wins, check-ins and referral bonuses.
// Deposits, withdrawal refunds, admin adjustments and internal
// transfers are real wallet credits too, but not earnings, so they're
// deliberately excluded here.
$earningSources = [
    LEDGER_SOURCE_MINING,
    LEDGER_SOURCE_TASK,
    LEDGER_SOURCE_AD,
    LEDGER_SOURCE_SPIN,
    LEDGER_SOURCE_CHECKIN,
    LEDGER_SOURCE_REFERRAL,
];
$sourcePlaceholders = implode(',', array_fill(0, count($earningSources), '?'));

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders})"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
$totalEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders}) AND created_at >= CURDATE()"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
$todayEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare('SELECT COUNT(*) AS c FROM referrals WHERE user_id = ? AND level = 1');
$stmt->execute([$user['id']]);
$directReferrals = (int) $stmt->fetch()['c'];

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source = ?"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, LEDGER_SOURCE_REFERRAL]);
$referralBonusesEarned = (float) $stmt->fetch()['total'];

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(mp.daily_return), 0) AS daily_total, COUNT(*) AS active_count
     FROM user_mining um
     INNER JOIN mining_plans mp ON mp.id = um.plan_id
     WHERE um.user_id = ? AND um.status = 'active'"
);
$stmt->execute([$user['id']]);
$dailyMiningSummary = $stmt->fetch();
$dailyMiningTotal = (float) $dailyMiningSummary['daily_total'];
$activePositionCount = (int) $dailyMiningSummary['active_count'];

$stmt = db()->prepare('SELECT * FROM wallet_ledger WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();

$sourceLabels = [
    'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'mining' => 'Mining',
    'task' => 'Task Reward', 'ad' => 'Ad Reward', 'spin' => 'Spin Wheel',
    'checkin' => 'Daily Check-in', 'referral' => 'Referral Bonus',
    'admin_adjustment' => 'Admin Adjustment', 'transfer' => 'Transfer',
];

// Week-over-week earnings trend for the balance card.
$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders}) AND created_at >= NOW() - INTERVAL 7 DAY"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
$thisWeekEarnings = (float) $stmt->fetch()['total'];

$stmt = db()->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_ledger
     WHERE user_id = ? AND type = ? AND source IN ({$sourcePlaceholders})
     AND created_at >= NOW() - INTERVAL 14 DAY AND created_at < NOW() - INTERVAL 7 DAY"
);
$stmt->execute([$user['id'], LEDGER_CREDIT, ...$earningSources]);
$lastWeekEarnings = (float) $stmt->fetch()['total'];

$weekTrendPercent = null;
if ($lastWeekEarnings > 0) {
    $weekTrendPercent = round((($thisWeekEarnings - $lastWeekEarnings) / $lastWeekEarnings) * 100, 1);
} elseif ($thisWeekEarnings > 0) {
    $weekTrendPercent = 100.0;
}

// Mini colorful preview of the real spin wheel segments, used as the
// "Spin & Earn" quick-action icon so it always matches whatever's
// actually configured in admin > Spin Wheel.
$spinColors = db()->query("SELECT color FROM spin_settings WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
if ($spinColors) {
    $sliceAngle = 360 / count($spinColors);
    $spinGradientParts = [];
    foreach ($spinColors as $i => $color) {
        $spinGradientParts[] = e($color) . ' ' . ($i * $sliceAngle) . 'deg ' . (($i + 1) * $sliceAngle) . 'deg';
    }
    $spinWheelGradient = 'conic-gradient(' . implode(', ', $spinGradientParts) . ')';
} else {
    $spinWheelGradient = 'linear-gradient(135deg, var(--brand-gold), var(--brand-emerald))';
}

// Preferred withdrawal account shown on the dashboard (default first,
// falling back to the oldest saved account).
$stmt = db()->prepare('SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at ASC LIMIT 1');
$stmt->execute([$user['id']]);
$primaryAccount = $stmt->fetch() ?: null;

// Daily check-in preview strip (today + next 4 days of the cycle).
$checkinAlready = checkin_has_checked_in_today((int) $user['id']);
$checkinNextDay = checkin_next_streak_day((int) $user['id']);
$checkinCurrentStreak = $checkinAlready ? $checkinNextDay : max(0, $checkinNextDay - 1);
$checkinStrip = [];
for ($i = 0; $i < 5; $i++) {
    $day = (($checkinNextDay - 1 + $i) % CHECKIN_CYCLE_DAYS) + 1;
    $checkinStrip[] = ['day' => $day, 'reward' => checkin_reward_for_day($day)];
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/partials/app-head.php';

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<?php $downloadBadgesCompact = true; require __DIR__ . '/../includes/partials/app-download-badges.php'; ?>

<div class="mb-3">
    <h4 class="fw-bold mb-1"><?= e($greeting) ?>, <?= e(explode(' ', trim($user['full_name']))[0] ?? $user['full_name']) ?> 👋</h4>
    <p style="color:var(--text-muted);" class="mb-0">Here's what's happening with your account today.</p>
</div>

<div class="balance-card">
    <div class="top-row">
        <span class="balance-label">Total Balance</span>
        <button type="button" class="balance-eye" id="balanceEyeToggle" aria-label="Show or hide balance"><i class="bi bi-eye-fill"></i></button>
    </div>
    <div class="balance-body">
        <div class="balance-info">
            <div class="balance-amount" id="balanceAmount" data-value="<?= e(money(wallet_total_balance((int) $user['id']))) ?>">
                <?= e(money(wallet_total_balance((int) $user['id']))) ?>
            </div>
            <div class="balance-usd">&asymp; <?= e(money_usd(wallet_total_balance((int) $user['id']))) ?></div>
            <?php if ($weekTrendPercent !== null): ?>
                <div class="balance-trend <?= $weekTrendPercent >= 0 ? 'up' : 'down' ?>">
                    <i class="bi bi-graph-<?= $weekTrendPercent >= 0 ? 'up' : 'down' ?>-arrow"></i>
                    <?= $weekTrendPercent >= 0 ? '+' : '' ?><?= e((string) $weekTrendPercent) ?>% this week
                </div>
            <?php endif; ?>
        </div>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/payments/deposit.php" class="balance-deposit-btn">
            <i class="bi bi-plus-circle-fill"></i><span>Deposit<br>Funds</span>
        </a>
    </div>
</div>

<div class="quick-row">
    <a href="<?= e(rtrim(APP_URL, '/')) ?>/mining/index.php" class="quick-btn primary">
        <i class="bi bi-cpu-fill"></i><span>Mining</span>
    </a>
    <a href="<?= e(rtrim(APP_URL, '/')) ?>/spin/index.php" class="quick-btn">
        <span class="spin-icon-wheel" style="background: <?= $spinWheelGradient ?>;"></span><span>Spin &amp; Earn</span>
    </a>
    <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/withdraw.php" class="quick-btn danger">
        <i class="bi bi-arrow-up-circle-fill"></i><span>Withdraw</span>
    </a>
</div>

<?php if ($primaryAccount): ?>
    <div class="app-info-card">
        <div class="info-icon"><i class="bi bi-bank"></i></div>
        <div class="info-body">
            <div class="info-title"><?= e($primaryAccount['type'] === 'usdt' ? 'USDT (' . $primaryAccount['network'] . ')' : $primaryAccount['bank_name']) ?></div>
            <div class="info-sub">
                <?= $primaryAccount['type'] === 'usdt'
                    ? e(substr((string) $primaryAccount['usdt_address'], 0, 6) . '...' . substr((string) $primaryAccount['usdt_address'], -4))
                    : e('**** ' . substr((string) $primaryAccount['account_number'], -4) . ' · ' . $primaryAccount['account_name']) ?>
            </div>
        </div>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/bank-accounts.php" style="color:var(--text-muted);font-size:13px;"><i class="bi bi-pencil-fill"></i></a>
    </div>
<?php else: ?>
    <div class="app-info-card">
        <div class="info-icon"><i class="bi bi-bank"></i></div>
        <div class="info-body">
            <div class="info-title">No withdrawal account yet</div>
            <div class="info-sub">Add a bank or USDT account to withdraw</div>
        </div>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/bank-accounts.php" class="pill-btn">Add</a>
    </div>
<?php endif; ?>

<div class="section-title" style="font-weight:700;font-size:15px;margin:20px 0 10px;">🔥 Daily Check-in</div>
<div class="checkin-strip">
    <?php foreach ($checkinStrip as $i => $d): ?>
        <div class="day-card<?= $i === 0 ? ' active' : '' ?>">
            <div class="num"><?= (int) $d['day'] ?></div>
            <div class="lbl"><?= $i === 0 ? 'Today' : 'Day ' . $d['day'] ?></div>
            <div class="rwd"><?= e(money($d['reward'])) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php if ($checkinAlready): ?>
    <button type="button" class="checkin-cta-btn" disabled><i class="bi bi-check-circle-fill"></i> Checked In Today</button>
<?php else: ?>
    <form method="POST" action="<?= e(rtrim(APP_URL, '/')) ?>/checkin/index.php">
        <?= csrf_field() ?>
        <button type="submit" class="checkin-cta-btn"><i class="bi bi-calendar-check-fill"></i> Check In Now</button>
    </form>
<?php endif; ?>

<div class="app-stats-row">
    <div class="app-stat-card"><i class="bi bi-graph-up-arrow"></i><div class="val"><?= e(money($totalEarnings)) ?></div><div class="lbl">Total Earned</div></div>
    <div class="app-stat-card"><i class="bi bi-cpu-fill"></i><div class="val"><?= $activePositionCount ?> Active</div><div class="lbl">Mining Plans</div></div>
    <div class="app-stat-card"><i class="bi bi-fire"></i><div class="val"><?= $checkinCurrentStreak ?></div><div class="lbl">Day Streak</div></div>
</div>

<div class="app-stats-row">
    <div class="app-stat-card">
        <i class="bi bi-cpu-fill"></i>
        <div class="val"><?= e(money($dailyMiningTotal)) ?></div>
        <div class="lbl">Daily Mining Earning</div>
        <div class="sub"><?= $activePositionCount ?> active position<?= $activePositionCount === 1 ? '' : 's' ?></div>
    </div>
    <div class="app-stat-card">
        <i class="bi bi-people-fill"></i>
        <div class="val"><?= number_format($directReferrals) ?></div>
        <div class="lbl">Direct Referrals</div>
        <div class="sub"><?= e(money($referralBonusesEarned)) ?> in bonuses</div>
    </div>
    <div class="app-stat-card">
        <i class="bi bi-sun-fill"></i>
        <div class="val"><?= e(money($todayEarnings)) ?></div>
        <div class="lbl">Today's Earnings</div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Activity</h5>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/history.php" class="small fw-semibold" style="color:var(--brand-emerald);">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if (!$recent): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">
                    <i class="bi bi-receipt" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">No activity yet. Explore the earning tools to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <tbody>
                            <?php foreach ($recent as $row):
                                $rowDescription = $row['description'] ?: ($sourceLabels[$row['source']] ?? ucfirst($row['source']));
                            ?>
                                <tr data-ledger-row
                                    data-ledger-description="<?= e($rowDescription) ?>"
                                    data-ledger-wallet="<?= e($row['wallet_type']) ?>"
                                    data-ledger-type="<?= e(ucfirst($row['type'])) ?>"
                                    data-ledger-amount="<?= e(($row['type'] === 'credit' ? '+' : '-') . money($row['amount'])) ?>"
                                    data-ledger-balance="<?= e(money($row['balance_after'])) ?>"
                                    data-ledger-status="<?= e(ucfirst($row['status'])) ?>"
                                    data-ledger-reference="<?= e($row['reference'] ?: '-') ?>"
                                    data-ledger-date="<?= e(date('M d, Y H:i', strtotime($row['created_at']))) ?>">
                                    <td><?= e($rowDescription) ?></td>
                                    <td class="fw-semibold <?= $row['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                        <?= $row['type'] === 'credit' ? '+' : '-' ?><?= e(money($row['amount'])) ?>
                                    </td>
                                    <td class="small text-end" style="color:var(--text-muted);"><?= e(time_ago($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-surface p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Your Referral Link</h5>
                <span class="small fw-semibold" style="color:var(--text-muted);"><i class="bi bi-people-fill me-1"></i><?= number_format($directReferrals) ?> direct</span>
            </div>
            <div class="input-group mb-2">
                <input type="text" class="form-control form-control-sm" readonly id="refLink" value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
                <button class="btn btn-outline-brand btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); SureCashMining.toast('Referral link copied!');">Copy</button>
            </div>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/referrals.php" class="small fw-semibold" style="color:var(--brand-emerald);">View Referral Stats &amp; Leaderboard <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Quick Access</h5>
            <div class="quick-access-grid">
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/wallet/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-wallet2"></i></span>
                    <span>My Wallet</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/mining/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-cpu-fill"></i></span>
                    <span>Mining</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/tasks/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-list-check"></i></span>
                    <span>Task Center</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/spin/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(242,201,76,0.16);color:var(--brand-gold-dark);"><i class="bi bi-disc-fill"></i></span>
                    <span>Spin Wheel</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/ads/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(240,68,56,0.12);color:var(--danger);"><i class="bi bi-play-btn-fill"></i></span>
                    <span>Watch &amp; Earn</span>
                </a>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/checkin/index.php" class="quick-access-item">
                    <span class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-calendar-check-fill"></i></span>
                    <span>Daily Check-in</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/transaction-detail-modal.php'; ?>

<?php if ($pendingBroadcasts): ?>
<div class="modal fade" id="broadcastModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface);color:var(--text);">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-megaphone-fill me-2" style="color:var(--brand-gold-dark);"></i><span id="broadcastModalTitle"></span></h6>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="broadcastModalMessage" style="white-space:pre-line;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand w-100" id="broadcastModalOkBtn">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var queue = <?= json_encode(array_map(fn ($n) => ['id' => (int) $n['id'], 'title' => $n['title'], 'message' => $n['message']], $pendingBroadcasts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var modalEl = document.getElementById('broadcastModal');
    var modal = new bootstrap.Modal(modalEl);
    var titleEl = document.getElementById('broadcastModalTitle');
    var messageEl = document.getElementById('broadcastModalMessage');
    var okBtn = document.getElementById('broadcastModalOkBtn');
    var csrfToken = <?= json_encode(csrf_token()) ?>;

    function showNext() {
        if (!queue.length) {
            modal.hide();
            return;
        }
        var next = queue[0];
        titleEl.textContent = next.title;
        messageEl.textContent = next.message;
        modal.show();
    }

    okBtn.addEventListener('click', function () {
        var current = queue.shift();
        okBtn.disabled = true;
        fetch('<?= e(rtrim(APP_URL, '/')) ?>/ajax/notifications-ack.php', {
            method: 'POST',
            body: new URLSearchParams({ csrf_token: csrfToken, notification_id: current.id }),
        })
            .finally(function () {
                okBtn.disabled = false;
                showNext();
            });
    });

    showNext();
});
</script>
<?php endif; ?>

<script>
(function () {
    var toggle = document.getElementById('balanceEyeToggle');
    var amount = document.getElementById('balanceAmount');
    if (!toggle || !amount) return;
    var real = amount.getAttribute('data-value');
    var hidden = false;
    toggle.addEventListener('click', function () {
        hidden = !hidden;
        amount.textContent = hidden ? '••••••' : real;
        toggle.innerHTML = hidden ? '<i class="bi bi-eye-slash-fill"></i>' : '<i class="bi bi-eye-fill"></i>';
    });
})();
</script>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
