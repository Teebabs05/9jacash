<?php
/**
 * Watch-to-earn ad reward primitives. The "ad" itself is a simulated
 * countdown (no third-party ad network is configured in this build);
 * the countdown is enforced server-side via a short-lived session
 * token so the reward cannot be claimed before the required watch time
 * has actually elapsed.
 */

declare(strict_types=1);

if (!function_exists('ads_today_count')) {
    function ads_today_count(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) AS c FROM ads_logs WHERE user_id = ? AND watched_at >= CURDATE()');
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['c'];
    }
}

if (!function_exists('ads_seconds_until_next')) {
    function ads_seconds_until_next(int $userId): int
    {
        $cooldown = (int) get_setting('ad_cooldown_seconds', 30);

        $stmt = db()->prepare('SELECT watched_at FROM ads_logs WHERE user_id = ? ORDER BY watched_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $last = $stmt->fetch();

        if (!$last) {
            return 0;
        }

        $elapsed = time() - strtotime((string) $last['watched_at']);
        return max(0, $cooldown - $elapsed);
    }
}

if (!function_exists('ads_can_watch')) {
    function ads_can_watch(int $userId): array
    {
        $dailyLimit = (int) get_setting('ad_daily_limit', 10);
        $watched = ads_today_count($userId);

        if ($watched >= $dailyLimit) {
            return ['can_watch' => false, 'reason' => 'You have reached your daily ad limit. Please come back tomorrow.'];
        }

        $cooldownRemaining = ads_seconds_until_next($userId);
        if ($cooldownRemaining > 0) {
            return ['can_watch' => false, 'reason' => "Please wait {$cooldownRemaining}s before watching another ad.", 'cooldown' => $cooldownRemaining];
        }

        return ['can_watch' => true, 'reason' => ''];
    }
}

if (!function_exists('ads_start_watch')) {
    function ads_start_watch(int $userId): array
    {
        $eligibility = ads_can_watch($userId);
        if (!$eligibility['can_watch']) {
            return ['success' => false, 'message' => $eligibility['reason']];
        }

        $token = generate_token(16);
        $duration = (int) get_setting('ad_watch_duration_seconds', 15);

        $_SESSION['ad_watch'] = [
            'token' => $token,
            'started_at' => time(),
            'duration' => $duration,
        ];

        return ['success' => true, 'token' => $token, 'duration' => $duration];
    }
}

if (!function_exists('ads_claim_watch')) {
    function ads_claim_watch(int $userId, string $token): array
    {
        $pending = $_SESSION['ad_watch'] ?? null;

        if (!$pending || !hash_equals($pending['token'], $token)) {
            return ['success' => false, 'message' => 'Invalid or expired ad session. Please start again.'];
        }

        $elapsed = time() - $pending['started_at'];
        if ($elapsed < $pending['duration']) {
            return ['success' => false, 'message' => 'Please finish watching the ad before claiming your reward.'];
        }

        unset($_SESSION['ad_watch']);

        $eligibility = ads_can_watch($userId);
        if (!$eligibility['can_watch']) {
            return ['success' => false, 'message' => $eligibility['reason']];
        }

        $reward = (float) get_setting('ad_reward_amount', 10);

        $stmt = db()->prepare('INSERT INTO ads_logs (user_id, reward_amount, watched_at) VALUES (?, ?, NOW())');
        $stmt->execute([$userId, $reward]);

        $newBalance = wallet_credit($userId, WALLET_BONUS, $reward, LEDGER_SOURCE_AD, 'Reward for watching an ad');
        notify_user($userId, 'Ad Reward Credited', 'You earned ' . money($reward) . ' for watching an ad.', NOTIFY_TYPE_SYSTEM);

        return ['success' => true, 'message' => 'You earned ' . money($reward) . '!', 'amount' => $reward, 'new_balance' => $newBalance];
    }
}
