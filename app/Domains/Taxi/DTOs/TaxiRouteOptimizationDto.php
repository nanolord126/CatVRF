<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

final readonly class TaxiRouteOptimizationDto
{
    public function __construct(
        public float $pickupLat,
        public float $pickupLon,
        public float $dropoffLat,
        public float $dropoffLon,
        public int $tenantId,
        public string $correlationId,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            pickupLat: (float) $data['pickup_lat'],
            pickupLon: (float) $data['pickup_lon'],
            dropoffLat: (float) $data['dropoff_lat'],
            dropoffLon: (float) $data['dropoff_lon'],
            tenantId: (int) $data['tenant_id'],
            correlationId: (string) $data['correlation_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'pickup_lat' => $this->pickupLat,
            'pickup_lon' => $this->pickupLon,
            'dropoff_lat' => $this->dropoffLat,
            'dropoff_lon' => $this->dropoffLon,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
