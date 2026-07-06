<?php

declare(strict_types=1);

/**
 * Pays out one day of mining profit to every active purchase that hasn't
 * been paid in the last ~23 hours, and marks matured plans completed.
 *
 * Run every hour via crontab, e.g.:
 *   0 * * * * /usr/bin/php /path/to/9jacash/cron/mining_earnings.php >> /path/to/9jacash/storage/logs/cron.log 2>&1
 */

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/helpers.php';

use App\Models\CronLog;
use App\Services\MiningService;

try {
    $result = MiningService::runDailyPayouts();
    $message = "Paid {$result['paid']} purchase(s), completed {$result['completed']}.";
    CronLog::record('mining_earnings', 'success', $message);
    echo '[' . date('Y-m-d H:i:s') . "] {$message}\n";
} catch (Throwable $e) {
    CronLog::record('mining_earnings', 'failed', $e->getMessage());
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] FAILED: ' . $e->getMessage() . "\n");
    exit(1);
}
