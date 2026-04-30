<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\DTOs;

use Illuminate\Http\Request;
use Ramsey\Uuid\UuidInterface;

final readonly class CreateAppointmentDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public array $data,
        public ?string $idempotencyKey = null,
        public bool $isB2B = false,
    ) {}

    public static function from(Request $request, UuidInterface $uuid): self
    {
        return new self(
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()?->id,
            correlationId: $request->header('X-Correlation-ID', $uuid->toString()),
            data: $request->validated(),
            idempotencyKey: $request->header('Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'is_b2b' => $this->isB2B,
            'data' => $this->data,
        ];
    }
}
