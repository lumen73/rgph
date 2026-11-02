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
 * Table `utilisateur`.
 */
final class Utilisateur extends Model
{
    /** @var string */
    protected static $table = 'utilisateur';

    /** @var string */
    protected static $primaryKey = 'idutilisateur';

    /** @var array<int, string> */
    protected static $fillable = ['nom', 'email', 'mdp', 'role', 'actif'];

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            'SELECT * FROM utilisateur WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
    }

    public static function setPassword(int $id, string $plainPassword): void
    {
        Database::update(
            static::$table,
            ['mdp' => password_hash($plainPassword, PASSWORD_DEFAULT)],
            'idutilisateur = :id',
            ['id' => $id]
        );
    }

    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM utilisateur WHERE email = :email';
        $params = ['email' => $email];

        if ($ignoreId !== null) {
            $sql .= ' AND idutilisateur <> :ignore';
            $params['ignore'] = $ignoreId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    public static function paginate(string $search, string $role, int $page, int $perPage): Paginator
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            // Placeholders distincts : les requêtes préparées natives refusent
            // qu'un même paramètre nommé apparaisse deux fois (HY093).
            $where[]                = '(nom LIKE :search_nom OR email LIKE :search_email)';
            $params['search_nom']   = $like;
            $params['search_email'] = $like;
        }

        if ($role !== '') {
            $where[]        = 'role = :role';
            $params['role'] = $role;
        }

        $whereSql = $where === [] ? '1' : implode(' AND ', $where);
        $total    = (int) Database::scalar('SELECT COUNT(*) FROM utilisateur WHERE ' . $whereSql, $params);

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $items = Database::select(
            'SELECT idutilisateur, nom, email, role, actif FROM utilisateur'
            . ' WHERE ' . $whereSql
            . ' ORDER BY nom ASC LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return new Paginator($items, $total, $perPage, $page);
    }

    public static function countAdmins(): int
    {
        return static::countWhere("role = 'admin' AND actif = 1");
    }
}