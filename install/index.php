<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$locked = is_file(BASE_PATH . '/install/installed.lock');
$step = (int) ($_GET['step'] ?? 1);
$step = ($step >= 1 && $step <= 4) ? $step : 1;

$justFinished = !empty($_SESSION['install']['just_finished']);

if ($locked && !($step === 4 && $justFinished)) {
    $assetBase = rtrim(APP_URL, '/') . '/assets';
    installer_render_head('Already Installed', $assetBase);
    ?>
    <div class="install-card text-center">
        <div class="mb-3" style="font-size:3rem;color:var(--warning);"><i class="bi bi-shield-lock-fill"></i></div>
        <h2>9JACASH is already installed</h2>
        <p class="sub">The installation wizard has already been completed on this server. Delete <code>install/installed.lock</code> if you intentionally need to reinstall.</p>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/login.php" class="btn btn-brand w-100 mb-2">Go to Login</a>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/login.php" class="btn btn-outline-brand w-100">Admin Login</a>
    </div>
    <?php
    installer_render_foot($assetBase);
    exit;
}

$errors = [];

// ---------------------------------------------------------------
// STEP 2: Database configuration
// ---------------------------------------------------------------
$dbDefaults = $_SESSION['install']['db'] ?? [
    'host' => 'localhost',
    'port' => '3306',
    'name' => '9jacash',
    'user' => 'root',
    'pass' => '',
];

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $dbDefaults = [
        'host' => clean($_POST['host'] ?? ''),
        'port' => clean($_POST['port'] ?? '3306'),
        'name' => clean($_POST['name'] ?? ''),
        'user' => clean($_POST['user'] ?? ''),
        'pass' => (string) ($_POST['pass'] ?? ''),
    ];

    if ($dbDefaults['host'] === '' || $dbDefaults['name'] === '' || $dbDefaults['user'] === '') {
        $errors[] = 'Please fill in all required database fields.';
    } else {
        $test = installer_test_db_connection($dbDefaults['host'], $dbDefaults['port'], $dbDefaults['name'], $dbDefaults['user'], $dbDefaults['pass']);

        if ($test['success']) {
            $_SESSION['install']['db'] = $dbDefaults;
            redirect(rtrim(APP_URL, '/') . '/install/index.php?step=3');
        }

        $errors[] = $test['message'];
    }
}

