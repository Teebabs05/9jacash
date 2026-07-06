<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function send(int $userId, string $title, string $message, string $type = 'info'): void
    {
        static::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }

    public static function forUser(int $userId, int $limit = 30): array
    {
        $limit = max(1, min(200, $limit));
        return static::db()->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT {$limit}",
            ['uid' => $userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return static::count('user_id = :uid AND is_read = 0', ['uid' => $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        static::db()->update('notifications', ['is_read' => 1], 'user_id = :uid', ['uid' => $userId]);
    }
}
