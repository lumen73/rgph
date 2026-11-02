<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use RuntimeException;
use Throwable;

/**
 * Moteur de rendu : inclut un template PHP et, optionnellement,
 * l'imbrique dans un layout.
 */
final class View
{
    /** @var array<string, mixed> */
    private $shared = [];

    /**
     * @param array<string, mixed> $data
     * @param string|null          $layout
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        echo self::capture($view, $data, $layout);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function capture(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::captureFile($view, $data);

        if ($layout !== null) {
            $content = self::captureFile($layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function captureFile(string $view, array $data): string
    {
        $file = VIEW_PATH . '/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('Vue introuvable : ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    public static function renderError(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        echo self::capture('errors/error', [
            'status'  => $status,
            'message' => $message,
        ]);
    }
}