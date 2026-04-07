<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Domain\Entities;

use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Domain\Enums\RoomType;
use App\Shared\Domain\Entities\Entity;

final class Room extends Entity
{
    /**
     * @param RoomId $id
     * @param RoomType $type
     * @param int $pricePerNight
     * @param int $capacity
     * @param Collection<string> $amenities
     * @param bool $isAvailable
     */
    public function __construct(
        private readonly RoomId $id,
        private readonly RoomType $type,
        private int $pricePerNight,
        private readonly int $capacity,
        private \Illuminate\Support\Collection $amenities,
        private bool $isAvailable = true
    ) {
        if ($pricePerNight <= 0) {
            throw new \InvalidArgumentException('Price per night must be positive');
        }

        if ($capacity <= 0) {
            throw new \InvalidArgumentException('Room capacity must be positive');
        }
    }

    public function getId(): RoomId
    {
        return $this->id;
    }

    public function getType(): RoomType
    {
        return $this->type;
    }

    public function getPricePerNight(): int
    {
        return $this->pricePerNight;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getAmenities(): \Illuminate\Support\Collection
    {
        return $this->amenities;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function markAsUnavailable(): void
    {
        $this->isAvailable = false;
    }

    public function markAsAvailable(): void
    {
        $this->isAvailable = true;
    }

    public function updatePrice(int $newPrice): void
    {
        if ($newPrice <= 0) {
            throw new \InvalidArgumentException('Price must be positive.');
        }
        $this->pricePerNight = $newPrice;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'type' => $this->type->value,
            'price_per_night' => $this->pricePerNight,
            'capacity' => $this->capacity,
            'amenities' => $this->amenities->toArray(),
            'is_available' => $this->isAvailable,
        ];
    }
}
