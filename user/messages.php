<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $result = support_send_user_message((int) $user['id'], (string) ($_POST['message'] ?? ''));
    if (!$result['success']) {
        flash('messages', $result['message'], 'error');
    }

    redirect(rtrim(APP_URL, '/') . '/user/messages.php');
}

support_mark_read_by_user((int) $user['id']);
$thread = support_thread_for_user((int) $user['id']);

$pageTitle = 'Messages';
$activeNav = 'messages';
require __DIR__ . '/../includes/partials/app-head.php';
?>
<p class="mb-4" style="color:var(--text-muted);">Chat directly with our support team. We usually reply within a few hours.</p>

<?php require __DIR__ . '/../includes/partials/flash-messages.php'; ?>

<div class="card-surface p-4">
    <div class="d-flex flex-column gap-3 mb-4" style="max-height:520px;overflow-y:auto;" id="threadScroll">
        <?php if (!$thread): ?>
            <div class="text-center py-5" style="color:var(--text-muted);">
                <i class="bi bi-chat-dots" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">No messages yet. Send us a message and our support team will respond here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($thread as $m): ?>
                <?php $isAdmin = $m['sender'] === 'admin'; ?>
                <div class="d-flex <?= $isAdmin ? 'justify-content-start' : 'justify-content-end' ?>">
                    <div style="max-width:75%;">
                        <div class="p-3" style="border-radius:14px;background:<?= $isAdmin ? 'var(--surface-alt)' : 'var(--brand-emerald)' ?>;color:<?= $isAdmin ? 'var(--text)' : '#fff' ?>;">
                            <?= nl2br(e($m['message'])) ?>
                        </div>
                        <div class="small mt-1" style="color:var(--text-muted);<?= $isAdmin ? '' : 'text-align:right;' ?>">
                            <?= $isAdmin ? e('Support' . ($m['admin_name'] ? ' · ' . $m['admin_name'] : '')) : 'You' ?> &middot; <?= e(time_ago($m['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="" class="d-flex gap-2">
        <?= csrf_field() ?>
        <textarea class="form-control" name="message" rows="2" placeholder="Type your message..." required></textarea>
        <button type="submit" class="btn btn-brand px-4">Send</button>
    </form>
</div>

<script>
    (function () {
        var el = document.getElementById('threadScroll');
        if (el) el.scrollTop = el.scrollHeight;
    })();
</script>

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
