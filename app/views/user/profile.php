<div class="row g-3">
    <div class="col-lg-4">
        <div class="surface-card p-4 text-center">
            <img src="<?= e(user_avatar_url($user)) ?>" class="rounded-circle mb-3" width="110" height="110" style="object-fit:cover;">
            <h6 class="fw-bold mb-0"><?= e($user['full_name']) ?></h6>
            <p class="text-muted-soft small">@<?= e($user['username']) ?></p>
            <form action="<?= base_url('profile/avatar') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="file" name="avatar" class="form-control form-control-sm mb-2" accept="image/*" required>
                <button class="btn btn-outline-brand btn-sm rounded-pill w-100" type="submit">Update Photo</button>
            </form>
            <hr>
            <div class="text-start small">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted-soft">Referral Code</span><strong><?= e($user['referral_code']) ?></strong></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted-soft">KYC Status</span><span class="badge badge-status-<?= e($user['kyc_status']) ?>"><?= e(ucfirst(str_replace('_',' ',$user['kyc_status']))) ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted-soft">Member Since</span><strong><?= date('M Y', strtotime($user['created_at'])) ?></strong></div>
            </div>
            <div class="d-grid gap-2 mt-3">
                <a href="<?= base_url('profile/password') ?>" class="btn btn-light border btn-sm">Change Password</a>
                <a href="<?= base_url('profile/2fa') ?>" class="btn btn-light border btn-sm">Two-Factor Authentication</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-3">Edit Profile</h6>
            <form method="post" action="<?= base_url('profile') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email <span class="text-muted-soft fw-normal">(read-only)</span></label>
                        <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Username <span class="text-muted-soft fw-normal">(read-only)</span></label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Country</label>
                        <input type="text" name="country" class="form-control" value="<?= e($user['country']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">State</label>
                        <input type="text" name="state" class="form-control" value="<?= e($user['state']) ?>" required>
                    </div>
                </div>
                <button class="btn btn-brand rounded-pill px-4 mt-4" type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</div>
