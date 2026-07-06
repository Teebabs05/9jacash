<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">Withdrawals</h6>
        <div class="d-flex gap-2">
            <form class="d-flex gap-2" method="get">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <?php foreach (['pending','approved','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= base_url('admin/withdrawals/export' . ($status ? '?status=' . $status : '')) ?>" class="btn btn-outline-brand btn-sm rounded-pill"><i class="fa-solid fa-download me-1"></i>Export CSV</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Amount</th><th>Net</th><th>Bank</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $w): ?>
                <tr>
                    <td><?= e($w['username']) ?><div class="small text-muted-soft"><?= e($w['full_name']) ?></div></td>
                    <td><?= money($w['amount']) ?></td>
                    <td class="fw-semibold"><?= money($w['net_amount']) ?></td>
                    <td class="small"><?= e($w['bank_name']) ?><br><?= e($w['account_number']) ?> (<?= e($w['account_name']) ?>)</td>
                    <td><span class="badge badge-status-<?= e($w['status']) ?> text-capitalize"><?= e($w['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= date('M j, Y g:ia', strtotime($w['created_at'])) ?></td>
                    <td>
                        <?php if ($w['status'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <form method="post" action="<?= base_url('admin/withdrawals/' . $w['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success" onclick="return confirm('Confirm you have paid this out?')">Approve</button></form>
                            <form method="post" action="<?= base_url('admin/withdrawals/' . $w['id'] . '/reject') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject and refund this withdrawal?')">Reject</button></form>
                        </div>
                        <?php else: ?><span class="small text-muted-soft">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted-soft py-4">No withdrawals found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/withdrawals') . ($status ? '?status=' . $status : ''); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
