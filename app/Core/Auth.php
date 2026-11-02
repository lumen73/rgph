<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Models\Utilisateur;

/**
 * Gestion de l'authentification et des autorisations (rôles).
 */
final class Auth
{
    private const USER_KEY = '_auth_id';

    /** @var array<string, mixed>|null|false  false = non chargé */
    private static $cachedUser = false;

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get(self::USER_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== false) {
            return self::$cachedUser;
        }

        $id = self::id();
        self::$cachedUser = $id === null ? null : Utilisateur::find($id);

        return self::$cachedUser;
    }

    /**
     * Tente une connexion. Gère la migration transparente des anciens
     * mots de passe stockés en clair vers un hachage bcrypt.
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = Utilisateur::findByEmail($email);

        if ($user === null || (int) ($user['actif'] ?? 1) !== 1) {
            return false;
        }

        $stored = (string) $user['mdp'];

        if ($stored !== '' && password_verify($password, $stored)) {
            self::login($user);

            return true;
        }

        if ($stored !== '' && hash_equals($stored, $password)) {
            Utilisateur::setPassword((int) $user['idutilisateur'], $password);
            self::login($user);

            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set(self::USER_KEY, (int) $user['idutilisateur']);
        self::$cachedUser = false;
    }

    public static function logout(): void
    {
        Session::remove(self::USER_KEY);
        self::$cachedUser = false;
        Session::regenerate();
    }

    public static function role(): string
    {
        $user = self::user();

        return $user !== null ? (string) ($user['role'] ?? 'lecteur') : 'lecteur';
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Vérifie si l'utilisateur courant possède une autorisation donnée.
     */
    public static function can(string $ability): bool
    {
        $matrix = [
            'admin' => ['*'],
            'agent' => [
                'ville.create', 'ville.edit', 'ville.view',
                'commune.create', 'commune.edit', 'commune.view',
                'arrondissement.create', 'arrondissement.edit', 'arrondissement.view',
                'registre.create', 'registre.edit', 'registre.view',
                'departement.view', 'rapport.view', 'export',
            ],
            'lecteur' => [
                'ville.view', 'commune.view', 'arrondissement.view',
                'registre.view',
                'departement.view', 'rapport.view',
            ],
        ];

        $abilities = $matrix[self::role()] ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }
}