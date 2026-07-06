<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\MiningPlan;
use App\Models\MiningPurchase;
use App\Models\Notification;
use App\Models\ReferralEarning;
use App\Models\Wallet;
use Exception;

class MiningService
{
    public static function purchase(int $userId, int $planId): array
    {
        $plan = MiningPlan::find($planId);
        if (!$plan || $plan['status'] !== 'active') {
            throw new Exception('This mining plan is not available.');
        }
        if (!MiningPlan::hasCapacity($plan)) {
            throw new Exception('This plan has reached its maximum number of users.');
        }
        if (Wallet::withdrawableBalance($userId) < (float) $plan['price']) {
            throw new Exception('Insufficient main wallet balance. Please deposit to continue.');
        }

        $db = App::db();
        $db->beginTransaction();
        try {
            Wallet::debit($userId, 'main', (float) $plan['price'], 'mining_purchase', 'Purchased ' . $plan['name']);

            $purchaseId = MiningPurchase::create([
                'user_id' => $userId,
                'plan_id' => $planId,
                'amount_invested' => $plan['price'],
                'daily_profit' => $plan['daily_profit'],
                'duration_days' => $plan['duration_days'],
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+' . $plan['duration_days'] . ' days')),
                'status' => 'active',
            ]);

            MiningPlan::incrementUsers($planId);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        ReferralEarning::distribute($userId, 'mining', (float) $plan['price']);
        Notification::send($userId, 'Mining Plan Activated', "You've activated the {$plan['name']} plan. Daily profit: " . money($plan['daily_profit']) . ".", 'success');

        return ['id' => $purchaseId];
    }

    /**
     * Runs from cron: pay out one day's profit to every purchase that
     * hasn't been paid in the last ~23h, then complete plans that have
     * reached their duration.
     */
    public static function runDailyPayouts(): array
    {
        $due = MiningPurchase::dueForPayout();
        $paid = 0;
        $completed = 0;

        foreach ($due as $purchase) {
            $db = App::db();
            $db->beginTransaction();
            try {
                Wallet::credit(
                    (int) $purchase['user_id'],
                    'mining',
                    (float) $purchase['daily_profit'],
                    'mining_profit',
                    'Daily mining profit'
                );

                $daysCompleted = (int) $purchase['days_completed'] + 1;
                $totalEarned = (float) $purchase['total_earned'] + (float) $purchase['daily_profit'];
                $isDone = $daysCompleted >= (int) $purchase['duration_days'];

                $db->update('mining_purchases', [
                    'days_completed' => $daysCompleted,
                    'total_earned' => $totalEarned,
                    'last_payout_at' => date('Y-m-d H:i:s'),
                    'status' => $isDone ? 'completed' : 'active',
                ], 'id = :id', ['id' => $purchase['id']]);

                $db->commit();
                $paid++;
                if ($isDone) {
                    $completed++;
                    Notification::send((int) $purchase['user_id'], 'Mining Plan Completed', 'Your mining plan has completed its full duration. Total earned: ' . money($totalEarned) . '.', 'success');
                }
            } catch (Exception $e) {
                $db->rollBack();
                error_log('Mining payout failed for purchase #' . $purchase['id'] . ': ' . $e->getMessage());
            }
        }

        return ['paid' => $paid, 'completed' => $completed];
    }

    public static function forceComplete(int $purchaseId): void
    {
        $purchase = MiningPurchase::find($purchaseId);
        if (!$purchase) {
            throw new Exception('Mining purchase not found.');
        }
        MiningPurchase::updateById($purchaseId, ['status' => 'completed']);
        Notification::send((int) $purchase['user_id'], 'Mining Plan Completed', 'Your mining plan was marked completed by the admin.', 'info');
    }
}
