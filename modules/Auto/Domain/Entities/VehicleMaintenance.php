<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Auto\Domain\ValueObjects\VehicleId;

final readonly class VehicleMaintenance
{
    public function __construct(
        public readonly ?int $id,
        public readonly VehicleId $vehicleId,
        public readonly string $maintenanceType,
        public readonly string $description,
        public readonly float $cost,
        public readonly CarbonImmutable $scheduledDate,
        public readonly ?CarbonImmutable $completedAt,
        public readonly string $status,
        public readonly array $metadata,
        public readonly CarbonImmutable $createdAt,
    ) {}

    public static function schedule(
        VehicleId $vehicleId,
        string $maintenanceType,
        string $description,
        float $cost,
        CarbonImmutable $scheduledDate,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            vehicleId: $vehicleId,
            maintenanceType: $maintenanceType,
            description: $description,
            cost: $cost,
            scheduledDate: $scheduledDate,
            completedAt: null,
            status: 'scheduled',
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
        );
    }

    public function complete(): self
    {
        return new self(
            id: $this->id,
            vehicleId: $this->vehicleId,
            maintenanceType: $this->maintenanceType,
            description: $this->description,
            cost: $this->cost,
            scheduledDate: $this->scheduledDate,
            completedAt: CarbonImmutable::now(),
            status: 'completed',
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduledDate->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
