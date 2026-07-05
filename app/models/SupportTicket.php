<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SupportTicket extends Model
{
    protected static string $table = 'support_tickets';

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId, 'updated_at DESC');
    }

    public static function paginated(int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $perPage = max(1, min(200, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $where = '1=1';
        $params = [];
        if ($status) {
            $where = 'support_tickets.status = :status';
            $params['status'] = $status;
        }
        $rows = static::db()->fetchAll(
            "SELECT support_tickets.*, users.username FROM support_tickets
             JOIN users ON users.id = support_tickets.user_id
             WHERE {$where} ORDER BY support_tickets.updated_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM support_tickets WHERE {$where}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }
}
