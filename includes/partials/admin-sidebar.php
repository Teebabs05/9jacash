<?php
/**
 * Admin left sidebar navigation.
 * Expects $activeNav (string key) to be set before including admin-head.php.
 */
$activeNav = $activeNav ?? '';
$base = rtrim(APP_URL, '/');

if (!function_exists('admin_nav_link')) {
    function admin_nav_link(string $key, string $href, string $icon, string $label, string $activeNav, bool $built = true, ?int $badge = null): void
    {
        $isActive = $key === $activeNav;
        if (!$built) {
            echo '<span class="nav-link disabled"><i class="bi ' . e($icon) . '"></i>' . e($label) . '<span class="badge-soon">Soon</span></span>';
            return;
        }
        $badgeHtml = $badge ? '<span class="badge-soon ms-auto" style="background:var(--danger);color:#fff;border-color:var(--danger);">' . (int) $badge . '</span>' : '';
        echo '<a href="' . e($href) . '" class="nav-link' . ($isActive ? ' active' : '') . '"><i class="bi ' . e($icon) . '"></i>' . e($label) . $badgeHtml . '</a>';
    }
}

$pendingSubmissions = (int) db()->query("SELECT COUNT(*) AS c FROM task_submissions WHERE status = 'pending'")->fetch()['c'];
?>
<aside class="app-sidebar">
    <a href="<?= e($base) ?>/admin/index.php" class="brand text-decoration-none" style="color:var(--text);">
        <span class="brand-mark">9</span>
        <span>9JACASH Admin</span>
    </a>
    <nav class="app-nav">
        <div class="nav-section-label">Overview</div>
        <?php admin_nav_link('dashboard', $base . '/admin/index.php', 'bi-grid-1x2-fill', 'Dashboard', $activeNav); ?>

        <div class="nav-section-label">Users</div>
        <?php admin_nav_link('users', $base . '/admin/users.php', 'bi-people-fill', 'Manage Users', $activeNav, false); ?>

        <div class="nav-section-label">Finance</div>
        <?php admin_nav_link('deposits', $base . '/admin/deposits.php', 'bi-arrow-down-circle-fill', 'Deposits', $activeNav, false); ?>
        <?php admin_nav_link('withdrawals', $base . '/admin/withdrawals.php', 'bi-arrow-up-circle-fill', 'Withdrawals', $activeNav, false); ?>

        <div class="nav-section-label">Earning Modules</div>
        <?php admin_nav_link('mining-plans', $base . '/admin/mining-plans.php', 'bi-cpu-fill', 'Mining Plans', $activeNav, false); ?>
        <?php admin_nav_link('tasks', $base . '/admin/tasks.php', 'bi-list-check', 'Tasks', $activeNav); ?>
        <?php admin_nav_link('task-submissions', $base . '/admin/task-submissions.php', 'bi-inbox-fill', 'Task Submissions', $activeNav, true, $pendingSubmissions ?: null); ?>

        <div class="nav-section-label">Platform</div>
        <?php admin_nav_link('settings', $base . '/admin/settings.php', 'bi-gear-fill', 'Settings', $activeNav, false); ?>
        <a href="<?= e($base) ?>/admin/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i>Logout</a>
    </nav>
</aside>
