<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для поиска/фильтрации Appointment в вертикали Beauty.
 * Layer 2: DTOs — CatVRF 2026
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class SearchAppointmentDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public ?string $query = null,
        public ?string $status = null,
        public ?int $salonId = null,
        public ?int $masterId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public string $sortBy = 'created_at',
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
            status: $request->input('status'),
            salonId: $request->input('salon_id') ? (int) $request->input('salon_id') : null,
            masterId: $request->input('master_id') ? (int) $request->input('master_id') : null,
            dateFrom: $request->input('date_from'),
            dateTo: $request->input('date_to'),
            sortBy: $request->input('sort_by', 'created_at'),
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
            'status' => $this->status,
            'salon_id' => $this->salonId,
            'master_id' => $this->masterId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
