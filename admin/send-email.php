<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'queue') {
        $subject = clean($_POST['subject'] ?? '');
        // Not run through clean()/strip_tags() like most admin text
        // fields - this is an email body where admins are expected to
        // be able to write links and basic formatting, and stripping
        // tags would silently mangle any <a href> they include.
        $body = trim((string) ($_POST['body'] ?? ''));
        $audience = ($_POST['audience'] ?? 'all') === 'selected' ? 'selected' : 'all';
        $selectedIds = array_filter(array_map('intval', explode(',', (string) ($_POST['selected_user_ids'] ?? ''))));

        if (strlen($subject) < 3) {
            $errors[] = 'Please enter a subject.';
        }
        if (strlen($body) < 3) {
            $errors[] = 'Please enter a message.';
        }
        if ($audience === 'selected' && !$selectedIds) {
            $errors[] = 'Please search for and select at least one recipient.';
        }

        if (!$errors) {
            if ($audience === 'all') {
                $userIds = db()->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = db()->prepare("SELECT id FROM users WHERE id IN ({$placeholders})");
                $stmt->execute($selectedIds);
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (!$userIds) {
                $errors[] = 'No matching recipients found.';
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $pdo->prepare(
                        'INSERT INTO bulk_emails (admin_id, subject, body, audience, total_recipients, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
                    )->execute([$admin['id'], $subject, $body, $audience, count($userIds), 'pending']);
                    $bulkEmailId = (int) $pdo->lastInsertId();

                    $insertRecipient = $pdo->prepare(
                        'INSERT INTO bulk_email_recipients (bulk_email_id, user_id, status) VALUES (?, ?, ?)'
                    );
                    foreach ($userIds as $userId) {
                        $insertRecipient->execute([$bulkEmailId, $userId, 'pending']);
                    }

                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    app_log('error', 'Bulk email queue failed: ' . $e->getMessage(), ['admin_id' => $admin['id']]);
                    $errors[] = 'Something went wrong queuing the email. Please try again.';
                }

                if (!$errors) {
                    log_activity(null, (int) $admin['id'], 'bulk_email_queued', "Queued \"{$subject}\" to " . count($userIds) . ' users');
                    flash('send_email', 'Queued for ' . number_format(count($userIds)) . ' recipient(s). Sending happens gradually in the background.', 'success');
                    redirect(rtrim(APP_URL, '/') . '/admin/send-email.php');
                }
            }
        }
    }
}

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalCampaigns = (int) db()->query('SELECT COUNT(*) AS c FROM bulk_emails')->fetch()['c'];
$totalPages = max(1, (int) ceil($totalCampaigns / $perPage));

$campaigns = db()->query(
    "SELECT be.*, a.full_name AS admin_name FROM bulk_emails be
     LEFT JOIN admins a ON a.id = be.admin_id
     ORDER BY be.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
)->fetchAll();

$statusPill = [
    'pending' => 'pending',
    'processing' => 'pending',
    'completed' => 'approved',
];

