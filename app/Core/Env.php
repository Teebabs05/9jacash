<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env loader (no composer dependency, cPanel-friendly).
 */
class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, "\"'");

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }

    /**
     * Rewrite specific keys in the .env file in place, preserving
     * everything else (comments, ordering, unrelated keys).
     */
    public static function updateFile(string $path, array $updates): void
    {
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
        $seen = [];

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$name] = explode('=', $trimmed, 2);
            $name = trim($name);
            if (array_key_exists($name, $updates)) {
                $lines[$i] = $name . '=' . self::quoteIfNeeded((string) $updates[$name]);
                $seen[$name] = true;
            }
        }

        foreach ($updates as $name => $value) {
            if (empty($seen[$name])) {
                $lines[] = $name . '=' . self::quoteIfNeeded((string) $value);
            }
        }

        file_put_contents($path, implode("\n", $lines) . "\n", LOCK_EX);
    }

    private static function quoteIfNeeded(string $value): string
    {
        return preg_match('/\s/', $value) ? '"' . str_replace('"', '\\"', $value) . '"' : $value;
    }
}
