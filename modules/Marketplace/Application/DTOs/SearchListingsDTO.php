<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\DTOs;

use Modules\Marketplace\Domain\Enums\ListingType;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;

/**
 * DTO для поиска витринных позиций
 */
final readonly class SearchListingsDTO
{
    private function __construct(
        public ?string $query,
        public ?VerticalSource $vertical,
        public ?string $category,
        public ?ListingType $type,
        public ?float $minPrice,
        public ?float $maxPrice,
        public ?bool $inStock,
        public ?bool $isFeatured,
        public ?float $minRating,
        public ?array $tags,
        public ?int $tenantId,
        public ?string $sortBy,
        public ?string $sortOrder,
        public int $page,
        public int $perPage,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? null,
            vertical: isset($data['vertical']) ? VerticalSource::tryFrom($data['vertical']) : null,
            category: $data['category'] ?? null,
            type: isset($data['type']) ? ListingType::tryFrom($data['type']) : null,
            minPrice: isset($data['min_price']) ? (float) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (float) $data['max_price'] : null,
            inStock: $data['in_stock'] ?? null,
            isFeatured: $data['is_featured'] ?? null,
            minRating: isset($data['min_rating']) ? (float) $data['min_rating'] : null,
            tags: $data['tags'] ?? null,
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            sortBy: $data['sort_by'] ?? 'ranking_score',
            sortOrder: $data['sort_order'] ?? 'desc',
            page: isset($data['page']) ? (int) $data['page'] : 1,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
        );
    }

    public static function create(
        ?string $query = null,
        ?VerticalSource $vertical = null,
        ?string $category = null,
        ?ListingType $type = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?bool $inStock = null,
        ?bool $isFeatured = null,
        ?float $minRating = null,
        ?array $tags = null,
        ?int $tenantId = null,
        ?string $sortBy = null,
        ?string $sortOrder = null,
        int $page = 1,
        int $perPage = 20,
    ): self {
        return new self(
            query: $query,
            vertical: $vertical,
            category: $category,
            type: $type,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            inStock: $inStock,
            isFeatured: $isFeatured,
            minRating: $minRating,
            tags: $tags,
            tenantId: $tenantId,
            sortBy: $sortBy ?? 'ranking_score',
            sortOrder: $sortOrder ?? 'desc',
            page: $page,
            perPage: $perPage,
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'vertical' => $this->vertical?->value,
            'category' => $this->category,
            'type' => $this->type?->value,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'in_stock' => $this->inStock,
            'is_featured' => $this->isFeatured,
            'min_rating' => $this->minRating,
            'tags' => $this->tags,
            'tenant_id' => $this->tenantId,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasQuery(): bool
    {
        return $this->query !== null && trim($this->query) !== '';
    }

    public function hasFilters(): bool
    {
        return $this->vertical !== null
            || $this->category !== null
            || $this->type !== null
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || $this->inStock !== null
            || $this->isFeatured !== null
            || $this->minRating !== null
            || !empty($this->tags);
    }

    public function validate(): void
    {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be at least 1');
        }

        if ($this->perPage < 1 || $this->perPage > 100) {
            throw new \InvalidArgumentException('Per page must be between 1 and 100');
        }

        if ($this->minPrice !== null && $this->minPrice < 0) {
            throw new \InvalidArgumentException('Min price cannot be negative');
        }

        if ($this->maxPrice !== null && $this->maxPrice < 0) {
            throw new \InvalidArgumentException('Max price cannot be negative');
        }

        if ($this->minPrice !== null && $this->maxPrice !== null && $this->minPrice > $this->maxPrice) {
            throw new \InvalidArgumentException('Min price cannot be greater than max price');
        }

        if ($this->minRating !== null && ($this->minRating < 0 || $this->minRating > 5)) {
            throw new \InvalidArgumentException('Min rating must be between 0 and 5');
        }

        if (!in_array($this->sortOrder, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException('Sort order must be asc or desc');
        }

        $allowedSortBy = ['ranking_score', 'price', 'created_at', 'popularity_score', 'rating'];
        if (!in_array($this->sortBy, $allowedSortBy, true)) {
            throw new \InvalidArgumentException('Invalid sort by field');
        }
    }
}
