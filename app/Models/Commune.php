<?php
declare(strict_types=1);

namespace App\Models;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Core\Database;
use App\Core\Model;
use App\Core\Paginator;

/**
 * Table `commune`.
 */
final class Commune extends Model
{
    /** @var string */
    protected static $table = 'commune';

    /** @var string */
    protected static $primaryKey = 'idcommune';

    /** @var array<int, string> */
    protected static $fillable = ['nom', 'iddepartement'];

    /**
     * Liste pour les menus déroulants (optionnellement filtrée par département).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function options(int $departementId = 0): array
    {
        $sql = 'SELECT c.idcommune, c.nom, c.iddepartement, d.nom AS departement'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement';

        if ($departementId > 0) {
            return Database::select(
                $sql . ' WHERE c.iddepartement = :id ORDER BY d.nom ASC, c.nom ASC',
                ['id' => $departementId]
            );
        }

        return Database::select($sql . ' ORDER BY d.nom ASC, c.nom ASC');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function byDepartement(int $departementId): array
    {
        return Database::select(
            'SELECT idcommune, nom FROM commune WHERE iddepartement = :id ORDER BY nom ASC',
            ['id' => $departementId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT c.idcommune, c.nom, c.iddepartement, d.nom AS departement'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' WHERE c.idcommune = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function nameExistsInDepartement(string $name, int $departementId, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM commune WHERE nom = :nom AND iddepartement = :departement';
        $params = ['nom' => $name, 'departement' => $departementId];

        if ($ignoreId !== null) {
            $sql .= ' AND idcommune <> :ignore';
            $params['ignore'] = $ignoreId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    public static function paginate(string $search, int $departementId, int $page, int $perPage): Paginator
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = 'c.nom LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($departementId > 0) {
            $where[]               = 'c.iddepartement = :departement';
            $params['departement'] = $departementId;
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);
        $total    = (int) Database::scalar('SELECT COUNT(*) FROM commune c WHERE ' . $whereSql, $params);

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT c.idcommune, c.nom, c.iddepartement, d.nom AS departement,'
            . ' COUNT(v.idville) AS nb_villes, COALESCE(SUM(v.population), 0) AS population,'
            . ' (SELECT COUNT(*) FROM arrondissement a WHERE a.idcommune = c.idcommune) AS nb_arrondissements'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' WHERE ' . $whereSql
            . ' GROUP BY c.idcommune, c.nom, c.iddepartement, d.nom'
            . ' ORDER BY d.nom ASC, c.nom ASC LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return new Paginator($items, $total, $perPage, $page);
    }

    public static function hasVilles(int $id): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM ville WHERE idcommune = :id',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Toutes les communes pour l'export.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allForExport(): array
    {
        return Database::select(
            'SELECT d.nom AS departement, c.nom AS commune,'
            . ' (SELECT COUNT(*) FROM arrondissement a WHERE a.idcommune = c.idcommune) AS nb_arrondissements,'
            . ' (SELECT COUNT(*) FROM ville v2 WHERE v2.idcommune = c.idcommune) AS nb_villes,'
            . ' COALESCE((SELECT SUM(v3.population) FROM ville v3 WHERE v3.idcommune = c.idcommune), 0) AS population'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' ORDER BY d.nom ASC, c.nom ASC'
        );
    }
}