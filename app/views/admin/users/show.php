<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4 text-center mb-3">
            <img src="<?= e(user_avatar_url($user)) ?>" class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover;">
            <h6 class="fw-bold mb-0"><?= e($user['full_name']) ?></h6>
            <p class="text-muted-soft small">@<?= e($user['username']) ?> &middot; <?= e($user['email']) ?></p>
            <span class="badge badge-status-<?= e($user['status']) ?> text-capitalize mb-3"><?= e($user['status']) ?></span>

            <div class="d-grid gap-2">
                <?php if ($user['status'] === 'suspended'): ?>
                    <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/activate') ?>"><?= csrf_field() ?><button class="btn btn-success btn-sm w-100">Activate User</button></form>
                <?php else: ?>
                    <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/suspend') ?>" onsubmit="return confirm('Suspend this user?')"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm w-100">Suspend User</button></form>
                <?php endif; ?>
                <a href="<?= base_url('admin/users/' . $user['id'] . '/login-as') ?>" class="btn btn-outline-secondary btn-sm">Login as User</a>
                <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/reset-password') ?>" onsubmit="return confirm('Reset this user\'s password?')"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm w-100">Reset Password</button></form>
                <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/delete') ?>" onsubmit="return confirm('Permanently delete this user? This cannot be undone.')"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm w-100">Delete User</button></form>
            </div>
        </div>

        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Wallet Balances</h6>
            <?php foreach (['main','bonus','referral','mining','task'] as $w): ?>
                <div class="d-flex justify-content-between small mb-2"><span class="text-capitalize text-muted-soft"><?= $w ?></span><strong><?= money($wallet[$w . '_balance']) ?></strong></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="surface-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Edit Details</h6>
            <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/update') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Country</label><input type="text" name="country" class="form-control" value="<?= e($user['country']) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">State</label><input type="text" name="state" class="form-control" value="<?= e($user['state']) ?>"></div>
                    <div class="col-md-6">
                        <label class="form-label small">KYC Status</label>
                        <select name="kyc_status" class="form-select">
                            <?php foreach (['not_required','pending','approved','rejected'] as $k): ?>
                                <option value="<?= $k ?>" <?= $user['kyc_status'] === $k ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$k)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-brand rounded-pill px-4 mt-3">Save Changes</button>
            </form>
        </div>

        <div class="row g-3">
            <div class="col-md-4"><div class="stat-tile"><div class="value"><?= count($downline) ?></div><div class="label">Referrals</div></div></div>
            <div class="col-md-4"><div class="stat-tile"><div class="value"><?= money($referralEarnings) ?></div><div class="label">Referral Earnings</div></div></div>
            <div class="col-md-4"><div class="stat-tile"><div class="value"><?= count($mining) ?></div><div class="label">Mining Plans</div></div></div>
        </div>

        <div class="surface-card p-4 mt-3">
            <h6 class="fw-bold mb-3">Recent Deposits</h6>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($deposits as $d): ?>
                    <tr><td><?= money($d['amount']) ?></td><td class="text-capitalize"><?= e($d['method']) ?></td><td><span class="badge badge-status-<?= e($d['status']) ?> text-capitalize"><?= e($d['status']) ?></span></td><td class="small text-muted-soft"><?= time_ago($d['created_at']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($deposits)): ?><tr><td colspan="4" class="text-center text-muted-soft py-3">No deposits.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="surface-card p-4 mt-3">
            <h6 class="fw-bold mb-3">Recent Withdrawals</h6>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Amount</th><th>Bank</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($withdrawals as $w): ?>
                    <tr><td><?= money($w['net_amount']) ?></td><td class="small"><?= e($w['bank_name']) ?></td><td><span class="badge badge-status-<?= e($w['status']) ?> text-capitalize"><?= e($w['status']) ?></span></td><td class="small text-muted-soft"><?= time_ago($w['created_at']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($withdrawals)): ?><tr><td colspan="4" class="text-center text-muted-soft py-3">No withdrawals.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
