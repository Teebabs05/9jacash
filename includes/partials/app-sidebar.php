<?php
/**
 * Authenticated left sidebar navigation.
 * Expects $activeNav (string key) to be set before including app-head.php.
 */
$activeNav = $activeNav ?? '';
$base = rtrim(APP_URL, '/');

if (!function_exists('nav_link')) {
    function nav_link(string $key, string $href, string $icon, string $label, string $activeNav, bool $built = true): void
    {
        $isActive = $key === $activeNav;
        if (!$built) {
            echo '<span class="nav-link disabled"><i class="bi ' . e($icon) . '"></i>' . e($label) . '<span class="badge-soon">Soon</span></span>';
            return;
        }
        echo '<a href="' . e($href) . '" class="nav-link' . ($isActive ? ' active' : '') . '"><i class="bi ' . e($icon) . '"></i>' . e($label) . '</a>';
    }
}
?>
<aside class="app-sidebar">
    <a href="<?= e($base) ?>/user/dashboard.php" class="brand text-decoration-none" style="color:var(--text);">
        <?= brand_mark_html() ?>
        <span>9JACASH</span>
    </a>
    <nav class="app-nav">
        <div class="nav-section-label">Main</div>
        <?php nav_link('dashboard', $base . '/user/dashboard.php', 'bi-grid-1x2-fill', 'Dashboard', $activeNav); ?>
        <?php nav_link('wallet', $base . '/wallet/index.php', 'bi-wallet2', 'Wallet', $activeNav); ?>
        <?php nav_link('history', $base . '/wallet/history.php', 'bi-clock-history', 'Transaction History', $activeNav); ?>

        <div class="nav-section-label">Earn</div>
        <?php nav_link('mining', $base . '/mining/index.php', 'bi-cpu-fill', 'Mining', $activeNav); ?>
        <?php nav_link('tasks', $base . '/tasks/index.php', 'bi-list-check', 'Task Center', $activeNav); ?>
        <?php nav_link('ads', $base . '/ads/index.php', 'bi-play-btn-fill', 'Watch & Earn', $activeNav); ?>
        <?php nav_link('spin', $base . '/spin/index.php', 'bi-disc-fill', 'Spin Wheel', $activeNav); ?>
        <?php nav_link('checkin', $base . '/checkin/index.php', 'bi-calendar-check-fill', 'Daily Check-in', $activeNav); ?>

        <div class="nav-section-label">Funds</div>
        <?php nav_link('deposit', $base . '/payments/deposit.php', 'bi-arrow-down-circle-fill', 'Deposit', $activeNav); ?>
        <?php nav_link('withdraw', $base . '/wallet/withdraw.php', 'bi-arrow-up-circle-fill', 'Withdraw', $activeNav); ?>
        <?php nav_link('bank-accounts', $base . '/wallet/bank-accounts.php', 'bi-credit-card-2-front-fill', 'Withdrawal Accounts', $activeNav); ?>
        <?php nav_link('referrals', $base . '/user/referrals.php', 'bi-people-fill', 'Referrals', $activeNav); ?>

        <div class="nav-section-label">Account</div>
        <?php nav_link('notifications', $base . '/user/notifications.php', 'bi-bell-fill', 'Notifications', $activeNav); ?>
        <?php nav_link('profile', $base . '/user/profile.php', 'bi-person-fill', 'Profile', $activeNav); ?>
        <a href="<?= e($base) ?>/user/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i>Logout</a>
    </nav>
</aside>
