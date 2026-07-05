<?php

declare(strict_types=1);

namespace App\Core;

/**
 * DB-backed rate limiting for login/OTP/password-reset attempts,
 * keyed by identifier (email/username) + IP.
 */
class RateLimiter
{
    public static function tooManyAttempts(string $identifier, string $action, int $maxAttempts = 5, int $decayMinutes = 15): bool
    {
        $db = App::db();
        $since = date('Y-m-d H:i:s', time() - $decayMinutes * 60);

        $row = $db->fetch(
            'SELECT COUNT(*) as c FROM login_attempts
             WHERE identifier = :identifier AND action = :action AND success = 0 AND created_at >= :since',
            ['identifier' => $identifier, 'action' => $action, 'since' => $since]
        );

        return (int) ($row['c'] ?? 0) >= $maxAttempts;
    }

    public static function hit(string $identifier, string $action, bool $success): void
    {
        App::db()->insert('login_attempts', [
            'identifier' => $identifier,
            'action' => $action,
            'ip_address' => Security::clientIp(),
            'success' => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function clear(string $identifier, string $action): void
    {
        App::db()->delete('login_attempts', 'identifier = :identifier AND action = :action', [
            'identifier' => $identifier,
            'action' => $action,
        ]);
    }

    public static function secondsUntilRetry(string $identifier, string $action, int $decayMinutes = 15): int
    {
        $row = App::db()->fetch(
            'SELECT created_at FROM login_attempts
             WHERE identifier = :identifier AND action = :action
             ORDER BY created_at DESC LIMIT 1',
            ['identifier' => $identifier, 'action' => $action]
        );
        if (!$row) {
            return 0;
        }
        $elapsed = time() - strtotime($row['created_at']);
        return max(0, ($decayMinutes * 60) - $elapsed);
    }
}
