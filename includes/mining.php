<?php
/**
 * Core mining primitives: plan purchase, pause/resume, and the daily
 * payout engine shared by the web UI and cron/mining-payout.php.
 */

declare(strict_types=1);

/**
 * Purchase a mining plan for a user: debits the main wallet and opens
 * a new active user_mining position. Returns ['success' => bool, 'message' => string].
 */
/**
 * Cycle-day options a plan can offer. mining_plans.available_cycles is
 * a comma-separated subset of this list (admin-configurable per plan).
 */
if (!defined('MINING_CYCLE_OPTIONS')) {
    define('MINING_CYCLE_OPTIONS', [7, 14]);
}

if (!function_exists('mining_plan_cycles')) {
    function mining_plan_cycles(array $plan): array
    {
        $cycles = array_filter(array_map('intval', explode(',', (string) ($plan['available_cycles'] ?? ''))));
        $cycles = array_values(array_intersect(MINING_CYCLE_OPTIONS, $cycles));

        return $cycles ?: [(int) ($plan['duration_days'] ?? 30)];
    }
}

/**
 * A user's effective mining payout release schedule: their own override
 * if set, otherwise the site-wide default. This controls when accrued
 * daily earnings move from the locked pending wallet into the
 * withdrawable mining wallet - it never changes how much is earned.
 */
if (!function_exists('mining_effective_payout_schedule')) {
    function mining_effective_payout_schedule(string $userPayoutSchedule): string
    {
        if ($userPayoutSchedule !== PAYOUT_SCHEDULE_DEFAULT && in_array($userPayoutSchedule, PAYOUT_SCHEDULES, true)) {
            return $userPayoutSchedule;
        }

        $global = (string) get_setting('mining_payout_schedule', PAYOUT_SCHEDULE_DAILY);

        return in_array($global, PAYOUT_SCHEDULES, true) ? $global : PAYOUT_SCHEDULE_DAILY;
    }
}

/**
 * Days between releases for the periodic schedules. Daily releases
 * immediately (no interval) and cycle_end releases once at completion,
 * so neither has a recurring interval.
 */
if (!function_exists('mining_release_interval_days')) {
    function mining_release_interval_days(string $schedule): ?int
    {
        return match ($schedule) {
            PAYOUT_SCHEDULE_WEEKLY => 7,
            PAYOUT_SCHEDULE_BIWEEKLY => 14,
            default => null,
        };
    }
}

if (!function_exists('mining_purchase_plan')) {
    function mining_purchase_plan(int $userId, int $planId, int $cycleDays): array
    {
        $pdo = db();

        $stmt = $pdo->prepare("SELECT * FROM mining_plans WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            return ['success' => false, 'message' => 'This mining plan is not available.'];
        }

        if (!in_array($cycleDays, mining_plan_cycles($plan), true)) {
            return ['success' => false, 'message' => 'Please choose a valid mining cycle for this plan.'];
        }

        $wallet = get_wallet($userId);
        if ((float) $wallet['main_balance'] < (float) $plan['price']) {
            return ['success' => false, 'message' => 'Insufficient main wallet balance. Please deposit funds first.'];
        }

        // wallet_debit() and the INSERT below each need their own top-level
        // transaction (PDO/MySQL doesn't support nesting), so debit first and
        // compensate with a refund credit if creating the position fails.
        wallet_debit(
            $userId,
            WALLET_MAIN,
            (float) $plan['price'],
            LEDGER_SOURCE_MINING,
            'Invested in ' . $plan['name'] . ' mining plan (' . $cycleDays . '-day cycle)'
        );

        try {
            $startedAt = date('Y-m-d H:i:s');
            $nextPayoutAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $endsAt = date('Y-m-d H:i:s', strtotime('+' . $cycleDays . ' days'));

            $stmt = $pdo->prepare(
                'INSERT INTO user_mining (user_id, plan_id, amount_invested, total_earned, started_at, next_payout_at, ends_at, status, created_at)
                 VALUES (?, ?, ?, 0.00, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$userId, $planId, $plan['price'], $startedAt, $nextPayoutAt, $endsAt, MINING_STATUS_ACTIVE]);
        } catch (Throwable $e) {
            app_log('error', 'Mining purchase failed, refunding: ' . $e->getMessage(), ['user_id' => $userId, 'plan_id' => $planId]);
            wallet_credit($userId, WALLET_MAIN, (float) $plan['price'], LEDGER_SOURCE_MINING, 'Refund: ' . $plan['name'] . ' mining plan could not be activated');
            return ['success' => false, 'message' => 'Could not process your mining investment. Your wallet has not been charged.'];
        }

        notify_user($userId, 'Mining Plan Activated', "You've successfully invested " . money($plan['price']) . " in the {$plan['name']} plan ({$cycleDays}-day cycle).", NOTIFY_TYPE_MINING);
        log_activity($userId, null, 'mining_purchase', "Invested in {$plan['name']} (" . money($plan['price']) . ", {$cycleDays}-day cycle)");

        return ['success' => true, 'message' => "You've successfully invested in the {$plan['name']} plan!"];
    }
}

