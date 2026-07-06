<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

AdminAuth::requireGuest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $username = clean($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter your username and password.';
    } else {
        $result = AdminAuth::attemptLogin($username, $password);

        if ($result['success']) {
            redirect(rtrim(APP_URL, '/') . '/admin/index.php');
        }

        $errors[] = $result['message'];
    }
}

$assetBase = rtrim(APP_URL, '/') . '/assets';
$pageTitle = 'Admin Login';
$siteName = get_setting('site_name', 'SURECASH MINING');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>(function(){var t=localStorage.getItem('surecash_theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<?= favicon_link_html() ?>
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/vendor/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/fonts.css">
<link rel="stylesheet" href="<?= e($assetBase) ?>/css/theme.css">
</head>
<body>
<div class="auth-mobile-brand">
    <?= brand_mark_html(40) ?>
    <span><?= e($siteName) ?> Admin</span>
</div>
<div class="auth-shell">
    <div class="auth-visual">
        <div class="brand"><?= brand_mark_html() ?><span><?= e($siteName) ?> ADMIN</span></div>
        <div class="pitch">
            <h1>Full control over your platform.</h1>
            <p>Manage users, deposits, withdrawals, mining plans, tasks and every setting from one secure dashboard.</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-card fade-in-up">
            <h2>Administrator Login</h2>
            <p class="sub">Restricted area. Authorized personnel only.</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="input-group-text" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-brand w-100">Log In</button>
            </form>

            <div id="biometricLoginWrap" class="d-none">
                <div class="d-flex align-items-center gap-2 my-3">
                    <hr class="flex-grow-1"><span class="small" style="color:var(--text-muted);">OR</span><hr class="flex-grow-1">
                </div>
                <button type="button" id="biometricLoginBtn" class="btn btn-outline-brand w-100"><i class="bi bi-fingerprint me-1"></i> Log In with Biometrics</button>
            </div>
        </div>
    </div>
</div>
<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="<?= e($assetBase) ?>/js/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script src="<?= e($assetBase) ?>/js/main.js"></script>
<script src="<?= e($assetBase) ?>/js/webauthn.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!SureCashWebAuthn.supported()) return;
    document.getElementById('biometricLoginWrap').classList.remove('d-none');

    // Prefetch the login challenge as soon as the button is shown, and
    // keep the *resolved* value (not just a pending promise) cached, so
    // the click handler can reach navigator.credentials.get() without
    // any await in between - even awaiting an already-resolved promise
    // is enough to burn through the click's user-activation window.
    let cachedOptions = null;
    function refreshOptions() {
        cachedOptions = null;
        SureCashWebAuthn.fetchLoginOptions().then((res) => { cachedOptions = res; });
    }
    refreshOptions();

    document.getElementById('biometricLoginBtn').addEventListener('click', async function () {
        const btn = this;
        SureCashMining.setLoading(btn, true);
        try {
            const optionsRes = cachedOptions || await SureCashWebAuthn.fetchLoginOptions();
            const result = await SureCashWebAuthn.login('admin', optionsRes);
            if (result.success) {
                window.location.href = result.redirect;
            } else {
                SureCashMining.toast(result.message || 'Biometric login failed.', 'error');
                SureCashMining.setLoading(btn, false);
                refreshOptions();
            }
        } catch (e) {
            SureCashMining.setLoading(btn, false);
            refreshOptions();
        }
    });
});
</script>
</body>
</html>
