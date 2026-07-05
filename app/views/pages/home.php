<section class="hero-gradient py-5">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 mb-3 fw-semibold">🇳🇬 Nigeria's Trusted Earning Platform</span>
                <h1 class="display-5 fw-bold mb-3">Earn Daily with Mining, Tasks &amp; Referrals</h1>
                <p class="lead mb-4 opacity-90"><?= e(setting('site_tagline', 'Earn. Grow. Cash Out.')) ?> Join <?= number_format($totalUsers) ?>+ members already earning on <?= e(setting('site_name', '9JACASH')) ?>.</p>
                <div class="d-flex gap-3">
                    <a href="<?= base_url('register') ?>" class="btn btn-accent btn-lg rounded-pill px-4">Start Earning Free</a>
                    <a href="<?= base_url('pricing') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4">View Mining Plans</a>
                </div>
                <div class="row mt-5 g-3">
                    <div class="col-4"><div class="fw-bold fs-4"><?= number_format($totalUsers) ?>+</div><div class="small opacity-75">Active Members</div></div>
                    <div class="col-4"><div class="fw-bold fs-4"><?= money($totalPaid) ?></div><div class="small opacity-75">Total Paid Out</div></div>
                    <div class="col-4"><div class="fw-bold fs-4">24/7</div><div class="small opacity-75">Instant Support</div></div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="glass-card p-4" style="transform:rotate(2deg);">
                    <div class="d-flex justify-content-between mb-3"><span class="fw-semibold">Wallet Overview</span><i class="fa-solid fa-wallet"></i></div>
                    <div class="display-6 fw-bold mb-1">₦248,500.00</div>
                    <div class="small opacity-75 mb-4">Total Balance</div>
                    <div class="row g-2 small">
                        <div class="col-6"><div class="p-2 rounded-3" style="background:rgba(255,255,255,.12);">Mining <br><strong>₦82,000</strong></div></div>
                        <div class="col-6"><div class="p-2 rounded-3" style="background:rgba(255,255,255,.12);">Referral <br><strong>₦45,200</strong></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container py-4">
        <h2 class="text-center fw-bold mb-2">Multiple Ways to Earn</h2>
        <p class="text-center text-muted-soft mb-5">Diversify your income streams, all in one secure wallet.</p>
        <div class="row g-4">
            <?php
            $features = [
                ['icon' => 'fa-server', 'title' => 'Mining Plans', 'desc' => 'Invest in mining plans and earn fixed daily profit automatically.'],
                ['icon' => 'fa-list-check', 'title' => 'Micro Tasks', 'desc' => 'Complete simple social media and survey tasks for instant rewards.'],
                ['icon' => 'fa-share-nodes', 'title' => 'Referral Program', 'desc' => 'Earn commissions across multiple levels when you invite friends.'],
                ['icon' => 'fa-gift', 'title' => 'Daily Rewards', 'desc' => 'Check in daily, spin the wheel and climb the leaderboard for bonuses.'],
            ];
            ?>
            <?php foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-3">
                <div class="surface-card p-4 h-100 text-center">
                    <span class="icon-badge mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem;"><i class="fa-solid <?= $f['icon'] ?>"></i></span>
                    <h6 class="fw-bold"><?= $f['title'] ?></h6>
                    <p class="small text-muted-soft mb-0"><?= $f['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5" style="background:var(--bg-surface-alt);">
    <div class="container py-4">
        <h2 class="text-center fw-bold mb-2">Popular Mining Plans</h2>
        <p class="text-center text-muted-soft mb-5">Pick a plan that fits your budget and start earning daily.</p>
        <div class="row g-4">
            <?php foreach (array_slice($plans, 0, 3) as $plan): ?>
            <div class="col-md-4">
                <div class="surface-card p-4 h-100 text-center">
                    <h6 class="fw-bold"><?= e($plan['name']) ?></h6>
                    <div class="display-6 fw-bold my-2" style="color:var(--c-primary);"><?= money($plan['price']) ?></div>
                    <p class="small text-muted-soft">Earn <strong><?= money($plan['daily_profit']) ?>/day</strong> for <?= (int) $plan['duration_days'] ?> days</p>
                    <a href="<?= base_url('register') ?>" class="btn btn-outline-brand rounded-pill w-100">Get Started</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4"><a href="<?= base_url('pricing') ?>" class="fw-semibold">View all mining plans <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
    </div>
</section>

<section class="py-5">
    <div class="container py-4">
        <h2 class="text-center fw-bold mb-5">Top Earners This Month</h2>
        <div class="row g-3 justify-content-center">
            <?php foreach ($leaders as $i => $l): ?>
            <div class="col-md-6 col-lg-4">
                <div class="surface-card p-3 d-flex align-items-center gap-3">
                    <span class="fs-4">🏆</span>
                    <img src="<?= e(user_avatar_url($l)) ?>" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                    <div><div class="fw-semibold"><?= e($l['username']) ?></div><div class="small text-success"><?= money($l['total_earned']) ?> earned</div></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 hero-gradient text-center">
    <div class="container py-4">
        <h2 class="fw-bold mb-3">Ready to start earning?</h2>
        <p class="lead opacity-90 mb-4">Create your free account in under a minute.</p>
        <a href="<?= base_url('register') ?>" class="btn btn-accent btn-lg rounded-pill px-5">Create Free Account</a>
    </div>
</section>
