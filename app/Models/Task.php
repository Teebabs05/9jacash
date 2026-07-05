<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Task extends Model
{
    protected static string $table = 'tasks';

    public static function activeTasks(): array
    {
        return static::where('status', 'active', 'created_at DESC');
    }

    /** Tasks the user can still perform (not completed, or daily-repeatable and not yet done today). */
    public static function availableForUser(int $userId): array
    {
        return static::db()->fetchAll(
            "SELECT t.* FROM tasks t
             WHERE t.status = 'active'
             AND NOT EXISTS (
                 SELECT 1 FROM task_submissions ts
                 WHERE ts.task_id = t.id AND ts.user_id = :uid
                 AND ts.status IN ('pending','approved')
                 AND (t.repeatable = 'once' OR DATE(ts.created_at) = CURDATE())
             )
             ORDER BY t.created_at DESC",
            ['uid' => $userId]
        );
    }
}
