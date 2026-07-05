<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$token = (string) ($_GET['token'] ?? '');
$userId = (int) ($_GET['uid'] ?? 0);

if ($token === '' || $userId <= 0) {
    $result = ['success' => false, 'message' => 'Invalid verification link.'];
} else {
    $result = Auth::verifyEmailToken($userId, $token);
}

$pageTitle = 'Verify Email';
$visualTitle = 'Almost there.';
$visualText = 'Verify your email address to unlock deposits, withdrawals and every earning feature on 9JACASH.';
require __DIR__ . '/../includes/partials/auth-head.php';
?>
<div class="auth-shell">
    <?php require __DIR__ . '/../includes/partials/auth-visual.php'; ?>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up text-center">
            <?php if ($result['success']): ?>
                <div class="mb-3" style="font-size:3rem;color:var(--success);"><i class="bi bi-patch-check-fill"></i></div>
                <h2>Email Verified</h2>
            <?php else: ?>
                <div class="mb-3" style="font-size:3rem;color:var(--danger);"><i class="bi bi-x-circle-fill"></i></div>
                <h2>Verification Failed</h2>
            <?php endif; ?>
            <p class="sub"><?= e($result['message']) ?></p>

            <?php if ($result['success']): ?>
                <a href="login.php" class="btn btn-brand w-100">Continue to Login</a>
            <?php else: ?>
                <a href="resend-verification.php<?= $userId ? '?uid=' . $userId : '' ?>" class="btn btn-outline-brand w-100">Resend Verification Email</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/partials/auth-scripts.php'; ?>
