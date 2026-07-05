<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MiningPlan extends Model
{
    protected static string $table = 'mining_plans';

    public static function activePlans(): array
    {
        return static::where('status', 'active', 'price ASC');
    }

    public static function incrementUsers(int $planId): void
    {
        static::db()->query('UPDATE mining_plans SET current_users = current_users + 1 WHERE id = :id', ['id' => $planId]);
    }

    public static function hasCapacity(array $plan): bool
    {
        return $plan['max_users'] === null || (int) $plan['current_users'] < (int) $plan['max_users'];
    }
}
