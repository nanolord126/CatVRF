<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\Enums\AppointmentStatus;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\Price;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\ClientId;
use Carbon\CarbonImmutable;
use DomainException;

final class Appointment extends Entity
{
    public function __construct(
        private AppointmentId $id,
        private SalonId $salonId,
        private MasterId $masterId,
        private ServiceId $serviceId,
        private ClientId $clientId,
        private CarbonImmutable $startAt,
        private CarbonImmutable $endAt,
        private Price $price,
        private AppointmentStatus $status,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): AppointmentId
    {
        return $this->id;
    }

    public function getSalonId(): SalonId
    {
        return $this->salonId;
    }

    public function getMasterId(): MasterId
    {
        return $this->masterId;
    }

    public function getServiceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getStartAt(): CarbonImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): CarbonImmutable
    {
        return $this->endAt;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function confirm(): void
    {
        if ($this->status !== AppointmentStatus::PENDING) {
            throw new DomainException('Only pending appointments can be confirmed.');
        }
        $this->status = AppointmentStatus::CONFIRMED;
        $this->touch();
    }

    public function complete(): void
    {
        if ($this->status !== AppointmentStatus::CONFIRMED) {
            throw new DomainException('Only confirmed appointments can be completed.');
        }
        $this->status = AppointmentStatus::COMPLETED;
        $this->touch();
    }

    public function cancel(): void
    {
        if (in_array($this->status, [AppointmentStatus::COMPLETED, AppointmentStatus::CANCELLED])) {
            throw new DomainException('Cannot cancel an already completed or cancelled appointment.');
        }
        $this->status = AppointmentStatus::CANCELLED;
        $this->touch();
    }

    public function reschedule(CarbonImmutable $newStartAt, CarbonImmutable $newEndAt): void
    {
        if ($this->status !== AppointmentStatus::CONFIRMED) {
            throw new DomainException('Only confirmed appointments can be rescheduled.');
        }
        $this->startAt = $newStartAt;
        $this->endAt = $newEndAt;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'salon_id' => $this->salonId->getValue(),
            'master_id' => $this->masterId->getValue(),
            'service_id' => $this->serviceId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'start_at' => $this->startAt->toIso8601String(),
            'end_at' => $this->endAt->toIso8601String(),
            'price' => $this->price->getAmountInCents(),
            'status' => $this->status->value,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
