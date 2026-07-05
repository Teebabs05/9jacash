<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/functions.php';

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$errors = [];
$success = null;

// Step 4 (the completion screen) is shown exactly once, immediately
// after finishing step 3 in *this* session — otherwise, once locked,
// every step (including a freshly-completed step=4) falls back to the
// "already installed" screen so default credentials aren't advertised
// indefinitely to anyone who revisits /install/ later.
$justCompleted = $step === 4 && !empty($_SESSION['install_just_completed']);
if (install_is_locked() && $step !== 99 && !$justCompleted) {
    $step = 99;
}
if ($justCompleted) {
    unset($_SESSION['install_just_completed']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int) ($_POST['step'] ?? 1);

    if ($postedStep === 1) {
        header('Location: ?step=2');
        exit;
    }

    if ($postedStep === 2) {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? '9jacash');
        $user = trim($_POST['db_user'] ?? 'root');
        $pass = (string) ($_POST['db_pass'] ?? '');

        $dbError = null;
        $pdo = install_test_connection($host, $port, $user, $pass, $dbError);
        if (!$pdo) {
            $errors[] = 'Could not connect to MySQL: ' . ($dbError ?: 'unknown error') . '. Please check host, port, username and password.';
            if ($dbError && stripos($dbError, 'Access denied') !== false) {
                $errors[] = 'On cPanel / shared hosting, database usernames and database names are usually prefixed with your account name (e.g. "cpaneluser_9jacash"), and the database + user must both be created via "MySQL Databases" in cPanel first, with the user explicitly added to that database.';
            }
        } else {
            try {
                try {
                    // Many shared-hosting DB users don't have CREATE privilege —
                    // that's fine as long as the database already exists (which it
                    // must, on cPanel, since you create it there before running this).
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (PDOException) {
                    // Ignored — the USE statement below will fail loudly if the
                    // database genuinely doesn't exist and can't be created.
                }

                $pdo->exec("USE `{$name}`");

                install_run_sql_file($pdo, install_base_path() . '/database/schema.sql');
                install_run_sql_file($pdo, install_base_path() . '/database/seed.sql');

                $_SESSION['install_db'] = compact('host', 'port', 'name', 'user', 'pass');
                header('Location: ?step=3');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Database setup failed: ' . $e->getMessage();
                $msg = $e->getMessage();
                if (stripos($msg, 'Unknown database') !== false || (stripos($msg, 'Access denied') !== false && stripos($msg, 'database') !== false)) {
                    $errors[] = 'This usually means the database doesn\'t exist yet, or this MySQL user hasn\'t been granted access to it. On cPanel, go to "MySQL Databases", create the database and a user (if you haven\'t already), then use "Add User To Database" and grant ALL PRIVILEGES — then enter those exact (prefixed) names here.';
                }
            }
        }
    }

    if ($postedStep === 3) {
        $db = $_SESSION['install_db'] ?? null;
        if (!$db) {
            header('Location: ?step=2');
            exit;
        }

        $siteName = trim($_POST['site_name'] ?? '9JACASH');
        $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
        $cronSecret = bin2hex(random_bytes(16));

        try {
            install_write_env([
                'APP_NAME' => '"' . $siteName . '"',
                'APP_URL' => $siteUrl,
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => install_generate_key(),
                'DB_HOST' => $db['host'],
                'DB_PORT' => $db['port'],
                'DB_NAME' => $db['name'],
                'DB_USER' => $db['user'],
                'DB_PASS' => $db['pass'],
                'CRON_SECRET' => $cronSecret,
            ]);

            if (@file_put_contents(__DIR__ . '/installed.lock', date('c')) === false) {
                throw new RuntimeException('Could not write install/installed.lock — check folder permissions.');
            }

            unset($_SESSION['install_db']);
            $_SESSION['install_just_completed'] = true;

            header('Location: ?step=4');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Could not finish installation: ' . $e->getMessage();
        }
    }
}

