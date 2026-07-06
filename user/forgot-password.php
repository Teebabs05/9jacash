<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireGuest();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(clean($_POST['email'] ?? ''));

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $identifier = 'forgot:' . $email . ':' . client_ip();

        if (is_rate_limited($identifier)) {
            $errors[] = 'Too many requests. Please try again in a few minutes.';
        } else {
            $result = Auth::sendPasswordReset($email);
            register_failed_attempt($identifier); // throttle repeated requests
            $success = $result['message'];
        }
    }
}

$pageTitle = 'Forgot Password';
$visualTitle = 'Forgot your password? No worries.';
$visualText = "We'll email you a secure link so you can choose a new password and get right back to earning.";
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <h2>Forgot Password</h2>
            <p class="sub">Enter your account email to receive a reset link.</p>

            <?php if ($success): ?>
                <div class="alert alert-success py-2 px-3 small mb-3"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="btn btn-brand w-100">Send Reset Link</button>
            </form>

            <p class="text-center small mt-4 mb-0" style="color:var(--text-muted);">
                <a href="login.php" style="color:var(--brand-emerald);font-weight:600;">Back to Login</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
