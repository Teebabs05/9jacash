<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): array|false
    {
        return static::findBy('email', $email);
    }

    public static function findByUsername(string $username): array|false
    {
        return static::findBy('username', $username);
    }

    public static function findByLogin(string $login): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM users WHERE email = :login OR username = :login LIMIT 1',
            ['login' => $login]
        );
    }

    public static function findByReferralCode(string $code): array|false
    {
        return static::findBy('referral_code', $code);
    }

    public static function generateUniqueReferralCode(string $username): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $username));
        $base = substr($base, 0, 8) ?: 'USER';
        do {
            $code = $base . random_int(100, 999);
        } while (static::findByReferralCode($code) !== false);
        return $code;
    }

    public static function emailExists(string $email): bool
    {
        return static::findByEmail($email) !== false;
    }

    public static function usernameExists(string $username): bool
    {
        return static::findByUsername($username) !== false;
    }

    public static function downline(int $userId): array
    {
        return static::where('referred_by', $userId, 'created_at DESC');
    }

    public static function searchPaginated(string $search, int $page, int $perPage = 20): array
    {
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $like = '%' . $search . '%';

        $rows = static::db()->fetchAll(
            "SELECT * FROM users WHERE full_name LIKE :s OR username LIKE :s OR email LIKE :s OR phone LIKE :s
             ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            ['s' => $like]
        );

        $total = static::db()->fetch(
            'SELECT COUNT(*) c FROM users WHERE full_name LIKE :s OR username LIKE :s OR email LIKE :s OR phone LIKE :s',
            ['s' => $like]
        );

        return ['rows' => $rows, 'total' => (int) ($total['c'] ?? 0)];
    }

    public static function sanitizePublic(array $user): array
    {
        unset($user['password_hash'], $user['two_fa_secret'], $user['remember_token']);
        return $user;
    }
}