$pageTitle = 'Send Email';
$activeNav = 'send-email';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Compose Email</h5>
            <form method="POST" action="" id="sendEmailForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="queue">
                <input type="hidden" name="selected_user_ids" id="selectedUserIds" value="">

                <div class="mb-3">
                    <label class="form-label small">Subject</label>
                    <input type="text" class="form-control" name="subject" maxlength="200" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Message</label>
                    <textarea class="form-control" name="body" rows="6" required></textarea>
                    <div class="form-text">Basic HTML is allowed. Plain text works fine too.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small d-block">Send To</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="audience" value="all" id="audienceAll" checked>
                        <label class="form-check-label small" for="audienceAll">All Users</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="audience" value="selected" id="audienceSelected">
                        <label class="form-check-label small" for="audienceSelected">Selected Users</label>
                    </div>
                </div>

                <div id="userPicker" class="mb-4 d-none">
                    <label class="form-label small">Search Users</label>
                    <input type="text" class="form-control mb-2" id="userSearchInput" placeholder="Search by name, username or email...">
                    <div id="userSearchResults" class="mb-2" style="max-height:180px;overflow-y:auto;"></div>
                    <label class="form-label small">Selected (<span id="selectedCount">0</span>)</label>
                    <div id="selectedUserChips" class="d-flex flex-wrap gap-2"></div>
                </div>

                <button type="submit" class="btn btn-brand w-100"><i class="bi bi-send-fill me-1"></i> Queue Email</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Recent Campaigns</h5>
            <?php if (!$campaigns): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">No emails sent yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead><tr><th>Subject</th><th>Audience</th><th>Progress</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($campaigns as $c): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($c['subject']) ?></td>
                                    <td class="small text-capitalize"><?= e($c['audience']) ?> (<?= number_format((int) $c['total_recipients']) ?>)</td>
                                    <td class="small"><?= number_format((int) $c['sent_count']) ?> sent<?= $c['failed_count'] > 0 ? ', ' . number_format((int) $c['failed_count']) . ' failed' : '' ?></td>
                                    <td><span class="pill pill-<?= e($statusPill[$c['status']] ?? 'pending') ?>"><?= e(ucfirst($c['status'])) ?></span></td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var audienceAll = document.getElementById('audienceAll');
    var audienceSelected = document.getElementById('audienceSelected');
    var userPicker = document.getElementById('userPicker');
    var searchInput = document.getElementById('userSearchInput');
    var resultsEl = document.getElementById('userSearchResults');
    var chipsEl = document.getElementById('selectedUserChips');
    var countEl = document.getElementById('selectedCount');
    var hiddenIds = document.getElementById('selectedUserIds');
    var form = document.getElementById('sendEmailForm');
    var selected = {};
    var searchTimer = null;

    function toggleAudience() {
        userPicker.classList.toggle('d-none', !audienceSelected.checked);
    }
    audienceAll.addEventListener('change', toggleAudience);
    audienceSelected.addEventListener('change', toggleAudience);

    function renderChips() {
        chipsEl.innerHTML = '';
        var ids = Object.keys(selected);
        countEl.textContent = ids.length;
        hiddenIds.value = ids.join(',');
        ids.forEach(function (id) {
            var chip = document.createElement('span');
            chip.className = 'pill pill-active';
            chip.style.cursor = 'pointer';
            chip.appendChild(document.createTextNode(selected[id] + ' '));
            var icon = document.createElement('i');
            icon.className = 'bi bi-x-circle-fill ms-1';
            chip.appendChild(icon);
            chip.addEventListener('click', function () {
                delete selected[id];
                renderChips();
            });
            chipsEl.appendChild(chip);
        });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) {
            resultsEl.innerHTML = '';
            return;
        }
        searchTimer = setTimeout(function () {
            fetch('<?= e(rtrim(APP_URL, '/')) ?>/ajax/admin-search-users.php?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    resultsEl.innerHTML = '';
                    if (!data.success || !data.users.length) {
                        resultsEl.innerHTML = '<div class="small" style="color:var(--text-muted);">No matches.</div>';
                        return;
                    }
                    data.users.forEach(function (u) {
                        var row = document.createElement('div');
                        row.className = 'd-flex justify-content-between align-items-center py-1 small';
                        var label = document.createElement('span');
                        label.appendChild(document.createTextNode(u.full_name + ' '));
                        var emailSpan = document.createElement('span');
                        emailSpan.style.color = 'var(--text-muted)';
                        emailSpan.textContent = '(' + u.email + ')';
                        label.appendChild(emailSpan);
                        row.appendChild(label);
                        var addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 'btn btn-outline-brand btn-sm py-0 px-2';
                        addBtn.textContent = selected[u.id] ? 'Added' : 'Add';
                        addBtn.disabled = !!selected[u.id];
                        addBtn.addEventListener('click', function () {
                            selected[u.id] = u.full_name;
                            renderChips();
                            addBtn.textContent = 'Added';
                            addBtn.disabled = true;
                        });
                        row.appendChild(addBtn);
                        resultsEl.appendChild(row);
                    });
                });
        }, 300);
    });

    form.addEventListener('submit', function (e) {
        if (audienceSelected.checked && Object.keys(selected).length === 0) {
            e.preventDefault();
            alert('Please search for and select at least one recipient.');
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