// ---------------------------------------------------------------
// STEP 3: Site + Admin setup, then run the installation
// ---------------------------------------------------------------
if ($step === 3) {
    if (empty($_SESSION['install']['db'])) {
        redirect(rtrim(APP_URL, '/') . '/install/index.php?step=2');
    }

    $siteDefaults = [
        'site_name' => '9JACASH',
        'site_url' => APP_URL,
        'admin_username' => 'admin',
        'admin_email' => 'admin@9jacash.com',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();

        $siteDefaults['site_name'] = clean($_POST['site_name'] ?? '9JACASH');
        $siteDefaults['site_url'] = rtrim(clean($_POST['site_url'] ?? APP_URL), '/');
        $siteDefaults['admin_username'] = strtolower(clean($_POST['admin_username'] ?? 'admin'));
        $siteDefaults['admin_email'] = strtolower(clean($_POST['admin_email'] ?? ''));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        $adminPasswordConfirm = (string) ($_POST['admin_password_confirmation'] ?? '');

        if (!preg_match('/^[a-z0-9_]{4,20}$/', $siteDefaults['admin_username'])) {
            $errors[] = 'Admin username must be 4-20 characters (letters, numbers, underscore only).';
        }

        if (!is_valid_email($siteDefaults['admin_email'])) {
            $errors[] = 'Please enter a valid admin email address.';
        }

        if (strlen($adminPassword) < 8) {
            $errors[] = 'Admin password must be at least 8 characters long.';
        } elseif ($adminPassword !== $adminPasswordConfirm) {
            $errors[] = 'Admin passwords do not match.';
        }

        if (!$errors) {
            $db = $_SESSION['install']['db'];

            $importResult = installer_import_schema($db['host'], $db['port'], $db['name'], $db['user'], $db['pass']);

            if (!$importResult['success']) {
                $errors[] = $importResult['message'];
            } else {
                $conn = installer_test_db_connection($db['host'], $db['port'], $db['name'], $db['user'], $db['pass']);
                $pdo = $conn['pdo'];

                $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

                $adminId = $pdo->query('SELECT MIN(id) AS id FROM admins')->fetch()['id'] ?? null;

                if ($adminId) {
                    $stmt = $pdo->prepare('UPDATE admins SET username = ?, email = ?, password = ? WHERE id = ?');
                    $stmt->execute([$siteDefaults['admin_username'], $siteDefaults['admin_email'], $hashedPassword, $adminId]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO admins (username, email, password, full_name, role, status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
                    );
                    $stmt->execute([$siteDefaults['admin_username'], $siteDefaults['admin_email'], $hashedPassword, 'Super Administrator', 'super_admin', 'active']);
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO site_settings (setting_key, setting_value) VALUES ("site_name", ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );
                $stmt->execute([$siteDefaults['site_name']]);

                installer_write_config($db, $siteDefaults['site_url']);
                installer_write_lock();

                unset($_SESSION['install']['db']);
                $_SESSION['install']['just_finished'] = true;

                redirect(rtrim($siteDefaults['site_url'], '/') . '/install/index.php?step=4');
            }
        }
    }
}

// ---------------------------------------------------------------
// STEP 4: Finished
// ---------------------------------------------------------------
if ($step === 4 && $justFinished) {
    unset($_SESSION['install']['just_finished']);
}

$requirementChecks = installer_check_requirements();
$requirementsPassed = installer_requirements_passed($requirementChecks);
$assetBase = rtrim(APP_URL, '/') . '/assets';

installer_render_head('Install 9JACASH', $assetBase);
?>
<div class="install-card">
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="brand-mark-sm">9</span>
        <strong>9JACASH Installation Wizard</strong>
    </div>

    <div class="step-nav mb-4">
        <?php foreach ([1 => 'Requirements', 2 => 'Database', 3 => 'Site & Admin', 4 => 'Finish'] as $num => $label): ?>
            <div class="step-dot <?= $step === $num ? 'active' : ($step > $num ? 'done' : '') ?>">
                <span><?= $num ?></span><small><?= e($label) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3">
            <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <h4 class="fw-bold mb-3">Server Requirements Check</h4>
        <table class="table table-sm align-middle">
            <tbody>
            <?php foreach ($requirementChecks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?><?= $check['required'] ? '' : ' <span class="badge text-bg-secondary">optional</span>' ?></td>
                    <td class="text-muted small"><?= e($check['detail']) ?></td>
                    <td class="text-end">
                        <?php if ($check['ok']): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($requirementsPassed): ?>
            <a href="?step=2" class="btn btn-brand w-100">Continue to Database Setup</a>
        <?php else: ?>
            <button class="btn btn-brand w-100" disabled>Resolve issues above to continue</button>
            <a href="?step=1" class="btn btn-outline-brand w-100 mt-2">Re-check Requirements</a>
        <?php endif; ?>

    <?php elseif ($step === 2): ?>
        <h4 class="fw-bold mb-3">Database Configuration</h4>
        <p class="sub">Enter the MySQL database credentials created in your hosting control panel.</p>
        <form method="POST" action="?step=2" data-loading-submit>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Database Host</label>
                    <input type="text" class="form-control" name="host" value="<?= e($dbDefaults['host']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Port</label>
                    <input type="text" class="form-control" name="port" value="<?= e($dbDefaults['port']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Database Name</label>
                    <input type="text" class="form-control" name="name" value="<?= e($dbDefaults['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Database Username</label>
                    <input type="text" class="form-control" name="user" value="<?= e($dbDefaults['user']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Database Password</label>
                    <input type="password" class="form-control" name="pass" value="<?= e($dbDefaults['pass']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-brand w-100 mt-4">Test Connection &amp; Continue</button>
        </form>

    <?php elseif ($step === 3): ?>
        <h4 class="fw-bold mb-3">Site &amp; Administrator Setup</h4>
        <p class="sub">This will import the database schema and create your administrator account.</p>
        <form method="POST" action="?step=3" data-loading-submit>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Site Name</label>
                    <input type="text" class="form-control" name="site_name" value="<?= e($siteDefaults['site_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site URL</label>
                    <input type="text" class="form-control" name="site_url" value="<?= e($siteDefaults['site_url']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Username</label>
                    <input type="text" class="form-control" name="admin_username" value="<?= e($siteDefaults['admin_username']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Email</label>
                    <input type="email" class="form-control" name="admin_email" value="<?= e($siteDefaults['admin_email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Password</label>
                    <input type="password" class="form-control" name="admin_password" required minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="admin_password_confirmation" required minlength="8">
                </div>
            </div>
            <button type="submit" class="btn btn-brand w-100 mt-4">Install 9JACASH</button>
        </form>

    <?php elseif ($step === 4): ?>
        <div class="text-center">
            <div class="mb-3" style="font-size:3rem;color:var(--success);"><i class="bi bi-check-circle-fill"></i></div>
            <h4 class="fw-bold">Installation Complete!</h4>
            <p class="sub">9JACASH has been installed successfully. For security, please delete or restrict access to the <code>/install</code> directory now.</p>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/user/login.php" class="btn btn-brand w-100 mb-2">Go to Login</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/login.php" class="btn btn-outline-brand w-100">Admin Login</a>
        </div>
    <?php endif; ?>
</div>
<?php installer_render_foot($assetBase); ?>
