<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$platforms = [
    TASK_PLATFORM_FACEBOOK => 'Facebook',
    TASK_PLATFORM_TELEGRAM => 'Telegram',
    TASK_PLATFORM_INSTAGRAM => 'Instagram',
    TASK_PLATFORM_WHATSAPP => 'WhatsApp',
    TASK_PLATFORM_TIKTOK => 'TikTok',
    TASK_PLATFORM_WEBSITE => 'Website Visit',
    TASK_PLATFORM_CUSTOM => 'Custom',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = clean($_POST['title'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $platform = $_POST['platform'] ?? TASK_PLATFORM_CUSTOM;
        $taskUrl = clean($_POST['task_url'] ?? '');
        $rewardAmount = (float) ($_POST['reward_amount'] ?? 0);
        $requiresScreenshot = isset($_POST['requires_screenshot']) ? 1 : 0;
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if (strlen($title) < 3) {
            $errors[] = 'Please enter a task title.';
        }
        if (!array_key_exists($platform, $platforms)) {
            $errors[] = 'Please select a valid platform.';
        }
        if ($rewardAmount <= 0) {
            $errors[] = 'Reward amount must be greater than zero.';
        }

        if (!$errors) {
            if ($id > 0) {
                $stmt = db()->prepare(
                    'UPDATE tasks SET title = ?, description = ?, platform = ?, task_url = ?, reward_amount = ?, requires_screenshot = ?, status = ?, updated_at = NOW() WHERE id = ?'
                );
                $stmt->execute([$title, $description, $platform, $taskUrl, $rewardAmount, $requiresScreenshot, $status, $id]);
                log_activity(null, (int) current_admin()['id'], 'task_updated', "Updated task #{$id}: {$title}");
                flash('tasks', 'Task updated successfully.', 'success');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO tasks (title, description, platform, task_url, reward_amount, requires_screenshot, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$title, $description, $platform, $taskUrl, $rewardAmount, $requiresScreenshot, $status]);
                log_activity(null, (int) current_admin()['id'], 'task_created', "Created task: {$title}");
                flash('tasks', 'Task created successfully.', 'success');
            }

            redirect(rtrim(APP_URL, '/') . '/admin/tasks.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
        log_activity(null, (int) current_admin()['id'], 'task_deleted', "Deleted task #{$id}");
        flash('tasks', 'Task deleted.', 'success');
        redirect(rtrim(APP_URL, '/') . '/admin/tasks.php');
    } elseif ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("UPDATE tasks SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?")->execute([$id]);
        redirect(rtrim(APP_URL, '/') . '/admin/tasks.php');
    }
}

$editTask = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editTask = $stmt->fetch() ?: null;
}

$tasks = db()->query(
    "SELECT t.*, (SELECT COUNT(*) FROM task_submissions ts WHERE ts.task_id = t.id) AS submission_count
     FROM tasks t ORDER BY t.created_at DESC"
)->fetchAll();

$pageTitle = 'Task Management';
$activeNav = 'tasks';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3"><?= $editTask ? 'Edit Task' : 'Create New Task' ?></h5>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editTask['id'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label small">Title</label>
                    <input type="text" class="form-control" name="title" value="<?= e($editTask['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= e($editTask['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Platform</label>
                    <select class="form-select" name="platform">
                        <?php foreach ($platforms as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= ($editTask['platform'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Task URL</label>
                    <input type="text" class="form-control" name="task_url" value="<?= e($editTask['task_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Reward Amount (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="reward_amount" value="<?= e((string) ($editTask['reward_amount'] ?? '')) ?>" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="requires_screenshot" name="requires_screenshot" <?= ($editTask['requires_screenshot'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="requires_screenshot">Require screenshot proof</label>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($editTask['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editTask['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-brand w-100"><?= $editTask ? 'Save Changes' : 'Create Task' ?></button>
                <?php if ($editTask): ?>
                    <a href="tasks.php" class="btn btn-outline-brand w-100 mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">All Tasks</h5>
            <?php if (!$tasks): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No tasks created yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Title</th><th>Platform</th><th>Reward</th><th>Submissions</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($tasks as $t): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($t['title']) ?></td>
                                    <td><span class="text-capitalize"><?= e($platforms[$t['platform']] ?? $t['platform']) ?></span></td>
                                    <td><?= e(money($t['reward_amount'])) ?></td>
                                    <td><?= (int) $t['submission_count'] ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                            <button type="submit" class="pill pill-<?= $t['status'] === 'active' ? 'approved' : 'rejected' ?> border-0" style="cursor:pointer;"><?= e(ucfirst($t['status'])) ?></button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="?edit=<?= (int) $t['id'] ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete this task? This cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
