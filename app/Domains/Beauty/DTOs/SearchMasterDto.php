<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для поиска/фильтрации Master в вертикали Beauty.
 * Layer 2: DTOs — CatVRF 2026
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class SearchMasterDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public ?string $query = null,
        public ?int $salonId = null,
        public ?string $specialization = null,
        public ?float $minRating = null,
        public string $sortBy = 'rating',
        public string $sortDir = 'desc',
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
            specialization: $request->input('specialization'),
            minRating: $request->input('min_rating') ? (float) $request->input('min_rating') : null,
            sortBy: $request->input('sort_by', 'rating'),
            sortDir: $request->input('sort_dir', 'desc'),
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
            'specialization' => $this->specialization,
            'min_rating' => $this->minRating,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
