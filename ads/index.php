<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$dailyLimit = (int) get_setting('ad_daily_limit', 10);
$rewardAmount = (float) get_setting('ad_reward_amount', 10);
$watchedToday = ads_today_count((int) $user['id']);
$eligibility = ads_can_watch((int) $user['id']);
$cooldownTarget = !empty($eligibility['cooldown']) ? date(DATE_ATOM, time() + $eligibility['cooldown']) : null;

$stmt = db()->prepare('SELECT * FROM ads_logs WHERE user_id = ? ORDER BY watched_at DESC LIMIT 10');
$stmt->execute([$user['id']]);
$logs = $stmt->fetchAll();

$pageTitle = 'Watch & Earn';
$activeNav = 'ads';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4 text-center">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:72px;height:72px;background:rgba(242,201,76,0.14);color:var(--brand-gold-dark);font-size:1.8rem;">
                <i class="bi bi-play-btn-fill"></i>
            </div>
            <h5 class="fw-bold">Watch an Ad, Earn <?= e(money($rewardAmount)) ?></h5>
            <p class="small" style="color:var(--text-muted);">Watched today: <?= $watchedToday ?> / <?= $dailyLimit ?></p>

            <div class="progress-thin mb-4"><span style="width:<?= $dailyLimit > 0 ? min(100, (int) round($watchedToday / $dailyLimit * 100)) : 0 ?>%;"></span></div>

            <?php if ($eligibility['can_watch']): ?>
                <button type="button" class="btn btn-brand w-100" id="watchAdBtn">
                    <i class="bi bi-play-circle me-1"></i> Watch Ad Now
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-outline-brand w-100" disabled>
                    <?php if ($cooldownTarget): ?>
                        Next ad in <span data-countdown data-target="<?= e($cooldownTarget) ?>">--</span>
                    <?php else: ?>
                        Daily Limit Reached
                    <?php endif; ?>
                </button>
                <p class="small mt-2 mb-0" style="color:var(--text-muted);"><?= e($eligibility['reason']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Recent Ad Rewards</h5>
            <?php if (!$logs): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No ads watched yet today.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Reward</th><th>Watched</th></tr></thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-success fw-semibold">+<?= e(money($log['reward_amount'])) ?></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(time_ago($log['watched_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Watch Ad Modal -->
<div class="modal fade" id="adModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface);color:var(--text);">
            <div class="modal-header">
                <h6 class="modal-title">Ad Playing</h6>
            </div>
            <div class="modal-body text-center py-5">
                <i class="bi bi-play-btn" style="font-size:2.5rem;color:var(--brand-emerald);"></i>
                <div class="fs-2 fw-bold mt-3" id="adCountdownNumber">--</div>
                <p class="small mb-0" style="color:var(--text-muted);">Please wait while your ad plays...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand w-100" id="claimAdBtn" disabled>Claim Reward</button>
            </div>
        </div>
    </div>
</div>

<form id="csrfForm"><?= csrf_field() ?></form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const watchBtn = document.getElementById('watchAdBtn');
    if (!watchBtn) return;

    const modalEl = document.getElementById('adModal');
    const modal = new bootstrap.Modal(modalEl);
    const claimBtn = document.getElementById('claimAdBtn');
    const countdownEl = document.getElementById('adCountdownNumber');
    const csrfToken = document.querySelector('#csrfForm input[name="csrf_token"]').value;
    let watchToken = null;
    let timer = null;

    watchBtn.addEventListener('click', function () {
        NineJaCash.setLoading(watchBtn, true);
        fetch('<?= e(rtrim(APP_URL, '/')) ?>/ajax/ads-start.php', {
            method: 'POST',
            body: new URLSearchParams({ csrf_token: csrfToken }),
        })
            .then(r => r.json())
            .then(data => {
                NineJaCash.setLoading(watchBtn, false);
                if (!data.success) {
                    NineJaCash.toast(data.message, 'error');
                    return;
                }
                watchToken = data.token;
                let remaining = data.duration;
                claimBtn.disabled = true;
                countdownEl.textContent = remaining + 's';
                modal.show();

                timer = setInterval(function () {
                    remaining -= 1;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        countdownEl.textContent = 'Ready!';
                        claimBtn.disabled = false;
                    } else {
                        countdownEl.textContent = remaining + 's';
                    }
                }, 1000);
            })
            .catch(() => { NineJaCash.setLoading(watchBtn, false); NineJaCash.toast('Something went wrong.', 'error'); });
    });

    claimBtn.addEventListener('click', function () {
        NineJaCash.setLoading(claimBtn, true);
        fetch('<?= e(rtrim(APP_URL, '/')) ?>/ajax/ads-claim.php', {
            method: 'POST',
            body: new URLSearchParams({ csrf_token: csrfToken, watch_token: watchToken }),
        })
            .then(r => r.json())
            .then(data => {
                modal.hide();
                NineJaCash.toast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => window.location.reload(), 1200);
            })
            .finally(() => NineJaCash.setLoading(claimBtn, false));
    });
});
</script>

<script src="<?= e($assetBase) ?>/js/countdown.js"></script>
<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
