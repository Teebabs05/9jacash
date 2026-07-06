<?php
/**
 * Spin wheel primitives: weighted-probability draw against the active
 * spin_settings segments, daily limit enforcement, and reward crediting.
 */

declare(strict_types=1);

if (!function_exists('spin_today_count')) {
    function spin_today_count(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) AS c FROM spin_logs WHERE user_id = ? AND created_at >= CURDATE()');
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['c'];
    }
}

if (!function_exists('spin_can_play')) {
    function spin_can_play(int $userId): bool
    {
        $dailyLimit = (int) get_setting('spin_daily_limit', 1);
        return spin_today_count($userId) < $dailyLimit;
    }
}

/**
 * Pick a segment using weighted random selection based on `probability`.
 */
if (!function_exists('spin_pick_segment')) {
    function spin_pick_segment(array $segments): array
    {
        $totalWeight = array_sum(array_map(fn ($s) => (float) $s['probability'], $segments));

        if ($totalWeight <= 0) {
            return $segments[array_rand($segments)];
        }

        $roll = mt_rand() / mt_getrandmax() * $totalWeight;
        $cumulative = 0.0;

        foreach ($segments as $segment) {
            $cumulative += (float) $segment['probability'];
            if ($roll <= $cumulative) {
                return $segment;
            }
        }

        return $segments[count($segments) - 1];
    }
}

/**
 * Play a spin for the given user. Returns the winning segment plus its
 * index within the *active segment list* (used by the frontend to know
 * which wedge to animate to).
 */
if (!function_exists('spin_play')) {
    function spin_play(int $userId): array
    {
        if (!spin_can_play($userId)) {
            return ['success' => false, 'message' => 'You have already used your daily spin. Come back tomorrow!'];
        }

        $segments = db()->query('SELECT * FROM spin_settings WHERE is_active = 1 ORDER BY id ASC')->fetchAll();

        if (!$segments) {
            return ['success' => false, 'message' => 'The spin wheel is not available right now.'];
        }

        $winner = spin_pick_segment($segments);
        $winnerIndex = array_search($winner, $segments, true);
        $amount = (float) $winner['amount'];

        $stmt = db()->prepare('INSERT INTO spin_logs (user_id, spin_setting_id, amount_won, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, $winner['id'], $amount]);

        if ($amount > 0) {
            wallet_credit($userId, WALLET_BONUS, $amount, LEDGER_SOURCE_SPIN, 'Spin wheel reward: ' . $winner['label']);
            notify_user($userId, 'Spin Wheel Reward', 'You won ' . money($amount) . ' on the spin wheel!', NOTIFY_TYPE_SYSTEM);
        }

        return [
            'success' => true,
            'label' => $winner['label'],
            'amount' => $amount,
            'segment_index' => $winnerIndex,
            'segment_count' => count($segments),
            'message' => $amount > 0 ? "Congratulations! You won {$winner['label']}!" : 'Better luck next time!',
        ];
    }
}
