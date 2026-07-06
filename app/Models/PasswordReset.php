<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';

    public static function create(array $data): string
    {
        return parent::create($data);
    }

    public static function generate(string $email, int $ttlMinutes = 30): string
    {
        $token = Security::randomToken(32);
        static::db()->insert('password_resets', [
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
            'used' => 0,
        ]);
        return $token;
    }

    public static function findValid(string $email, string $token): array|false
    {
        $row = static::db()->fetch(
            'SELECT * FROM password_resets WHERE email = :email AND used = 0 ORDER BY id DESC LIMIT 1',
            ['email' => $email]
        );
        if (!$row || strtotime($row['expires_at']) < time() || !hash_equals($row['token'], hash('sha256', $token))) {
            return false;
        }
        return $row;
    }

    public static function markUsed(int $id): void
    {
        static::db()->update('password_resets', ['used' => 1], 'id = :id', ['id' => $id]);
    }
}
