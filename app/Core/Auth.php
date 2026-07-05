<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user', self::toSessionArray($user));
    }

    public static function refresh(): void
    {
        $current = Session::get('user');
        if (!$current) {
            return;
        }
        $fresh = User::find((int) $current['id']);
        if ($fresh) {
            Session::set('user', self::toSessionArray($fresh));
        }
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    private static function toSessionArray(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'status' => $user['status'],
            'avatar' => $user['avatar'],
            'force_password_change' => (bool) $user['force_password_change'],
            'kyc_status' => $user['kyc_status'],
            'two_fa_enabled' => (bool) $user['two_fa_enabled'],
        ];
    }
}
