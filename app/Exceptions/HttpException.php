<?php
declare(strict_types=1);

namespace App\Exceptions;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

use RuntimeException;

/**
 * Exception HTTP portant un code de statut (404, 403, 422, ...).
 */
class HttpException extends RuntimeException
{
    /** @var int */
    private $statusCode;

    public function __construct(int $statusCode, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function notFound(string $message = 'Page introuvable.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'Accès refusé.'): self
    {
        return new self(403, $message);
    }

    public static function badRequest(string $message = 'Requête invalide.'): self
    {
        return new self(400, $message);
    }
}