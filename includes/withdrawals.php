<?php
/**
 * Withdrawal primitives: request creation (funds are debited immediately
 * on request, drawn from the user's combined wallet balance), and the
 * admin approve/reject queue.
 */

declare(strict_types=1);

if (!function_exists('withdrawals_today_count')) {
    function withdrawals_today_count(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) AS c FROM withdrawals WHERE user_id = ? AND created_at >= CURDATE()');
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['c'];
    }
}

if (!function_exists('withdrawals_calculate_charge')) {
    function withdrawals_calculate_charge(float $amount): float
    {
        $percent = (float) get_setting('withdrawal_charge_percent', 2);
        return round($amount * $percent / 100, 2);
    }
}

/**
 * Create a withdrawal request: validates limits and balance, debits the
 * user's combined wallet balance immediately, and opens a pending
 * withdrawals row for admin review/payout.
 */
if (!function_exists('withdrawals_create')) {
    function withdrawals_create(int $userId, int $bankAccountId, float $amount): array
    {
        $minWithdrawal = (float) get_setting('min_withdrawal', 1000);
        $maxWithdrawal = (float) get_setting('max_withdrawal', 500000);
        $dailyLimit = (int) get_setting('daily_withdrawal_limit', 1);

        if ($amount < $minWithdrawal || $amount > $maxWithdrawal) {
            return ['success' => false, 'message' => 'Amount must be between ' . money($minWithdrawal) . ' and ' . money($maxWithdrawal) . '.'];
        }

        if (withdrawals_today_count($userId) >= $dailyLimit) {
            return ['success' => false, 'message' => 'You have reached your daily withdrawal request limit. Please try again tomorrow.'];
        }

        if (wallet_total_balance($userId) < $amount) {
            return ['success' => false, 'message' => 'Insufficient wallet balance for this withdrawal.'];
        }

        $stmt = db()->prepare('SELECT * FROM bank_accounts WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$bankAccountId, $userId]);
        $account = $stmt->fetch();

        if (!$account) {
            return ['success' => false, 'message' => 'Please select a valid withdrawal account.'];
        }

        $charge = withdrawals_calculate_charge($amount);
        $netAmount = $amount - $charge;
        $reference = 'WD-' . strtoupper(bin2hex(random_bytes(6)));

        $accountDetails = ($account['type'] === 'usdt'
            ? "USDT ({$account['network']}): {$account['usdt_address']}"
            : "{$account['bank_name']} - {$account['account_number']} ({$account['account_name']})")
            . " [Ref: {$reference}]";

        try {
            wallet_debit_combined($userId, $amount, LEDGER_SOURCE_WITHDRAWAL, "Withdrawal request ({$reference})", $reference);
        } catch (Throwable $e) {
            app_log('error', 'Withdrawal debit failed: ' . $e->getMessage(), ['user_id' => $userId]);
            return ['success' => false, 'message' => 'Could not process your withdrawal. Please try again.'];
        }

        $stmt = db()->prepare(
            'INSERT INTO withdrawals (user_id, bank_account_id, method, amount, charge, net_amount, account_details, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $bankAccountId, $account['type'], $amount, $charge, $netAmount, $accountDetails, STATUS_PENDING]);
        $withdrawalId = (int) db()->lastInsertId();

        notify_user($userId, 'Withdrawal Requested', 'Your withdrawal request of ' . money($amount) . ' has been submitted and is being processed.', NOTIFY_TYPE_WITHDRAWAL);
        log_activity($userId, null, 'withdrawal_requested', "Requested withdrawal of " . money($amount) . " (ref {$reference})");

        return ['success' => true, 'message' => 'Withdrawal request submitted successfully.', 'id' => $withdrawalId];
    }
}

/**
 * Approve a withdrawal: funds were already debited at request time, so
 * this simply marks the request as paid out (the actual bank/USDT
 * transfer happens outside the system, performed by the admin/finance
 * team) and notifies the user.
 */
if (!function_exists('withdrawals_approve')) {
    function withdrawals_approve(int $withdrawalId, ?int $adminId = null): array
    {
        $stmt = db()->prepare('SELECT * FROM withdrawals WHERE id = ? LIMIT 1');
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch();

        if (!$withdrawal) {
            return ['success' => false, 'message' => 'Withdrawal not found.'];
        }

        if ($withdrawal['status'] !== STATUS_PENDING) {
            return ['success' => false, 'message' => 'This withdrawal has already been reviewed.'];
        }

        db()->prepare("UPDATE withdrawals SET status = 'approved', processed_at = NOW() WHERE id = ?")->execute([$withdrawalId]);

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$withdrawal['user_id']]);
        $user = $stmt->fetch();

        notify_user((int) $withdrawal['user_id'], 'Withdrawal Approved', 'Your withdrawal of ' . money($withdrawal['net_amount']) . ' has been approved and sent.', NOTIFY_TYPE_WITHDRAWAL);
        if ($user) {
            Mailer::sendWithdrawalEmail($user['email'], $user['full_name'], (float) $withdrawal['net_amount'], 'approved');
        }

        log_activity((int) $withdrawal['user_id'], $adminId, 'withdrawal_approved', "Withdrawal #{$withdrawalId} approved (" . money($withdrawal['net_amount']) . ')');

        return ['success' => true, 'message' => 'Withdrawal approved.'];
    }
}

/**
 * Reject a pending withdrawal and refund the full requested amount
 * (pre-charge) back to the user's main wallet.
 */
if (!function_exists('withdrawals_reject')) {
    function withdrawals_reject(int $withdrawalId, ?int $adminId, string $note = ''): array
    {
        $stmt = db()->prepare('SELECT * FROM withdrawals WHERE id = ? LIMIT 1');
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch();

        if (!$withdrawal) {
            return ['success' => false, 'message' => 'Withdrawal not found.'];
        }

        if ($withdrawal['status'] !== STATUS_PENDING) {
            return ['success' => false, 'message' => 'This withdrawal has already been reviewed.'];
        }

        db()->prepare("UPDATE withdrawals SET status = 'rejected', admin_note = ?, processed_at = NOW() WHERE id = ?")
            ->execute([$note, $withdrawalId]);

        wallet_credit(
            (int) $withdrawal['user_id'],
            WALLET_MAIN,
            (float) $withdrawal['amount'],
            LEDGER_SOURCE_WITHDRAWAL,
            'Withdrawal rejected - refund'
        );

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$withdrawal['user_id']]);
        $user = $stmt->fetch();

        notify_user((int) $withdrawal['user_id'], 'Withdrawal Rejected', 'Your withdrawal of ' . money($withdrawal['amount']) . ' was rejected and refunded to your main wallet.' . ($note ? " Reason: {$note}" : ''), NOTIFY_TYPE_WITHDRAWAL);
        if ($user) {
            Mailer::sendWithdrawalEmail($user['email'], $user['full_name'], (float) $withdrawal['amount'], 'rejected');
        }

        log_activity((int) $withdrawal['user_id'], $adminId, 'withdrawal_rejected', "Withdrawal #{$withdrawalId} rejected and refunded");

        return ['success' => true, 'message' => 'Withdrawal rejected and refunded.'];
    }
}
