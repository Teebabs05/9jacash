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
if (!function_exists('mining_purchase_plan')) {
    function mining_purchase_plan(int $userId, int $planId): array
    {
        $pdo = db();

        $stmt = $pdo->prepare("SELECT * FROM mining_plans WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            return ['success' => false, 'message' => 'This mining plan is not available.'];
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
            'Invested in ' . $plan['name'] . ' mining plan'
        );

        try {
            $startedAt = date('Y-m-d H:i:s');
            $nextPayoutAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $endsAt = date('Y-m-d H:i:s', strtotime('+' . (int) $plan['duration_days'] . ' days'));

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

        notify_user($userId, 'Mining Plan Activated', "You've successfully invested " . money($plan['price']) . " in the {$plan['name']} plan.", NOTIFY_TYPE_MINING);
        log_activity($userId, null, 'mining_purchase', "Invested in {$plan['name']} (" . money($plan['price']) . ')');

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
            "SELECT um.*, mp.name AS plan_name, mp.daily_return AS plan_daily_return
             FROM user_mining um
             INNER JOIN mining_plans mp ON mp.id = um.plan_id
             WHERE um.status = 'active' AND um.next_payout_at <= NOW()"
        );
        $due = $stmt->fetchAll();

        foreach ($due as $position) {
            $dailyReturn = (float) $position['plan_daily_return'];

            // wallet_credit() runs its own top-level transaction (PDO/MySQL
            // doesn't support nesting), so the ledger credit is committed
            // first; if the bookkeeping below fails we compensate with a
            // debit rather than wrapping everything in one transaction.
            try {
                wallet_credit(
                    (int) $position['user_id'],
                    WALLET_MINING,
                    $dailyReturn,
                    LEDGER_SOURCE_MINING,
                    'Daily mining payout - ' . $position['plan_name']
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

                $updateStmt = $pdo->prepare(
                    'UPDATE user_mining SET total_earned = total_earned + ?, next_payout_at = ?, status = ? WHERE id = ?'
                );
                $updateStmt->execute([$dailyReturn, $isFinished ? $position['next_payout_at'] : $nextPayoutAt, $newStatus, $position['id']]);

                $processed++;
                $totalPaid += $dailyReturn;
                if ($isFinished) {
                    $completed++;
                    notify_user((int) $position['user_id'], 'Mining Plan Completed', "Your {$position['plan_name']} mining plan has completed its cycle.", NOTIFY_TYPE_MINING);
                } else {
                    notify_user((int) $position['user_id'], 'Mining Payout Received', 'You received ' . money($dailyReturn) . " from your {$position['plan_name']} plan.", NOTIFY_TYPE_MINING);
                }
            } catch (Throwable $e) {
                app_log('error', 'Mining payout bookkeeping failed for position #' . $position['id'] . ', reverting credit: ' . $e->getMessage());
                wallet_debit((int) $position['user_id'], WALLET_MINING, $dailyReturn, LEDGER_SOURCE_MINING, 'Reverted mining payout due to a processing error');
            }
        }

        return ['processed' => $processed, 'completed' => $completed, 'total_paid' => $totalPaid];
    }
}
