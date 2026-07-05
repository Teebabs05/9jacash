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
