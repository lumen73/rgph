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
 * Table `departement`.
 */
final class Departement extends Model
{
    /** @var string */
    protected static $table = 'departement';

    /** @var string */
    protected static $primaryKey = 'iddepartement';

    /** @var array<int, string> */
    protected static $fillable = ['nom'];

    /**
     * Liste pour les menus déroulants.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function options(): array
    {
        return Database::select('SELECT iddepartement, nom FROM departement ORDER BY nom ASC');
    }

    /**
     * Départements enrichis de statistiques (communes, villes, population).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allWithStats(): array
    {
        return Database::select(
            'SELECT d.iddepartement, d.nom,'
            . ' COUNT(DISTINCT c.idcommune) AS nb_communes,'
            . ' COUNT(v.idville) AS nb_villes,'
            . ' COALESCE(SUM(v.population), 0) AS population'
            . ' FROM departement d'
            . ' LEFT JOIN commune c ON c.iddepartement = d.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' GROUP BY d.iddepartement, d.nom'
            . ' ORDER BY population DESC, d.nom ASC'
        );
    }

    public static function paginate(string $search, int $page, int $perPage): Paginator
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = 'd.nom LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);
        $total    = (int) Database::scalar('SELECT COUNT(*) FROM departement d WHERE ' . $whereSql, $params);

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT d.iddepartement, d.nom,'
            . ' COUNT(DISTINCT c.idcommune) AS nb_communes,'
            . ' COUNT(v.idville) AS nb_villes,'
            . ' COALESCE(SUM(v.population), 0) AS population'
            . ' FROM departement d'
            . ' LEFT JOIN commune c ON c.iddepartement = d.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' WHERE ' . $whereSql
            . ' GROUP BY d.iddepartement, d.nom'
            . ' ORDER BY d.nom ASC LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return new Paginator($items, $total, $perPage, $page);
    }

    public static function hasCommunes(int $id): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM commune WHERE iddepartement = :id',
            ['id' => $id]
        ) > 0;
    }
}