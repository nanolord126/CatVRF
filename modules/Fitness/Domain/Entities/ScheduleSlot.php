<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class ScheduleSlot
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public int $trainerId,
        public int $workoutTypeId,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public int $capacity,
        public int $bookedCount,
        public bool $isRecurring,
        public ?string $recurrencePattern,
        public ?CarbonImmutable $recurrenceEnd,
        public ?string $notes,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        int $trainerId,
        int $workoutTypeId,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        int $capacity = 20,
        bool $isRecurring = false,
        ?string $recurrencePattern = null,
        ?CarbonImmutable $recurrenceEnd = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            trainerId: $trainerId,
            workoutTypeId: $workoutTypeId,
            startTime: $startTime,
            endTime: $endTime,
            capacity: $capacity,
            bookedCount: 0,
            isRecurring: $isRecurring,
            recurrencePattern: $recurrencePattern,
            recurrenceEnd: $recurrenceEnd,
            notes: $notes,
            isActive: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasAvailableSlots(): bool
    {
        return $this->bookedCount < $this->capacity;
    }

    public function getAvailableSlots(): int
    {
        return max(0, $this->capacity - $this->bookedCount);
    }

    public function getOccupancyRate(): float
    {
        if ($this->capacity === 0) {
            return 0.0;
        }

        return ($this->bookedCount / $this->capacity) * 100;
    }

    public function incrementBookedCount(): self
    {
        return new self(
            ...get_object_vars($this),
            bookedCount: $this->bookedCount + 1,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function decrementBookedCount(): self
    {
        return new self(
            ...get_object_vars($this),
            bookedCount: max(0, $this->bookedCount - 1),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: false,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isPast(): bool
    {
        return $this->endTime->isPast();
    }

    public function isFuture(): bool
    {
        return $this->startTime->isFuture();
    }
}
