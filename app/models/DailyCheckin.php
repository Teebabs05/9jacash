<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DailyCheckin extends Model
{
    protected static string $table = 'daily_checkins';

    public static function todayFor(int $userId): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM daily_checkins WHERE user_id = :uid AND checkin_date = CURDATE()',
            ['uid' => $userId]
        );
    }

    public static function lastFor(int $userId): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM daily_checkins WHERE user_id = :uid ORDER BY checkin_date DESC LIMIT 1',
            ['uid' => $userId]
        );
    }

    public static function currentStreak(int $userId): int
    {
        $last = self::lastFor($userId);
        if (!$last) {
            return 0;
        }
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($last['checkin_date'] === date('Y-m-d') || $last['checkin_date'] === $yesterday) {
            return (int) $last['streak_count'];
        }
        return 0;
    }
}
