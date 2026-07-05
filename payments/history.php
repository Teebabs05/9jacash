<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

$stmt = db()->prepare('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$deposits = $stmt->fetchAll();

$pageTitle = 'Deposit History';
$activeNav = 'deposit';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="mb-0" style="color:var(--text-muted);">Your deposit requests and their status.</p>
    <a href="deposit.php" class="btn btn-brand btn-sm">New Deposit</a>
</div>

<div class="card-surface p-4">
    <?php if (!$deposits): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">No deposits yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>Reference</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($deposits as $d): ?>
                        <tr>
                            <td class="small"><?= e($d['reference']) ?></td>
                            <td class="text-uppercase small"><?= e($d['method']) ?></td>
                            <td><?= e(money($d['amount'])) ?></td>
                            <td><span class="pill pill-<?= e($d['status']) ?>"><?= e(ucfirst($d['status'])) ?></span></td>
                            <td class="small" style="color:var(--text-muted);"><?= e(time_ago($d['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
