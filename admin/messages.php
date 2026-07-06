<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$viewUserId = (int) ($_GET['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $result = support_send_admin_reply($targetUserId, (int) $admin['id'], (string) ($_POST['message'] ?? ''));

    if (!$result['success']) {
        flash('messages', $result['message'], 'error');
    }

    redirect(rtrim(APP_URL, '/') . '/admin/messages.php?user_id=' . $targetUserId);
}

$conversations = support_conversation_list_for_admin();

$viewUser = null;
$thread = [];
if ($viewUserId) {
    $stmt = db()->prepare('SELECT id, username, full_name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$viewUserId]);
    $viewUser = $stmt->fetch();

    if ($viewUser) {
        support_mark_read_by_admin($viewUserId);
        $thread = support_thread_for_user($viewUserId);
    }
}

$pageTitle = 'Support Messages';
$activeNav = 'messages';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php require __DIR__ . '/../includes/partials/flash-messages.php'; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-surface p-0">
            <div class="p-3 fw-bold" style="border-bottom:1px solid var(--border);">Conversations</div>
            <?php if (!$conversations): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">
                    <i class="bi bi-chat-dots" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">No messages yet.</p>
                </div>
            <?php else: ?>
                <div style="max-height:600px;overflow-y:auto;">
                    <?php foreach ($conversations as $c): ?>
                        <a href="?user_id=<?= (int) $c['user_id'] ?>" class="d-block p-3 text-decoration-none" style="border-bottom:1px solid var(--border);color:var(--text);<?= $viewUserId === (int) $c['user_id'] ? 'background:var(--surface-alt);' : '' ?>">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($c['full_name']) ?></strong>
                                <?php if ((int) $c['unread_count'] > 0): ?>
                                    <span class="badge rounded-pill" style="background:var(--danger);"><?= (int) $c['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small" style="color:var(--text-muted);">@<?= e($c['username']) ?> &middot; <?= e(time_ago($c['last_message_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (!$viewUser): ?>
            <div class="card-surface p-5 text-center" style="color:var(--text-muted);">
                <i class="bi bi-chat-square-text" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">Select a conversation to view and reply.</p>
            </div>
        <?php else: ?>
            <div class="card-surface p-4">
                <h5 class="fw-bold mb-1"><?= e($viewUser['full_name']) ?></h5>
                <p class="small mb-4" style="color:var(--text-muted);">@<?= e($viewUser['username']) ?> &middot; <?= e($viewUser['email']) ?></p>

                <div class="d-flex flex-column gap-3 mb-4" style="max-height:460px;overflow-y:auto;" id="threadScroll">
                    <?php if (!$thread): ?>
                        <div class="text-center py-4" style="color:var(--text-muted);">No messages in this conversation yet.</div>
                    <?php else: ?>
                        <?php foreach ($thread as $m): ?>
                            <?php $isAdmin = $m['sender'] === 'admin'; ?>
                            <div class="d-flex <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div style="max-width:75%;">
                                    <div class="p-3" style="border-radius:14px;background:<?= $isAdmin ? 'var(--brand-emerald)' : 'var(--surface-alt)' ?>;color:<?= $isAdmin ? '#fff' : 'var(--text)' ?>;">
                                        <?= nl2br(e($m['message'])) ?>
                                    </div>
                                    <div class="small mt-1" style="color:var(--text-muted);<?= $isAdmin ? 'text-align:right;' : '' ?>">
                                        <?= $isAdmin ? e('You' . ($m['admin_name'] ? ' (' . $m['admin_name'] . ')' : '')) : e($viewUser['full_name']) ?> &middot; <?= e(time_ago($m['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                    <textarea class="form-control" name="message" rows="2" placeholder="Type your reply..." required></textarea>
                    <button type="submit" class="btn btn-brand px-4">Reply</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var el = document.getElementById('threadScroll');
        if (el) el.scrollTop = el.scrollHeight;
    })();
</script>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
