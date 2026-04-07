<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Events;

use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\ValueObjects\ClientId;
use Carbon\CarbonImmutable;

/**
 * Class AppointmentBooked
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
final class AppointmentBooked implements DomainEvent
{
    public function __construct(
        public readonly AppointmentId $appointmentId,
        public readonly ClientId $clientId,
        public readonly CarbonImmutable $startAt,
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
        return 'beauty.appointment.booked';
    }

    /**
     * Handle getPayload operation.
     *
     * @throws \DomainException
     */
    public function getPayload(): array
    {
        return [
            'appointment_id' => $this->appointmentId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'start_at' => $this->startAt->toIso8601String(),
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
