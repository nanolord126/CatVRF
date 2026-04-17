<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

final readonly class TaxiDriverMatchingDto
{
    public function __construct(
        public int $rideId,
        public float $pickupLat,
        public float $pickupLon,
        public int $tenantId,
        public string $correlationId,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            rideId: (int) $data['ride_id'],
            pickupLat: (float) $data['pickup_lat'],
            pickupLon: (float) $data['pickup_lon'],
            tenantId: (int) $data['tenant_id'],
            correlationId: (string) $data['correlation_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'ride_id' => $this->rideId,
            'pickup_lat' => $this->pickupLat,
            'pickup_lon' => $this->pickupLon,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
