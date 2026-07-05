<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\App;
use Exception;

/**
 * Each user has one wallet row split into 5 purses. Only `main_balance`
 * is directly withdrawable; earnings in the other purses are transferred
 * into main by the user (transferToMain) before cashing out.
 */
class Wallet extends Model
{
    protected static string $table = 'wallets';

    private const PURSES = ['main', 'bonus', 'referral', 'mining', 'task'];

    public static function forUser(int $userId): array
    {
        $wallet = static::findBy('user_id', $userId);
        if (!$wallet) {
            $id = static::create(['user_id' => $userId]);
            $wallet = static::find($id);
        }
        return $wallet;
    }

    public static function withdrawableBalance(int $userId): float
    {
        $wallet = self::forUser($userId);
        return (float) $wallet['main_balance'];
    }

    /**
     * Credit a purse and write an immutable ledger row. Runs inside the
     * caller's transaction if one is already open.
     */
    public static function credit(int $userId, string $purse, float $amount, string $category, string $description = '', ?string $reference = null): void
    {
        self::assertPurse($purse);
        if ($amount <= 0) {
            throw new Exception('Credit amount must be positive.');
        }

        $db = App::db();
        $wallet = self::forUser($userId);
        $column = $purse . '_balance';
        $newBalance = (float) $wallet[$column] + $amount;

        $update = [$column => $newBalance];
        if (in_array($category, ['mining_profit', 'task_reward', 'referral_bonus', 'checkin', 'spin', 'signup_bonus'], true)) {
            $update['total_earned'] = (float) $wallet['total_earned'] + $amount;
        }

        $db->update('wallets', $update, 'user_id = :uid', ['uid' => $userId]);

        Transaction::create([
            'user_id' => $userId,
            'wallet_type' => $purse,
            'type' => 'credit',
            'category' => $category,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference' => $reference,
            'description' => $description,
            'status' => 'completed',
        ]);
    }

    public static function debit(int $userId, string $purse, float $amount, string $category, string $description = '', ?string $reference = null): void
    {
        self::assertPurse($purse);
        if ($amount <= 0) {
            throw new Exception('Debit amount must be positive.');
        }

        $db = App::db();
        $wallet = self::forUser($userId);
        $column = $purse . '_balance';

        if ((float) $wallet[$column] < $amount) {
            throw new Exception('Insufficient balance.');
        }

        $newBalance = (float) $wallet[$column] - $amount;
        $db->update('wallets', [$column => $newBalance], 'user_id = :uid', ['uid' => $userId]);

        Transaction::create([
            'user_id' => $userId,
            'wallet_type' => $purse,
            'type' => 'debit',
            'category' => $category,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference' => $reference,
            'description' => $description,
            'status' => 'completed',
        ]);
    }

    public static function transferToMain(int $userId, string $fromPurse, float $amount): void
    {
        if ($fromPurse === 'main') {
            throw new Exception('Already in main wallet.');
        }
        $db = App::db();
        $db->beginTransaction();
        try {
            self::debit($userId, $fromPurse, $amount, 'wallet_transfer', "Transferred from {$fromPurse} wallet to main wallet");
            self::credit($userId, 'main', $amount, 'wallet_transfer', "Transferred from {$fromPurse} wallet");
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function assertPurse(string $purse): void
    {
        if (!in_array($purse, self::PURSES, true)) {
            throw new Exception("Invalid wallet purse: {$purse}");
        }
    }
}
