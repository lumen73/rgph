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
 * Table `registre` : profils individuels rattachés à une ville.
 *
 * Le nombre de profils d'une ville ne peut jamais dépasser la valeur
 * `ville.population`. Les créations et modifications passent par des
 * transactions qui verrouillent la ligne de la ville concernée.
 */
final class Registre extends Model
{
    /** @var string */
    protected static $table = 'registre';

    /** @var string */
    protected static $primaryKey = 'idregistre';

    /** @var array<int, string> */
    protected static $fillable = ['idville', 'nom', 'prenom', 'date_ref', 'genre'];

    /**
     * Expression SQL du profil « Junior » (moins de 18 ans).
     */
    private const JUNIOR_SQL = 'TIMESTAMPDIFF(YEAR, r.date_ref, CURDATE()) < 18';

    /**
     * Liste paginée avec recherche et filtres.
     */
    public static function paginate(
        string $search,
        int $villeId,
        string $profil,
        int $page,
        int $perPage
    ): Paginator {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            // Placeholders distincts : les requêtes préparées natives refusent
            // qu'un même paramètre nommé apparaisse deux fois (HY093).
            $where[]                 = '(r.nom LIKE :search_nom OR r.prenom LIKE :search_prenom)';
            $params['search_nom']    = $like;
            $params['search_prenom'] = $like;
        }

        if ($villeId > 0) {
            $where[]           = 'r.idville = :ville';
            $params['ville']   = $villeId;
        }

        if ($profil === 'M' || $profil === 'F') {
            $where[]          = 'r.genre = :genre';
            $params['genre']  = $profil;
        } elseif ($profil === 'junior') {
            $where[] = self::JUNIOR_SQL;
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);

