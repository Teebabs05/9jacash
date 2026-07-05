<aside class="app-sidebar">
    <div class="p-3 border-bottom border-opacity-25" style="border-color:rgba(255,255,255,.1) !important;">
        <a href="<?= base_url('admin') ?>"><?php require APP_PATH . '/views/partials/logo.php'; ?></a>
    </div>
    <nav class="py-3">
        <div class="nav-section-title">Overview</div>
        <a class="nav-link <?= is_active_path('admin') === 'active' && current_path() === 'admin' ? 'active' : '' ?>" href="<?= base_url('admin') ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <div class="nav-section-title">Users</div>
        <a class="nav-link <?= is_active_path('admin/users') ?>" href="<?= base_url('admin/users') ?>"><i class="fa-solid fa-users"></i> Manage Users</a>
        <a class="nav-link <?= is_active_path('admin/kyc') ?>" href="<?= base_url('admin/kyc') ?>"><i class="fa-solid fa-id-card"></i> KYC Requests</a>

        <div class="nav-section-title">Finance</div>
        <a class="nav-link <?= is_active_path('admin/deposits') ?>" href="<?= base_url('admin/deposits') ?>"><i class="fa-solid fa-circle-plus"></i> Deposits</a>
        <a class="nav-link <?= is_active_path('admin/payment-methods') ?>" href="<?= base_url('admin/payment-methods') ?>"><i class="fa-solid fa-building-columns"></i> Payment Methods</a>
        <a class="nav-link <?= is_active_path('admin/withdrawals') ?>" href="<?= base_url('admin/withdrawals') ?>"><i class="fa-solid fa-money-bill-transfer"></i> Withdrawals</a>

        <div class="nav-section-title">Earning Engine</div>
        <a class="nav-link <?= is_active_path('admin/mining') ?>" href="<?= base_url('admin/mining/plans') ?>"><i class="fa-solid fa-server"></i> Mining Plans</a>
        <a class="nav-link <?= is_active_path('admin/tasks') ?>" href="<?= base_url('admin/tasks') ?>"><i class="fa-solid fa-list-check"></i> Tasks</a>
        <a class="nav-link <?= is_active_path('admin/referral-settings') ?>" href="<?= base_url('admin/referral-settings') ?>"><i class="fa-solid fa-share-nodes"></i> Referral Settings</a>
        <a class="nav-link <?= is_active_path('admin/coupons') ?>" href="<?= base_url('admin/coupons') ?>"><i class="fa-solid fa-ticket"></i> Coupons</a>

        <div class="nav-section-title">Support &amp; Content</div>
        <a class="nav-link <?= is_active_path('admin/support') ?>" href="<?= base_url('admin/support') ?>"><i class="fa-solid fa-headset"></i> Support Tickets</a>
        <a class="nav-link <?= is_active_path('admin/announcements') ?>" href="<?= base_url('admin/announcements') ?>"><i class="fa-solid fa-bullhorn"></i> Announcements</a>

        <div class="nav-section-title">System</div>
        <a class="nav-link <?= is_active_path('admin/settings') ?>" href="<?= base_url('admin/settings') ?>"><i class="fa-solid fa-gears"></i> Settings</a>
        <a class="nav-link <?= is_active_path('admin/activity-logs') ?>" href="<?= base_url('admin/activity-logs') ?>"><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</a>
        <a class="nav-link <?= is_active_path('admin/webhook-logs') ?>" href="<?= base_url('admin/webhook-logs') ?>"><i class="fa-solid fa-satellite-dish"></i> Webhook Logs</a>
        <a class="nav-link" href="<?= base_url('/') ?>"><i class="fa-solid fa-arrow-left"></i> Back to Site</a>
        <form action="<?= base_url('logout') ?>" method="post">
            <?= csrf_field() ?>
            <button class="nav-link border-0 w-100 text-start bg-transparent" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
        </form>
    </nav>
</aside>