/**
 * Toggle an active/paused mining position owned by the given user.
 */
if (!function_exists('mining_toggle_status')) {
    function mining_toggle_status(int $userMiningId, int $userId, string $action): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM user_mining WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$userMiningId, $userId]);
        $position = $stmt->fetch();

        if (!$position) {
            return ['success' => false, 'message' => 'Mining position not found.'];
        }

        if ($action === 'pause' && $position['status'] === MINING_STATUS_ACTIVE) {
            $pdo->prepare('UPDATE user_mining SET status = ? WHERE id = ?')->execute([MINING_STATUS_PAUSED, $userMiningId]);
            return ['success' => true, 'message' => 'Mining position paused. Daily payouts are on hold.'];
        }

        if ($action === 'resume' && $position['status'] === MINING_STATUS_PAUSED) {
            // Push the next payout a full day ahead so paused time isn't back-paid.
            $nextPayoutAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $pdo->prepare('UPDATE user_mining SET status = ?, next_payout_at = ? WHERE id = ?')
                ->execute([MINING_STATUS_ACTIVE, $nextPayoutAt, $userMiningId]);
            return ['success' => true, 'message' => 'Mining position resumed.'];
        }

        return ['success' => false, 'message' => 'That action cannot be applied to this mining position.'];
    }
}

/**
 * Process every due mining payout across all users. Safe to run as often
 * as desired (e.g. hourly cron) — only positions whose next_payout_at has
 * elapsed are credited. Returns a summary array for logging.
 */
if (!function_exists('mining_process_payouts')) {
    function mining_process_payouts(): array
    {
        $pdo = db();
        $processed = 0;
        $completed = 0;
        $totalPaid = 0.0;

        $stmt = $pdo->query(
            "SELECT um.*, mp.name AS plan_name, mp.daily_return AS plan_daily_return, u.payout_schedule AS user_payout_schedule
             FROM user_mining um
             INNER JOIN mining_plans mp ON mp.id = um.plan_id
             INNER JOIN users u ON u.id = um.user_id
             WHERE um.status = 'active' AND um.next_payout_at <= NOW()"
        );
        $due = $stmt->fetchAll();

        foreach ($due as $position) {
            $dailyReturn = (float) $position['plan_daily_return'];
            $schedule = mining_effective_payout_schedule((string) $position['user_payout_schedule']);
            // Daily schedule pays straight into the withdrawable mining
            // wallet like before; every other schedule accrues into the
            // locked pending wallet until mining_process_releases() moves
            // it across on the configured cadence.
            $creditWallet = $schedule === PAYOUT_SCHEDULE_DAILY ? WALLET_MINING : WALLET_PENDING;

            // wallet_credit() runs its own top-level transaction (PDO/MySQL
            // doesn't support nesting), so the ledger credit is committed
            // first; if the bookkeeping below fails we compensate with a
            // debit rather than wrapping everything in one transaction.
            try {
                wallet_credit(
                    (int) $position['user_id'],
                    $creditWallet,
                    $dailyReturn,
                    LEDGER_SOURCE_MINING,
                    ($creditWallet === WALLET_PENDING ? 'Mining earning accrued (pending release) - ' : 'Daily mining payout - ') . $position['plan_name']
                );
            } catch (Throwable $e) {
                app_log('error', 'Mining payout credit failed for position #' . $position['id'] . ': ' . $e->getMessage());
                continue;
            }

            try {
                $logStmt = $pdo->prepare(
                    'INSERT INTO mining_logs (user_mining_id, user_id, amount, created_at) VALUES (?, ?, ?, NOW())'
                );
                $logStmt->execute([$position['id'], $position['user_id'], $dailyReturn]);

                $nextPayoutAt = date('Y-m-d H:i:s', strtotime((string) $position['next_payout_at'] . ' +1 day'));
                $isFinished = strtotime($nextPayoutAt) >= strtotime((string) $position['ends_at']);
                $newStatus = $isFinished ? MINING_STATUS_COMPLETED : MINING_STATUS_ACTIVE;

                // released_earned tracks how much of total_earned has
                // actually reached the withdrawable mining wallet so far,
                // regardless of which schedule paid it out - keeping it in
                // sync here (not just in mining_process_releases()) means a
                // mid-course schedule change never double-releases funds
                // that were already paid out immediately under 'daily'.
                $releasedIncrement = $creditWallet === WALLET_MINING ? $dailyReturn : 0.0;

                $updateStmt = $pdo->prepare(
                    'UPDATE user_mining SET total_earned = total_earned + ?, released_earned = released_earned + ?, next_payout_at = ?, status = ? WHERE id = ?'
                );
                $updateStmt->execute([$dailyReturn, $releasedIncrement, $isFinished ? $position['next_payout_at'] : $nextPayoutAt, $newStatus, $position['id']]);

                $processed++;
                $totalPaid += $dailyReturn;
                if ($isFinished) {
                    $completed++;
                    notify_user((int) $position['user_id'], 'Mining Plan Completed', "Your {$position['plan_name']} mining plan has completed its cycle.", NOTIFY_TYPE_MINING);
                } elseif ($creditWallet === WALLET_MINING) {
                    notify_user((int) $position['user_id'], 'Mining Payout Received', 'You received ' . money($dailyReturn) . " from your {$position['plan_name']} plan.", NOTIFY_TYPE_MINING);
                }
            } catch (Throwable $e) {
                app_log('error', 'Mining payout bookkeeping failed for position #' . $position['id'] . ', reverting credit: ' . $e->getMessage());
                wallet_debit((int) $position['user_id'], $creditWallet, $dailyReturn, LEDGER_SOURCE_MINING, 'Reverted mining payout due to a processing error');
            }
        }

        return ['processed' => $processed, 'completed' => $completed, 'total_paid' => $totalPaid];
    }
}

