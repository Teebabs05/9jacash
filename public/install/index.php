<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/functions.php';

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$errors = [];
$success = null;

if (install_is_locked() && $step !== 99) {
    $step = 99;
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

        $pdo = install_test_connection($host, $port, $user, $pass);
        if (!$pdo) {
            $errors[] = 'Could not connect to MySQL with the details provided. Please check host, port, username and password.';
        } else {
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$name}`");

                install_run_sql_file($pdo, install_base_path() . '/database/schema.sql');
                install_run_sql_file($pdo, install_base_path() . '/database/seed.sql');

                $_SESSION['install_db'] = compact('host', 'port', 'name', 'user', 'pass');
                header('Location: ?step=3');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Database setup failed: ' . $e->getMessage();
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

        file_put_contents(__DIR__ . '/installed.lock', date('c'));
        unset($_SESSION['install_db']);

        header('Location: ?step=4');
        exit;
    }
}

$requirements = install_requirements();
$requirementsPass = !in_array(false, $requirements, true);
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
        <form method="post">
            <input type="hidden" name="step" value="1">
            <button class="btn btn-primary rounded-pill px-4" type="submit" <?= $requirementsPass ? '' : 'disabled' ?>>Continue</button>
        </form>

    <?php elseif ($step === 2): ?>
        <h6 class="fw-bold mb-3">Step 2: Database Configuration</h6>
        <p class="text-muted small">We'll create the database (if it doesn't exist) and import the schema + sample data automatically.</p>
        <form method="post">
            <input type="hidden" name="step" value="2">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label small">Database Host</label><input type="text" name="db_host" class="form-control" value="127.0.0.1" required></div>
                <div class="col-md-4"><label class="form-label small">Port</label><input type="text" name="db_port" class="form-control" value="3306" required></div>
                <div class="col-md-6"><label class="form-label small">Database Name</label><input type="text" name="db_name" class="form-control" value="9jacash" required></div>
                <div class="col-md-6"><label class="form-label small">Database User</label><input type="text" name="db_user" class="form-control" value="root" required></div>
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