$requirements = install_requirements();
$requirementsPass = !in_array(false, $requirements, true);
$storageDiag = install_storage_diagnostics();
$storageAllOk = !in_array(false, array_column($storageDiag['paths'], 'writable'), true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>9JACASH — Installation Wizard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg,#0D47A1,#08245c); min-height:100vh; font-family: 'Segoe UI', sans-serif; }
        .wizard-card { max-width: 720px; margin: 4rem auto; background:#fff; border-radius: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); padding: 2.5rem; }
        .step-pill { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eef2f9;color:#64748b;font-weight:700;font-size:.85rem; }
        .step-pill.active { background:#0D47A1;color:#fff; }
        .step-pill.done { background:#00b894;color:#fff; }
        code { color:#0D47A1; }
    </style>
</head>
<body>
<div class="wizard-card">
    <div class="text-center mb-4">
        <h4 class="fw-bold" style="color:#0D47A1;">9JACASH Installation Wizard</h4>
    </div>

    <?php if ($step !== 99): ?>
    <div class="d-flex justify-content-center gap-3 mb-4">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="step-pill <?= $i === $step ? 'active' : ($i < $step ? 'done' : '') ?>"><?= $i ?></div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 99): ?>
        <div class="alert alert-warning">This application has already been installed. Delete <code>install/installed.lock</code> if you intentionally need to run the installer again (only do this on a fresh database).</div>
        <a href="/" class="btn btn-primary rounded-pill px-4">Go to Homepage</a>

    <?php elseif ($step === 1): ?>
        <h6 class="fw-bold mb-3">Step 1: Server Requirements</h6>
        <ul class="list-group mb-4">
            <?php foreach ($requirements as $label => $pass): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($label) ?>
                    <?= $pass ? '<span class="text-success"><i class="fa-solid fa-circle-check"></i> OK</span>' : '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Missing</span>' ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$requirementsPass): ?>
            <div class="alert alert-danger small">Please resolve the missing requirements above before continuing.</div>
        <?php endif; ?>

        <?php if ($storageDiag['open_basedir'] && !$storageDiag['base_in_basedir']): ?>
            <div class="alert alert-danger small">
                <strong>PHP's <code>open_basedir</code> setting will block installation.</strong>
                Your project root (<code><?= htmlspecialchars($storageDiag['base_path']) ?></code>) is outside the
                paths PHP is allowed to access (<code><?= htmlspecialchars($storageDiag['open_basedir']) ?></code>).
                This means database/schema.sql, database/seed.sql, and the storage folders can never be read no
                matter how permissions are set. Ask your host (or use cPanel's "MultiPHP INI Editor") to widen
                <code>open_basedir</code> to include your whole home directory, e.g.
                <code><?= htmlspecialchars(rtrim($storageDiag['base_path'], '/')) ?>:<?= htmlspecialchars(sys_get_temp_dir()) ?></code> —
                or move the entire project (not just <code>public/</code>) inside whatever path is currently allowed.
            </div>
        <?php elseif ($storageDiag['open_basedir']): ?>
            <div class="alert alert-info small">
                <code>open_basedir</code> is set to <code><?= htmlspecialchars($storageDiag['open_basedir']) ?></code>,
                but your project root is within it, so this shouldn't block anything.
            </div>
        <?php endif; ?>

        <h6 class="fw-bold mb-2 mt-4">Storage &amp; Uploads <span class="fw-normal text-muted small">(optional right now)</span></h6>
        <p class="text-muted small">
            These folders are only used for profile pictures, deposit receipts, task proof and KYC uploads —
            not for installation itself. You can continue installing even if something below isn't OK yet, and
            fix it afterward from Admin → Settings or via SSH/File Manager.
        </p>
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
                <thead><tr><th>Path</th><th>Status</th><th>Permissions</th><th>Owner</th></tr></thead>
                <tbody>
                <?php foreach ($storageDiag['paths'] as $label => $info): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($label) ?></code></td>
                        <td>
                            <?php if (!$info['exists']): ?>
                                <span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Missing folder</span>
                            <?php elseif ($info['writable']): ?>
                                <span class="text-success"><i class="fa-solid fa-circle-check"></i> Writable</span>
                            <?php else: ?>
                                <span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> Not writable</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $info['perms'] ? htmlspecialchars($info['perms']) : '—' ?></td>
                        <td><?= $info['owner'] !== null ? htmlspecialchars($info['owner']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$storageAllOk): ?>
            <div class="alert alert-warning small">
                PHP is currently running as <strong><?= htmlspecialchars($storageDiag['process_user'] ?? 'unknown') ?></strong>.
                Any row above showing a different <em>Owner</em> than that user is the actual problem — permission bits alone
                (e.g. <code>chmod 755</code>) can't fix an ownership mismatch; you'd need to <code>chown</code> the folder to
                match, or ask your host to do it. If the owner already matches, re-run:
                <code>chmod -R 755 storage</code> (try <code>775</code> if 755 doesn't work) from your project root.
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="step" value="1">
            <button class="btn btn-primary rounded-pill px-4" type="submit" <?= $requirementsPass ? '' : 'disabled' ?>>Continue</button>
        </form>

    <?php elseif ($step === 2): ?>
        <?php
        $dbHostVal = $_POST['db_host'] ?? 'localhost';
        $dbPortVal = $_POST['db_port'] ?? '3306';
        $dbNameVal = $_POST['db_name'] ?? '';
        $dbUserVal = $_POST['db_user'] ?? '';
        ?>
        <h6 class="fw-bold mb-3">Step 2: Database Configuration</h6>
        <p class="text-muted small">We'll create the database (if it doesn't exist) and import the schema + sample data automatically.</p>
        <p class="text-muted small">On cPanel / shared hosting: create the database and a database user under <strong>MySQL&reg; Databases</strong> first (both are usually prefixed with your account name, e.g. <code>cpaneluser_9jacash</code>), add the user to the database with full privileges, then enter those exact values below. The host is almost always <code>localhost</code>, not an IP address.</p>
        <form method="post">
            <input type="hidden" name="step" value="2">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label small">Database Host</label><input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($dbHostVal) ?>" required></div>
                <div class="col-md-4"><label class="form-label small">Port</label><input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($dbPortVal) ?>" required></div>
                <div class="col-md-6"><label class="form-label small">Database Name</label><input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($dbNameVal) ?>" placeholder="e.g. cpaneluser_9jacash" required></div>
                <div class="col-md-6"><label class="form-label small">Database User</label><input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($dbUserVal) ?>" placeholder="e.g. cpaneluser_dbuser" required></div>
                <div class="col-12"><label class="form-label small">Database Password</label><input type="password" name="db_pass" class="form-control"></div>
            </div>
            <button class="btn btn-primary rounded-pill px-4 mt-4" type="submit">Test Connection &amp; Import Database</button>
        </form>

    <?php elseif ($step === 3): ?>
        <h6 class="fw-bold mb-3">Step 3: Site Configuration</h6>
        <form method="post">
            <input type="hidden" name="step" value="3">
            <div class="mb-3"><label class="form-label small">Site Name</label><input type="text" name="site_name" class="form-control" value="9JACASH" required></div>
            <div class="mb-3">
                <label class="form-label small">Site URL</label>
                <input type="url" name="site_url" class="form-control" value="http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/public" required>
                <div class="form-text">The full URL including <code>/public</code> if your document root points at the project root.</div>
            </div>
            <button class="btn btn-primary rounded-pill px-4" type="submit">Finish Installation</button>
        </form>

    <?php elseif ($step === 4): ?>
        <div class="text-center">
            <i class="fa-solid fa-circle-check text-success" style="font-size:3rem;"></i>
            <h5 class="fw-bold mt-3">Installation Complete!</h5>
            <p class="text-muted">Your 9JACASH platform is ready. Log in with the default administrator account below and change the password immediately (you'll be required to on first login).</p>
            <div class="bg-light rounded-3 p-3 d-inline-block text-start mb-3">
                <div><strong>Username:</strong> admin</div>
                <div><strong>Password:</strong> 1988125012</div>
            </div>
            <div class="alert alert-warning small text-start">
                <strong>Before going live:</strong>
                <ul class="mb-0 mt-2">
                    <li>Set up your cron job — see <code>README.md</code> for the exact command.</li>
                    <li>Configure SMTP, PayVessel and reCAPTCHA under Admin → Settings.</li>
                    <li>Review <code>public/.htaccess</code> and enable HTTPS redirection.</li>
                </ul>
            </div>
            <br>
            <a href="/login" class="btn btn-primary rounded-pill px-4">Go to Login</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
