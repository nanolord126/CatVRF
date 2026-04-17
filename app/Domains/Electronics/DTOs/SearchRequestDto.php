<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class SearchRequestDto
{
    /**
     * @param array<string> $brands
     * @param array<string> $categories
     * @param array<string> $colors
     * @param array<string, mixed> $specsFilters
     * @param array<string> $sort
     */
    public function __construct(
        public string $query,
        public int $page,
        public int $perPage,
        public ?int $minPriceKopecks,
        public ?int $maxPriceKopecks,
        public array $brands,
        public array $categories,
        public array $colors,
        public array $specsFilters,
        public ?bool $inStockOnly,
        public ?bool $withDiscount,
        public array $sort,
        public ?string $type,
        public string $correlationId,
    ) {
    }

    public static function fromRequest(array $data, string $correlationId): self
    {
        return new self(
            query: $data['query'] ?? '',
            page: (int) ($data['page'] ?? 1),
            perPage: min((int) ($data['per_page'] ?? 20), 100),
            minPriceKopecks: isset($data['min_price']) ? (int) ($data['min_price'] * 100) : null,
            maxPriceKopecks: isset($data['max_price']) ? (int) ($data['max_price'] * 100) : null,
            brands: (array) ($data['brands'] ?? []),
            categories: (array) ($data['categories'] ?? []),
            colors: (array) ($data['colors'] ?? []),
            specsFilters: (array) ($data['specs'] ?? []),
            inStockOnly: $data['in_stock_only'] ?? null,
            withDiscount: $data['with_discount'] ?? null,
            sort: (array) ($data['sort'] ?? ['field' => 'relevance', 'direction' => 'desc']),
            type: $data['type'] ?? null,
            correlationId: $correlationId,
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'min_price' => $this->minPriceKopecks ? $this->minPriceKopecks / 100 : null,
            'max_price' => $this->maxPriceKopecks ? $this->maxPriceKopecks / 100 : null,
            'brands' => $this->brands,
            'categories' => $this->categories,
            'colors' => $this->colors,
            'specs' => $this->specsFilters,
            'in_stock_only' => $this->inStockOnly,
            'with_discount' => $this->withDiscount,
            'sort' => $this->sort,
            'type' => $this->type,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
