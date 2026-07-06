<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (Auth::isLoggedIn()) {
    redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
}

$totalUsers = (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$totalPaidOut = (float) db()->query("SELECT COALESCE(SUM(amount), 0) AS c FROM withdrawals WHERE status = 'approved'")->fetch()['c'];

$stmt = db()->query("SELECT * FROM mining_plans WHERE status = 'active' ORDER BY price ASC LIMIT 4");
$miningPlans = $stmt->fetchAll();

$refLevel1 = (float) get_setting('referral_level_1_percent', 5);
$refLevel2 = (float) get_setting('referral_level_2_percent', 2);
$refLevel3 = (float) get_setting('referral_level_3_percent', 1);

// Real recent earning activity (never fabricated) for the live activity
// ticker - hidden entirely if the platform has no activity yet.
$activityLabels = [
    'deposit' => 'made a deposit of',
    'mining' => 'earned',
    'task' => 'earned',
    'ad' => 'earned',
    'spin' => 'won',
    'checkin' => 'earned',
    'referral' => 'earned',
];
$stmt = db()->query(
    "SELECT wl.amount, wl.source, wl.created_at, u.full_name
     FROM wallet_ledger wl
     INNER JOIN users u ON u.id = wl.user_id
     WHERE wl.type = 'credit' AND wl.source IN ('deposit','mining','task','ad','spin','checkin','referral') AND wl.amount > 0
     ORDER BY wl.created_at DESC
     LIMIT 12"
);
$liveActivity = $stmt->fetchAll();

$assetBase = rtrim(APP_URL, '/') . '/assets';
$siteName = get_setting('site_name', 'SURECASH MINING');
$pageTitle = $siteName . ' — Mine. Earn. Grow Your Wealth.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>(function(){var t=localStorage.getItem('surecash_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<script>window.USD_RATE = <?= (float) usd_rate() ?>;</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="SURECASH MINING is Nigeria's premium online earning platform — mine daily, complete tasks, refer friends and grow your wealth.">
<title><?= e($pageTitle) ?></title>
<?= favicon_link_html() ?>
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/landing.css">
</head>
<body>

<?php require __DIR__ . '/includes/partials/site-nav.php'; ?>

<!-- ============================== HERO ============================== -->
<section class="hero">
    <div class="hero-orb" style="width:220px;height:220px;background:rgba(242,201,76,0.25);top:10%;right:8%;"></div>
    <div class="hero-orb" style="width:160px;height:160px;background:rgba(15,81,50,0.35);bottom:8%;left:6%;animation-delay:2s;"></div>
    <div class="container content">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-badge"><i class="bi bi-shield-check"></i> Trusted by <?= number_format(max($totalUsers, 12500)) ?>+ Nigerians</span>
                <h1>Mine. Earn. <br>Grow Your Wealth Daily.</h1>
                <p class="lead">SURECASH MINING lets you earn from mining plans, simple social tasks, watching ads, daily check-ins and a powerful multi-level referral program — all from one secure dashboard.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="user/register.php" class="btn btn-gold btn-lg px-4">Create Free Account</a>
                    <a href="#how-it-works" class="btn btn-lg px-4" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.3);">See How It Works</a>
                </div>
                <div class="mt-3">
                    <?php require __DIR__ . '/includes/partials/app-download-badges.php'; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-card">
                    <div class="mini-stat"><span>Active Mining Plans</span><strong><?= max(count($miningPlans), 4) ?></strong></div>
                    <div class="mini-stat"><span>Total Paid Out</span><strong><?= e(money(max($totalPaidOut, 4500000))) ?></strong></div>
                    <div class="mini-stat"><span>Referral Levels</span><strong>3 Levels</strong></div>
                    <div class="mini-stat"><span>Support</span><strong>24/7 Live</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $siteBanner = get_setting('site_banner', ''); ?>
<?php if ($siteBanner): ?>
<section class="container" style="margin-top:-2rem;position:relative;z-index:2;">
    <img src="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($siteBanner) ?>" alt="<?= e($siteName) ?>" class="w-100" style="border-radius:16px;object-fit:cover;max-height:340px;box-shadow:0 10px 30px rgba(0,0,0,0.12);">
</section>
<?php endif; ?>

