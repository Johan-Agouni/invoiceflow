<?php

declare(strict_types=1);

namespace App;

abstract class Model
{
    protected static string $table;

    protected static string $primaryKey = 'id';

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM ' . static::$table . ' ORDER BY ' . static::$primaryKey . ' DESC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        return Database::fetch(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = ?",
            [$value]
        );
    }

    public static function where(string $column, mixed $value): array
    {
        return Database::fetchAll(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = ?",
            [$value]
        );
    }

    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Database::insert(static::$table, $data);
    }

    public static function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Database::update(
            static::$table,
            $data,
            static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function delete(int $id): int
    {
        return Database::delete(
            static::$table,
            static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $result = Database::fetch(
            'SELECT COUNT(*) as count FROM ' . static::$table . " WHERE {$where}",
            $params
        );

        return (int) ($result['count'] ?? 0);
    }

    public static function sum(string $column, string $where = '1=1', array $params = []): float
    {
        $result = Database::fetch(
            "SELECT SUM({$column}) as total FROM " . static::$table . " WHERE {$where}",
            $params
        );

        return (float) ($result['total'] ?? 0);
    }

    public static function paginate(int $page = 1, int $perPage = 15, string $where = '1=1', array $params = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total = self::count($where, $params);
        $totalPages = (int) ceil($total / $perPage);

        $items = Database::fetchAll(
            'SELECT * FROM ' . static::$table . " WHERE {$where} ORDER BY " . static::$primaryKey . " DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }
}
