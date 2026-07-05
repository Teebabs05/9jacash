<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$taskId = (int) ($_GET['task'] ?? $_POST['task_id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    flash('tasks', 'That task is not available.', 'error');
    redirect(rtrim(APP_URL, '/') . '/tasks/index.php');
}

$stmt = db()->prepare('SELECT id FROM task_submissions WHERE task_id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$taskId, $user['id']]);
if ($stmt->fetch()) {
    flash('tasks', 'You have already submitted proof for this task.', 'error');
    redirect(rtrim(APP_URL, '/') . '/tasks/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $proofText = clean($_POST['proof_text'] ?? '');
    $screenshotPath = null;

    if ((bool) $task['requires_screenshot']) {
        if (empty($_FILES['screenshot']['name'])) {
            $errors[] = 'A screenshot is required for this task.';
        } else {
            $error = validate_upload($_FILES['screenshot'], ['image/jpeg', 'image/png', 'image/webp'], 3 * 1024 * 1024);
            if ($error) {
                $errors[] = $error;
            } else {
                $screenshotPath = store_upload($_FILES['screenshot'], 'tasks');
            }
        }
    }

    if ($proofText === '' && !$screenshotPath) {
        $errors[] = 'Please provide proof text or a screenshot.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO task_submissions (task_id, user_id, proof_text, screenshot, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$taskId, $user['id'], $proofText, $screenshotPath, STATUS_PENDING]);

        log_activity((int) $user['id'], null, 'task_submitted', "Submitted proof for task: {$task['title']}");
        flash('tasks', 'Your submission has been received and is awaiting review.', 'success');
        redirect(rtrim(APP_URL, '/') . '/tasks/index.php');
    }
}

$pageTitle = 'Submit Task Proof';
$activeNav = 'tasks';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-1"><?= e($task['title']) ?></h5>
            <p class="small mb-3" style="color:var(--text-muted);"><?= e($task['description']) ?></p>
            <div class="d-flex justify-content-between py-2 mb-3" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Reward</span><strong class="text-success"><?= e(money($task['reward_amount'])) ?></strong>
            </div>

            <?php if ($task['task_url']): ?>
                <a href="<?= e($task['task_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-brand w-100 mb-3"><i class="bi bi-box-arrow-up-right me-1"></i> Open Task Link</a>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" data-loading-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">

                <div class="mb-3">
                    <label class="form-label small">Proof / Notes <?= $task['requires_screenshot'] ? '(optional)' : '(required)' ?></label>
                    <textarea class="form-control" name="proof_text" rows="3" placeholder="e.g. your username used, or a short note for the reviewer"></textarea>
                </div>

                <?php if ($task['requires_screenshot']): ?>
                    <div class="mb-4">
                        <label class="form-label small">Screenshot Proof (required)</label>
                        <input type="file" class="form-control" name="screenshot" accept="image/png,image/jpeg,image/webp" required>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-brand w-100">Submit for Review</button>
                <a href="index.php" class="btn btn-outline-brand w-100 mt-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
