<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\DTOs;

use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class CreateServiceDto
 *
 * Part of the Gardening vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final readonly class CreateServiceDto
{
    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $businessGroupId,
        private readonly int $userId,
        private readonly string $correlationId,
        private readonly array $data,
        private readonly ?string $idempotencyKey = null,
        private readonly bool $isB2B = false
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId:          (int) $request->user()?->id,
            correlationId:   $request->header('X-Correlation-ID', Str::uuid()->toString()),
            data:            $request->validated(),
            idempotencyKey:  $request->header('Idempotency-Key'),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return array_merge($this->data, [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
        ]);
    }
}
