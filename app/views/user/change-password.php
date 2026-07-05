<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="surface-card p-4">
            <h6 class="fw-bold mb-1">Change Password</h6>
            <?php if (!empty($forced)): ?>
                <div class="alert alert-warning small">You must set a new password before continuing to use your account.</div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('profile/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                <button class="btn btn-brand rounded-pill px-4" type="submit">Update Password</button>
            </form>
        </div>
    </div>
</div>
