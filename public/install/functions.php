<?php

declare(strict_types=1);

/**
 * Installer helpers. Deliberately dependency-free (no app/ classes) so
 * the wizard can run before .env / autoloading are configured.
 */

function install_base_path(): string
{
    return dirname(__DIR__, 2);
}

/**
 * Requirements that must pass to run the installer at all — it only
 * needs to talk to MySQL and write a .env file in the project root.
 * Storage/upload permissions are checked separately (see
 * install_storage_diagnostics) and do NOT block installation, since
 * they only affect avatar/receipt/KYC/task-proof uploads, which can be
 * fixed any time after the site is up.
 */
function install_requirements(): array
{
    $base = install_base_path();

    return [
        'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Driver' => extension_loaded('pdo_mysql'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'fileinfo Extension' => extension_loaded('fileinfo'),
        'openssl Extension' => extension_loaded('openssl'),
        'Project root is writable (.env)' => @is_writable($base),
    ];
}

/**
 * Diagnostic (non-blocking) info about the folders used for uploads,
 * so a permission/ownership mismatch can be identified precisely
 * instead of just reported as "Missing".
 */
function install_storage_diagnostics(): array
{
    $base = install_base_path();
    $paths = [
        'storage/' => $base . '/storage',
        'storage/logs/' => $base . '/storage/logs',
        'storage/uploads/' => $base . '/storage/uploads',
        'storage/uploads/avatars/' => $base . '/storage/uploads/avatars',
        'storage/uploads/receipts/' => $base . '/storage/uploads/receipts',
        'storage/uploads/proofs/' => $base . '/storage/uploads/proofs',
        'storage/uploads/kyc/' => $base . '/storage/uploads/kyc',
    ];

    $results = [];
    foreach ($paths as $label => $path) {
        $exists = @is_dir($path);
        $writable = $exists && (@is_writable($path) || @chmod($path, 0755) && @is_writable($path));

        $owner = null;
        if ($exists && function_exists('posix_getpwuid') && function_exists('fileowner')) {
            $info = @posix_getpwuid(@fileowner($path));
            $owner = $info['name'] ?? (string) @fileowner($path);
        } elseif ($exists) {
            $owner = (string) @fileowner($path);
        }

        $results[$label] = [
            'exists' => $exists,
            'writable' => $writable,
            'perms' => $exists ? substr(sprintf('%o', @fileperms($path)), -4) : null,
            'owner' => $owner,
        ];
    }

    $processUser = null;
    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $info = @posix_getpwuid(posix_geteuid());
        $processUser = $info['name'] ?? (string) posix_geteuid();
    }

    $openBasedir = ini_get('open_basedir') ?: null;
    $baseInBasedir = true;
    if ($openBasedir) {
        $baseInBasedir = false;
        foreach (explode(PATH_SEPARATOR, $openBasedir) as $allowed) {
            $allowed = rtrim($allowed, '/');
            if ($allowed !== '' && str_starts_with(rtrim($base, '/'), $allowed)) {
                $baseInBasedir = true;
                break;
            }
        }
    }

    return [
        'paths' => $results,
        'process_user' => $processUser,
        'open_basedir' => $openBasedir,
        'base_path' => $base,
        'base_in_basedir' => $baseInBasedir,
    ];
}

function install_is_locked(): bool
{
    return @is_file(__DIR__ . '/installed.lock');
}

function install_test_connection(string $host, string $port, string $user, string $pass, ?string &$error = null): ?PDO
{
    try {
        return new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    } catch (PDOException $e) {
        $error = $e->getMessage();
        return null;
    }
}

/**
 * True if this database already has the app's schema + seed data loaded
 * (e.g. a previous installer attempt succeeded here before a later step
 * failed). Re-running schema.sql/seed.sql against an already-seeded
 * database throws duplicate-key errors on the seed INSERTs, so callers
 * should skip the import entirely when this returns true.
 */
function install_database_already_set_up(PDO $pdo, string $dbName): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = 'users'"
    );
    $stmt->execute(['db' => $dbName]);
    if ((int) $stmt->fetchColumn() === 0) {
        return false;
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
}

function install_run_sql_file(PDO $pdo, string $path): void
{
    if (!@is_file($path)) {
        $message = install_open_basedir_hint("SQL file not found at: {$path}.", $path);
        throw new RuntimeException($message . ' Make sure database/schema.sql and database/seed.sql were uploaded alongside app/, storage/, etc.');
    }

    error_clear_last();
    $sql = @file_get_contents($path);
    if ($sql === false) {
        $lastError = error_get_last();
        $reason = $lastError['message'] ?? 'unknown reason';
        throw new RuntimeException("Could not read SQL file: {$path} — PHP reported: {$reason}");
    }

    // Strip comment-only lines, then split on statement-terminating
    // semicolons (schema/seed files contain no stored procedures, so a
    // naive split is safe here).
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
}

function install_write_env(array $values): void
{
    $base = install_base_path();
    $examplePath = $base . '/.env.example';

    if (!@is_file($examplePath)) {
        throw new RuntimeException(install_open_basedir_hint("Could not find .env.example at: {$examplePath}.", $base));
    }

    $lines = @file($examplePath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException(install_open_basedir_hint("Could not read .env.example at: {$examplePath}.", $base));
    }

    $output = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            $output[] = $line;
            continue;
        }
        [$key] = explode('=', $trimmed, 2);
        $key = trim($key);
        $output[] = array_key_exists($key, $values) ? "{$key}={$values[$key]}" : $line;
    }

    $written = @file_put_contents($base . '/.env', implode("\n", $output) . "\n", LOCK_EX);
    if ($written === false) {
        throw new RuntimeException(install_open_basedir_hint("Could not write .env file to: {$base}/.env.", $base));
    }
}

function install_open_basedir_hint(string $message, string $path): string
{
    $obd = ini_get('open_basedir');
    if (!$obd) {
        return $message;
    }
    return $message . " Note: open_basedir is restricted to \"{$obd}\" — if \"{$path}\" falls outside that, PHP cannot see it even if it exists.";
}

function install_generate_key(): string
{
    return bin2hex(random_bytes(16));
}
