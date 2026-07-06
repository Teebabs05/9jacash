<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Create Coupon</h6>
            <form method="post" action="<?= base_url('admin/coupons') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small">Code</label><input type="text" name="code" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Max Uses</label><input type="number" name="max_uses" class="form-control" value="1" required></div>
                <div class="mb-3"><label class="form-label small">Expires At <span class="text-muted-soft">(optional)</span></label><input type="date" name="expires_at" class="form-control"></div>
                <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Create Coupon</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">All Coupons</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Code</th><th>Amount</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($coupons as $c): ?>
                        <tr>
                            <td class="fw-bold"><?= e($c['code']) ?></td>
                            <td><?= money($c['amount']) ?></td>
                            <td><?= (int) $c['used_count'] ?>/<?= (int) $c['max_uses'] ?></td>
                            <td class="small text-muted-soft"><?= $c['expires_at'] ? date('M j, Y', strtotime($c['expires_at'])) : 'Never' ?></td>
                            <td><span class="badge badge-status-<?= $c['status'] === 'active' ? 'active' : 'suspended' ?> text-capitalize"><?= e($c['status']) ?></span></td>
                            <td><form method="post" action="<?= base_url('admin/coupons/' . $c['id'] . '/delete') ?>" onsubmit="return confirm('Delete this coupon?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($coupons)): ?><tr><td colspan="6" class="text-center text-muted-soft py-4">No coupons yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
