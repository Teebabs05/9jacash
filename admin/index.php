<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

$totalUsers = (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$pendingDeposits = (int) db()->query("SELECT COUNT(*) AS c FROM deposits WHERE status = 'pending'")->fetch()['c'];
$pendingWithdrawals = (int) db()->query("SELECT COUNT(*) AS c FROM withdrawals WHERE status = 'pending'")->fetch()['c'];
$pendingSubmissions = (int) db()->query("SELECT COUNT(*) AS c FROM task_submissions WHERE status = 'pending'")->fetch()['c'];
$activeMining = (int) db()->query("SELECT COUNT(*) AS c FROM user_mining WHERE status = 'active'")->fetch()['c'];

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<h4 class="fw-bold mb-1">Welcome, <?= e($admin['full_name']) ?></h4>
<p style="color:var(--text-muted);">Platform overview at a glance.</p>

<div class="row g-4 mt-1">
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(11,37,69,0.10);color:var(--brand-navy);"><i class="bi bi-people-fill"></i></div>
            <div class="label">Total Users</div>
            <div class="value"><?= number_format($totalUsers) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(46,144,250,0.14);color:var(--info);"><i class="bi bi-arrow-down-circle-fill"></i></div>
            <div class="label">Pending Deposits</div>
            <div class="value"><?= number_format($pendingDeposits) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(240,68,56,0.12);color:var(--danger);"><i class="bi bi-arrow-up-circle-fill"></i></div>
            <div class="label">Pending Withdrawals</div>
            <div class="value"><?= number_format($pendingWithdrawals) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-tile">
            <div class="icon-badge" style="background:rgba(15,81,50,0.12);color:var(--brand-emerald);"><i class="bi bi-cpu-fill"></i></div>
            <div class="label">Active Mining Positions</div>
            <div class="value"><?= number_format($activeMining) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0">Task Submissions</h5>
                <a href="task-submissions.php" class="small fw-semibold" style="color:var(--brand-emerald);">Review <i class="bi bi-arrow-right"></i></a>
            </div>
            <p style="color:var(--text-muted);" class="mb-0"><?= number_format($pendingSubmissions) ?> submission(s) awaiting review.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0">Task Management</h5>
                <a href="tasks.php" class="small fw-semibold" style="color:var(--brand-emerald);">Manage <i class="bi bi-arrow-right"></i></a>
            </div>
            <p style="color:var(--text-muted);" class="mb-0">Create and manage social/website tasks for users to complete.</p>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 small">
    User management, deposit/withdrawal approval, mining plan management and platform settings are being rolled out in the next build phases.
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
