<?php
/**
 * PDO database connection manager (singleton).
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
            $port    = defined('DB_PORT') ? DB_PORT : '3306';
            $name    = defined('DB_NAME') ? DB_NAME : '';
            $user    = defined('DB_USER') ? DB_USER : '';
            $pass    = defined('DB_PASS') ? DB_PASS : '';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}, sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                self::syncTimezone(self::$instance);
            } catch (PDOException $e) {
                app_log('error', 'Database connection failed: ' . $e->getMessage());

                if (defined('APP_DEBUG') && APP_DEBUG === true) {
                    throw $e;
                }

                http_response_code(500);
                die('Service temporarily unavailable. Please try again shortly.');
            }
        }

        return self::$instance;
    }

    /**
     * Make MySQL's session `time_zone` match PHP's currently active
     * default timezone, so a MySQL-generated NOW()/CURDATE() value and
     * PHP's time()/date()/strtotime() always agree on the same wall
     * clock. Without this, comparing a MySQL timestamp against PHP's
     * time() silently drifts by the difference between the two
     * timezones (e.g. Africa/Lagos vs. a UTC database server).
     */
    public static function syncTimezone(PDO $pdo): void
    {
        $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
        $pdo->exec('SET time_zone = ' . $pdo->quote($offset));
    }

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new \RuntimeException('Cannot unserialize a singleton.');
    }
}

/**
 * Convenience accessor used across the whole codebase.
 */
function db(): PDO
{
    return Database::getInstance();
}

/**
 * Re-sync MySQL's session time_zone to PHP's current default timezone.
 * Call this after changing PHP's timezone at runtime (e.g. once a
 * site_settings override has been loaded).
 */
function sync_db_timezone(): void
{
    Database::syncTimezone(db());
}
