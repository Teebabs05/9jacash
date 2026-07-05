<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TaskSubmission extends Model
{
    protected static string $table = 'task_submissions';

    public static function forUser(int $userId): array
    {
        return static::db()->fetchAll(
            "SELECT task_submissions.*, tasks.title, tasks.reward_amount FROM task_submissions
             JOIN tasks ON tasks.id = task_submissions.task_id
             WHERE task_submissions.user_id = :uid
             ORDER BY task_submissions.created_at DESC",
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
            $where = 'task_submissions.status = :status';
            $params['status'] = $status;
        }
        $rows = static::db()->fetchAll(
            "SELECT task_submissions.*, tasks.title, tasks.reward_amount, users.username
             FROM task_submissions
             JOIN tasks ON tasks.id = task_submissions.task_id
             JOIN users ON users.id = task_submissions.user_id
             WHERE {$where}
             ORDER BY task_submissions.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = static::db()->fetch("SELECT COUNT(*) c FROM task_submissions WHERE {$where}", $params);
        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }
}
