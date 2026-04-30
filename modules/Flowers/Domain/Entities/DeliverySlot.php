<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class DeliverySlot
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $tenantId,
        public CarbonImmutable $date,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public int $capacity,
        public int $bookedCount,
        public bool $isAvailable,
        public float $deliveryFee,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $venueId,
        int $tenantId,
        CarbonImmutable $date,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        int $capacity = 5,
        float $deliveryFee = 0,
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            tenantId: $tenantId,
            date: $date,
            startTime: $startTime,
            endTime: $endTime,
            capacity: $capacity,
            bookedCount: 0,
            isAvailable: true,
            deliveryFee: $deliveryFee,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getAvailableSlots(): int
    {
        return max(0, $this->capacity - $this->bookedCount);
    }

    public function isFullyBooked(): bool
    {
        return $this->bookedCount >= $this->capacity;
    }

    public function canBook(int $quantity = 1): bool
    {
        return $this->isAvailable && !$this->isFullyBooked() && $this->getAvailableSlots() >= $quantity;
    }

    public function book(int $quantity = 1): self
    {
        if (!$this->canBook($quantity)) {
            throw new \RuntimeException('Cannot book this delivery slot');
        }

        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            date: $this->date,
            startTime: $this->startTime,
            endTime: $this->endTime,
            capacity: $this->capacity,
            bookedCount: $this->bookedCount + $quantity,
            isAvailable: $this->getAvailableSlots() - $quantity > 0,
            deliveryFee: $this->deliveryFee,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function releaseBooking(int $quantity = 1): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            date: $this->date,
            startTime: $this->startTime,
            endTime: $this->endTime,
            capacity: $this->capacity,
            bookedCount: max(0, $this->bookedCount - $quantity),
            isAvailable: true,
            deliveryFee: $this->deliveryFee,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
