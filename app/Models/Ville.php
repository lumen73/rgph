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
 * Table `ville`.
 */
final class Ville extends Model
{
    /** @var string */
    protected static $table = 'ville';

    /** @var string */
    protected static $primaryKey = 'idville';

    /** @var array<int, string> */
    protected static $fillable = ['nom', 'population', 'idcommune', 'idarrondissement'];

    /**
     * Liste paginée avec recherche, filtres, tri et jointures.
     */
    public static function paginate(
        string $search,
        int $departementId,
        int $communeId,
        string $sort,
        string $direction,
        int $page,
        int $perPage
    ): Paginator {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]          = 'v.nom LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($departementId > 0) {
            $where[]               = 'c.iddepartement = :departement';
            $params['departement'] = $departementId;
        }

        if ($communeId > 0) {
            $where[]           = 'v.idcommune = :commune';
            $params['commune'] = $communeId;
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM ville v INNER JOIN commune c ON c.idcommune = v.idcommune WHERE ' . $whereSql,
            $params
        );

        // Tri : liste blanche pour éviter toute injection dans ORDER BY.
        $sortable = [
            'nom'        => 'v.nom',
            'population' => 'v.population',
            'commune'    => 'c.nom',
        ];
        $orderColumn = $sortable[$sort] ?? 'v.population';
        $orderDir    = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT v.idville, v.nom, v.population, v.idcommune, v.idarrondissement,'
            . ' c.nom AS commune, d.nom AS departement, d.iddepartement, a.nom AS arrondissement,'
            . ' (SELECT COUNT(*) FROM registre r WHERE r.idville = v.idville) AS nb_registres'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' WHERE ' . $whereSql
            . ' ORDER BY ' . $orderColumn . ' ' . $orderDir
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return new Paginator($items, $total, $perPage, $page);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findDetailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT v.idville, v.nom, v.population, v.idcommune, v.idarrondissement,'
            . ' c.nom AS commune, d.nom AS departement, d.iddepartement, a.nom AS arrondissement,'
            . ' (SELECT COUNT(*) FROM registre r WHERE r.idville = v.idville) AS nb_registres'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' WHERE v.idville = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function nameExistsInCommune(string $name, int $communeId, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM ville WHERE nom = :nom AND idcommune = :commune';
        $params = ['nom' => $name, 'commune' => $communeId];

        if ($ignoreId !== null) {
            $sql .= ' AND idville <> :ignore';
            $params['ignore'] = $ignoreId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    /**
     * Met à jour une ville en garantissant que sa population reste supérieure
     * ou égale au nombre de profils du registre. Le verrou de ligne empêche
     * qu'une inscription concurrente fasse dépasser la capacité.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> ['ok' => bool, 'message' => string]
     */
    public static function updateWithCapacity(int $id, array $data): array
    {
        return Database::transaction(static function () use ($id, $data): array {
            $ville = Database::selectOne(
                'SELECT idville, nom, population FROM ville WHERE idville = :id FOR UPDATE',
                ['id' => $id]
            );

            if ($ville === null) {
                return ['ok' => false, 'message' => 'Cette ville est introuvable.'];
            }

            $registered    = (int) Database::scalar(
                'SELECT COUNT(*) FROM registre WHERE idville = :id',
                ['id' => $id]
            );
            $newPopulation = (int) ($data['population'] ?? 0);

            if ($newPopulation < $registered) {
                return [
                    'ok'      => false,
                    'message' => 'La population de la ville « ' . $ville['nom']
                        . ' » ne peut pas être inférieure aux ' . $registered
                        . ' profil(s) déjà enregistré(s) dans le registre.',
                ];
            }

            self::updateById($id, $data);

            return ['ok' => true, 'message' => ''];
        });
    }

    /**
     * Supprime une ville si aucun profil du registre n'y est rattaché.
     * Le verrou de ligne sérialise la suppression avec les inscriptions.
     *
     * @return array<string, mixed> ['ok' => bool, 'message' => string]
     */
    public static function deleteWithCapacity(int $id): array
    {
        return Database::transaction(static function () use ($id): array {
            $ville = Database::selectOne(
                'SELECT idville, nom FROM ville WHERE idville = :id FOR UPDATE',
                ['id' => $id]
            );

            if ($ville === null) {
                return ['ok' => false, 'message' => 'Cette ville est introuvable.'];
            }

            $registered = (int) Database::scalar(
                'SELECT COUNT(*) FROM registre WHERE idville = :id',
                ['id' => $id]
            );

            if ($registered > 0) {
                return [
                    'ok'      => false,
                    'message' => 'Suppression impossible : ' . $registered
                        . ' profil(s) du registre sont rattachés à la ville « '
                        . $ville['nom'] . ' ». Supprimez d\'abord ces profils.',
                ];
            }

            self::deleteById($id);

            return ['ok' => true, 'message' => ''];
        });
    }

    /**
     * Villes avec leur capacité et le nombre de profils déjà enregistrés.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function optionsWithCapacity(): array
    {
        return Database::select(
            'SELECT v.idville, v.nom, v.population, v.idcommune, v.idarrondissement,'
            . ' c.nom AS commune, d.nom AS departement, a.nom AS arrondissement,'
            . ' (SELECT COUNT(*) FROM registre r WHERE r.idville = v.idville) AS nb_registres'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' ORDER BY d.nom ASC, c.nom ASC, v.nom ASC'
        );
    }

    /**
     * Recherche textuelle (API / autocomplétion).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $term, int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));

        return Database::select(
            'SELECT v.idville, v.nom, v.population, c.nom AS commune, d.nom AS departement'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' WHERE v.nom LIKE :nom OR c.nom LIKE :commune'
            . ' ORDER BY v.population DESC'
            . ' LIMIT ' . $limit,
            ['nom' => '%' . $term . '%', 'commune' => '%' . $term . '%']
        );
    }

    /**
     * Statistiques globales.
     *
     * @return array<string, mixed>
     */
    public static function stats(): array
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total,'
            . ' COALESCE(SUM(population), 0) AS population,'
            . ' COALESCE(AVG(population), 0) AS moyenne,'
            . ' COALESCE(MAX(population), 0) AS maximum'
            . ' FROM ville'
        );

        return $row ?? ['total' => 0, 'population' => 0, 'moyenne' => 0, 'maximum' => 0];
    }

    /**
     * Population agrégée par département.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function populationByDepartement(): array
    {
        return Database::select(
            'SELECT d.iddepartement, d.nom,'
            . ' COALESCE(SUM(v.population), 0) AS population,'
            . ' COUNT(v.idville) AS nb_villes,'
            . ' COUNT(DISTINCT c.idcommune) AS nb_communes'
            . ' FROM departement d'
            . ' LEFT JOIN commune c ON c.iddepartement = d.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' GROUP BY d.iddepartement, d.nom'
            . ' ORDER BY population DESC'
        );
    }

    /**
     * Population agrégée par commune.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function populationByCommune(int $limit = 20): array
    {
        $limit = max(1, min(200, $limit));

        return Database::select(
            'SELECT c.idcommune, c.nom, d.nom AS departement,'
            . ' COALESCE(SUM(v.population), 0) AS population,'
            . ' COUNT(v.idville) AS nb_villes'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' GROUP BY c.idcommune, c.nom, d.nom'
            . ' ORDER BY population DESC'
            . ' LIMIT ' . $limit
        );
    }

    /**
     * Villes les plus peuplées.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topVilles(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        return Database::select(
            'SELECT v.idville, v.nom, v.population, c.nom AS commune, d.nom AS departement'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' ORDER BY v.population DESC'
            . ' LIMIT ' . $limit
        );
    }

    /**
     * Villes créées le plus récemment.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recents(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        return Database::select(
            'SELECT v.idville, v.nom, v.population, c.nom AS commune, d.nom AS departement'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' ORDER BY v.idville DESC'
            . ' LIMIT ' . $limit
        );
    }

    /**
     * Toutes les villes pour l'export.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allForExport(): array
    {
        return Database::select(
            'SELECT d.nom AS departement, c.nom AS commune, a.nom AS arrondissement, v.nom, v.population'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' ORDER BY d.nom ASC, c.nom ASC, v.nom ASC'
        );
    }
}