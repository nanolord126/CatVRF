<?php

declare(strict_types=1);

namespace Modules\Auto\Application\DTOs;

final readonly class VehicleMaintenanceDto
{
    public function __construct(
        public ?int $id,
        public int $vehicleId,
        public string $maintenanceType,
        public string $description,
        public float $cost,
        public string $scheduledDate,
        public ?string $completedAt,
        public string $status,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            vehicleId: (int) ($data['vehicle_id'] ?? 0),
            maintenanceType: (string) ($data['maintenance_type'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            cost: (float) ($data['cost'] ?? 0.0),
            scheduledDate: (string) ($data['scheduled_date'] ?? now()->toIso8601String()),
            completedAt: $data['completed_at'] ?? null,
            status: (string) ($data['status'] ?? 'scheduled'),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicleId,
            'maintenance_type' => $this->maintenanceType,
            'description' => $this->description,
            'cost' => $this->cost,
            'scheduled_date' => $this->scheduledDate,
            'completed_at' => $this->completedAt,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
