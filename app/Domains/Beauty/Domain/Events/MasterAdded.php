<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Events;

use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Shared\Domain\Events\DomainEvent;

/**
 * Class MasterAdded
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Beauty\Domain\Events
 */
final class MasterAdded implements DomainEvent
{
    public function __construct(
        public readonly MasterId $masterId,
        public readonly SalonId $salonId,
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
        return 'beauty.master.added';
    }

    /**
     * Handle getPayload operation.
     *
     * @throws \DomainException
     */
    public function getPayload(): array
    {
        return [
            'master_id' => $this->masterId->getValue(),
            'salon_id' => $this->salonId->getValue(),
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
