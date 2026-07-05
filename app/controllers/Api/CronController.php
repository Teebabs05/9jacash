<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\CronLog;
use App\Services\MiningService;

/**
 * HTTP-triggered cron fallback for shared hosts without shell/CLI cron
 * access — configure the host's "cron via URL" feature (or an external
 * pinger like cron-job.org) to hit /cron/mining-earnings?token=CRON_SECRET
 * on a schedule. Prefer the CLI scripts in /cron when available.
 */
class CronController extends Controller
{
    public function run(string $job): void
    {
        $token = (string) $this->input('token', '');
        $expected = (string) config('cron.secret');

        if (!$expected || !hash_equals($expected, $token)) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($job) {
            case 'mining-earnings':
                try {
                    $result = MiningService::runDailyPayouts();
                    CronLog::record('mining_earnings', 'success', "Paid {$result['paid']}, completed {$result['completed']}.");
                    $this->json(['success' => true, 'result' => $result]);
                } catch (\Throwable $e) {
                    CronLog::record('mining_earnings', 'failed', $e->getMessage());
                    $this->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
                break;

            default:
                $this->json(['success' => false, 'message' => 'Unknown job'], 404);
        }
    }
}
