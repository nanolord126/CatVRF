<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Entities;

use App\Domains\Auto\Taxi\Domain\Enums\VehicleClassEnum;
use App\Domains\Auto\Taxi\Domain\ValueObjects\VehicleId;
use App\Shared\Domain\Entity;

final class Vehicle extends Entity
{
    public function __construct(
        private readonly VehicleId $id,
        private string $brand,
        private string $model,
        private string $licensePlate,
        private VehicleClassEnum $class,
        private bool $isInUse,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt) {

    }

    public static function create(
        VehicleId $id,
        string $brand,
        string $model,
        string $licensePlate,
        VehicleClassEnum $class
    ): self {
        $now = new \DateTimeImmutable();
        return new self(
            $id,
            $brand,
            $model,
            $licensePlate,
            $class,
            false,
            $now,
            $now
        );
    }

    public function getId(): VehicleId
    {
        return $this->id;
    }

    public function markAsInUse(): void
    {
        $this->isInUse = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsFree(): void
    {
        $this->isInUse = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'brand' => $this->brand,
            'model' => $this->model,
            'license_plate' => $this->licensePlate,
            'class' => $this->class->value,
            'is_in_use' => $this->isInUse,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
