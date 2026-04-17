<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

final readonly class DynamicPricingDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $masterId,
        public int $serviceId,
        public ?string $timeSlot,
        public ?int $basePrice,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?bool $isB2B = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            businessGroupId: $request->has('inn') && $request->has('business_card_id')
                ? (int) $request->input('business_card_id')
                : null,
            masterId: (int) $request->input('master_id'),
            serviceId: (int) $request->input('service_id'),
            timeSlot: $request->input('time_slot'),
            basePrice: $request->input('base_price') ? (int) $request->input('base_price') : null,
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'master_id' => $this->masterId,
            'service_id' => $this->serviceId,
            'time_slot' => $this->timeSlot,
            'base_price' => $this->basePrice,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'is_b2b' => $this->isB2B,
        ];
    }
}
