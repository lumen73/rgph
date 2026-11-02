<?php
/**
 * Amorce de l'application : autoload, configuration, session, erreurs.
 */

declare(strict_types=1);

define('APP_RUNNING', true);
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Empêche l'accès direct au fichier depuis le navigateur.
if (PHP_SAPI !== 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'bootstrap.php'
) {
    http_response_code(404);
    exit;
}

// --- Autoloader PSR-4 pour le namespace App\ -------------------------------
require APP_PATH . '/Core/Config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = APP_PATH . '/' . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// --- Configuration ---------------------------------------------------------
App\Core\Config::load(BASE_PATH . '/config/config.php');

date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'UTC'));

// --- Fonctions utilitaires globales ---------------------------------------
require APP_PATH . '/Core/helpers.php';

// --- Rapport d'erreurs -----------------------------------------------------
if (App\Core\Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// --- Session ---------------------------------------------------------------
App\Core\Session::start(
    (string) App\Core\Config::get('session.name', 'rgph5_session'),
    (int) App\Core\Config::get('session.lifetime', 0)
);

// --- Gestion centralisée des erreurs --------------------------------------
App\Core\ErrorHandler::register();