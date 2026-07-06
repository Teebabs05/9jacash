<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (($_POST['action'] ?? '') === 'mark_all_read') {
        $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0');
        $stmt->execute([$user['id']]);
        flash('notifications', 'All notifications marked as read.', 'success');
    }

    redirect(rtrim(APP_URL, '/') . '/user/notifications.php');
}

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = db()->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? OR user_id IS NULL');
$countStmt->execute([$user['id']]);
$total = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare(
    'SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
);
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

// Mark the ones shown as read.
if ($notifications) {
    $ids = array_column($notifications, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ({$in})")->execute($ids);
}

$typeIcons = [
    'deposit' => 'bi-arrow-down-circle-fill text-success',
    'withdrawal' => 'bi-arrow-up-circle-fill text-danger',
    'mining' => 'bi-cpu-fill text-warning',
    'referral' => 'bi-people-fill text-info',
    'task' => 'bi-list-check text-primary',
    'system' => 'bi-gear-fill text-secondary',
    'broadcast' => 'bi-megaphone-fill text-warning',
];

$pageTitle = 'Notifications';
$activeNav = 'notifications';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="mb-0" style="color:var(--text-muted);">All account and system notifications.</p>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-outline-brand btn-sm">Mark all as read</button>
    </form>
</div>

<div class="card-surface">
    <?php if (!$notifications): ?>
        <div class="p-5 text-center" style="color:var(--text-muted);">
            <i class="bi bi-bell-slash" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">You have no notifications yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
            <div class="d-flex gap-3 p-3" style="border-bottom:1px solid var(--border);">
                <div class="flex-shrink-0"><i class="bi <?= e($typeIcons[$n['type']] ?? 'bi-bell-fill') ?>" style="font-size:1.3rem;"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e($n['title']) ?></div>
                    <div class="small" style="color:var(--text-muted);"><?= e($n['message']) ?></div>
                    <div class="small mt-1" style="color:var(--text-muted);"><?= e(time_ago($n['created_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
