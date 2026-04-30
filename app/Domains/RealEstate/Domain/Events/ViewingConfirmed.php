<?php

declare(strict_types=1);
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class ViewingConfirmed
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
final class ViewingConfirmed
{
    public function __construct(
        public string $viewingId,
        public string $propertyId,
        public int $clientId,
        public string $agentId,
        public DateTimeImmutable $scheduledAt,
        public string $correlationId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}
