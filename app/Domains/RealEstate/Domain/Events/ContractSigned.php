<?php

declare(strict_types=1);

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
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\RealEstate\Domain\Events
 */
final class ContractSigned
{
    public function __construct(
        public string            $contractId,
        public string            $propertyId,
        public string            $agentId,
        public int               $clientId,
        public string            $type,
        public int               $amountKopecks,
        public int               $commissionKopecks,
        public string            $correlationId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()) {}
}
