<?php

declare(strict_types=1);

namespace App\DTO\SplitKey;

use Carbon\CarbonImmutable;

/**
 * Generate Split Key DTO
 *
 * Immutable data transfer object for split key generation.
 * Contains server part and device attestation data.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class GenerateSplitKeyDTO
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public string $serverPart,
        public ?array $deviceAttestation,
        public ?string $ipAddress,
        public ?string $userAgent,
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
            serverPart: $data['server_part'],
            deviceAttestation: $data['device_attestation'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
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
            'server_part' => $this->serverPart,
            'device_attestation' => $this->deviceAttestation,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'correlation_id' => $this->correlationId,
        ];
    }
}
