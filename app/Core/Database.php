<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Couche d'accès aux données basée sur PDO.
 *
 * Toutes les requêtes passent par des requêtes préparées : aucune donnée
 * utilisateur n'est jamais concaténée directement dans le SQL.
 */
final class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database', []);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($cfg['host'] ?? '127.0.0.1'),
            (int) ($cfg['port'] ?? 3306),
            (string) ($cfg['database'] ?? ''),
            (string) ($cfg['charset'] ?? 'utf8mb4')
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) ($cfg['username'] ?? 'root'),
                (string) ($cfg['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Connexion à la base de données impossible.', 0, $e);
        }

        return self::$pdo;
    }

    /**
     * Exécute une requête préparée.
     *
     * @param array<string|int, mixed> $params
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(self::normalize($params));

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return mixed
     */
    public static function scalar(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * Insère une ligne et retourne l'identifiant créé.
     *
     * @param array<string, mixed> $data
     */
    public static function insert(string $table, array $data): int
    {
        if ($data === []) {
            throw new RuntimeException('Aucune donnée à insérer.');
        }

        $columns      = array_keys($data);
        $placeholders = [];
        foreach ($columns as $column) {
            $placeholders[] = ':' . $column;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quote($table),
            implode(', ', array_map([self::class, 'quote'], $columns)),
            implode(', ', $placeholders)
        );

        self::run($sql, self::bind($data, ''));
        $last = self::pdo()->lastInsertId();

        return $last === false ? 0 : (int) $last;
    }

    /**
     * Met à jour des lignes.
     *
     * @param array<string, mixed>  $data
     * @param array<string, mixed>  $whereParams
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = self::quote($column) . ' = :__set_' . $column;
        }

        $sql    = sprintf('UPDATE %s SET %s WHERE %s', self::quote($table), implode(', ', $sets), $where);
        $params = self::bind($data, '__set_') + self::normalize($whereParams);

        return self::run($sql, $params)->rowCount();
    }

    /**
     * Supprime des lignes.
     *
     * @param array<string, mixed> $whereParams
     */
    public static function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', self::quote($table), $where);

        return self::run($sql, $whereParams)->rowCount();
    }

    /**
     * Exécute une closure dans une transaction.
     *
     * @return mixed Le résultat de la closure.
     */
    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Sécurise un nom de table/colonne (protection contre l'injection
     * d'identifiants).
     */
    public static function quote(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bind(array $data, string $prefix): array
    {
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $prefix . $key] = $value;
        }

        return $params;
    }

    /**
     * Normalise les clés de paramètres en ajoutant le préfixe ":" si absent.
     *
     * @param array<string|int, mixed> $params
     * @return array<string|int, mixed>
     */
    private static function normalize(array $params): array
    {
        $normalized = [];
        foreach ($params as $key => $value) {
            if (is_string($key) && $key !== '' && $key[0] !== ':') {
                $key = ':' . $key;
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}