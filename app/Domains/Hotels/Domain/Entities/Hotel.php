<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Domain\Entities;

use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\Address;
use App\Shared\Domain\Entities\Entity;
use Illuminate\Support\Collection;

final class Hotel extends Entity
{
    /**
     * @param HotelId $id
     * @param int $tenantId
     * @param string $name
     * @param Address $address
     * @param string $description
     * @param Collection<Room> $rooms
     * @param Collection<string> $amenities
     * @param float $rating
     * @param string|null $correlationId
     */
    public function __construct(
        private readonly HotelId $id,
        private readonly int $tenantId,
        private string $name,
        private Address $address,
        protected string $description,
        private Collection $rooms,
        private Collection $amenities,
        private float $rating = 0.0,
        private ?string $correlationId = null
    ) {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Hotel name cannot be empty');
        }

        if ($rating < 0.0 || $rating > 5.0) {
            throw new \InvalidArgumentException('Rating must be between 0.0 and 5.0');
        }
    }

    public function getId(): HotelId
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function getAmenities(): Collection
    {
        return $this->amenities;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function updateDetails(string $name, string $description): void
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function addRoom(Room $room): void
    {
        if (!$this->rooms->contains('id', $room->getId())) {
            $this->rooms->add($room);
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'address' => $this->address->toArray(),
            'description' => $this->description,
            'rating' => $this->rating,
            'amenities' => $this->amenities->toArray(),
            'rooms' => $this->rooms->map(fn (Room $room) => $room->toArray())->toArray(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
