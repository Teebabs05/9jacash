<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4 text-center">Referral Program</h1>
            <p class="text-center text-muted-soft mb-5">Invite friends and earn commissions on their activity — automatically, forever.</p>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="surface-card p-4 text-center">
                        <i class="fa-solid fa-user-plus fs-3 mb-2" style="color:var(--c-primary);"></i>
                        <h6 class="fw-bold">Signup Bonus</h6>
                        <p class="small text-muted-soft mb-0">Earn a flat bonus every time someone joins using your referral link.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="surface-card p-4 text-center">
                        <i class="fa-solid fa-percent fs-3 mb-2" style="color:var(--c-accent);"></i>
                        <h6 class="fw-bold">Commission on Activity</h6>
                        <p class="small text-muted-soft mb-0">Earn a percentage whenever your referrals deposit, mine or complete tasks.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="surface-card p-4 text-center">
                        <i class="fa-solid fa-layer-group fs-3 mb-2" style="color:var(--c-primary);"></i>
                        <h6 class="fw-bold">Multi-Level Rewards</h6>
                        <p class="small text-muted-soft mb-0">Earn from your direct referrals and additional levels beneath them.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="<?= base_url(is_logged_in() ? 'referrals' : 'register') ?>" class="btn btn-brand rounded-pill px-4">
                    <?= is_logged_in() ? 'View Your Referral Dashboard' : 'Sign Up to Get Your Link' ?>
                </a>
            </div>
        </div>
    </div>
</div>
