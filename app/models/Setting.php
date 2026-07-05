<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function allCached(): array
    {
        $rows = static::db()->fetchAll('SELECT `key`, `value` FROM settings');
        $map = [];
        foreach ($rows as $row) {
            $map[$row['key']] = $row['value'];
        }
        return $map;
    }

    public static function set(string $key, string $value): void
    {
        $existing = static::findBy('key', $key);
        if ($existing) {
            static::db()->update('settings', ['value' => $value], '`key` = :k', ['k' => $key]);
        } else {
            static::create(['key' => $key, 'value' => $value]);
        }
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, (string) $value);
        }
    }
}
