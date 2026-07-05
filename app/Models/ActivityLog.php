<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';

    public static function log(?int $userId, string $action, string $description = ''): void
    {
        static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Security::clientIp(),
            'device' => Security::deviceType(),
            'user_agent' => Security::userAgent(),
        ]);
    }

    public static function recentPaginated(int $page = 1, int $perPage = 30, ?int $userId = null): array
    {
        $perPage = max(1, min(200, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $where = '1=1';
        $params = [];
        if ($userId) {
            $where = 'user_id = :uid';
            $params['uid'] = $userId;
        }
        $rows = static::db()->fetchAll(
            "SELECT activity_logs.*, users.username FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             WHERE {$where} ORDER BY activity_logs.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM activity_logs WHERE {$where}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }
}
