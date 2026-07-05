<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireGuest();

$errors = [];
$unverifiedUserId = null;
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $loginValue = clean($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($loginValue === '' || $password === '') {
        $errors[] = 'Please enter your email/username and password.';
    } else {
        $result = Auth::attemptLogin($loginValue, $password, $remember);

        if ($result['success']) {
            redirect(rtrim(APP_URL, '/') . '/user/dashboard.php');
        }

        $errors[] = $result['message'];

        if (!empty($result['unverified'])) {
            $unverifiedUserId = $result['user_id'];
        }
    }
}
?>
<?php
$pageTitle = 'Login';
$visualTitle = 'Welcome back to your earning dashboard.';
$visualText = 'Track your mining progress, complete tasks, spin the wheel and withdraw your earnings anytime.';
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2>Log in</h2>
                    <p class="sub">Enter your details to access your account.</p>
                </div>
                <button type="button" class="theme-toggle-btn" style="color:var(--text);border-color:var(--border);" data-theme-toggle data-theme-icon><i class="bi bi-moon-stars"></i></button>
            </div>

            <?php require __DIR__ . '/../includes/partials/flash-messages.php'; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($unverifiedUserId): ?>
                        <a class="d-inline-block mt-1 fw-semibold" href="resend-verification.php?uid=<?= (int) $unverifiedUserId ?>">Resend verification email</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate data-loading-submit>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="login">Email or Username</label>
                    <input type="text" class="form-control" id="login" name="login" value="<?= e($loginValue) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="input-group-text" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="small fw-semibold" style="color:var(--brand-emerald);">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-brand w-100">Log In</button>
            </form>

            <p class="text-center small mt-4 mb-0" style="color:var(--text-muted);">
                Don't have an account? <a href="register.php" style="color:var(--brand-emerald);font-weight:600;">Create one</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
