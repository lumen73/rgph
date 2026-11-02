<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Modèle de base : factorise l'accès aux tables via des requêtes préparées.
 *
 * Chaque modèle concret définit :
 *   - protected static $table      : nom de la table
 *   - protected static $primaryKey : clé primaire
 *   - protected static $fillable   : colonnes autorisées en écriture
 */
abstract class Model
{
    /** @var string */
    protected static $table = '';

    /** @var string */
    protected static $primaryKey = 'id';

    /** @var array<int, string> */
    protected static $fillable = [];

    public static function table(): string
    {
        return static::$table;
    }

    public static function primaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::selectOne(
            'SELECT * FROM ' . static::$table
            . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;

        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return Database::select($sql);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return Database::insert(static::$table, static::filter($data));
    }

    /**
     * Insère plusieurs lignes dans une transaction.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return int Nombre de lignes insérées.
     */
    public static function createMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        return (int) Database::transaction(static function () use ($rows): int {
            $count = 0;

            foreach ($rows as $row) {
                static::create($row);
                $count++;
            }

            return $count;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updateById(int $id, array $data): int
    {
        $data = static::filter($data);

        if ($data === []) {
            return 0;
        }

        return Database::update(
            static::$table,
            $data,
            static::$primaryKey . ' = :__id',
            ['__id' => $id]
        );
    }

    public static function deleteById(int $id): int
    {
        return Database::delete(
            static::$table,
            static::$primaryKey . ' = :__id',
            ['__id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function countWhere(string $where = '1', array $params = []): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM ' . static::$table . ' WHERE ' . $where,
            $params
        );
    }

    public static function exists(int $id): bool
    {
        return static::countWhere(static::$primaryKey . ' = :id', ['id' => $id]) > 0;
    }

    /**
     * Ne conserve que les colonnes déclarées comme "remplissables".
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function filter(array $data): array
    {
        if (static::$fillable === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip(static::$fillable));
    }
}