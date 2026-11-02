<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Encapsule la session PHP et fournit les messages "flash"
 * ainsi que la mise en mémoire des saisies (old input).
 */
final class Session
{
    private const FLASH_KEY = '_flash';
    private const OLD_KEY   = '_old';

    public static function start(string $name = 'PHPSESSID', int $lifetime = 0): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = max(0, $lifetime);

        if (PHP_SAPI !== 'cli') {
            if ($lifetime > 0) {
                // Évite les déconnexions surprises pendant la saisie.
                ini_set('session.gc_maxlifetime', (string) $lifetime);
                ini_set('session.cookie_lifetime', (string) $lifetime);
            }

            session_name($name);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => '',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        session_start();
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // --- Messages flash ---------------------------------------------------

    public static function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Récupère et vide les messages flash.
     *
     * @return array<int, array{type: string, message: string}>
     */
    public static function pullFlash(): array
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);

        return is_array($flash) ? $flash : [];
    }

    // --- Old input --------------------------------------------------------

    /**
     * @param array<string, mixed> $input
     */
    public static function flashInput(array $input): void
    {
        unset($input['_token'], $input['_method'], $input['csrf']);

        $_SESSION[self::OLD_KEY] = $input;
    }

    /**
     * @return array<string, mixed>
     */
    public static function oldInput(): array
    {
        $old = $_SESSION[self::OLD_KEY] ?? [];

        return is_array($old) ? $old : [];
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION[self::OLD_KEY]);
    }
}