<div class="surface-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">KYC Requests</h6>
        <form class="d-flex gap-2" method="get">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach (['pending','approved','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Document Type</th><th>Files</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $k): ?>
                <tr>
                    <td><?= e($k['username']) ?><div class="small text-muted-soft"><?= e($k['full_name']) ?></div></td>
                    <td class="text-capitalize"><?= e(str_replace('_',' ',$k['document_type'])) ?></td>
                    <td>
                        <a href="<?= base_url('files/kyc/' . basename($k['document_path'])) ?>" target="_blank">Document</a>
                        <?php if ($k['selfie_path']): ?> &middot; <a href="<?= base_url('files/kyc/' . basename($k['selfie_path'])) ?>" target="_blank">Selfie</a><?php endif; ?>
                    </td>
                    <td><span class="badge badge-status-<?= e($k['status']) ?> text-capitalize"><?= e($k['status']) ?></span></td>
                    <td class="small text-muted-soft"><?= time_ago($k['created_at']) ?></td>
                    <td>
                        <?php if ($k['status'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <form method="post" action="<?= base_url('admin/kyc/' . $k['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
                            <form method="post" action="<?= base_url('admin/kyc/' . $k['id'] . '/reject') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                        </div>
                        <?php else: ?><span class="small text-muted-soft">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No KYC submissions found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $baseUrl = base_url('admin/kyc') . ($status ? '?status=' . $status : ''); require APP_PATH . '/views/partials/pagination.php'; ?>
</div>
