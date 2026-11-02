<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Objet de pagination : porte les éléments d'une page et les métadonnées
 * nécessaires à l'affichage des liens.
 */
final class Paginator
{
    /** @var array<int, array<string, mixed>> */
    private $items;

    /** @var int */
    private $total;

    /** @var int */
    private $perPage;

    /** @var int */
    private $currentPage;

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(array $items, int $total, int $perPage, int $currentPage)
    {
        $this->items       = $items;
        $this->total       = max(0, $total);
        $this->perPage     = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : ($this->currentPage - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->currentPage * $this->perPage);
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }
}