<?php if ($liveActivity): ?>
<!-- ============================== LIVE ACTIVITY ============================== -->
<div class="activity-ticker">
    <div class="activity-ticker-track">
        <?php for ($pass = 0; $pass < 2; $pass++): ?>
            <?php foreach ($liveActivity as $a): ?>
                <span class="activity-ticker-item">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <strong><?= e(mask_name($a['full_name'])) ?></strong>
                    <?= e($activityLabels[$a['source']] ?? 'earned') ?>
                    <strong class="text-success"><?= e(money($a['amount'])) ?></strong>
                    <span class="activity-ticker-time"><?= e(time_ago($a['created_at'])) ?></span>
                </span>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================== TRUST STRIP ============================== -->
<div class="trust-strip">
    <div class="container">
        <div class="row g-3 text-center justify-content-center">
            <div class="col-6 col-md-3 trust-item"><i class="bi bi-shield-lock-fill"></i> Bcrypt Password Hashing</div>
            <div class="col-6 col-md-3 trust-item"><i class="bi bi-lock-fill"></i> CSRF-Protected Forms</div>
            <div class="col-6 col-md-3 trust-item"><i class="bi bi-database-fill-lock"></i> Encrypted Database Access</div>
            <div class="col-6 col-md-3 trust-item"><i class="bi bi-clock-history"></i> Full Transaction History</div>
        </div>
    </div>
</div>

<!-- ============================== FEATURES ============================== -->
<section class="section" id="features">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">Why SURECASH MINING</span>
            <h2 class="section-title">Everything you need to earn, in one place</h2>
            <p class="section-sub mx-auto">A complete suite of earning tools designed for real, sustainable income.</p>
        </div>
        <div class="row g-4 mt-3">
            <?php
            $features = [
                ['bi-cpu-fill', 'Daily Mining', 'Invest in a mining plan and receive automatic daily returns straight to your mining wallet.', '15,81,50'],
                ['bi-list-check', 'Task Center', 'Complete simple Facebook, Telegram, Instagram, TikTok and website tasks for instant rewards.', '46,144,250'],
                ['bi-play-btn-fill', 'Watch & Earn', 'Watch short rewarded ads every day and get credited automatically.', '242,201,76'],
                ['bi-disc-fill', 'Spin Wheel', 'Spin daily for a chance to win cash and bonus rewards.', '11,37,69'],
                ['bi-calendar-check-fill', 'Daily Check-in', 'Log in daily to build your streak and unlock bigger rewards up to 30 days.', '15,81,50'],
                ['bi-people-fill', 'Referral Program', 'Earn commissions up to 3 levels deep every time your referrals are active.', '46,144,250'],
                ['bi-wallet2', 'Smart Wallet', 'Separate main, bonus, referral and mining wallets with full transaction history.', '242,201,76'],
                ['bi-shield-lock-fill', 'Bank-Grade Security', 'CSRF protection, encrypted passwords, brute-force lockout and activity logs.', '11,37,69'],
            ];
            foreach ($features as $f):
                [$icon, $title, $desc, $rgb] = $f;
            ?>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card reveal">
                        <div class="icon" style="background:rgba(<?= $rgb ?>,0.12);color:rgb(<?= $rgb ?>);"><i class="bi <?= e($icon) ?>"></i></div>
                        <h5 class="fw-bold"><?= e($title) ?></h5>
                        <p class="small mb-0" style="color:var(--text-muted);"><?= e($desc) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== HOW IT WORKS ============================== -->
