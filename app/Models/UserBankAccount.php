<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class UserBankAccount extends Model
{
    protected static string $table = 'user_bank_accounts';

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId, 'is_default DESC, id DESC');
    }

    public static function setDefault(int $userId, int $accountId): void
    {
        static::db()->update('user_bank_accounts', ['is_default' => 0], 'user_id = :uid', ['uid' => $userId]);
        static::db()->update('user_bank_accounts', ['is_default' => 1], 'id = :id AND user_id = :uid', ['id' => $accountId, 'uid' => $userId]);
    }
}
