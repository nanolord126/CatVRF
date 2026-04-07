<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для поиска/фильтрации VerticalItem.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Используется для передачи критериев поиска в сервис.
 *
 * @package App\Domains\VerticalName\DTOs
 */
final readonly class SearchVerticalItemDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $correlationId,
        public ?string $query = null,
        public ?string $category = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?float $ratingMin = null,
        public ?bool $inStockOnly = null,
        public ?bool $b2bOnly = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = null,
        public int $perPage = 20,
        public int $page = 1,
        public bool $isB2B = false,
    ) {
    }

    /**
     * Гидрация из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            query: $request->input('q'),
            category: $request->input('category'),
            priceMin: $request->input('price_min') !== null
                ? (int) $request->input('price_min')
                : null,
            priceMax: $request->input('price_max') !== null
                ? (int) $request->input('price_max')
                : null,
            ratingMin: $request->input('rating_min') !== null
                ? (float) $request->input('rating_min')
                : null,
            inStockOnly: $request->boolean('in_stock_only', false) ?: null,
            b2bOnly: $request->boolean('b2b_only', false) ?: null,
            sortBy: $request->input('sort_by', 'created_at'),
            sortDirection: $request->input('sort_dir', 'desc'),
            perPage: min((int) $request->input('per_page', 20), 100),
            page: max((int) $request->input('page', 1), 1),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Есть ли фильтры для поиска?
     */
    public function hasFilters(): bool
    {
        return $this->query !== null
            || $this->category !== null
            || $this->priceMin !== null
            || $this->priceMax !== null
            || $this->ratingMin !== null
            || $this->inStockOnly !== null
            || $this->b2bOnly !== null;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }
}
