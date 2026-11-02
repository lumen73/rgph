<?php
/**
 * Front controller : point d'entrée unique de l'application.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

$request = Request::capture();
Request::setCurrent($request);

// En-têtes de sécurité applicatifs (mod_headers est désactivé sur ce WAMP :
// ils sont ici envoyés pour chaque réponse dynamique de l'application).
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

$router = new Router();
require APP_PATH . '/routes.php';

try {
    $router->dispatch($request);
} finally {
    Session::clearOldInput();
}