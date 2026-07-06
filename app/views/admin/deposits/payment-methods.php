<div class="row g-3">
    <div class="col-lg-5">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Add Payment Method</h6>
            <form method="post" action="<?= base_url('admin/payment-methods') ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label small fw-semibold">Bank Name</label><input type="text" name="bank_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Account Name</label><input type="text" name="account_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Account Number</label><input type="text" name="account_number" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Instructions</label><textarea name="instructions" class="form-control" rows="3"></textarea></div>
                <button class="btn btn-brand rounded-pill px-4" type="submit">Add</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Configured Methods</h6>
            <?php foreach ($methods as $m): ?>
                <div class="d-flex justify-content-between align-items-start border-bottom py-3" style="border-color:var(--border-color) !important;">
                    <div>
                        <div class="fw-semibold"><?= e($m['bank_name']) ?></div>
                        <div class="small text-muted-soft"><?= e($m['account_name']) ?> &middot; <?= e($m['account_number']) ?></div>
                    </div>
                    <form method="post" action="<?= base_url('admin/payment-methods/' . $m['id'] . '/delete') ?>" onsubmit="return confirm('Remove this payment method?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($methods)): ?><p class="text-muted-soft small text-center py-3">No payment methods yet.</p><?php endif; ?>
        </div>
    </div>
</div>
