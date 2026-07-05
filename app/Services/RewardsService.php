<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyCheckin;
use App\Models\SpinHistory;
use App\Models\Wallet;
use Exception;

class RewardsService
{
    public static function checkin(int $userId): float
    {
        if (DailyCheckin::todayFor($userId)) {
            throw new Exception('You have already checked in today. Come back tomorrow!');
        }

        $streak = DailyCheckin::currentStreak($userId) + 1;
        $streak = min($streak, 7);

        $base = (float) setting('checkin_base_reward', 10);
        $reward = round($base * (1 + ($streak - 1) * 0.15), 2);

        DailyCheckin::create([
            'user_id' => $userId,
            'checkin_date' => date('Y-m-d'),
            'streak_count' => $streak,
            'reward_amount' => $reward,
        ]);

        Wallet::credit($userId, 'bonus', $reward, 'checkin', "Daily check-in bonus (streak day {$streak})");

        return $reward;
    }

    public static function spin(int $userId): float
    {
        if (SpinHistory::hasSpunToday($userId)) {
            throw new Exception('You have already spun the wheel today. Come back tomorrow!');
        }

        $min = (float) setting('spin_min_reward', 5);
        $max = (float) setting('spin_max_reward', 200);
        $reward = round(random_int((int) ($min * 100), (int) ($max * 100)) / 100, 2);

        SpinHistory::create([
            'user_id' => $userId,
            'reward_amount' => $reward,
            'spin_date' => date('Y-m-d'),
        ]);

        Wallet::credit($userId, 'bonus', $reward, 'spin', 'Spin wheel reward');

        return $reward;
    }
}
