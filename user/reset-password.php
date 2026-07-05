<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireGuest();

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$userId = (int) ($_GET['uid'] ?? $_POST['uid'] ?? 0);
$errors = [];
$done = false;

if ($token === '' || $userId <= 0) {
    $errors[] = 'Invalid or missing password reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    require_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!is_strong_password($password)) {
        $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    } elseif ($password !== $passwordConfirmation) {
        $errors[] = 'Passwords do not match.';
    } else {
        $result = Auth::resetPassword($userId, $token, $password);

        if ($result['success']) {
            $done = true;
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Reset Password';
$visualTitle = 'Choose a new, strong password.';
$visualText = 'Keep your 9JACASH account secure with a strong, unique password.';
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <?php if ($done): ?>
                <div class="text-center">
                    <div class="mb-3" style="font-size:3rem;color:var(--success);"><i class="bi bi-patch-check-fill"></i></div>
                    <h2>Password Reset</h2>
                    <p class="sub">Your password has been updated successfully.</p>
                    <a href="login.php" class="btn btn-brand w-100">Continue to Login</a>
                </div>
            <?php else: ?>
                <h2>Reset Password</h2>
                <p class="sub">Enter a new password for your account.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger py-2 px-3 small mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" data-loading-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="uid" value="<?= (int) $userId ?>">

                    <div class="mb-3">
                        <label class="form-label" for="password">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <button class="input-group-text" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="pw-strength"><span></span></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-brand w-100">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
