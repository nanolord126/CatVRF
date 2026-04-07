<?php

declare(strict_types=1);

namespace App\Domains\CleaningServices\DTOs;

use Illuminate\Http\Request;

/**
 * Class SearchServiceDto
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\CleaningServices\DTOs
 */
final readonly class SearchServiceDto
{
    public function __construct(
        private readonly int     $tenantId,
        private readonly ?int    $businessGroupId,
        private readonly int     $userId,
        private readonly string  $correlationId,
        private ?string $query = null,
        private ?string $status = null,
        private ?string $sortBy = 'created_at',
        private string $sortDir = 'desc',
        public int $perPage = 20,
        private int $page = 1,
        private bool $isB2B = false) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
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
}
