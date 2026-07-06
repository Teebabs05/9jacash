<?php
/**
 * Referral bonus crediting. The `referrals` table already records the
 * upline chain (up to referral_max_levels) for every user at
 * registration time (see Auth::register()); this file is what actually
 * pays that chain out whenever a referred user's deposit is approved.
 */

declare(strict_types=1);

/**
 * Credit every upline referrer of $depositUserId a percentage of an
 * approved deposit, per level (site_settings: referral_level_N_percent).
 * Safe to call even if the user has no referrer (no-op).
 */
if (!function_exists('referrals_process_deposit_bonus')) {
    function referrals_process_deposit_bonus(int $depositUserId, float $depositAmount): void
    {
        $stmt = db()->prepare('SELECT user_id, level FROM referrals WHERE referred_id = ? ORDER BY level ASC');
        $stmt->execute([$depositUserId]);
        $upline = $stmt->fetchAll();

        if (!$upline) {
            return;
        }

        $stmt = db()->prepare('SELECT username, full_name FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$depositUserId]);
        $depositor = $stmt->fetch();
        $depositorName = $depositor['full_name'] ?? 'a referred user';

        foreach ($upline as $row) {
            $level = (int) $row['level'];
            $referrerId = (int) $row['user_id'];
            $percent = (float) get_setting("referral_level_{$level}_percent", 0);

            if ($percent <= 0) {
                continue;
            }

            $bonus = round($depositAmount * $percent / 100, 2);

            if ($bonus <= 0) {
                continue;
            }

            wallet_credit($referrerId, WALLET_REFERRAL, $bonus, LEDGER_SOURCE_REFERRAL, "Level {$level} referral bonus from {$depositorName}'s deposit");

            db()->prepare(
                'INSERT INTO referral_earnings (user_id, from_user_id, level, amount, source, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            )->execute([$referrerId, $depositUserId, $level, $bonus, 'deposit']);

            notify_user($referrerId, 'Referral Bonus Earned', 'You earned ' . money($bonus) . ' (' . referral_ordinal($level) . " level) from {$depositorName}'s deposit.", NOTIFY_TYPE_REFERRAL);
        }
    }
}

if (!function_exists('referral_ordinal')) {
    function referral_ordinal(int $n): string
    {
        if ($n % 100 >= 11 && $n % 100 <= 13) {
            return $n . 'th';
        }

        return $n . match ($n % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}
