<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;

/**
 * Runs once a new account is confirmed usable (either immediately at
 * registration, or after email OTP verification) — signup bonus and
 * the flat referral signup bonus for whoever invited them.
 */
class Onboarding
{
    public static function completeSignup(int $userId, ?int $referredBy): void
    {
        $bonus = (float) setting('registration_bonus', 0);
        if ($bonus > 0) {
            Wallet::credit($userId, 'bonus', $bonus, 'signup_bonus', 'Welcome signup bonus');
        }

        if ($referredBy) {
            $flatBonus = (float) setting('referral_signup_bonus', 0);
            if ($flatBonus > 0) {
                Wallet::credit($referredBy, 'referral', $flatBonus, 'referral_bonus', 'Referral signup bonus');
                Notification::send($referredBy, 'New Referral', 'Someone joined using your referral link! You earned ' . money($flatBonus) . '.', 'success');
            }
        }

        Notification::send($userId, 'Welcome to ' . setting('site_name', '9JACASH') . '!', 'Your account is ready. Explore mining plans, tasks and daily rewards to start earning.', 'success');
    }
}
