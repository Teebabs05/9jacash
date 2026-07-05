<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SpinHistory extends Model
{
    protected static string $table = 'spin_history';

    public static function hasSpunToday(int $userId): bool
    {
        return static::db()->fetch(
            'SELECT id FROM spin_history WHERE user_id = :uid AND spin_date = CURDATE()',
            ['uid' => $userId]
        ) !== false;
    }
}
