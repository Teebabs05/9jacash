<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ReferralEarning extends Model
{
    protected static string $table = 'referral_earnings';

    /**
     * Walk the referred_by chain crediting upline commissions for a
     * deposit/mining/task event. Percentages come from settings so admins
     * can tune (or zero-out) each level without a code change; level is
     * uncapped in code, it simply stops once a level's percent is 0/unset.
     */
    public static function distribute(int $fromUserId, string $source, float $baseAmount): void
    {
        if ($baseAmount <= 0) {
            return;
        }

        $currentUserId = $fromUserId;
        $level = 1;

        while ($level <= 10) {
            $user = User::find($currentUserId);
            $referrerId = $user['referred_by'] ?? null;
            if (!$referrerId) {
                break;
            }

            $percent = (float) setting(self::percentKey($level, $source), 0);
            if ($percent > 0) {
                $amount = round($baseAmount * $percent / 100, 2);
                if ($amount > 0) {
                    Wallet::credit((int) $referrerId, 'referral', $amount, 'referral_bonus', ucfirst($source) . " referral bonus (level {$level})");
                    static::create([
                        'user_id' => $referrerId,
                        'from_user_id' => $fromUserId,
                        'source' => $source,
                        'level' => $level,
                        'amount' => $amount,
                    ]);
                    Notification::send((int) $referrerId, 'Referral Bonus', "You earned " . money($amount) . " referral bonus (level {$level}).", 'success');
                }
            }

            $currentUserId = (int) $referrerId;
            $level++;
        }
    }

    private static function percentKey(int $level, string $source): string
    {
        if ($level === 1) {
            return "referral_{$source}_percent";
        }
        return "referral_level_{$level}_percent";
    }

    public static function forUser(int $userId): array
    {
        return static::db()->fetchAll(
            "SELECT referral_earnings.*, users.username as from_username FROM referral_earnings
             JOIN users ON users.id = referral_earnings.from_user_id
             WHERE referral_earnings.user_id = :uid
             ORDER BY referral_earnings.created_at DESC LIMIT 100",
            ['uid' => $userId]
        );
    }

    public static function totalForUser(int $userId): float
    {
        $row = static::db()->fetch(
            'SELECT COALESCE(SUM(amount),0) s FROM referral_earnings WHERE user_id = :uid',
            ['uid' => $userId]
        );
        return (float) $row['s'];
    }

    public static function leaderboard(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        return static::db()->fetchAll(
            "SELECT users.username, users.full_name, users.avatar, COUNT(DISTINCT users2.id) as referral_count,
                    COALESCE(SUM(referral_earnings.amount),0) as total_earned
             FROM users
             LEFT JOIN users users2 ON users2.referred_by = users.id
             LEFT JOIN referral_earnings ON referral_earnings.user_id = users.id
             WHERE users.role = 'user'
             GROUP BY users.id
             ORDER BY total_earned DESC
             LIMIT {$limit}"
        );
    }
}
