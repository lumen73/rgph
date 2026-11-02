<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Protection contre les attaques CSRF (Cross-Site Request Forgery).
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    /**
     * Retourne le jeton CSRF courant (créé à la demande).
     */
    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    /**
     * Champ caché prêt à insérer dans un formulaire.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie un jeton reçu (comparaison à temps constant).
     */
    public static function verify(?string $token): bool
    {
        $stored = Session::get(self::KEY);

        return is_string($stored)
            && $stored !== ''
            && is_string($token)
            && $token !== ''
            && hash_equals($stored, $token);
    }
}