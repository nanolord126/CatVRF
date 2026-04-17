<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class SearchResponseDto
{
    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed> $aggregations
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public array $products,
        public int $total,
        public int $page,
        public int $perPage,
        public int $totalPages,
        public array $aggregations,
        public array $metadata,
        public string $correlationId,
        public ?float $searchTimeMs,
    ) {
    }

    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages,
            'aggregations' => $this->aggregations,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
            'search_time_ms' => $this->searchTimeMs,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            products: $data['products'] ?? [],
            total: $data['total'] ?? 0,
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 20,
            totalPages: $data['total_pages'] ?? 1,
            aggregations: $data['aggregations'] ?? [],
            metadata: $data['metadata'] ?? [],
            correlationId: $data['correlation_id'] ?? '',
            searchTimeMs: $data['search_time_ms'] ?? null,
        );
    }
}
