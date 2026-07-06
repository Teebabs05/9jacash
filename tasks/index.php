<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

$stmt = db()->prepare(
    "SELECT t.*, ts.status AS submission_status, ts.admin_note, ts.id AS submission_id
     FROM tasks t
     LEFT JOIN task_submissions ts ON ts.task_id = t.id AND ts.user_id = ?
     WHERE t.status = 'active'
     ORDER BY t.created_at DESC"
);
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();

$platformIcons = [
    'facebook' => 'bi-facebook',
    'telegram' => 'bi-telegram',
    'instagram' => 'bi-instagram',
    'whatsapp' => 'bi-whatsapp',
    'tiktok' => 'bi-tiktok',
    'website' => 'bi-globe2',
    'custom' => 'bi-star-fill',
];

$platformLabels = [
    'facebook' => 'Facebook', 'telegram' => 'Telegram', 'instagram' => 'Instagram',
    'whatsapp' => 'WhatsApp', 'tiktok' => 'TikTok', 'website' => 'Website Visit', 'custom' => 'Custom',
];

$stmt = db()->prepare('SELECT COALESCE(SUM(t.reward_amount), 0) AS total FROM task_submissions ts INNER JOIN tasks t ON t.id = ts.task_id WHERE ts.user_id = ? AND ts.status = ?');
$stmt->execute([$user['id'], STATUS_APPROVED]);
$totalTaskEarnings = (float) $stmt->fetch()['total'];

$pageTitle = 'Task Center';
$activeNav = 'tasks';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <p class="mb-0" style="color:var(--text-muted);">Complete simple tasks and earn instant rewards after admin approval.</p>
    <div class="card-surface px-3 py-2 small">Total Task Earnings: <strong class="text-success"><?= e(money($totalTaskEarnings)) ?></strong></div>
</div>

<div class="row g-4">
    <?php if (!$tasks): ?>
        <div class="col-12 text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-list-check" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No tasks are available right now. Please check back soon.</p>
        </div>
    <?php endif; ?>
    <?php foreach ($tasks as $task): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card-surface p-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi <?= e($platformIcons[$task['platform']] ?? 'bi-star-fill') ?>" style="color:var(--brand-emerald);font-size:1.2rem;"></i>
                    <span class="small fw-semibold text-uppercase" style="color:var(--text-muted);letter-spacing:.04em;"><?= e($platformLabels[$task['platform']] ?? $task['platform']) ?></span>
                </div>
                <h5 class="fw-bold"><?= e($task['title']) ?></h5>
                <p class="small flex-grow-1" style="color:var(--text-muted);"><?= e($task['description']) ?></p>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-success"><?= e(money($task['reward_amount'])) ?></span>
                    <?php if ($task['task_url']): ?>
                        <a href="<?= e($task['task_url']) ?>" target="_blank" rel="noopener" class="small">Visit Link <i class="bi bi-box-arrow-up-right"></i></a>
                    <?php endif; ?>
                </div>

                <?php if ($task['submission_status'] === null): ?>
                    <a href="submit.php?task=<?= (int) $task['id'] ?>" class="btn btn-brand w-100">Submit Proof</a>
                <?php elseif ($task['submission_status'] === STATUS_PENDING): ?>
                    <button class="btn btn-outline-brand w-100" disabled><span class="pill pill-pending me-1">Pending</span> Awaiting Review</button>
                <?php elseif ($task['submission_status'] === STATUS_APPROVED): ?>
                    <button class="btn w-100" disabled style="background:rgba(18,183,106,0.12);color:#12b76a;border:none;"><i class="bi bi-check-circle-fill me-1"></i> Approved &amp; Paid</button>
                <?php else: ?>
                    <button class="btn w-100" disabled style="background:rgba(240,68,56,0.12);color:#f04438;border:none;"><i class="bi bi-x-circle-fill me-1"></i> Rejected</button>
                    <?php if (!empty($task['admin_note'])): ?><div class="small mt-1" style="color:var(--text-muted);">Reason: <?= e($task['admin_note']) ?></div><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
