<?php

declare(strict_types=1);

namespace Modules\Taxi\Application\DTOs;

final readonly class DriverDto
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public int $userId,
        public string $licensePlate,
        public string $vehicleModel,
        public string $vehicleColor,
        public float $rating,
        public int $totalRides,
        public ?float $currentLatitude,
        public ?float $currentLongitude,
        public string $status,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tenantId: (int) ($data['tenant_id'] ?? 1),
            userId: (int) ($data['user_id'] ?? 0),
            licensePlate: (string) ($data['license_plate'] ?? ''),
            vehicleModel: (string) ($data['vehicle_model'] ?? ''),
            vehicleColor: (string) ($data['vehicle_color'] ?? ''),
            rating: (float) ($data['rating'] ?? 5.0),
            totalRides: (int) ($data['total_rides'] ?? 0),
            currentLatitude: isset($data['current_latitude']) ? (float) $data['current_latitude'] : null,
            currentLongitude: isset($data['current_longitude']) ? (float) $data['current_longitude'] : null,
            status: (string) ($data['status'] ?? 'offline'),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'license_plate' => $this->licensePlate,
            'vehicle_model' => $this->vehicleModel,
            'vehicle_color' => $this->vehicleColor,
            'rating' => $this->rating,
            'total_rides' => $this->totalRides,
            'current_latitude' => $this->currentLatitude,
            'current_longitude' => $this->currentLongitude,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
