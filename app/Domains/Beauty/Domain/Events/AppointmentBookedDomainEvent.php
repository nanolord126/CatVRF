<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;
use Ramsey\Uuid\Uuid;

/**
 * Domain Event: Beauty appointment was booked
 */
final class AppointmentBookedDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $userId,
        private readonly string $salonId,
        private readonly string $masterId,
        private readonly float $totalPrice,
        private readonly bool $isB2b,
        private readonly \DateTimeImmutable $scheduledAt,
        mixed $correlationId = null,
    ) {
        parent::__construct($correlationId ?? Uuid::uuid4()->toString());
    }

    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSalonId(): string
    {
        return $this->salonId;
    }

    public function getMasterId(): string
    {
        return $this->masterId;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function isB2b(): bool
    {
        return $this->isB2b;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function eventName(): string
    {
        return 'beauty.appointment.booked';
    }

    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'user_id' => $this->userId,
            'salon_id' => $this->salonId,
            'master_id' => $this->masterId,
            'total_price' => $this->totalPrice,
            'is_b2b' => $this->isB2b,
            'scheduled_at' => $this->scheduledAt->format(DATE_ATOM),
            'correlation_id' => $this->getCorrelationId(),
        ];
    }
}
