<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Fabrique de réponses HTTP (redirections, JSON, texte).
 */
final class Response
{
    public static function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, $status);
        }

        exit;
    }

    /**
     * @param mixed $data
     */
    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function text(string $content, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo $content;
        exit;
    }
}