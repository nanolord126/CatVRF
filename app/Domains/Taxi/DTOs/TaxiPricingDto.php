<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

final readonly class TaxiPricingDto
{
    public function __construct(
        public float $distanceKm,
        public int $estimatedMinutes,
        public float $pickupLat,
        public float $pickupLon,
        public int $tenantId,
        public bool $isB2B,
        public string $correlationId,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            distanceKm: (float) $data['distance_km'],
            estimatedMinutes: (int) $data['estimated_minutes'],
            pickupLat: (float) $data['pickup_lat'],
            pickupLon: (float) $data['pickup_lon'],
            tenantId: (int) $data['tenant_id'],
            isB2B: (bool) $data['is_b2b'],
            correlationId: (string) $data['correlation_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'distance_km' => $this->distanceKm,
            'estimated_minutes' => $this->estimatedMinutes,
            'pickup_lat' => $this->pickupLat,
            'pickup_lon' => $this->pickupLon,
            'tenant_id' => $this->tenantId,
            'is_b2b' => $this->isB2B,
            'correlation_id' => $this->correlationId,
        ];
    }
}
