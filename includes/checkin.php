<?php
/**
 * Daily check-in primitives: streak tracking (resets if a day is missed,
 * wraps every CHECKIN_CYCLE_DAYS) with bonus multipliers on the 7th and
 * final day of the cycle.
 */

declare(strict_types=1);

if (!function_exists('checkin_has_checked_in_today')) {
    function checkin_has_checked_in_today(int $userId): bool
    {
        $stmt = db()->prepare('SELECT id FROM daily_checkins WHERE user_id = ? AND checkin_date = CURDATE() LIMIT 1');
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }
}

/**
 * Determine what the *next* streak day would be without recording anything.
 */
if (!function_exists('checkin_next_streak_day')) {
    function checkin_next_streak_day(int $userId): int
    {
        $stmt = db()->prepare('SELECT streak_day FROM daily_checkins WHERE user_id = ? AND checkin_date = CURDATE() - INTERVAL 1 DAY LIMIT 1');
        $stmt->execute([$userId]);
        $yesterday = $stmt->fetch();

        if (!$yesterday) {
            return 1;
        }

        $next = (int) $yesterday['streak_day'] + 1;
        return $next > CHECKIN_CYCLE_DAYS ? 1 : $next;
    }
}

if (!function_exists('checkin_reward_for_day')) {
    function checkin_reward_for_day(int $streakDay): float
    {
        $base = (float) get_setting('checkin_base_reward', 10);
        $reward = $base * $streakDay;

        if ($streakDay === CHECKIN_CYCLE_DAYS) {
            $reward *= CHECKIN_DAY30_MULTIPLIER;
        } elseif ($streakDay === 7) {
            $reward *= CHECKIN_DAY7_MULTIPLIER;
        }

        return $reward;
    }
}

if (!function_exists('checkin_perform')) {
    function checkin_perform(int $userId): array
    {
        if (checkin_has_checked_in_today($userId)) {
            return ['success' => false, 'message' => 'You have already checked in today. Come back tomorrow!'];
        }

        $streakDay = checkin_next_streak_day($userId);
        $reward = checkin_reward_for_day($streakDay);

        $stmt = db()->prepare(
            'INSERT INTO daily_checkins (user_id, streak_day, reward_amount, checkin_date, created_at) VALUES (?, ?, ?, CURDATE(), NOW())'
        );
        $stmt->execute([$userId, $streakDay, $reward]);

        wallet_credit($userId, WALLET_BONUS, $reward, LEDGER_SOURCE_CHECKIN, "Daily check-in reward (Day {$streakDay})");

        $milestone = $streakDay === CHECKIN_CYCLE_DAYS ? ' — 30-Day Milestone Bonus!' : ($streakDay === 7 ? ' — 7-Day Milestone Bonus!' : '');
        notify_user($userId, 'Daily Check-in', "You checked in for Day {$streakDay} and earned " . money($reward) . $milestone, NOTIFY_TYPE_SYSTEM);

        return [
            'success' => true,
            'message' => "Checked in! You earned " . money($reward) . " (Day {$streakDay})" . $milestone,
            'streak_day' => $streakDay,
            'reward' => $reward,
        ];
    }
}
