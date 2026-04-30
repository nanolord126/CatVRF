<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Taxi\Domain\ValueObjects\Location;

final readonly class Driver
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $licensePlate,
        public readonly string $vehicleModel,
        public readonly string $vehicleColor,
        public readonly float $rating,
        public readonly int $totalRides,
        public readonly ?Location $currentLocation,
        public readonly string $status,
        public readonly array $metadata,
        public readonly CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $userId,
        string $licensePlate,
        string $vehicleModel,
        string $vehicleColor,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            userId: $userId,
            licensePlate: $licensePlate,
            vehicleModel: $vehicleModel,
            vehicleColor: $vehicleColor,
            rating: 5.0,
            totalRides: 0,
            currentLocation: null,
            status: 'offline',
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
            updatedAt: null,
        );
    }

    public function updateLocation(Location $location): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            licensePlate: $this->licensePlate,
            vehicleModel: $this->vehicleModel,
            vehicleColor: $this->vehicleColor,
            rating: $this->rating,
            totalRides: $this->totalRides,
            currentLocation: $location,
            status: $this->status,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function goOnline(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            licensePlate: $this->licensePlate,
            vehicleModel: $this->vehicleModel,
            vehicleColor: $this->vehicleColor,
            rating: $this->rating,
            totalRides: $this->totalRides,
            currentLocation: $this->currentLocation,
            status: 'online',
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function goOffline(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            licensePlate: $this->licensePlate,
            vehicleModel: $this->vehicleModel,
            vehicleColor: $this->vehicleColor,
            rating: $this->rating,
            totalRides: $this->totalRides,
            currentLocation: $this->currentLocation,
            status: 'offline',
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateRating(float $newRating): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            licensePlate: $this->licensePlate,
            vehicleModel: $this->vehicleModel,
            vehicleColor: $this->vehicleColor,
            rating: $newRating,
            totalRides: $this->totalRides + 1,
            currentLocation: $this->currentLocation,
            status: $this->status,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online';
    }
}
