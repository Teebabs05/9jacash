<h4 class="fw-bold mb-1">Reset password</h4>

<?php if (empty($valid)): ?>
    <p class="text-danger">This password reset link is invalid or has expired.</p>
    <a href="<?= base_url('forgot-password') ?>" class="btn btn-brand w-100 rounded-pill py-2">Request a new link</a>
<?php else: ?>
    <p class="text-muted-soft mb-4">Choose a new password for your account.</p>
    <form method="post" action="<?= base_url('reset-password') ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3">
            <label class="form-label small fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" required minlength="8" autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button class="btn btn-brand w-100 rounded-pill py-2" type="submit">Reset Password</button>
    </form>
<?php endif; ?>
