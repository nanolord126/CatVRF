<?php

declare(strict_types=1);

namespace App\DTO\SplitKey;

/**
 * Invalidate Split Key DTO
 *
 * Immutable data transfer object for split key invalidation.
 * Contains reason and risk level for audit.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class InvalidateSplitKeyDTO
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public string $reason,
        public string $riskLevel, // low, medium, high, critical
        public ?string $source, // fraud, behavioral, insider, manual
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
            reason: $data['reason'],
            riskLevel: $data['risk_level'],
            source: $data['source'] ?? null,
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
            'reason' => $this->reason,
            'risk_level' => $this->riskLevel,
            'source' => $this->source,
            'ip_address' => $this->ipAddress,
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Check if invalidation is critical (requires immediate action)
     */
    public function isCritical(): bool
    {
        return $this->riskLevel === 'critical' || $this->riskLevel === 'high';
    }
}
