<?php

declare(strict_types=1);

namespace App\DTOs\AI\Luxury;

/**
 * Request DTO for Luxury AI Constructor
 */
final readonly class LuxuryRequestDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public string $correlationId,
        public array $inputData,
        public ?string $idempotencyKey = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            correlationId: $data['correlation_id'],
            inputData: $data['input_data'] ?? [],
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'input_data' => $this->inputData,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}