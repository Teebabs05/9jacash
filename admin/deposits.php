<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'approve') {
        $result = deposits_approve($id, (int) $admin['id']);
        flash('deposits', $result['message'], $result['success'] ? 'success' : 'error');
    } elseif ($action === 'reject') {
        $note = clean($_POST['admin_note'] ?? '');
        $result = deposits_reject($id, (int) $admin['id'], $note);
        flash('deposits', $result['message'], $result['success'] ? 'success' : 'error');
    }

    redirect(rtrim(APP_URL, '/') . '/admin/deposits.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}

$where = '';
$params = [];
if ($statusFilter !== 'all') {
    $where = 'WHERE d.status = ?';
    $params[] = $statusFilter;
}

$stmt = db()->prepare(
    "SELECT d.*, u.username, u.full_name, u.email
     FROM deposits d
     INNER JOIN users u ON u.id = d.user_id
     {$where}
     ORDER BY d.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$deposits = $stmt->fetchAll();

$pageTitle = 'Deposit Management';
$activeNav = 'deposits';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
            <a href="?status=<?= e($key) ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-brand' : 'btn-outline-brand' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="GET" action="export-deposits.php" class="d-flex align-items-center gap-2 flex-wrap">
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <input type="date" name="from" class="form-control form-control-sm" style="width:150px;" title="From date (optional)">
        <input type="date" name="to" class="form-control form-control-sm" style="width:150px;" title="To date (optional)">
        <button type="submit" class="btn btn-sm btn-outline-brand"><i class="bi bi-download"></i> Export CSV</button>
    </form>
</div>

<div class="card-surface p-4">
    <?php if (!$deposits): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-arrow-down-circle" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No deposits in this category.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>User</th><th>Method</th><th>Reference</th><th>Amount</th><th>Proof</th><th>Date</th><th>Status</th><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($deposits as $d): ?>
                        <tr data-href="receipt.php?type=deposit&id=<?= (int) $d['id'] ?>">
                            <td><?= e($d['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($d['username']) ?></div></td>
                            <td class="text-uppercase small"><?= e($d['method']) ?></td>
                            <td class="small"><?= e($d['reference']) ?></td>
                            <td><?= e(money($d['amount'])) ?></td>
                            <td>
                                <?php if ($d['proof']): ?>
                                    <a href="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($d['proof']) ?>" target="_blank" class="small fw-semibold" style="color:var(--brand-emerald);"><i class="bi bi-file-earmark-image"></i> View</a>
                                <?php else: ?>
                                    <span class="small" style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small" style="color:var(--text-muted);"><?= e(time_ago($d['created_at'])) ?></td>
                            <td><span class="pill pill-<?= e($d['status']) ?>"><?= e(ucfirst($d['status'])) ?></span></td>
                            <td class="text-end" style="min-width:170px;">
                                <?php if ($d['status'] === STATUS_PENDING): ?>
                                    <form method="POST" action="?status=<?= e($statusFilter) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                        <button type="submit" class="btn btn-brand btn-sm">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectDep<?= (int) $d['id'] ?>">Reject</button>

                                    <div class="modal fade" id="rejectDep<?= (int) $d['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="background:var(--surface);color:var(--text);">
                                                <form method="POST" action="?status=<?= e($statusFilter) ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                                    <div class="modal-header"><h6 class="modal-title">Reject Deposit</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <label class="form-label small">Reason (optional, shown to the user)</label>
                                                        <textarea class="form-control" name="admin_note" rows="3"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-brand btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject Deposit</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="small" style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><a href="receipt.php?type=deposit&id=<?= (int) $d['id'] ?>" class="btn btn-outline-brand btn-sm">Receipt</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
