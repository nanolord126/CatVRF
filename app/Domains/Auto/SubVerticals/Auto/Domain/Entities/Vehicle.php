<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Auto\Domain\ValueObjects\LicensePlate;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use Modules\Auto\Domain\ValueObjects\VehicleType;

final readonly class Vehicle
{
    public function __construct(
        public readonly ?VehicleId $id,
        public readonly int $tenantId,
        public readonly VehicleType $type,
        public readonly LicensePlate $licensePlate,
        public readonly string $make,
        public readonly string $model,
        public readonly int $year,
        public readonly string $color,
        public readonly ?int $mileage,
        public readonly array $metadata,
        public readonly CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        VehicleType $type,
        LicensePlate $licensePlate,
        string $make,
        string $model,
        int $year,
        string $color,
        ?int $mileage = null,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            type: $type,
            licensePlate: $licensePlate,
            make: $make,
            model: $model,
            year: $year,
            color: $color,
            mileage: $mileage,
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
            updatedAt: null,
        );
    }

    public function withId(VehicleId $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            type: $this->type,
            licensePlate: $this->licensePlate,
            make: $this->make,
            model: $this->model,
            year: $this->year,
            color: $this->color,
            mileage: $this->mileage,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateMileage(int $newMileage): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            licensePlate: $this->licensePlate,
            make: $this->make,
            model: $this->model,
            year: $this->year,
            color: $this->color,
            mileage: $newMileage,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getAge(): int
    {
        return CarbonImmutable::now()->year - $this->year;
    }

    public function isElectric(): bool
    {
        return $this->type->value === VehicleType::ELECTRIC;
    }

    public function isHybrid(): bool
    {
        return $this->type->value === VehicleType::HYBRID;
    }
}
