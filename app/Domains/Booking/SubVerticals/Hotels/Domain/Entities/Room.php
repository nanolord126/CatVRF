<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Room
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public int $roomTypeId,
        public string $uuid,
        public string $roomNumber,
        public string $floor,
        public ?string $building,
        public ?string $wing,
        public string $status,
        public string $cleanStatus,
        public ?array $features,
        public ?array $photos,
        public bool $isAccessible,
        public bool $hasView,
        public bool $connectable,
        public ?array $connectedRooms,
        public ?string $housekeepingNotes,
        public ?CarbonImmutable $lastCleanedAt,
        public ?int $lastCleanedBy,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        int $roomTypeId,
        string $roomNumber,
        string $floor = '1',
        ?string $building = null,
        ?string $wing = null,
        ?array $features = null,
        ?array $photos = null,
        bool $isAccessible = false,
        bool $hasView = false,
        bool $connectable = false,
        ?array $connectedRooms = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            roomTypeId: $roomTypeId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            roomNumber: $roomNumber,
            floor: $floor,
            building: $building,
            wing: $wing,
            status: 'available',
            cleanStatus: 'clean',
            features: $features,
            photos: $photos,
            isAccessible: $isAccessible,
            hasView: $hasView,
            connectable: $connectable,
            connectedRooms: $connectedRooms,
            housekeepingNotes: null,
            lastCleanedAt: null,
            lastCleanedBy: null,
            isActive: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markAvailable(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'available',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markOccupied(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'occupied',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markDirty(): self
    {
        return new self(
            ...get_object_vars($this),
            cleanStatus: 'dirty',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markClean(): self
    {
        return new self(
            ...get_object_vars($this),
            cleanStatus: 'clean',
            lastCleanedAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markMaintenance(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'maintenance',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->cleanStatus === 'clean';
    }

    public function needsCleaning(): bool
    {
        return $this->cleanStatus === 'dirty';
    }
}
