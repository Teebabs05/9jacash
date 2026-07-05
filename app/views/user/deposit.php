<div class="row g-3">
    <div class="col-lg-7">
        <div class="surface-card p-4 mb-3">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#payvessel-tab">Instant Deposit (PayVessel)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#manual-tab">Manual Bank Transfer</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="payvessel-tab">
                    <p class="small text-muted-soft">Pay instantly via card, bank transfer or USSD. Your wallet is credited automatically.</p>
                    <form method="post" action="<?= base_url('deposit/payvessel/init') ?>">
                        <?= csrf_field() ?>
                        <label class="form-label small fw-semibold">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control mb-3" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" required>
                        <p class="small text-muted-soft">Min: <?= money($minDeposit) ?> &middot; Max: <?= money($maxDeposit) ?></p>
                        <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Pay Now</button>
                    </form>
                </div>
                <div class="tab-pane fade" id="manual-tab">
                    <?php if (empty($paymentMethods)): ?>
                        <p class="text-muted-soft small">No manual payment method is configured at the moment.</p>
                    <?php else: ?>
                    <?php foreach ($paymentMethods as $pm): ?>
                        <div class="p-3 rounded-3 mb-3" style="background:var(--bg-surface-alt);">
                            <div class="d-flex justify-content-between"><span class="text-muted-soft small">Bank</span><strong><?= e($pm['bank_name']) ?></strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted-soft small">Account Name</span><strong><?= e($pm['account_name']) ?></strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted-soft small">Account Number</span><strong><?= e($pm['account_number']) ?></strong></div>
                            <?php if ($pm['instructions']): ?><p class="small text-muted-soft mt-2 mb-0"><?= e($pm['instructions']) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <form method="post" action="<?= base_url('deposit/manual') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_method_id" value="<?= (int) ($paymentMethods[0]['id'] ?? 0) ?>">
                        <label class="form-label small fw-semibold">Amount Paid</label>
                        <input type="number" step="0.01" name="amount" class="form-control mb-3" min="<?= $minDeposit ?>" max="<?= $maxDeposit ?>" required>
                        <label class="form-label small fw-semibold">Upload Payment Receipt</label>
                        <input type="file" name="receipt" class="form-control mb-3" accept="image/*" required>
                        <button class="btn btn-brand rounded-pill px-4 w-100" type="submit">Submit Deposit</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="surface-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent Deposits</h6>
                <a href="<?= base_url('deposit/history') ?>" class="small">View all</a>
            </div>
            <?php foreach ($recent as $d): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2" style="border-color:var(--border-color) !important;">
                    <div>
                        <div class="fw-semibold"><?= money($d['amount']) ?></div>
                        <div class="small text-muted-soft text-capitalize"><?= e($d['method']) ?> &middot; <?= time_ago($d['created_at']) ?></div>
                    </div>
                    <span class="badge badge-status-<?= e($d['status']) ?> text-capitalize"><?= e($d['status']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?><p class="text-muted-soft small text-center py-3">No deposits yet.</p><?php endif; ?>
        </div>
    </div>
</div>
