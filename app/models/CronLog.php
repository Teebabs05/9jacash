<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CronLog extends Model
{
    protected static string $table = 'cron_logs';

    public static function record(string $jobName, string $status, string $message = ''): void
    {
        static::create(['job_name' => $jobName, 'status' => $status, 'message' => $message]);
    }
}
