<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class KycSubmission extends Model
{
    protected static string $table = 'kyc_submissions';

    public static function latestForUser(int $userId): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM kyc_submissions WHERE user_id = :uid ORDER BY id DESC LIMIT 1',
            ['uid' => $userId]
        );
    }

    public static function paginatedPending(int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $perPage = max(1, min(200, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $where = '1=1';
        $params = [];
        if ($status) {
            $where = 'kyc_submissions.status = :status';
            $params['status'] = $status;
        }
        $rows = static::db()->fetchAll(
            "SELECT kyc_submissions.*, users.username, users.full_name FROM kyc_submissions
             JOIN users ON users.id = kyc_submissions.user_id
             WHERE {$where} ORDER BY kyc_submissions.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM kyc_submissions WHERE {$where}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }
}
