<?php

declare(strict_types=1);

namespace App\DTO\SplitKey;

/**
 * Validate Split Key DTO
 *
 * Immutable data transfer object for split key validation.
 * Contains challenge and signature for verification.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class ValidateSplitKeyDTO
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public string $challenge,
        public string $signature,
        public ?string $ipAddress,
        public ?string $correlationId,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'] ?? null,
            challenge: $data['challenge'],
            signature: $data['signature'],
            ipAddress: $data['ip_address'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'challenge' => $this->challenge,
            'signature' => $this->signature,
            'ip_address' => $this->ipAddress,
            'correlation_id' => $this->correlationId,
        ];
    }
}