        $total = (int) Database::scalar(
            'SELECT COUNT(*) FROM registre r WHERE ' . $whereSql,
            $params
        );

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT r.idregistre, r.idville, r.nom, r.prenom, r.date_ref, r.genre,'
            . ' v.nom AS ville, c.nom AS commune, d.nom AS departement, a.nom AS arrondissement,'
            . ' TIMESTAMPDIFF(YEAR, r.date_ref, CURDATE()) AS age'
            . ' FROM registre r'
            . ' INNER JOIN ville v ON v.idville = r.idville'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' WHERE ' . $whereSql
            . ' ORDER BY r.nom ASC, r.prenom ASC, r.idregistre ASC'
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
            'SELECT r.idregistre, r.idville, r.nom, r.prenom, r.date_ref, r.genre,'
            . ' v.nom AS ville, c.nom AS commune, d.nom AS departement, a.nom AS arrondissement,'
            . ' TIMESTAMPDIFF(YEAR, r.date_ref, CURDATE()) AS age'
            . ' FROM registre r'
            . ' INNER JOIN ville v ON v.idville = r.idville'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' WHERE r.idregistre = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function countForVille(int $villeId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM registre WHERE idville = :id',
            ['id' => $villeId]
        );
    }

    /**
     * Indicateurs globaux M / F / Junior.
     *
     * @return array<string, int>
     */
    public static function stats(): array
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total,'
            . ' COALESCE(SUM(CASE WHEN genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM registre r'
        );

        return [
            'total'   => (int) ($row['total'] ?? 0),
            'hommes'  => (int) ($row['hommes'] ?? 0),
            'femmes'  => (int) ($row['femmes'] ?? 0),
            'juniors' => (int) ($row['juniors'] ?? 0),
        ];
    }

    /**
     * Profils agrégés par ville, avec la capacité déclarée.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byVille(): array
    {
        return Database::select(
            'SELECT v.idville, v.nom, v.population,'
            . ' c.nom AS commune, d.nom AS departement, a.nom AS arrondissement,'
            . ' COUNT(r.idregistre) AS total,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM ville v'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' LEFT JOIN registre r ON r.idville = v.idville'
            . ' GROUP BY v.idville, v.nom, v.population, c.nom, d.nom, a.nom'
            . ' ORDER BY total DESC, v.nom ASC'
        );
    }

    /**
     * Profils agrégés par arrondissement.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byArrondissement(): array
    {
        return Database::select(
            'SELECT a.idarrondissement, a.nom, c.nom AS commune, d.nom AS departement,'
            . ' COUNT(r.idregistre) AS total,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM arrondissement a'
            . ' INNER JOIN commune c ON c.idcommune = a.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN ville v ON v.idarrondissement = a.idarrondissement'
            . ' LEFT JOIN registre r ON r.idville = v.idville'
            . ' GROUP BY a.idarrondissement, a.nom, c.nom, d.nom'
            . ' ORDER BY d.nom ASC, c.nom ASC, a.nom ASC'
        );
    }

    /**
     * Profils des villes sans arrondissement valide (non renseigné ou orphelin).
     *
     * @return array<string, mixed>
     */
    public static function byArrondissementNonRattache(): array
    {
        $row = Database::selectOne(
            'SELECT COUNT(r.idregistre) AS total,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM ville v'
            . ' LEFT JOIN registre r ON r.idville = v.idville'
            . ' WHERE v.idarrondissement IS NULL'
            . ' OR NOT EXISTS ('
            . '     SELECT 1 FROM arrondissement a'
            . '     WHERE a.idarrondissement = v.idarrondissement'
            . ' )'
        );

        return [
            'idarrondissement' => 0,
            'nom'              => 'Non rattaché',
            'commune'          => '',
            'departement'      => '',
            'total'            => (int) ($row['total'] ?? 0),
            'hommes'           => (int) ($row['hommes'] ?? 0),
            'femmes'           => (int) ($row['femmes'] ?? 0),
            'juniors'          => (int) ($row['juniors'] ?? 0),
        ];
    }

    /**
     * Profils agrégés par commune.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byCommune(): array
    {
        return Database::select(
            'SELECT c.idcommune, c.nom, d.nom AS departement,'
            . ' COUNT(r.idregistre) AS total,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM commune c'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' LEFT JOIN registre r ON r.idville = v.idville'
            . ' GROUP BY c.idcommune, c.nom, d.nom'
            . ' ORDER BY total DESC, c.nom ASC'
        );
    }

    /**
     * Profils agrégés par département.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byDepartement(): array
    {
        return Database::select(
            'SELECT d.iddepartement, d.nom,'
            . ' COUNT(r.idregistre) AS total,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'M\' THEN 1 ELSE 0 END), 0) AS hommes,'
            . ' COALESCE(SUM(CASE WHEN r.genre = \'F\' THEN 1 ELSE 0 END), 0) AS femmes,'
            . ' COALESCE(SUM(CASE WHEN ' . self::JUNIOR_SQL . ' THEN 1 ELSE 0 END), 0) AS juniors'
            . ' FROM departement d'
            . ' LEFT JOIN commune c ON c.iddepartement = d.iddepartement'
            . ' LEFT JOIN ville v ON v.idcommune = c.idcommune'
            . ' LEFT JOIN registre r ON r.idville = v.idville'
            . ' GROUP BY d.iddepartement, d.nom'
            . ' ORDER BY total DESC, d.nom ASC'
        );
    }

    /**
     * Crée un profil en respectant la capacité de la ville.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> ['ok' => bool, 'message' => string]
     */
    public static function createWithinCapacity(array $data): array
    {
        return Database::transaction(static function () use ($data): array {
            $villeId = (int) ($data['idville'] ?? 0);
            $ville   = Database::selectOne(
                'SELECT idville, nom, population FROM ville WHERE idville = :id FOR UPDATE',
                ['id' => $villeId]
            );

            if ($ville === null) {
                return ['ok' => false, 'message' => 'La ville sélectionnée est introuvable.'];
            }

            $capacity = (int) ($ville['population'] ?? 0);
            $count    = (int) Database::scalar(
                'SELECT COUNT(*) FROM registre WHERE idville = :id',
                ['id' => $villeId]
            );

            if ($count >= $capacity) {
                return [
                    'ok'      => false,
                    'message' => 'La capacité de la ville « ' . $ville['nom'] . ' » est atteinte ('
                        . $count . ' / ' . $capacity . '). Aucun profil supplémentaire ne peut être ajouté.',
                ];
            }

            self::create($data);

            return ['ok' => true, 'message' => ''];
        });
    }

    /**
     * Met à jour un profil en vérifiant la capacité de la ville cible.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> ['ok' => bool, 'message' => string]
     */
    public static function updateWithinCapacity(int $id, array $data): array
    {
        return Database::transaction(static function () use ($id, $data): array {
            $villeId = (int) ($data['idville'] ?? 0);
            $ville   = Database::selectOne(
                'SELECT idville, nom, population FROM ville WHERE idville = :id FOR UPDATE',
                ['id' => $villeId]
            );

            if ($ville === null) {
                return ['ok' => false, 'message' => 'La ville sélectionnée est introuvable.'];
            }

            $existing = Database::selectOne(
                'SELECT idregistre FROM registre WHERE idregistre = :id FOR UPDATE',
                ['id' => $id]
            );

            if ($existing === null) {
                return ['ok' => false, 'message' => 'Ce profil est introuvable.'];
            }

            $capacity = (int) ($ville['population'] ?? 0);
            $count    = (int) Database::scalar(
                'SELECT COUNT(*) FROM registre WHERE idville = :ville AND idregistre <> :id',
                ['ville' => $villeId, 'id' => $id]
            );

            if ($count >= $capacity) {
                return [
                    'ok'      => false,
                    'message' => 'La capacité de la ville « ' . $ville['nom'] . ' » est atteinte ('
                        . $count . ' / ' . $capacity . '). Impossible d\'y déplacer ce profil.',
                ];
            }

            self::updateById($id, $data);

            return ['ok' => true, 'message' => ''];
        });
    }

    /**
     * Toutes les lignes pour l'export CSV.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allForExport(): array
    {
        return Database::select(
            'SELECT d.nom AS departement, c.nom AS commune, a.nom AS arrondissement,'
            . ' v.nom AS ville, r.nom, r.prenom, r.date_ref, r.genre,'
            . ' TIMESTAMPDIFF(YEAR, r.date_ref, CURDATE()) AS age'
            . ' FROM registre r'
            . ' INNER JOIN ville v ON v.idville = r.idville'
            . ' INNER JOIN commune c ON c.idcommune = v.idcommune'
            . ' INNER JOIN departement d ON d.iddepartement = c.iddepartement'
            . ' LEFT JOIN arrondissement a ON a.idarrondissement = v.idarrondissement'
            . ' ORDER BY d.nom ASC, c.nom ASC, v.nom ASC, r.nom ASC, r.prenom ASC'
        );
    }
}
