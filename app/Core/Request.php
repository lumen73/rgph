<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Représente la requête HTTP entrante et donne accès à ses données.
 */
final class Request
{
    /** @var Request|null */
    private static $current = null;

    /** @var string */
    private $method;

    /** @var string */
    private $uri;

    /** @var array<string, mixed> */
    private $query;

    /** @var array<string, mixed> */
    private $body;

    /** @var array<string, mixed> */
    private $files;

    /** @var array<string, mixed> */
    private $server;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        string $method,
        string $uri,
        array $query,
        array $body,
        array $files,
        array $server
    ) {
        $this->method = strtoupper($method);
        $this->uri    = trim($uri, '/');
        $this->query  = $query;
        $this->body   = $body;
        $this->files  = $files;
        $this->server = $server;
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Permet de simuler PUT/PATCH/DELETE via un champ caché _method.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        return new self($method, self::resolveUri(), $_GET, $_POST, $_FILES, $_SERVER);
    }

    public static function setCurrent(self $request): void
    {
        self::$current = $request;
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    /**
     * Détermine l'URI logique (route) à partir de la query string
     * (index.php?route=...) ou du chemin réel (URLs propres).
     */
    private static function resolveUri(): string
    {
        $route = $_GET['route'] ?? null;
        if (is_string($route) && $route !== '') {
            return trim($route, '/');
        }

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = str_replace('\\', '/', $path);

        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if ($scriptDir !== '' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = (string) preg_replace('#^/index\.php#', '', $path);

        return trim($path, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax()
            || strpos(strtolower((string) ($this->server['HTTP_ACCEPT'] ?? '')), 'application/json') !== false;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->input($key);
        }

        return $result;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function token(): ?string
    {
        $token = $this->input('_token');

        return is_string($token) ? $token : null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return isset($this->server[$key]) ? (string) $this->server[$key] : $default;
    }

    public function referer(): ?string
    {
        return isset($this->server['HTTP_REFERER']) ? (string) $this->server['HTTP_REFERER'] : null;
    }
}