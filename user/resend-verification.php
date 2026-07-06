<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$errors = [];
$success = null;
$prefillEmail = '';

if (!empty($_GET['uid'])) {
    $stmt = db()->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['uid']]);
    $row = $stmt->fetch();
    $prefillEmail = $row['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(clean($_POST['email'] ?? ''));

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $identifier = 'resend:' . $email . ':' . client_ip();

        if (is_rate_limited($identifier)) {
            $errors[] = 'Please wait a few minutes before requesting another verification email.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && empty($user['email_verified_at'])) {
                Auth::sendVerificationEmail((int) $user['id'], $user['email'], $user['full_name']);
                register_failed_attempt($identifier); // reuse as a cooldown throttle
            }

            $success = 'If that email is registered and unverified, a new verification link has been sent.';
        }
    }
}

$pageTitle = 'Resend Verification';
$visualTitle = 'Check your inbox.';
$visualText = 'We will send you a fresh verification link so you can activate your SURECASH MINING account.';
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <h2>Resend Verification Email</h2>
            <p class="sub">Enter the email address you registered with.</p>

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
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($prefillEmail) ?>" required>
                </div>
                <button type="submit" class="btn btn-brand w-100">Send Verification Link</button>
            </form>

            <p class="text-center small mt-4 mb-0" style="color:var(--text-muted);">
                <a href="login.php" style="color:var(--brand-emerald);font-weight:600;">Back to Login</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
