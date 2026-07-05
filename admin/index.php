<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

$totalUsers = (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$pendingDeposits = (int) db()->query("SELECT COUNT(*) AS c FROM deposits WHERE status = 'pending'")->fetch()['c'];
$pendingWithdrawals = (int) db()->query("SELECT COUNT(*) AS c FROM withdrawals WHERE status = 'pending'")->fetch()['c'];

$assetBase = rtrim(APP_URL, '/') . '/assets';
$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>(function(){var t=localStorage.getItem('9jacash_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(get_setting('site_name', '9JACASH')) ?></title>
<link rel="icon" href="<?= e($assetBase) ?>/images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background:var(--surface);border-bottom:1px solid var(--border);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php" style="color:var(--brand-emerald);">9JACASH ADMIN</a>
    <div class="d-flex align-items-center gap-3">
      <button type="button" class="theme-toggle-btn" style="color:var(--text);border-color:var(--border);" data-theme-toggle data-theme-icon><i class="bi bi-moon-stars"></i></button>
      <span class="small" style="color:var(--text-muted);">Logged in as <strong><?= e($admin['username']) ?></strong></span>
      <a href="logout.php" class="btn btn-outline-brand btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h2 class="fw-bold mb-1">Welcome, <?= e($admin['full_name']) ?></h2>
  <p style="color:var(--text-muted);">Platform overview at a glance.</p>

  <div class="row g-4 mt-2">
    <div class="col-md-4">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Total Users</div>
        <div class="fs-3 fw-bold"><?= number_format($totalUsers) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Pending Deposits</div>
        <div class="fs-3 fw-bold"><?= number_format($pendingDeposits) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Pending Withdrawals</div>
        <div class="fs-3 fw-bold"><?= number_format($pendingWithdrawals) ?></div>
      </div>
    </div>
  </div>

  <div class="alert alert-info mt-4 small">
    User management, deposit/withdrawal approval, mining plans, task management and settings modules are being rolled out in the next build phases.
  </div>
</div>

<script src="<?= e($assetBase) ?>/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script src="<?= e($assetBase) ?>/js/main.js"></script>
</body>
</html>
