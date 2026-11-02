<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Accès à la configuration applicative par notation pointée
 * (ex. Config::get('database.host')).
 */
final class Config
{
    /** @var array<string, mixed> */
    private static $items = [];

    public static function load(string $file): void
    {
        $config = require $file;

        if (is_array($config)) {
            self::$items = $config;
        }
    }

    /**
     * @param string $key     Clé pointée (ex. "app.name").
     * @param mixed  $default Valeur retournée si la clé est absente.
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $ref      = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $value;
    }
}