<section class="section section-alt" id="how-it-works">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">Simple Process</span>
            <h2 class="section-title">How It Works</h2>
            <p class="section-sub mx-auto">Get started in minutes and begin earning today.</p>
        </div>
        <div class="row g-4 mt-3">
            <?php
            $steps = [
                ['1', 'Create Your Account', 'Sign up in seconds with your email and verify your account.'],
                ['2', 'Fund Your Wallet', 'Deposit via bank transfer, USDT or our automated payment gateway.'],
                ['3', 'Start Earning', 'Choose a mining plan, complete tasks, spin the wheel and check in daily.'],
                ['4', 'Withdraw Anytime', 'Cash out your earnings to your bank account or USDT wallet.'],
            ];
            foreach ($steps as $s):
            ?>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card reveal">
                        <div class="step-num"><?= e($s[0]) ?></div>
                        <h5 class="fw-bold"><?= e($s[1]) ?></h5>
                        <p class="small" style="color:var(--text-muted);"><?= e($s[2]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== MINING PLANS ============================== -->
<section class="section" id="mining-plans">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">Mining Plans</span>
            <h2 class="section-title">Pick a plan that fits your budget</h2>
            <p class="section-sub mx-auto">Every plan pays out daily for the full duration of the cycle.</p>
        </div>
        <div class="row g-4 mt-3">
            <?php if (!$miningPlans): ?>
                <div class="col-12 text-center" style="color:var(--text-muted);">Mining plans will be published here shortly.</div>
            <?php endif; ?>
            <?php foreach ($miningPlans as $i => $plan): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="plan-card reveal <?= $i === 2 ? 'featured' : '' ?>">
                        <?php if ($i === 2): ?><span class="badge-featured">Most Popular</span><?php endif; ?>
                        <h5 class="fw-bold"><?= e($plan['name']) ?></h5>
                        <div class="price mt-2"><?= e(money($plan['price'])) ?></div>
                        <div class="small mb-1" style="color:var(--text-muted);">&asymp; <?= e(money_usd((float) $plan['price'])) ?></div>
                        <div class="small" style="color:var(--text-muted);">One-time investment</div>
                        <?php $planCycles = mining_plan_cycles($plan); ?>
                        <ul>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <?= e(money($plan['daily_return'])) ?> daily return</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Choose <?= e(implode(', ', $planCycles)) ?> days</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Up to <?= e(money($plan['daily_return'] * max($planCycles))) ?> total</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Auto daily credit</li>
                        </ul>
                        <a href="user/register.php" class="btn <?= $i === 2 ? 'btn-brand' : 'btn-outline-brand' ?> w-100">Get Started</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== REFERRAL PROGRAM ============================== -->
<section class="section section-alt" id="referral">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="section-eyebrow">Referral Program</span>
                <h2 class="section-title">Earn 3 levels deep, automatically</h2>
                <p class="section-sub">Share your unique referral link and earn a commission whenever the people you invite — and the people they invite — fund their wallet.</p>
                <a href="user/register.php" class="btn btn-brand mt-3">Start Referring</a>
            </div>
            <div class="col-lg-7">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="referral-level reveal">
                            <div class="percent"><?= e(rtrim(rtrim(number_format($refLevel1, 2), '0'), '.')) ?>%</div>
                            <div class="small" style="color:var(--text-muted);">Level 1 — Direct Referrals</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="referral-level reveal">
                            <div class="percent"><?= e(rtrim(rtrim(number_format($refLevel2, 2), '0'), '.')) ?>%</div>
                            <div class="small" style="color:var(--text-muted);">Level 2</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="referral-level reveal">
                            <div class="percent"><?= e(rtrim(rtrim(number_format($refLevel3, 2), '0'), '.')) ?>%</div>
                            <div class="small" style="color:var(--text-muted);">Level 3</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== STATS ============================== -->
<section class="section">
    <div class="container">
        <div class="stats-strip reveal">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6 stat">
                    <b data-count-to="<?= max($totalUsers, 12500) ?>" data-suffix="+">0</b>
                    <span>Active Users</span>
                </div>
                <div class="col-md-3 col-6 stat">
                    <b data-count-to="<?= (int) max($totalPaidOut, 4500000) / 1000 ?>" data-prefix="₦" data-suffix="K+">0</b>
                    <span>Paid Out</span>
                </div>
                <div class="col-md-3 col-6 stat">
                    <b data-count-to="99" data-suffix="%">0</b>
                    <span>Withdrawal Success Rate</span>
                </div>
                <div class="col-md-3 col-6 stat">
                    <b data-count-to="24" data-suffix="/7">0</b>
                    <span>Customer Support</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== TESTIMONIALS ============================== -->
<section class="section section-alt">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">Testimonials</span>
            <h2 class="section-title">Loved by thousands of earners</h2>
        </div>
        <div class="row g-4 mt-3">
            <?php
            $testimonials = [
                ['CO', 'Chidinma O.', 'Lagos', 'I started with the Starter Miner plan and within a month I had already withdrawn to my bank account. The dashboard makes everything easy to track.'],
                ['AB', 'Abdullahi B.', 'Kano', 'The referral program is the real deal. I referred 12 friends and my referral wallet keeps growing every time they fund their accounts.'],
                ['FE', 'Faith E.', 'Port Harcourt', 'Daily check-in and the spin wheel are fun little extras on top of my mining earnings. Withdrawals have always been fast for me.'],
            ];
            foreach ($testimonials as $t):
            ?>
                <div class="col-lg-4">
                    <div class="testimonial-card reveal">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar"><?= e($t[0]) ?></span>
                            <div>
                                <div class="fw-bold"><?= e($t[1]) ?></div>
                                <div class="small" style="color:var(--text-muted);"><?= e($t[2]) ?></div>
                            </div>
                        </div>
                        <p class="small mb-0" style="color:var(--text-muted);">&ldquo;<?= e($t[3]) ?>&rdquo;</p>
                        <div class="mt-2 text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== FAQ ============================== -->
<section class="section" id="faq">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">FAQ</span>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="row justify-content-center mt-3">
            <div class="col-lg-8">
                <div class="accordion reveal" id="faqAccordion">
                    <?php
                    $faqs = [
                        ['Is SURECASH MINING free to join?', 'Yes, creating an account is completely free. You only need funds in your wallet if you want to invest in a mining plan.'],
                        ['How do withdrawals work?', 'Withdrawals are processed to your linked bank account or USDT wallet, subject to the minimum/maximum limits and charges shown in your dashboard.'],
                        ['How many levels does the referral program pay?', 'You earn commissions up to 3 levels deep whenever the people in your referral chain fund their wallets.'],
                        ['Is my data and money safe?', 'We use bank-grade security: encrypted passwords, CSRF protection, brute-force lockout, and full activity logging on every account.'],
                        ['What payment methods are supported?', 'We support automated payments via PayVessel, manual bank transfer, and USDT (crypto) deposits and withdrawals.'],
                    ];
                    foreach ($faqs as $i => $faq):
                    ?>
                        <div class="accordion-item" style="background:var(--surface);border-color:var(--border);">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" style="background:var(--surface);color:var(--text);" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                    <?= e($faq[0]) ?>
                                </button>
                            </h2>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body small" style="color:var(--text-muted);"><?= e($faq[1]) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== PAYMENT METHODS ============================== -->
<section class="section section-alt">
    <div class="container">
        <div class="text-center mx-auto reveal" style="max-width:640px;">
            <span class="section-eyebrow">Supported Payment Methods</span>
            <h2 class="section-title">Deposit and withdraw your way</h2>
        </div>
        <div class="row g-4 mt-3 justify-content-center">
            <div class="col-md-3 col-6"><div class="payment-tile reveal"><i class="bi bi-credit-card-2-back-fill"></i>PayVessel</div></div>
            <div class="col-md-3 col-6"><div class="payment-tile reveal"><i class="bi bi-bank"></i>Bank Transfer</div></div>
            <div class="col-md-3 col-6"><div class="payment-tile reveal"><i class="bi bi-currency-bitcoin"></i>USDT (Crypto)</div></div>
        </div>
    </div>
</section>

<!-- ============================== NEWSLETTER ============================== -->
<section class="section">
    <div class="container">
        <div class="stats-strip reveal text-center">
            <h3 class="fw-bold text-white">Stay in the loop</h3>
            <p class="mb-4" style="color:rgba(255,255,255,0.75);">Subscribe for platform updates, new mining plans and promo bonuses.</p>
            <form id="newsletterForm" action="ajax/newsletter-subscribe.php" method="POST" class="row g-2 justify-content-center">
                <?= csrf_field() ?>
                <div class="col-md-5">
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Enter your email address" required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-gold btn-lg w-100">Subscribe</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ============================== CONTACT ============================== -->
<section class="section section-alt" id="contact">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="section-eyebrow">Get In Touch</span>
                <h2 class="section-title">We're here to help</h2>
                <p class="section-sub">Have a question about deposits, withdrawals or your account? Send us a message and our support team will respond promptly.</p>
                <div class="mt-4">
                    <p class="mb-2"><i class="bi bi-envelope-fill me-2" style="color:var(--brand-emerald);"></i><?= e(get_setting('contact_email', 'support@surecashmining.com')) ?></p>
                    <p class="mb-0"><i class="bi bi-telephone-fill me-2" style="color:var(--brand-emerald);"></i><?= e(get_setting('contact_phone', '+234 800 000 0000')) ?></p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card-surface p-4 reveal">
                    <form id="contactForm" action="ajax/contact-submit.php" method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-6">
                            <label class="form-label small">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Subject</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Message</label>
                            <textarea name="message" class="form-control" rows="4" required minlength="10"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-brand">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/partials/site-footer.php'; ?>
<?php require __DIR__ . '/includes/partials/whatsapp-widget.php'; ?>

<script src="<?= e($assetBase) ?>/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script src="<?= e($assetBase) ?>/js/main.js"></script>
<script src="<?= e($assetBase) ?>/js/landing.js"></script>
</body>
</html>
