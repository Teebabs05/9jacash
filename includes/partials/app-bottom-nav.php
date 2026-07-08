<?php
/**
 * Mobile bottom tab bar shown on every authenticated user page (hidden
 * on desktop widths - see .app-bottom-nav in app.css). Complements the
 * existing sidebar/hamburger menu with quick access to the busiest
 * sections; the sidebar still covers everything else.
 */
$activeNav = $activeNav ?? '';
$bnBase = rtrim(APP_URL, '/');
?>
<nav class="app-bottom-nav">
    <a href="<?= e($bnBase) ?>/user/dashboard.php" class="bn-item<?= $activeNav === 'dashboard' ? ' active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>Home
    </a>
    <a href="<?= e($bnBase) ?>/mining/index.php" class="bn-item<?= $activeNav === 'mining' ? ' active' : '' ?>">
        <i class="bi bi-cpu-fill"></i>Mining
    </a>
    <a href="<?= e($bnBase) ?>/mining/index.php" class="bn-fab" aria-label="Mine">
        <i class="bi bi-hammer"></i>
    </a>
    <a href="<?= e($bnBase) ?>/tasks/index.php" class="bn-item<?= $activeNav === 'tasks' ? ' active' : '' ?>">
        <i class="bi bi-list-check"></i>Tasks
    </a>
    <a href="<?= e($bnBase) ?>/wallet/index.php" class="bn-item<?= $activeNav === 'wallet' ? ' active' : '' ?>">
        <i class="bi bi-wallet2"></i>Wallet
    </a>
</nav>
