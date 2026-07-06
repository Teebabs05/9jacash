<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): Database
    {
        return App::db();
    }

    public static function find(int|string $id): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function findBy(string $column, mixed $value): array|false
    {
        return static::db()->fetch(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = :value LIMIT 1",
            ['value' => $value]
        );
    }

    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->fetchAll($sql);
    }

    public static function where(string $column, mixed $value, string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table . " WHERE {$column} = :value";
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->fetchAll($sql, ['value' => $value]);
    }

    public static function create(array $data): string
    {
        return static::db()->insert(static::$table, $data);
    }

    public static function updateById(int|string $id, array $data): int
    {
        return static::db()->update(static::$table, $data, static::$primaryKey . ' = :id', ['id' => $id]);
    }

    public static function deleteById(int|string $id): int
    {
        return static::db()->delete(static::$table, static::$primaryKey . ' = :id', ['id' => $id]);
    }

    public static function count(string $where = '1', array $params = []): int
    {
        $row = static::db()->fetch('SELECT COUNT(*) as c FROM ' . static::$table . ' WHERE ' . $where, $params);
        return (int) ($row['c'] ?? 0);
    }
}
