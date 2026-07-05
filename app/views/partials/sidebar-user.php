<aside class="app-sidebar">
    <div class="p-3 border-bottom border-opacity-25" style="border-color:rgba(255,255,255,.1) !important;">
        <a href="<?= base_url('/') ?>"><?php require APP_PATH . '/views/partials/logo.php'; ?></a>
    </div>
    <nav class="py-3">
        <div class="nav-section-title">Overview</div>
        <a class="nav-link <?= is_active_path('dashboard') ?>" href="<?= base_url('dashboard') ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a class="nav-link <?= is_active_path('wallet') ?>" href="<?= base_url('wallet') ?>"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a class="nav-link <?= is_active_path('transactions') ?>" href="<?= base_url('transactions') ?>"><i class="fa-solid fa-receipt"></i> Transactions</a>

        <div class="nav-section-title">Finance</div>
        <a class="nav-link <?= is_active_path('deposit') ?>" href="<?= base_url('deposit') ?>"><i class="fa-solid fa-circle-plus"></i> Deposit</a>
        <a class="nav-link <?= is_active_path('withdraw') ?>" href="<?= base_url('withdraw') ?>"><i class="fa-solid fa-money-bill-transfer"></i> Withdraw</a>

        <div class="nav-section-title">Earn</div>
        <a class="nav-link <?= is_active_path('mining') ?>" href="<?= base_url('mining') ?>"><i class="fa-solid fa-server"></i> Mining Plans</a>
        <a class="nav-link <?= is_active_path('tasks') ?>" href="<?= base_url('tasks') ?>"><i class="fa-solid fa-list-check"></i> Tasks</a>
        <a class="nav-link <?= is_active_path('rewards') ?>" href="<?= base_url('rewards') ?>"><i class="fa-solid fa-gift"></i> Daily Rewards</a>
        <a class="nav-link <?= is_active_path('referrals') ?>" href="<?= base_url('referrals') ?>"><i class="fa-solid fa-share-nodes"></i> Referrals</a>
        <a class="nav-link <?= is_active_path('leaderboard') ?>" href="<?= base_url('leaderboard') ?>"><i class="fa-solid fa-trophy"></i> Leaderboard</a>

        <div class="nav-section-title">Account</div>
        <?php if (setting('kyc_required') === '1'): ?>
        <a class="nav-link <?= is_active_path('kyc') ?>" href="<?= base_url('kyc') ?>"><i class="fa-solid fa-id-card"></i> KYC Verification</a>
        <?php endif; ?>
        <a class="nav-link <?= is_active_path('profile') ?>" href="<?= base_url('profile') ?>"><i class="fa-solid fa-user-gear"></i> Profile</a>
        <a class="nav-link <?= is_active_path('notifications') ?>" href="<?= base_url('notifications') ?>"><i class="fa-solid fa-bell"></i> Notifications</a>
        <a class="nav-link <?= is_active_path('support') ?>" href="<?= base_url('support') ?>"><i class="fa-solid fa-headset"></i> Support</a>
        <form action="<?= base_url('logout') ?>" method="post">
            <?= csrf_field() ?>
            <button class="nav-link border-0 w-100 text-start bg-transparent" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
        </form>
    </nav>
</aside>
