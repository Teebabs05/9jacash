<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Withdrawal extends Model
{
    protected static string $table = 'withdrawals';

    public static function paginated(int $page = 1, int $perPage = 20, ?string $status = null, ?int $userId = null): array
    {
        $perPage = max(1, min(200, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['1=1'];
        $params = [];
        if ($status) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($userId) {
            $where[] = 'user_id = :uid';
            $params['uid'] = $userId;
        }
        $whereSql = implode(' AND ', $where);

        $rows = static::db()->fetchAll(
            "SELECT withdrawals.*, users.username, users.full_name FROM withdrawals
             JOIN users ON users.id = withdrawals.user_id
             WHERE {$whereSql} ORDER BY withdrawals.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM withdrawals WHERE {$whereSql}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }

    public static function sumTodayForUser(int $userId): float
    {
        $row = static::db()->fetch(
            "SELECT COALESCE(SUM(amount),0) s FROM withdrawals
             WHERE user_id = :uid AND DATE(created_at) = CURDATE() AND status != 'rejected'",
            ['uid' => $userId]
        );
        return (float) $row['s'];
    }
}
