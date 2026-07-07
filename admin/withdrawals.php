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
        $result = withdrawals_approve($id, (int) $admin['id']);
        flash('withdrawals', $result['message'], $result['success'] ? 'success' : 'error');
    } elseif ($action === 'reject') {
        $note = clean($_POST['admin_note'] ?? '');
        $result = withdrawals_reject($id, (int) $admin['id'], $note);
        flash('withdrawals', $result['message'], $result['success'] ? 'success' : 'error');
    }

    redirect(rtrim(APP_URL, '/') . '/admin/withdrawals.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}

$where = '';
$params = [];
if ($statusFilter !== 'all') {
    $where = 'WHERE w.status = ?';
    $params[] = $statusFilter;
}

$stmt = db()->prepare(
    "SELECT w.*, u.username, u.full_name, u.email
     FROM withdrawals w
     INNER JOIN users u ON u.id = w.user_id
     {$where}
     ORDER BY w.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Withdrawal Management';
$activeNav = 'withdrawals';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
            <a href="?status=<?= e($key) ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-brand' : 'btn-outline-brand' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="GET" action="export-withdrawals.php" class="d-flex align-items-center gap-2 flex-wrap">
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <input type="date" name="from" class="form-control form-control-sm" style="width:150px;" title="From date (optional)">
        <input type="date" name="to" class="form-control form-control-sm" style="width:150px;" title="To date (optional)">
        <button type="submit" class="btn btn-sm btn-outline-brand"><i class="bi bi-download"></i> Export CSV</button>
    </form>
</div>

<div class="card-surface p-4">
    <?php if (!$withdrawals): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <i class="bi bi-arrow-up-circle" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No withdrawals in this category.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ledger-table mb-0">
                <thead><tr><th>User</th><th>Method</th><th>Account Details</th><th>Amount</th><th>Charge</th><th>Net Payout</th><th>Date</th><th>Status</th><th></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr data-href="receipt.php?type=withdrawal&id=<?= (int) $w['id'] ?>">
                            <td><?= e($w['full_name']) ?><div class="small" style="color:var(--text-muted);">@<?= e($w['username']) ?></div></td>
                            <td class="text-uppercase small"><?= e($w['method']) ?></td>
                            <td class="small" style="max-width:220px;"><?= e($w['account_details']) ?></td>
                            <td><?= e(money($w['amount'])) ?></td>
                            <td class="text-danger">-<?= e(money($w['charge'])) ?></td>
                            <td class="fw-semibold"><?= e(money($w['net_amount'])) ?></td>
                            <td class="small" style="color:var(--text-muted);"><?= e(time_ago($w['created_at'])) ?></td>
                            <td><span class="pill pill-<?= e($w['status']) ?>"><?= e(ucfirst($w['status'])) ?></span></td>
                            <td class="text-end" style="min-width:170px;">
                                <?php if ($w['status'] === STATUS_PENDING): ?>
                                    <form method="POST" action="?status=<?= e($statusFilter) ?>" class="d-inline" onsubmit="return confirm('Confirm you have sent this payout externally?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                                        <button type="submit" class="btn btn-brand btn-sm">Mark Paid</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectWd<?= (int) $w['id'] ?>">Reject</button>
                                <?php else: ?>
                                    <span class="small" style="color:var(--text-muted);"><?= e($w['processed_at'] ? time_ago($w['processed_at']) : '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><a href="receipt.php?type=withdrawal&id=<?= (int) $w['id'] ?>" class="btn btn-outline-brand btn-sm">Receipt</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($withdrawals as $w): if ($w['status'] !== STATUS_PENDING) continue; ?>
    <div class="modal fade" id="rejectWd<?= (int) $w['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background:var(--surface);color:var(--text);">
                <form method="POST" action="?status=<?= e($statusFilter) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                    <div class="modal-header"><h6 class="modal-title">Reject Withdrawal</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="small">This will refund <?= e(money($w['amount'])) ?> to the user's main wallet.</p>
                        <label class="form-label small">Reason (optional, shown to the user)</label>
                        <textarea class="form-control" name="admin_note" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-brand btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject &amp; Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
