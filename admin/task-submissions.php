<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = db()->prepare(
        'SELECT ts.*, t.title, t.reward_amount, u.email, u.full_name
         FROM task_submissions ts
         INNER JOIN tasks t ON t.id = ts.task_id
         INNER JOIN users u ON u.id = ts.user_id
         WHERE ts.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $submission = $stmt->fetch();

    if (!$submission) {
        flash('submissions', 'Submission not found.', 'error');
    } elseif ($submission['status'] !== STATUS_PENDING) {
        flash('submissions', 'This submission has already been reviewed.', 'error');
    } elseif ($action === 'approve') {
        db()->prepare("UPDATE task_submissions SET status = 'approved', reviewed_at = NOW() WHERE id = ?")->execute([$id]);

        wallet_credit(
            (int) $submission['user_id'],
            WALLET_BONUS,
            (float) $submission['reward_amount'],
            LEDGER_SOURCE_TASK,
            'Task reward: ' . $submission['title']
        );

        notify_user((int) $submission['user_id'], 'Task Approved', 'Your submission for "' . $submission['title'] . '" was approved. ' . money($submission['reward_amount']) . ' has been credited to your bonus wallet.', NOTIFY_TYPE_TASK);
        log_activity(null, (int) $admin['id'], 'task_submission_approved', "Approved submission #{$id} ({$submission['title']}) for {$submission['email']}");
        flash('submissions', 'Submission approved and reward credited.', 'success');
    } elseif ($action === 'reject') {
        $note = clean($_POST['admin_note'] ?? '');
        db()->prepare("UPDATE task_submissions SET status = 'rejected', admin_note = ?, reviewed_at = NOW() WHERE id = ?")->execute([$note, $id]);

        notify_user((int) $submission['user_id'], 'Task Rejected', 'Your submission for "' . $submission['title'] . '" was rejected.' . ($note ? " Reason: {$note}" : ''), NOTIFY_TYPE_TASK);
        log_activity(null, (int) $admin['id'], 'task_submission_rejected', "Rejected submission #{$id} ({$submission['title']}) for {$submission['email']}");
        flash('submissions', 'Submission rejected.', 'success');
    }

    redirect(rtrim(APP_URL, '/') . '/admin/task-submissions.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}

$where = '';
$params = [];
if ($statusFilter !== 'all') {
    $where = 'WHERE ts.status = ?';
    $params[] = $statusFilter;
}

$stmt = db()->prepare(
    "SELECT ts.*, t.title AS task_title, t.reward_amount, t.requires_screenshot, u.username, u.email, u.full_name
     FROM task_submissions ts
     INNER JOIN tasks t ON t.id = ts.task_id
     INNER JOIN users u ON u.id = ts.user_id
     {$where}
     ORDER BY ts.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$pageTitle = 'Task Submissions';
$activeNav = 'task-submissions';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
        <a href="?status=<?= e($key) ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-brand' : 'btn-outline-brand' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="card-surface p-4">
    <?php if (!$submissions): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-inbox" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No submissions in this category.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>User</th><th>Task</th><th>Reward</th><th>Proof</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($submissions as $s): ?>
                        <tr>
                            <td><?= e($s['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($s['username']) ?></div></td>
                            <td><?= e($s['task_title']) ?></td>
                            <td><?= e(money($s['reward_amount'])) ?></td>
                            <td>
                                <?php if ($s['proof_text']): ?><div class="small mb-1"><?= e($s['proof_text']) ?></div><?php endif; ?>
                                <?php if ($s['screenshot']): ?>
                                    <a href="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($s['screenshot']) ?>" target="_blank" class="small fw-semibold" style="color:var(--brand-emerald);"><i class="bi bi-image"></i> View Screenshot</a>
                                <?php endif; ?>
                            </td>
                            <td class="small" style="color:var(--text-muted);"><?= e(time_ago($s['created_at'])) ?></td>
                            <td><span class="pill pill-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                            <td class="text-end" style="min-width:180px;">
                                <?php if ($s['status'] === STATUS_PENDING): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" class="btn btn-brand btn-sm">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int) $s['id'] ?>">Reject</button>

                                    <div class="modal fade" id="rejectModal<?= (int) $s['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="background:var(--surface);color:var(--text);">
                                                <form method="POST" action="">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                    <div class="modal-header"><h6 class="modal-title">Reject Submission</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <label class="form-label small">Reason (optional, shown to the user)</label>
                                                        <textarea class="form-control" name="admin_note" rows="3"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-brand btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject Submission</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="small" style="color:var(--text-muted);"><?= e($s['reviewed_at'] ? 'Reviewed ' . time_ago($s['reviewed_at']) : '') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
