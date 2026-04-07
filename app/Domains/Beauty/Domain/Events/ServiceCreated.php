<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Events;

use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\ValueObjects\TenantId;

/**
 * Class ServiceCreated
 *
 * Part of the Beauty vertical domain.
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
 * @package App\Domains\Beauty\Domain\Events
 */
final class ServiceCreated implements DomainEvent
{
    public function __construct(
        public readonly ServiceId $serviceId,
        public readonly TenantId $tenantId,
        public readonly string $correlationId,
    ) {
    }

    /**
     * Handle getEventName operation.
     *
     * @throws \DomainException
     */
    public function getEventName(): string
    {
        return 'beauty.service.created';
    }

    public function getPayload(): array
    {
        return [
            'service_id' => $this->serviceId->getValue(),
            'tenant_id' => $this->tenantId->getValue(),
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Время возникновения события.
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
