<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function forUserPaginated(int $userId, int $page = 1, int $perPage = 20, ?string $walletType = null): array
    {
        // LIMIT/OFFSET are cast to int and interpolated directly: with
        // ATTR_EMULATE_PREPARES off, MySQL's native prepare rejects
        // string-bound params in a LIMIT clause. Values here are
        // developer-controlled ints, never raw user input.
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $where = 'user_id = :uid';
        $params = ['uid' => $userId];
        if ($walletType) {
            $where .= ' AND wallet_type = :wt';
            $params['wt'] = $walletType;
        }

        $rows = static::db()->fetchAll(
            "SELECT * FROM transactions WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM transactions WHERE {$where}", $params);

        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }
}
