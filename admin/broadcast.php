<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        $title = clean($_POST['title'] ?? '');
        $message = clean($_POST['message'] ?? '');

        if (strlen($title) < 3) {
            $errors[] = 'Please enter a title.';
        }
        if (strlen($message) < 3) {
            $errors[] = 'Please enter a message.';
        }

        if (!$errors) {
            $userIds = db()->query("SELECT id FROM users WHERE status != 'banned'")->fetchAll(PDO::FETCH_COLUMN);

            $pdo = db();
            $pdo->beginTransaction();
            try {
                foreach ($userIds as $userId) {
                    notify_user((int) $userId, $title, $message, NOTIFY_TYPE_BROADCAST);
                }

                $pdo->prepare(
                    'INSERT INTO broadcast_campaigns (admin_id, title, message, recipient_count, created_at) VALUES (?, ?, ?, ?, NOW())'
                )->execute([$admin['id'], $title, $message, count($userIds)]);

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                app_log('error', 'Broadcast send failed: ' . $e->getMessage(), ['admin_id' => $admin['id']]);
                $errors[] = 'Something went wrong sending the broadcast. Please try again.';
            }

            if (!$errors) {
                log_activity(null, (int) $admin['id'], 'broadcast_sent', "Sent broadcast \"{$title}\" to " . count($userIds) . ' users');
                flash('broadcast', 'Broadcast sent to ' . number_format(count($userIds)) . ' users.', 'success');
                redirect(rtrim(APP_URL, '/') . '/admin/broadcast.php');
            }
        }
    }
}

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalCampaigns = (int) db()->query('SELECT COUNT(*) AS c FROM broadcast_campaigns')->fetch()['c'];
$totalPages = max(1, (int) ceil($totalCampaigns / $perPage));

$campaigns = db()->query(
    "SELECT bc.*, a.full_name AS admin_name FROM broadcast_campaigns bc
     LEFT JOIN admins a ON a.id = bc.admin_id
     ORDER BY bc.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
)->fetchAll();

$pageTitle = 'Broadcast Notifications';
$activeNav = 'broadcast';
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
            <h5 class="fw-bold mb-3">Send Push Notification</h5>
            <p class="small mb-3" style="color:var(--text-muted);">Shows as a pop-up on every user's dashboard the next time they log in or reload it, and stays in their notification history.</p>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send">
                <div class="mb-3">
                    <label class="form-label small">Title</label>
                    <input type="text" class="form-control" name="title" maxlength="150" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Message</label>
                    <textarea class="form-control" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-brand w-100" onclick="return confirm('Send this notification to every user right now?');">
                    <i class="bi bi-megaphone-fill me-1"></i> Send to All Users
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Broadcast History</h5>
            <?php if (!$campaigns): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No broadcasts sent yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Title</th><th>Message</th><th>Recipients</th><th>Sent By</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($campaigns as $c): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($c['title']) ?></td>
                                    <td class="small" style="max-width:280px;"><?= e(mb_strimwidth($c['message'], 0, 80, '...')) ?></td>
                                    <td><?= number_format((int) $c['recipient_count']) ?></td>
                                    <td class="small"><?= e($c['admin_name'] ?? 'Unknown') ?></td>
                                    <td class="small" style="color:var(--text-muted);"><?= e(date('M d, Y H:i', strtotime((string) $c['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
