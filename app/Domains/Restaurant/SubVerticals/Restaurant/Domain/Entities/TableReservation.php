<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\Enums\ReservationStatus;

final readonly class TableReservation
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $tableId,
        public ?int $userId,
        public string $customerName,
        public string $customerPhone,
        public string $customerEmail,
        public int $guestCount,
        public \Carbon\CarbonImmutable $reservationTime,
        public ?\Carbon\CarbonImmutable $arrivalTime,
        public ?\Carbon\CarbonImmutable $completedAt,
        public ?\Carbon\CarbonImmutable $cancelledAt,
        public ReservationStatus $status,
        public ?string $specialRequests,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $tableId,
        string $customerName,
        string $customerPhone,
        string $customerEmail,
        int $guestCount,
        \Carbon\CarbonImmutable $reservationTime,
        ?int $userId = null,
        ?string $specialRequests = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            tableId: $tableId,
            userId: $userId,
            customerName: $customerName,
            customerPhone: $customerPhone,
            customerEmail: $customerEmail,
            guestCount: $guestCount,
            reservationTime: $reservationTime,
            arrivalTime: null,
            completedAt: null,
            cancelledAt: null,
            status: ReservationStatus::PENDING,
            specialRequests: $specialRequests,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function confirm(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            tableId: $this->tableId,
            userId: $this->userId,
            customerName: $this->customerName,
            customerPhone: $this->customerPhone,
            customerEmail: $this->customerEmail,
            guestCount: $this->guestCount,
            reservationTime: $this->reservationTime,
            arrivalTime: null,
            completedAt: null,
            cancelledAt: null,
            status: ReservationStatus::CONFIRMED,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function markAsArrived(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            tableId: $this->tableId,
            userId: $this->userId,
            customerName: $this->customerName,
            customerPhone: $this->customerPhone,
            customerEmail: $this->customerEmail,
            guestCount: $this->guestCount,
            reservationTime: $this->reservationTime,
            arrivalTime: now()->toImmutable(),
            completedAt: null,
            cancelledAt: null,
            status: ReservationStatus::ARRIVED,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function complete(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            tableId: $this->tableId,
            userId: $this->userId,
            customerName: $this->customerName,
            customerPhone: $this->customerPhone,
            customerEmail: $this->customerEmail,
            guestCount: $this->guestCount,
            reservationTime: $this->reservationTime,
            arrivalTime: $this->arrivalTime,
            completedAt: now()->toImmutable(),
            cancelledAt: null,
            status: ReservationStatus::COMPLETED,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function cancel(?string $reason = null): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            tableId: $this->tableId,
            userId: $this->userId,
            customerName: $this->customerName,
            customerPhone: $this->customerPhone,
            customerEmail: $this->customerEmail,
            guestCount: $this->guestCount,
            reservationTime: $this->reservationTime,
            arrivalTime: $this->arrivalTime,
            completedAt: null,
            cancelledAt: now()->toImmutable(),
            status: ReservationStatus::CANCELLED,
            specialRequests: $reason ?? $this->specialRequests,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function noShow(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            tableId: $this->tableId,
            userId: $this->userId,
            customerName: $this->customerName,
            customerPhone: $this->customerPhone,
            customerEmail: $this->customerEmail,
            guestCount: $this->guestCount,
            reservationTime: $this->reservationTime,
            arrivalTime: null,
            completedAt: null,
            cancelledAt: now()->toImmutable(),
            status: ReservationStatus::NO_SHOW,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function isPastDue(): bool
    {
        return $this->reservationTime->isPast() && 
               $this->status === ReservationStatus::PENDING && 
               $this->arrivalTime === null;
    }

    public function getMinutesUntilReservation(): int
    {
        return max(0, $this->reservationTime->diffInMinutes(now()));
    }
}
