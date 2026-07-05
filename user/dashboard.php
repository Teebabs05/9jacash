<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$wallet = get_wallet((int) $user['id']);
$assetBase = rtrim(APP_URL, '/') . '/assets';
$pageTitle = 'Dashboard';
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
    <a class="navbar-brand fw-bold" href="dashboard.php" style="color:var(--brand-emerald);">9JACASH</a>
    <div class="d-flex align-items-center gap-3">
      <button type="button" class="theme-toggle-btn" style="color:var(--text);border-color:var(--border);" data-theme-toggle data-theme-icon><i class="bi bi-moon-stars"></i></button>
      <a href="logout.php" class="btn btn-outline-brand btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <?php require __DIR__ . '/../includes/partials/flash-messages.php'; ?>

  <h2 class="fw-bold mb-1">Welcome back, <?= e($user['full_name']) ?> 👋</h2>
  <p style="color:var(--text-muted);">Here's an overview of your account.</p>

  <div class="row g-4 mt-2">
    <div class="col-md-3 col-6">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Main Wallet</div>
        <div class="fs-4 fw-bold"><?= e(money($wallet['main_balance'])) ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Bonus Wallet</div>
        <div class="fs-4 fw-bold"><?= e(money($wallet['bonus_balance'])) ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Referral Wallet</div>
        <div class="fs-4 fw-bold"><?= e(money($wallet['referral_balance'])) ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-surface p-4">
        <div class="small" style="color:var(--text-muted);">Mining Wallet</div>
        <div class="fs-4 fw-bold"><?= e(money($wallet['mining_balance'])) ?></div>
      </div>
    </div>
  </div>

  <div class="card-surface p-4 mt-4">
    <h5 class="fw-bold mb-3">Your Referral Link</h5>
    <div class="input-group">
      <input type="text" class="form-control" readonly value="<?= e(rtrim(APP_URL, '/')) ?>/user/register.php?ref=<?= e($user['referral_code']) ?>">
      <button class="btn btn-outline-brand" type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); NineJaCash.toast('Referral link copied!');">Copy</button>
    </div>
  </div>

  <div class="alert alert-info mt-4 small">
    Mining, Tasks, Watch &amp; Earn, Spin Wheel, Daily Check-in, Deposits and Withdrawals are being rolled out in the next build phases and will appear here automatically.
  </div>
</div>

<script src="<?= e($assetBase) ?>/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script src="<?= e($assetBase) ?>/js/main.js"></script>
</body>
</html>
