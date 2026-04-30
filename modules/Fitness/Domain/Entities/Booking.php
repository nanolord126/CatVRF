<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Enums\BookingStatus;

final readonly class Booking
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public int $scheduleSlotId,
        public BookingStatus $status,
        public ?int $membershipId,
        public CarbonImmutable $bookedAt,
        public ?CarbonImmutable $checkedInAt,
        public ?CarbonImmutable $cancelledAt,
        public ?string $cancellationReason,
        public ?string $notes,
        public bool $isPaid,
        public float $price,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        int $scheduleSlotId,
        ?int $membershipId = null,
        bool $isPaid = false,
        float $price = 0.0,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            scheduleSlotId: $scheduleSlotId,
            status: BookingStatus::PENDING,
            membershipId: $membershipId,
            bookedAt: CarbonImmutable::now(),
            checkedInAt: null,
            cancelledAt: null,
            cancellationReason: null,
            notes: $notes,
            isPaid: $isPaid,
            price: $price,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function confirm(): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::CONFIRMED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function checkIn(): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::CHECKED_IN,
            checkedInAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::COMPLETED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(?string $reason = null): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::CANCELLED,
            cancelledAt: CarbonImmutable::now(),
            cancellationReason: $reason,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markNoShow(): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::NO_SHOW,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addToWaitlist(): self
    {
        return new self(
            ...get_object_vars($this),
            status: BookingStatus::WAITLIST,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    public function canCheckIn(): bool
    {
        return $this->status->canCheckIn();
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }
}
