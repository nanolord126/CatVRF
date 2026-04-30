<?php

declare(strict_types=1);
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class PropertyListed
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
final class PropertyListed
{
    public function __construct(
        public string $propertyId,
        public int $tenantId,
        public string $correlationId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}
