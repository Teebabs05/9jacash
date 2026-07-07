<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireLogin();

$admin = current_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        if (!password_verify($current, $admin['password'])) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (!is_strong_password($new)) {
            $errors[] = 'New password must be at least 8 characters and include letters and numbers.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $hashed = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            db()->prepare('UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?')->execute([$hashed, $admin['id']]);
            Mailer::sendPasswordChangedEmail($admin['email'], $admin['full_name']);
            log_activity(null, (int) $admin['id'], 'admin_password_changed', 'Administrator changed their password');
            flash('admin_profile', 'Password updated successfully.', 'success');
            redirect(rtrim(APP_URL, '/') . '/admin/profile.php');
        }
    }
}

$biometricCount = webauthn_credential_count('admin', (int) $admin['id']);

$pageTitle = 'My Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/partials/admin-head.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Profile Information</h5>
            <div class="mb-3">
                <label class="form-label small">Username</label>
                <input type="text" class="form-control" value="<?= e($admin['username']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label small">Email Address</label>
                <input type="email" class="form-control" value="<?= e($admin['email']) ?>" disabled>
            </div>
            <div class="mb-0">
                <label class="form-label small">Role</label>
                <input type="text" class="form-control" value="<?= e(ucwords(str_replace('_', ' ', $admin['role']))) ?>" disabled>
            </div>
        </div>

        <div class="card-surface p-4 mt-4">
            <h5 class="fw-bold mb-2">Biometric Login</h5>
            <p class="small mb-3" style="color:var(--text-muted);">Use your device's fingerprint/face unlock to log in to the admin panel instead of typing your password.</p>
            <div id="biometricNotSupported" class="alert alert-warning py-2 px-3 small mb-0 d-none">Your current browser/device doesn't support biometric login.</div>
            <div id="biometricControls">
                <?php if ($biometricCount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="pill pill-active">Enabled</span>
                        <form method="POST" action="<?= e(rtrim(APP_URL, '/')) ?>/webauthn/disable.php" onsubmit="return confirm('Turn off biometric login on this account?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Turn Off</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="pill pill-rejected">Disabled</span>
                        <button type="button" id="enableBiometricBtn" class="btn btn-brand btn-sm">Enable Biometric Login</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-surface p-4">
            <h5 class="fw-bold mb-3">Change Password</h5>
            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
                    <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required minlength="8">
                </div>

                <button type="submit" class="btn btn-brand w-100">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="<?= e($assetBase) ?>/js/webauthn.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!SureCashWebAuthn.supported()) {
        const notSupported = document.getElementById('biometricNotSupported');
        const controls = document.getElementById('biometricControls');
        if (notSupported) notSupported.classList.remove('d-none');
        if (controls) controls.classList.add('d-none');
        return;
    }

    const enableBtn = document.getElementById('enableBiometricBtn');
    if (!enableBtn) return;

    enableBtn.addEventListener('click', async function () {
        SureCashMining.setLoading(enableBtn, true);
        try {
            const result = await SureCashWebAuthn.register();
            if (result.success) {
                SureCashMining.toast(result.message || 'Biometric login enabled!');
                setTimeout(() => window.location.reload(), 900);
            } else {
                SureCashMining.toast(result.message || 'Could not enable biometric login.', 'error');
                SureCashMining.setLoading(enableBtn, false);
            }
        } catch (e) {
            SureCashMining.toast('Biometric setup was cancelled or failed.', 'error');
            SureCashMining.setLoading(enableBtn, false);
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/partials/admin-scripts.php'; ?>