/**
 * Release accrued mining earnings sitting in the pending wallet into the
 * withdrawable mining wallet for users on a weekly/every-2-weeks/cycle-end
 * schedule. Safe to run as often as mining_process_payouts() (it only
 * acts once a position's next_release_at has elapsed, or once a
 * cycle_end position completes).
 */
if (!function_exists('mining_process_releases')) {
    function mining_process_releases(): array
    {
        $pdo = db();
        $released = 0;
        $totalReleased = 0.0;

        $stmt = $pdo->query(
            "SELECT um.*, mp.name AS plan_name, u.payout_schedule AS user_payout_schedule
             FROM user_mining um
             INNER JOIN mining_plans mp ON mp.id = um.plan_id
             INNER JOIN users u ON u.id = um.user_id
             WHERE um.status IN ('active','completed') AND um.released_earned < um.total_earned"
        );
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $position) {
            $schedule = mining_effective_payout_schedule((string) $position['user_payout_schedule']);

            if ($schedule === PAYOUT_SCHEDULE_DAILY) {
                continue;
            }

            $intervalDays = mining_release_interval_days($schedule);
            $dueNow = false;

            if ($schedule === PAYOUT_SCHEDULE_CYCLE_END) {
                $dueNow = $position['status'] === MINING_STATUS_COMPLETED;
            } elseif ($intervalDays !== null) {
                if ($position['next_release_at'] === null) {
                    // First time we've seen this position under a periodic
                    // schedule - start the clock, nothing to release yet.
                    $pdo->prepare('UPDATE user_mining SET next_release_at = ? WHERE id = ?')
                        ->execute([date('Y-m-d H:i:s', strtotime('+' . $intervalDays . ' days')), $position['id']]);
                    continue;
                }

                $dueNow = strtotime((string) $position['next_release_at']) <= time();
            }

            if (!$dueNow) {
                continue;
            }

            $releasable = round((float) $position['total_earned'] - (float) $position['released_earned'], 2);
            if ($releasable <= 0.01) {
                if ($intervalDays !== null) {
                    $pdo->prepare('UPDATE user_mining SET next_release_at = ? WHERE id = ?')
                        ->execute([date('Y-m-d H:i:s', strtotime('+' . $intervalDays . ' days')), $position['id']]);
                }
                continue;
            }

            try {
                wallet_debit(
                    (int) $position['user_id'],
                    WALLET_PENDING,
                    $releasable,
                    LEDGER_SOURCE_MINING,
                    'Mining earning released to wallet - ' . $position['plan_name']
                );
                wallet_credit(
                    (int) $position['user_id'],
                    WALLET_MINING,
                    $releasable,
                    LEDGER_SOURCE_MINING,
                    'Mining earning released to wallet - ' . $position['plan_name']
                );
            } catch (Throwable $e) {
                app_log('error', 'Mining release failed for position #' . $position['id'] . ': ' . $e->getMessage());
                continue;
            }

            $updateStmt = $pdo->prepare('UPDATE user_mining SET released_earned = total_earned' . ($intervalDays !== null ? ', next_release_at = ?' : '') . ' WHERE id = ?');
            $intervalDays !== null
                ? $updateStmt->execute([date('Y-m-d H:i:s', strtotime('+' . $intervalDays . ' days')), $position['id']])
                : $updateStmt->execute([$position['id']]);

            $released++;
            $totalReleased += $releasable;
            notify_user((int) $position['user_id'], 'Mining Earnings Released', money($releasable) . " from your {$position['plan_name']} plan is now available in your wallet for withdrawal.", NOTIFY_TYPE_MINING);
        }

        return ['released' => $released, 'total_released' => $totalReleased];
    }
}
