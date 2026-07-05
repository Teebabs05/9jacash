<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MiningPurchase extends Model
{
    protected static string $table = 'mining_purchases';

    public static function activeForUser(int $userId): array
    {
        return static::db()->fetchAll(
            "SELECT mining_purchases.*, mining_plans.name as plan_name, mining_plans.image as plan_image
             FROM mining_purchases
             JOIN mining_plans ON mining_plans.id = mining_purchases.plan_id
             WHERE mining_purchases.user_id = :uid
             ORDER BY mining_purchases.created_at DESC",
            ['uid' => $userId]
        );
    }

    public static function dueForPayout(): array
    {
        // Active plans that haven't been paid out in the last ~24h.
        return static::db()->fetchAll(
            "SELECT * FROM mining_purchases
             WHERE status = 'active'
             AND (last_payout_at IS NULL OR last_payout_at <= DATE_SUB(NOW(), INTERVAL 23 HOUR))"
        );
    }

    public static function countActive(): int
    {
        return static::count("status = 'active'");
    }
}
