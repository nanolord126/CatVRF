<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для поиска/фильтрации Service в вертикали Beauty.
 * Layer 2: DTOs — CatVRF 2026
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class SearchServiceDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public ?string $query = null,
        public ?int $salonId = null,
        public ?string $category = null,
        public ?int $maxPrice = null,
        public ?int $maxDuration = null,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
        public int $perPage = 20,
        public int $page = 1,
        public bool $isB2B = false,
    ) {
    }

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            userId: (int) $request->user()?->id,
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            query: $request->input('q'),
            salonId: $request->input('salon_id') ? (int) $request->input('salon_id') : null,
            category: $request->input('category'),
            maxPrice: $request->input('max_price') ? (int) $request->input('max_price') : null,
            maxDuration: $request->input('max_duration') ? (int) $request->input('max_duration') : null,
            sortBy: $request->input('sort_by', 'name'),
            sortDir: $request->input('sort_dir', 'asc'),
            perPage: (int) $request->input('per_page', 20),
            page: (int) $request->input('page', 1),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function toFilterArray(): array
    {
        return array_filter([
            'salon_id' => $this->salonId,
            'category' => $this->category,
            'max_price' => $this->maxPrice,
            'max_duration' => $this->maxDuration,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
