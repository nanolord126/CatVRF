<?php

declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Illuminate\Http\Request;

/**
 * Class SearchServiceDto
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Hotels\DTOs
 */
final readonly class SearchServiceDto
{
    public function __construct(
        private int     $tenantId,
        private ?int    $businessGroupId,
        private int     $userId,
        private string  $correlationId,
        private ?string $query = null,
        private ?string $status = null,
        private ?string $sortBy = 'created_at',
        private string  $sortDir = 'desc',
        private int     $perPage = 20,
        private int     $page = 1,
        private bool    $isB2B = false,
    ) {}

    public static function from(Request $request, int $tenantId): self
    {
        return new self(
            tenantId:        $tenantId,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            query:           $request->input('q'),
            status:          $request->input('status'),
            sortBy:          $request->input('sort_by', 'created_at'),
            sortDir:         $request->input('sort_dir', 'desc'),
            perPage:         (int) $request->input('per_page', 20),
            page:            (int) $request->input('page', 1),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Преобразовать в массив для фильтрации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
            'query'             => $this->query,
            'status'            => $this->status,
            'sort_by'           => $this->sortBy,
            'sort_dir'          => $this->sortDir,
            'per_page'          => $this->perPage,
            'page'              => $this->page,
            'is_b2b'            => $this->isB2B,
        ];
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function isB2B(): bool
    {
        return $this->isB2B;
    }
}
