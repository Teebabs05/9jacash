<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Notification;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Exception;

/**
 * Withdrawal funds are debited from the main wallet the moment a request
 * is submitted (so users can never request more than they have and
 * balances can't go negative while a request is pending). Rejecting a
 * request refunds the full amount back to main; approving just marks it
 * processed since the payout happens off-platform via bank transfer.
 */
class WithdrawalService
{
    public static function request(int $userId, float $amount, array $bankAccount): array
    {
        $min = (float) setting('min_withdrawal', 1000);
        $max = (float) setting('max_withdrawal', 500000);
        $dailyLimit = (int) setting('daily_withdrawal_limit', 1);
        $chargePercent = (float) setting('withdrawal_charge_percent', 0);

        if ($amount < $min || $amount > $max) {
            throw new Exception('Withdrawal amount must be between ' . money($min) . ' and ' . money($max) . '.');
        }

        $todayCount = App::db()->fetch(
            "SELECT COUNT(*) c FROM withdrawals WHERE user_id = :uid AND DATE(created_at) = CURDATE() AND status != 'rejected'",
            ['uid' => $userId]
        );
        if ((int) ($todayCount['c'] ?? 0) >= $dailyLimit) {
            throw new Exception("You have reached your daily withdrawal limit ({$dailyLimit} per day).");
        }

        if (Wallet::withdrawableBalance($userId) < $amount) {
            throw new Exception('Insufficient main wallet balance.');
        }

        $charge = round($amount * $chargePercent / 100, 2);
        $net = $amount - $charge;
        $reference = generate_reference('WTH');

        $db = App::db();
        $db->beginTransaction();
        try {
            Wallet::debit($userId, 'main', $amount, 'withdrawal', "Withdrawal request ({$reference})", $reference);

            $id = Withdrawal::create([
                'user_id' => $userId,
                'amount' => $amount,
                'charge' => $charge,
                'net_amount' => $net,
                'bank_name' => $bankAccount['bank_name'],
                'account_number' => $bankAccount['account_number'],
                'account_name' => $bankAccount['account_name'],
                'reference' => $reference,
                'status' => 'pending',
            ]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        Notification::send($userId, 'Withdrawal Requested', 'Your withdrawal request of ' . money($amount) . ' is being reviewed.', 'info');

        return ['id' => $id, 'reference' => $reference];
    }

    public static function approve(int $withdrawalId, int $adminId, ?string $note = null): void
    {
        $withdrawal = Withdrawal::find($withdrawalId);
        if (!$withdrawal || $withdrawal['status'] !== 'pending') {
            throw new Exception('This withdrawal has already been processed.');
        }

        App::db()->update('withdrawals', [
            'status' => 'approved',
            'admin_note' => $note,
            'processed_by' => $adminId,
        ], 'id = :id', ['id' => $withdrawalId]);

        Notification::send((int) $withdrawal['user_id'], 'Withdrawal Approved', 'Your withdrawal of ' . money($withdrawal['net_amount']) . ' has been approved and sent to your bank account.', 'success');
    }

    public static function reject(int $withdrawalId, int $adminId, string $note): void
    {
        $withdrawal = Withdrawal::find($withdrawalId);
        if (!$withdrawal || $withdrawal['status'] !== 'pending') {
            throw new Exception('This withdrawal has already been processed.');
        }

        $db = App::db();
        $db->beginTransaction();
        try {
            $db->update('withdrawals', [
                'status' => 'rejected',
                'admin_note' => $note,
                'processed_by' => $adminId,
            ], 'id = :id', ['id' => $withdrawalId]);

            Wallet::credit((int) $withdrawal['user_id'], 'main', (float) $withdrawal['amount'], 'withdrawal_refund', 'Withdrawal rejected — funds returned (' . $withdrawal['reference'] . ')', $withdrawal['reference']);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        Notification::send((int) $withdrawal['user_id'], 'Withdrawal Rejected', 'Your withdrawal request was rejected: ' . $note . '. The funds have been returned to your main wallet.', 'error');
    }
}
