<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Coupon extends Model
{
    protected static string $table = 'coupons';

    public static function findActiveByCode(string $code): array|false
    {
        return static::db()->fetch(
            "SELECT * FROM coupons WHERE code = :code AND status = 'active'
             AND (expires_at IS NULL OR expires_at > NOW()) AND used_count < max_uses",
            ['code' => $code]
        );
    }

    public static function redeem(int $couponId, int $userId): bool
    {
        $already = static::db()->fetch(
            'SELECT id FROM coupon_redemptions WHERE coupon_id = :c AND user_id = :u',
            ['c' => $couponId, 'u' => $userId]
        );
        if ($already) {
            return false;
        }
        static::db()->insert('coupon_redemptions', ['coupon_id' => $couponId, 'user_id' => $userId]);
        static::db()->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id', ['id' => $couponId]);
        return true;
    }
}
