<?php

declare(strict_types=1);

namespace Modules\Taxi\Application\DTOs;

final readonly class RideDto
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public int $userId,
        public int $driverId,
        public float $pickupLatitude,
        public float $pickupLongitude,
        public string $pickupAddress,
        public float $dropoffLatitude,
        public float $dropoffLongitude,
        public string $dropoffAddress,
        public string $status,
        public float $estimatedPrice,
        public float $actualPrice,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tenantId: (int) ($data['tenant_id'] ?? 1),
            userId: (int) ($data['user_id'] ?? 0),
            driverId: (int) ($data['driver_id'] ?? 0),
            pickupLatitude: (float) ($data['pickup_latitude'] ?? 0.0),
            pickupLongitude: (float) ($data['pickup_longitude'] ?? 0.0),
            pickupAddress: (string) ($data['pickup_address'] ?? ''),
            dropoffLatitude: (float) ($data['dropoff_latitude'] ?? 0.0),
            dropoffLongitude: (float) ($data['dropoff_longitude'] ?? 0.0),
            dropoffAddress: (string) ($data['dropoff_address'] ?? ''),
            status: (string) ($data['status'] ?? 'requested'),
            estimatedPrice: (float) ($data['estimated_price'] ?? 0.0),
            actualPrice: (float) ($data['actual_price'] ?? 0.0),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'driver_id' => $this->driverId,
            'pickup_latitude' => $this->pickupLatitude,
            'pickup_longitude' => $this->pickupLongitude,
            'pickup_address' => $this->pickupAddress,
            'dropoff_latitude' => $this->dropoffLatitude,
            'dropoff_longitude' => $this->dropoffLongitude,
            'dropoff_address' => $this->dropoffAddress,
            'status' => $this->status,
            'estimated_price' => $this->estimatedPrice,
            'actual_price' => $this->actualPrice,
            'metadata' => $this->metadata,
        ];
    }
}
