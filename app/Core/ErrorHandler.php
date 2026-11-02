<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use App\Exceptions\HttpException;
use Throwable;

/**
 * Gestion centralisée des exceptions et des erreurs fatales.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void
    {
        $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

        self::log($e);

        if (headers_sent()) {
            return;
        }

        http_response_code($status);

        if ((bool) Config::get('app.debug') && $status >= 500) {
            echo self::debugPage($status, $e);

            return;
        }

        $message = $e instanceof HttpException
            ? $e->getMessage()
            : 'Une erreur interne est survenue. Veuillez réessayer plus tard.';

        View::renderError($status, $message);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::handleException(new \ErrorException(
                (string) $error['message'],
                0,
                (int) $error['type'],
                (string) $error['file'],
                (int) $error['line']
            ));
        }
    }

    private static function debugPage(int $status, Throwable $e): string
    {
        $title = 'Erreur ' . $status;
        $trace = $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">'
            . '<title>' . e($title) . '</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:40px}'
            . 'h1{color:#f87171}pre{background:#1e293b;padding:16px;border-radius:8px;overflow:auto;'
            . 'border:1px solid #334155;font-size:13px}</style></head><body>'
            . '<h1>' . e($title) . '</h1>'
            . '<p>' . e($e->getMessage()) . '</p>'
            . '<pre>' . e($trace) . '</pre>'
            . '</body></html>';
    }

    private static function log(Throwable $e): void
    {
        $dir = STORAGE_PATH . '/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s : %s dans %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}