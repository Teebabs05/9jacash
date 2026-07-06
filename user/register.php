<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

Auth::requireGuest();

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'referral_code' => $_GET['ref'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $rateLimitKey = 'register:' . client_ip();

    if (is_rate_limited($rateLimitKey)) {
        $errors[] = 'Too many registration attempts from this location. Please try again in 15 minutes.';
    }

    $old['full_name'] = clean($_POST['full_name'] ?? '');
    $old['username'] = strtolower(clean($_POST['username'] ?? ''));
    $old['email'] = strtolower(clean($_POST['email'] ?? ''));
    $old['phone'] = clean($_POST['phone'] ?? '');
    $old['referral_code'] = clean($_POST['referral_code'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
    $terms = isset($_POST['terms']);

    if (strlen($old['full_name']) < 3) {
        $errors[] = 'Please enter your full name.';
    }

    if (!preg_match('/^[a-z0-9_]{4,20}$/', $old['username'])) {
        $errors[] = 'Username must be 4-20 characters (letters, numbers, underscore only).';
    }

    if (!is_valid_email($old['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($old['phone'] === '' || !preg_match('/^[0-9+ ]{10,15}$/', $old['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if (!is_strong_password($password)) {
        $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    }

    if ($password !== $passwordConfirmation) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
    }

    if (!$errors) {
        register_failed_attempt($rateLimitKey);

        $result = Auth::register(
            $old['full_name'],
            $old['username'],
            $old['email'],
            $old['phone'],
            $password,
            $old['referral_code'] ?: null
        );

        if ($result['success']) {
            flash('register', 'Account created! Please check your email to verify your account before logging in.', 'success');
            redirect(rtrim(APP_URL, '/') . '/user/login.php');
        }

        $errors[] = $result['message'];
    }
}

$pageTitle = 'Create Account';
$visualTitle = 'Start earning from day one.';
$visualText = 'Register in seconds and unlock mining, tasks, daily check-ins, spin wheel rewards and a powerful referral program.';
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2>Create your account</h2>
                    <p class="sub">Join 9JACASH and start earning today.</p>
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
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate data-loading-submit>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= e($old['username']) ?>" pattern="[a-z0-9_]{4,20}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <button class="input-group-text" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="pw-strength"><span></span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="referral_code">Referral Code <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" class="form-control" id="referral_code" name="referral_code" value="<?= e($old['referral_code']) ?>">
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                    <label class="form-check-label small" for="terms">
                        I agree to the <a href="<?= e(rtrim(APP_URL, '/')) ?>/terms.php" target="_blank">Terms of Service</a> and <a href="<?= e(rtrim(APP_URL, '/')) ?>/privacy.php" target="_blank">Privacy Policy</a>.
                    </label>
                </div>

                <button type="submit" class="btn btn-brand w-100">Create Account</button>
            </form>

            <p class="text-center small mt-4 mb-0" style="color:var(--text-muted);">
                Already have an account? <a href="login.php" style="color:var(--brand-emerald);font-weight:600;">Log in</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
