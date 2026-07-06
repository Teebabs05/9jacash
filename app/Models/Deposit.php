<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Deposit extends Model
{
    protected static string $table = 'deposits';

    public static function findByReference(string $reference): array|false
    {
        return static::findBy('reference', $reference);
    }

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
            "SELECT deposits.*, users.username, users.full_name FROM deposits
             JOIN users ON users.id = deposits.user_id
             WHERE {$whereSql} ORDER BY deposits.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM deposits WHERE {$whereSql}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }

    public static function sumApprovedToday(): float
    {
        $row = static::db()->fetch(
            "SELECT COALESCE(SUM(amount),0) s FROM deposits WHERE status = 'approved' AND DATE(created_at) = CURDATE()"
        );
        return (float) $row['s'];
    }
}
