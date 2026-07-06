<div class="container py-5">
    <h1 class="fw-bold text-center mb-2">Mining Plans</h1>
    <p class="text-center text-muted-soft mb-5">Choose a plan and start earning fixed daily profit.</p>
    <div class="row g-4">
        <?php foreach ($plans as $plan): ?>
        <div class="col-md-6 col-lg-4">
            <div class="surface-card p-4 h-100 d-flex flex-column">
                <h5 class="fw-bold"><?= e($plan['name']) ?></h5>
                <div class="display-6 fw-bold my-2" style="color:var(--c-primary);"><?= money($plan['price']) ?></div>
                <ul class="list-unstyled small text-muted-soft flex-grow-1">
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Daily Profit: <strong><?= money($plan['daily_profit']) ?></strong></li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Duration: <strong><?= (int) $plan['duration_days'] ?> days</strong></li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Total ROI: <strong><?= (float) $plan['total_roi_percent'] ?>%</strong></li>
                </ul>
                <a href="<?= base_url(is_logged_in() ? 'mining' : 'register') ?>" class="btn btn-brand rounded-pill w-100">Get Started</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($plans)): ?><p class="text-center text-muted-soft">No plans available right now.</p><?php endif; ?>
    </div>
</div>
