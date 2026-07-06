<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny service locator holding the resolved config array and DB connection.
 */
class App
{
    private static array $config = [];
    private static ?Database $db = null;

    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function db(): Database
    {
        if (self::$db === null) {
            self::$db = new Database(self::$config['db']);
        }
        return self::$db;
    }
}
