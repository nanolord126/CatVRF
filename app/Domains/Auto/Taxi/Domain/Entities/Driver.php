<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Entities;

use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\VehicleId;
use App\Shared\Domain\Entity;

final class Driver extends Entity
{
    public function __construct(
        private readonly DriverId $id,
        private string $name,
        private string $licenseNumber,
        private bool $isAvailable,
        private ?VehicleId $vehicleId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt) {

    }

    public static function create(
        DriverId $id,
        string $name,
        string $licenseNumber,
    ): self {
        $now = new \DateTimeImmutable();
        return new self(
            $id,
            $name,
            $licenseNumber,
            true,
            null,
            $now,
            $now
        );
    }

    public function getId(): DriverId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLicenseNumber(): string
    {
        return $this->licenseNumber;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function assignVehicle(VehicleId $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsUnavailable(): void
    {
        $this->isAvailable = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsAvailable(): void
    {
        $this->isAvailable = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'name' => $this->name,
            'license_number' => $this->licenseNumber,
            'is_available' => $this->isAvailable,
            'vehicle_id' => $this->vehicleId?->toString(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
