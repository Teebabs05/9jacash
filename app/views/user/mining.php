<h5 class="fw-bold mb-3">Available Mining Plans</h5>
<div class="row g-3 mb-5">
    <?php foreach ($plans as $plan): ?>
    <div class="col-md-6 col-xl-4">
        <div class="surface-card p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-bold mb-0"><?= e($plan['name']) ?></h6>
                <span class="badge bg-primary-subtle text-primary-emphasis"><?= (float) $plan['total_roi_percent'] ?>% ROI</span>
            </div>
            <div class="display-6 fw-bold" style="color:var(--c-primary);"><?= money($plan['price']) ?></div>
            <ul class="list-unstyled small text-muted-soft my-3 flex-grow-1">
                <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>Daily Profit: <strong><?= money($plan['daily_profit']) ?></strong></li>
                <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>Duration: <strong><?= (int) $plan['duration_days'] ?> days</strong></li>
                <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>Total Return: <strong><?= money($plan['daily_profit'] * $plan['duration_days']) ?></strong></li>
                <?php if ($plan['max_users']): ?>
                <li><i class="fa-solid fa-users me-2"></i><?= (int) $plan['current_users'] ?>/<?= (int) $plan['max_users'] ?> slots filled</li>
                <?php endif; ?>
            </ul>
            <form method="post" action="<?= base_url('mining/buy/' . $plan['id']) ?>" onsubmit="return confirm('Activate the <?= e($plan['name']) ?> plan for <?= money($plan['price']) ?>?');">
                <?= csrf_field() ?>
                <button class="btn btn-brand w-100 rounded-pill" type="submit">Activate Plan</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($plans)): ?><p class="text-muted-soft">No mining plans available right now.</p><?php endif; ?>
</div>

<h5 class="fw-bold mb-3">My Mining Plans</h5>
<div class="row g-3">
    <?php foreach ($purchases as $p): ?>
    <div class="col-md-6">
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><?= e($p['plan_name']) ?></h6>
                <span class="badge badge-status-<?= e($p['status']) ?> text-capitalize"><?= e($p['status']) ?></span>
            </div>
            <div class="progress mb-2" style="height:8px;">
                <?php $pct = min(100, ($p['days_completed'] / max(1,$p['duration_days'])) * 100); ?>
                <div class="progress-bar" style="width:<?= $pct ?>%; background:var(--c-primary);"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted-soft mb-2">
                <span>Day <?= (int) $p['days_completed'] ?> / <?= (int) $p['duration_days'] ?></span>
                <span>Earned: <?= money($p['total_earned']) ?></span>
            </div>
            <?php if ($p['status'] === 'active'): ?>
            <div class="small text-muted-soft">Ends in: <strong data-countdown="<?= date('c', strtotime($p['end_date'])) ?>">calculating...</strong></div>
            <?php else: ?>
            <form method="post" action="<?= base_url('mining/renew/' . $p['id']) ?>" class="mt-2">
                <?= csrf_field() ?>
                <button class="btn btn-outline-brand btn-sm rounded-pill w-100" type="submit">Renew Plan</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($purchases)): ?><p class="text-muted-soft">You haven't purchased any mining plans yet.</p><?php endif; ?>
</div>
