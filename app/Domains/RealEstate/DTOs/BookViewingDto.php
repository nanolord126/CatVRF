<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

final readonly class BookViewingDto
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly int $propertyId,
        public readonly Carbon $scheduledAt,
        public readonly bool $isB2B,
        public readonly ?string $idempotencyKey = null,
        public readonly ?array $metadata = null
    ) {}

    public static function from(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            tenantId: (int) tenant()?->id ?? $validated['tenant_id'],
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()?->id ?? $validated['user_id'],
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            propertyId: (int) $validated['property_id'],
            scheduledAt: Carbon::parse($validated['scheduled_at']),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
            idempotencyKey: $request->header('Idempotency-Key'),
            metadata: $validated['metadata'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'property_id' => $this->propertyId,
            'scheduled_at' => $this->scheduledAt->toIso8601String(),
            'is_b2b' => $this->isB2B,
            'metadata' => $this->metadata,
        ];
    }
}
