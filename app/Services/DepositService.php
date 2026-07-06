<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Deposit;
use App\Models\Notification;
use App\Models\ReferralEarning;
use App\Models\Wallet;
use Exception;

/**
 * Single place that finalizes an approved deposit (manual admin approval,
 * PayVessel webhook, or PayVessel browser callback) so the wallet credit
 * + referral bonus + notification logic only lives in one spot.
 */
class DepositService
{
    public static function confirmPayvesselDeposit(string $reference): void
    {
        $deposit = Deposit::findByReference($reference);
        if (!$deposit) {
            throw new Exception('Deposit not found.');
        }
        if ($deposit['status'] !== 'pending') {
            return; // already processed — idempotent
        }

        $verification = PayVesselService::verifyTransaction($reference);
        if (empty($verification['status'])) {
            throw new Exception('Payment not yet confirmed by PayVessel.');
        }

        self::approve((int) $deposit['id'], null);
    }

    /**
     * @param int|null $adminId null when approved automatically by a gateway webhook
     */
    public static function approve(int $depositId, ?int $adminId, ?string $note = null): void
    {
        $db = App::db();
        $deposit = Deposit::find($depositId);
        if (!$deposit || $deposit['status'] !== 'pending') {
            throw new Exception('This deposit has already been processed.');
        }

        $db->beginTransaction();
        try {
            $db->update('deposits', [
                'status' => 'approved',
                'admin_note' => $note,
                'approved_by' => $adminId,
            ], 'id = :id', ['id' => $depositId]);

            Wallet::credit((int) $deposit['user_id'], 'main', (float) $deposit['amount'], 'deposit', 'Deposit approved (' . $deposit['reference'] . ')', $deposit['reference']);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        ReferralEarning::distribute((int) $deposit['user_id'], 'deposit', (float) $deposit['amount']);
        Notification::send((int) $deposit['user_id'], 'Deposit Approved', 'Your deposit of ' . money($deposit['amount']) . ' has been approved and credited to your wallet.', 'success');
    }

    public static function reject(int $depositId, int $adminId, string $note): void
    {
        $deposit = Deposit::find($depositId);
        if (!$deposit || $deposit['status'] !== 'pending') {
            throw new Exception('This deposit has already been processed.');
        }

        App::db()->update('deposits', [
            'status' => 'rejected',
            'admin_note' => $note,
            'approved_by' => $adminId,
        ], 'id = :id', ['id' => $depositId]);

        Notification::send((int) $deposit['user_id'], 'Deposit Rejected', 'Your deposit of ' . money($deposit['amount']) . ' was rejected. Reason: ' . $note, 'error');
    }
}
