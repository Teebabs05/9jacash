<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$segments = db()->query('SELECT * FROM spin_settings WHERE is_active = 1 ORDER BY id ASC')->fetchAll();
$canPlay = spin_can_play((int) $user['id']);
$extraPrice = spin_extra_price();
$canAffordExtra = wallet_total_balance((int) $user['id']) >= $extraPrice;
$segmentCount = count($segments);
$segmentAngle = $segmentCount > 0 ? 360 / $segmentCount : 0;

$gradientParts = [];
foreach ($segments as $i => $segment) {
    $start = $i * $segmentAngle;
    $end = ($i + 1) * $segmentAngle;
    $gradientParts[] = e($segment['color']) . " {$start}deg {$end}deg";
}
$conicGradient = 'conic-gradient(' . implode(', ', $gradientParts) . ')';

$stmt = db()->prepare(
    'SELECT sl.*, ss.label FROM spin_logs sl LEFT JOIN spin_settings ss ON ss.id = sl.spin_setting_id WHERE sl.user_id = ? ORDER BY sl.created_at DESC LIMIT 10'
);
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();

$pageTitle = 'Spin Wheel';
$activeNav = 'spin';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-surface p-4 text-center">
            <h5 class="fw-bold mb-3">Daily Spin Wheel</h5>

            <div class="spin-wheel-wrap mb-4">
                <div class="spin-pointer"></div>
                <div class="spin-wheel" id="spinWheel" style="background: <?= $conicGradient ?>;">
                    <?php foreach ($segments as $i => $segment):
                        $mid = $i * $segmentAngle + $segmentAngle / 2;
                    ?>
                        <div class="spin-segment-label" style="transform: rotate(<?= $mid ?>deg);">
                            <span><?= e($segment['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="spin-hub"><i class="bi bi-stars"></i></div>
            </div>

            <div class="alert alert-info small py-2 px-3 mb-3<?= $canPlay ? ' d-none' : '' ?>" id="spinLimitAlert">You've used today's free spin. Buy an extra spin from your wallet balance to keep playing.</div>

            <?php if ($canPlay): ?>
                <button type="button" class="btn btn-brand w-100" id="spinBtn" data-endpoint="spin-play.php">Spin Now</button>
            <?php elseif ($canAffordExtra): ?>
                <button type="button" class="btn btn-brand w-100" id="spinBtn" data-endpoint="spin-buy-extra.php">Buy Extra Spin (<?= e(money($extraPrice)) ?>)</button>
            <?php else: ?>
                <button type="button" class="btn btn-outline-brand w-100" id="spinBtn" data-endpoint="spin-buy-extra.php" disabled>Insufficient wallet balance for an extra spin (<?= e(money($extraPrice)) ?>)</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Spin History</h5>
            <?php if (!$history): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No spins yet. Try your luck today!</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Result</th><th>Reward</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><?= e($h['label'] ?? '—') ?></td>
                                    <td class="<?= $h['amount_won'] > 0 ? 'text-success fw-semibold' : '' ?>"><?= $h['amount_won'] > 0 ? '+' . e(money($h['amount_won'])) : '—' ?></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(time_ago($h['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<form id="csrfForm"><?= csrf_field() ?></form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const spinBtn = document.getElementById('spinBtn');
    if (!spinBtn) return;

    const wheel = document.getElementById('spinWheel');
    const limitAlert = document.getElementById('spinLimitAlert');
    const segmentCount = <?= (int) $segmentCount ?>;
    const segmentAngle = 360 / segmentCount;
    const csrfToken = document.querySelector('#csrfForm input[name="csrf_token"]').value;
    let cumulativeRotation = 0;
    let spinning = false;

    // Morphs the button (and limit notice) in place based on what the
    // server just told us, instead of reloading the page - covers both
    // "free spin just used up" and "extra spin balance changed".
    function applyPostSpinState(data) {
        if (!data.daily_limit_reached) {
            spinBtn.className = 'btn btn-brand w-100';
            spinBtn.disabled = false;
            spinBtn.textContent = 'Spin Now';
            spinBtn.setAttribute('data-endpoint', 'spin-play.php');
            return;
        }

        if (limitAlert) limitAlert.classList.remove('d-none');
        spinBtn.setAttribute('data-endpoint', 'spin-buy-extra.php');

        if (data.can_afford_extra) {
            spinBtn.className = 'btn btn-brand w-100';
            spinBtn.disabled = false;
            spinBtn.textContent = 'Buy Extra Spin (' + data.extra_price_formatted + ')';
        } else {
            spinBtn.className = 'btn btn-outline-brand w-100';
            spinBtn.disabled = true;
            spinBtn.textContent = 'Insufficient wallet balance for an extra spin (' + data.extra_price_formatted + ')';
        }
    }

    spinBtn.addEventListener('click', function () {
        if (spinning || spinBtn.disabled) return;
        spinning = true;
        SureCashMining.setLoading(spinBtn, true);

        const endpoint = spinBtn.getAttribute('data-endpoint');

        fetch('<?= e(rtrim(APP_URL, '/')) ?>/ajax/' + endpoint, {
            method: 'POST',
            body: new URLSearchParams({ csrf_token: csrfToken }),
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    SureCashMining.toast(data.message, 'error');
                    SureCashMining.setLoading(spinBtn, false);
                    spinning = false;
                    return;
                }

                const targetMod = (360 - (data.segment_index * segmentAngle + segmentAngle / 2) + 360) % 360;
                const currentMod = ((cumulativeRotation % 360) + 360) % 360;
                const delta = (targetMod - currentMod + 360) % 360;
                cumulativeRotation += 5 * 360 + delta;

                wheel.style.transform = `rotate(${cumulativeRotation}deg)`;

                setTimeout(function () {
                    SureCashMining.toast(data.message, data.amount > 0 ? 'success' : 'info');
                    SureCashMining.setLoading(spinBtn, false);
                    applyPostSpinState(data);
                    spinning = false;
                }, 4600);
            })
            .catch(() => {
                SureCashMining.toast('Something went wrong.', 'error');
                SureCashMining.setLoading(spinBtn, false);
                spinning = false;
            });
    });
});
</script>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
