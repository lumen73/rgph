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
 * Table `arrondissement`.
 */
final class Arrondissement extends Model
{
    /** @var string */
    protected static $table = 'arrondissement';

    /** @var string */
    protected static $primaryKey = 'idarrondissement';

    /** @var array<int, string> */
    protected static $fillable = ['nom', 'idcommune'];

    /**
     * Liste pour les menus déroulants (optionnellement filtrée par commune).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function options(int $communeId = 0): array
    {
        if ($communeId > 0) {
            return Database::select(
                'SELECT idarrondissement, nom FROM arrondissement WHERE idcommune = :id ORDER BY nom ASC',
                ['id' => $communeId]
            );
        }

        return Database::select('SELECT idarrondissement, nom FROM arrondissement ORDER BY nom ASC');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function byCommune(int $communeId): array
    {
        return Database::select(
            'SELECT idarrondissement, nom FROM arrondissement WHERE idcommune = :id ORDER BY nom ASC',
            ['id' => $communeId]
        );
    }

    /**
     * Liste des arrondissements avec leur commune et département, pour les
     * formulaires de ville (auto-remplissage).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function optionsWithLocation(): array
    {
        return Database::select(
            'SELECT a.idarrondissement, a.nom, a.idcommune, c.nom AS commune,'
            . ' c.iddepartement, d.nom AS departement'
            . ' FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' ORDER BY d.nom ASC, c.nom ASC, a.nom ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT a.idarrondissement, a.nom, a.idcommune, c.nom AS commune,'
            . ' c.iddepartement, d.nom AS departement'
            . ' FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' WHERE a.idarrondissement = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function nameExistsInCommune(string $name, int $communeId, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM arrondissement WHERE nom = :nom AND idcommune = :commune';
        $params = ['nom' => $name, 'commune' => $communeId];

        if ($ignoreId !== null) {
            $sql .= ' AND idarrondissement <> :ignore';
            $params['ignore'] = $ignoreId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    public static function paginate(
        string $search,
        int $communeId,
        int $departementId,
        int $page,
        int $perPage
    ): Paginator {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = 'a.nom LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($communeId > 0) {
            $where[]           = 'a.idcommune = :commune';
            $params['commune'] = $communeId;
        }

        if ($departementId > 0) {
            $where[]               = 'c.iddepartement = :departement';
            $params['departement'] = $departementId;
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' WHERE ' . $whereSql,
            $params
        );

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT a.idarrondissement, a.nom, a.idcommune, c.nom AS commune,'
            . ' d.nom AS departement,'
            . ' (SELECT COUNT(*) FROM ville v WHERE v.idarrondissement = a.idarrondissement) AS nb_villes'
            . ' FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' WHERE ' . $whereSql
            . ' ORDER BY d.nom ASC, c.nom ASC, a.nom ASC'
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return new Paginator($items, $total, $perPage, $page);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allForExport(): array
    {
        return Database::select(
            'SELECT d.nom AS departement, c.nom AS commune, a.nom AS arrondissement,'
            . ' (SELECT COUNT(*) FROM ville v WHERE v.idarrondissement = a.idarrondissement) AS nb_villes'
            . ' FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' ORDER BY d.nom ASC, c.nom ASC, a.nom ASC'
        );
    }

    public static function countForCommune(int $communeId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM arrondissement WHERE idcommune = :id',
            ['id' => $communeId]
        );
    }

    public static function countVilles(int $arrondissementId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM ville WHERE idarrondissement = :id',
            ['id' => $arrondissementId]
        );
    }
}
