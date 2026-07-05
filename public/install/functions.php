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
        'storage/ is writable' => is_writable($base . '/storage') || @chmod($base . '/storage', 0755),
        'storage/uploads/ is writable' => is_writable($base . '/storage/uploads') || @chmod($base . '/storage/uploads', 0755),
        'Project root is writable (.env)' => is_writable($base) ,
    ];
}

function install_is_locked(): bool
{
    return is_file(__DIR__ . '/installed.lock');
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

function install_run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Could not read SQL file: {$path}");
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
    $examplePath = install_base_path() . '/.env.example';
    $lines = file($examplePath, FILE_IGNORE_NEW_LINES);
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

    file_put_contents(install_base_path() . '/.env', implode("\n", $output) . "\n", LOCK_EX);
}

function install_generate_key(): string
{
    return bin2hex(random_bytes(16));
}
