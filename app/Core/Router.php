<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Exceptions\HttpException;

/**
 * Routeur HTTP minimaliste : associe méthode + URI à un contrôleur.
 */
final class Router
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    /**
     * @param array<string, mixed> $options
     */
    public function get(string $uri, string $handler, array $options = []): void
    {
        $this->add('GET', $uri, $handler, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri, string $handler, array $options = []): void
    {
        $this->add('POST', $uri, $handler, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function add(string $method, string $uri, string $handler, array $options): void
    {
        $normalized = '/' . trim($uri, '/');
        $pattern    = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $normalized);

        $this->routes[$method][] = [
            'uri'     => $normalized,
            'regex'   => '#^' . $pattern . '$#',
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = '/' . trim($request->uri(), '/');

        foreach ($this->routes[$request->method()] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $matches) === 1) {
                $this->authorize($route['options'], $request);

                /** @var array<string, string> $params */
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->invoke($route['handler'], $params, $request);

                return;
            }
        }

        throw HttpException::notFound('La page demandée est introuvable.');
    }

    /**
     * Applique les règles d'accès (invité, authentifié, permissions, CSRF).
     *
     * @param array<string, mixed> $options
     */
    private function authorize(array $options, Request $request): void
    {
        if (!empty($options['guest'])) {
            if (Auth::check()) {
                Response::redirect(url('/'));
            }
        } else {
            if (!empty($options['auth']) && !Auth::check()) {
                Session::flash('warning', 'Veuillez vous connecter pour accéder à cette page.');
                Response::redirect(url('login'));
            }

            if (!empty($options['can']) && !Auth::can((string) $options['can'])) {
                throw HttpException::forbidden("Vous n'avez pas les droits nécessaires pour effectuer cette action.");
            }
        }

        $mutating = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if ($mutating && empty($options['no_csrf']) && !Csrf::verify($request->token())) {
            throw HttpException::forbidden('Votre session a expiré ou la requête est invalide. Veuillez réessayer.');
        }
    }

    /**
     * @param array<string, string> $params
     */
    private function invoke(string $handler, array $params, Request $request): void
    {
        [$class, $method] = array_pad(explode('@', $handler, 2), 2, 'index');

        $fqcn = 'App\\Controllers\\' . $class;

        if (!class_exists($fqcn)) {
            throw HttpException::notFound('Contrôleur introuvable.');
        }

        $controller = new $fqcn();

        if (!method_exists($controller, $method)) {
            throw HttpException::notFound('Action introuvable.');
        }

        $controller->{$method}($request, ...array_values($params));
    }
}