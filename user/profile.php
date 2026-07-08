<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireLogin();

$user = current_user();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = clean($_POST['full_name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');

        if (strlen($fullName) < 3) {
            $errors[] = 'Please enter your full name.';
        }

        if ($phone === '' || !preg_match('/^[0-9+ ]{10,15}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }

        $avatarPath = $user['avatar'];

        if (!empty($_FILES['avatar']['name'])) {
            $error = validate_upload($_FILES['avatar'], ['image/jpeg', 'image/png', 'image/webp'], 2 * 1024 * 1024);
            if ($error) {
                $errors[] = $error;
            } else {
                $avatarPath = store_upload($_FILES['avatar'], 'avatars');
            }
        }

        if (!$errors) {
            $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ?, avatar = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$fullName, $phone, $avatarPath, $user['id']]);
            log_activity((int) $user['id'], null, 'profile_updated', 'User updated their profile');
            flash('profile', 'Profile updated successfully.', 'success');
            redirect(rtrim(APP_URL, '/') . '/user/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (!is_strong_password($new)) {
            $errors[] = 'New password must be at least 8 characters and include letters and numbers.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $hashed = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([$hashed, $user['id']]);
            db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$user['id']]);
            Mailer::sendPasswordChangedEmail($user['email'], $user['full_name']);
            log_activity((int) $user['id'], null, 'password_changed', 'User changed their password');
            flash('profile', 'Password updated successfully.', 'success');
            redirect(rtrim(APP_URL, '/') . '/user/profile.php');
        }
    } elseif ($action === 'toggle_login_notifications') {
        $enabled = ((int) ($user['login_notifications_enabled'] ?? 1)) === 1 ? 0 : 1;
        db()->prepare('UPDATE users SET login_notifications_enabled = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$enabled, $user['id']]);
        flash('profile', $enabled ? 'Login notification emails turned on.' : 'Login notification emails turned off.', 'success');
        redirect(rtrim(APP_URL, '/') . '/user/profile.php');
    }

    $user = current_user();
}

$biometricCount = webauthn_credential_count('user', (int) $user['id']);

$pageTitle = 'My Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/partials/app-head.php';
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
            <form method="POST" action="" enctype="multipart/form-data" data-loading-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="text-center mb-4">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= e(rtrim(APP_URL, '/')) ?>/uploads/<?= e($user['avatar']) ?>" class="rounded-circle" style="width:84px;height:84px;object-fit:cover;" alt="Avatar">
                    <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:84px;height:84px;background:var(--brand-emerald);color:#fff;font-size:1.8rem;">
                            <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                    <div class="mt-2">
                        <input type="file" class="form-control form-control-sm" name="avatar" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= e($user['phone']) ?>" required>
                </div>

                <button type="submit" class="btn btn-brand w-100">Save Changes</button>
            </form>
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

        <div class="card-surface p-4 mt-4">
            <h5 class="fw-bold mb-3">Account Status</h5>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">KYC Status</span>
                <span class="pill pill-<?= $user['kyc_status'] === 'approved' ? 'approved' : ($user['kyc_status'] === 'rejected' ? 'rejected' : 'pending') ?>"><?= e(ucfirst($user['kyc_status'])) ?></span>
            </div>
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);">Member Since</span>
                <span><?= e(date('M d, Y', strtotime($user['created_at']))) ?></span>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span style="color:var(--text-muted);">Referral Code</span>
                <span class="fw-semibold"><?= e($user['referral_code']) ?></span>
            </div>
        </div>

        <div class="card-surface p-4 mt-4">
            <h5 class="fw-bold mb-2">Login Notification Emails</h5>
            <p class="small mb-3" style="color:var(--text-muted);">Get an email every time your account signs in, with the IP address and device used.</p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="pill pill-<?= ((int) ($user['login_notifications_enabled'] ?? 1)) === 1 ? 'active' : 'rejected' ?>"><?= ((int) ($user['login_notifications_enabled'] ?? 1)) === 1 ? 'On' : 'Off' ?></span>
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_login_notifications">
                    <button type="submit" class="btn btn-outline-brand btn-sm"><?= ((int) ($user['login_notifications_enabled'] ?? 1)) === 1 ? 'Turn Off' : 'Turn On' ?></button>
                </form>
            </div>
        </div>

        <div class="card-surface p-4 mt-4">
            <h5 class="fw-bold mb-2">Biometric Login</h5>
            <p class="small mb-3" style="color:var(--text-muted);">Use your device's fingerprint/face unlock to log in instead of typing your password.</p>
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

<?php require __DIR__ . '/../includes/partials/app-scripts.php'; ?>
