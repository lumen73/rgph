<?php
/**
 * Configuration globale de l'application.
 *
 * @return array<string, mixed>
 */

// URL de base calculée automatiquement à partir de l'emplacement du script
// (permet de déplacer le projet sans rien modifier).
$baseUrl = '';
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_NAME'])) {
    $baseUrl = rtrim(str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME'])), '/');
}

return [
    'app' => [
        'name'        => 'RGPH5',
        'subtitle'    => "5e Recensement Général de la Population et de l'Habitat",
        'base_url'    => $baseUrl,
        // true => URLs "propres" (/villes). Nécessite mod_rewrite + .htaccess.
        'pretty_urls' => false,
        'debug'       => true,
        'timezone'    => 'Africa/Porto-Novo',
        'locale'      => 'fr',
    ],

    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'rgph5',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name' => 'rgph5_session',
        // Durée de vie de la session : 8 heures (en secondes).
        'lifetime' => 28800,
    ],

    'pagination' => [
        'per_page' => 10,
    ],
];