<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF tokens, output escaping and misc input-hardening helpers.
 * SQL injection is handled separately by always using prepared
 * statements (see Database::query/insert/update).
 */
class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string
    {
        $token = self::e(self::csrfToken());
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    public static function verifyCsrf(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /** Escape for safe HTML output (primary XSS defense on output). */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Strip tags / null bytes from raw user input before persisting. */
    public static function sanitizeString(mixed $value): string
    {
        $value = (string) $value;
        $value = str_replace("\0", '', $value);
        return trim(strip_tags($value));
    }

    public static function sanitizeArray(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $clean[$key] = is_array($value) ? self::sanitizeArray($value) : self::sanitizeString($value);
        }
        return $clean;
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function generateOtp(int $digits = 6): string
    {
        $max = (10 ** $digits) - 1;
        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
    }

    /** Very small device-family sniff — good enough for activity logs, not analytics. */
    public static function deviceType(): string
    {
        $ua = strtolower(self::userAgent());
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }
        return 'Desktop';
    }
}
