<?php
/**
 * Daily mining payout cron job.
 *
 * Credits every user_mining position whose next_payout_at has elapsed,
 * advances it to the next payout date, and marks the position completed
 * once its duration has been fully paid out. Safe to run as often as
 * your host allows (hourly is a good default) — it only pays out
 * positions that are actually due.
 *
 * cPanel cron example (every hour):
 *   0 * * * * /usr/bin/php /home/USERNAME/public_html/cron/mining-payout.php >> /home/USERNAME/public_html/logs/cron.log 2>&1
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/config.php';

$summary = mining_process_payouts();
$releaseSummary = mining_process_releases();

$line = sprintf(
    '[%s] Mining payout run: %d position(s) paid, %d completed, %s total credited. %d release(s), %s total released to withdrawable wallets.',
    date('Y-m-d H:i:s'),
    $summary['processed'],
    $summary['completed'],
    money($summary['total_paid']),
    $releaseSummary['released'],
    money($releaseSummary['total_released'])
);

echo $line . PHP_EOL;
app_log('info', $line);
