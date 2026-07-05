<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\ActivityLog;

class LogController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $result = ActivityLog::recentPaginated($page, 40);

        $this->view('admin/logs/activity', [
            'title' => 'Activity Logs',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 40,
        ], 'admin');
    }

    public function webhooks(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 40;
        $offset = ($page - 1) * $perPage;

        $rows = db()->fetchAll("SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $total = (int) (db()->fetch('SELECT COUNT(*) c FROM webhook_logs')['c'] ?? 0);

        $this->view('admin/logs/webhooks', [
            'title' => 'Webhook Logs',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ], 'admin');
    }
}
