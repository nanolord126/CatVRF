<?php

declare(strict_types=1);

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
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\RealEstate\Domain\Events
 */
final class ViewingConfirmed
{
    public function __construct(
        public string            $viewingId,
        public string            $propertyId,
        public int               $clientId,
        public string            $agentId,
        public DateTimeImmutable $scheduledAt,
        public string            $correlationId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()) {}
}
