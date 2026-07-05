<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class OtpCode extends Model
{
    protected static string $table = 'otp_codes';

    public static function generate(int $userId, string $purpose, int $ttlMinutes = 15): string
    {
        static::db()->update('otp_codes', ['used' => 1], 'user_id = :uid AND purpose = :p AND used = 0', [
            'uid' => $userId, 'p' => $purpose,
        ]);

        $code = Security::generateOtp(6);
        static::create([
            'user_id' => $userId,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
            'used' => 0,
        ]);
        return $code;
    }

    public static function verify(int $userId, string $purpose, string $code): bool
    {
        $row = static::db()->fetch(
            'SELECT * FROM otp_codes WHERE user_id = :uid AND purpose = :p AND used = 0
             ORDER BY id DESC LIMIT 1',
            ['uid' => $userId, 'p' => $purpose]
        );

        if (!$row || !hash_equals($row['code'], $code) || strtotime($row['expires_at']) < time()) {
            return false;
        }

        static::db()->update('otp_codes', ['used' => 1], 'id = :id', ['id' => $row['id']]);
        return true;
    }
}
