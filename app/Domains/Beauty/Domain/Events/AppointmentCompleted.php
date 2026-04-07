<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Events;

use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\ValueObjects\ClientId;
use Carbon\CarbonImmutable;

/**
 * Class AppointmentCompleted
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
final class AppointmentCompleted implements DomainEvent
{
    public function __construct(
        public readonly AppointmentId $appointmentId,
        public readonly SalonId       $salonId,
        public readonly MasterId      $masterId,
        public readonly ServiceId     $serviceId,
        public readonly ClientId      $clientId,
        public readonly CarbonImmutable $completedAt,
        public readonly string        $correlationId,
    ) {
    }

    public function getEventName(): string
    {
        return 'beauty.appointment.completed';
    }

    public function getPayload(): array
    {
        return [
            'appointment_id' => $this->appointmentId->getValue(),
            'salon_id'       => $this->salonId->getValue(),
            'master_id'      => $this->masterId->getValue(),
            'service_id'     => $this->serviceId->getValue(),
            'client_id'      => $this->clientId->getValue(),
            'completed_at'   => $this->completedAt->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
