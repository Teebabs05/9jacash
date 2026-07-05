<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Create Mining Plan</h6>
            <form method="post" action="<?= base_url('admin/mining/plans') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small fw-semibold">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Price</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Daily Profit</label><input type="number" step="0.01" name="daily_profit" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Duration (days)</label><input type="number" name="duration_days" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Total ROI %</label><input type="number" step="0.01" name="total_roi_percent" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Max Users <span class="text-muted-soft fw-normal">(blank = unlimited)</span></label><input type="number" name="max_users" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Plan Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Create Plan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">All Plans</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Price</th><th>Daily</th><th>Duration</th><th>Users</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><?= money($p['price']) ?></td>
                            <td><?= money($p['daily_profit']) ?></td>
                            <td><?= (int) $p['duration_days'] ?>d</td>
                            <td><?= (int) $p['current_users'] ?><?= $p['max_users'] ? '/' . (int) $p['max_users'] : '' ?></td>
                            <td><span class="badge badge-status-<?= $p['status'] === 'active' ? 'active' : 'suspended' ?> text-capitalize"><?= e($p['status']) ?></span></td>
                            <td class="d-flex gap-1">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editPlan<?= $p['id'] ?>"><i class="fa-solid fa-pen"></i></button>
                                <form method="post" action="<?= base_url('admin/mining/plans/' . $p['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-power-off"></i></button></form>
                                <form method="post" action="<?= base_url('admin/mining/plans/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Delete this plan?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editPlan<?= $p['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="<?= base_url('admin/mining/plans/' . $p['id'] . '/update') ?>" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <div class="modal-header"><h6 class="modal-title">Edit <?= e($p['name']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <div class="mb-2"><label class="form-label small">Name</label><input type="text" name="name" class="form-control" value="<?= e($p['name']) ?>" required></div>
                                            <div class="mb-2"><label class="form-label small">Price</label><input type="number" step="0.01" name="price" class="form-control" value="<?= e($p['price']) ?>" required></div>
                                            <div class="mb-2"><label class="form-label small">Daily Profit</label><input type="number" step="0.01" name="daily_profit" class="form-control" value="<?= e($p['daily_profit']) ?>" required></div>
                                            <div class="mb-2"><label class="form-label small">Duration (days)</label><input type="number" name="duration_days" class="form-control" value="<?= e($p['duration_days']) ?>" required></div>
                                            <div class="mb-2"><label class="form-label small">Total ROI %</label><input type="number" step="0.01" name="total_roi_percent" class="form-control" value="<?= e($p['total_roi_percent']) ?>" required></div>
                                            <div class="mb-2"><label class="form-label small">Max Users</label><input type="number" name="max_users" class="form-control" value="<?= e((string) $p['max_users']) ?>"></div>
                                        </div>
                                        <div class="modal-footer"><button class="btn btn-brand rounded-pill px-4">Save</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
