<?php

declare(strict_types=1);
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class ContractSigned
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class ContractSigned
{
    public function __construct(
        public string $contractId,
        public string $propertyId,
        public string $agentId,
        public int $clientId,
        public string $type,
        public int $amountKopecks,
        public int $commissionKopecks,
        public string $correlationId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}
