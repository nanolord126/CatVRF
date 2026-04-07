<?php
declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

use Illuminate\Http\Request;

final readonly class OrderRideDto
{
    public function __construct(
        public float $pickupLat,
        public float $pickupLon,
        public string $pickupAddress,
        public float $dropoffLat,
        public float $dropoffLon,
        public string $dropoffAddress,
        public string $vehicleClass,
        public string $correlationId,
        public ?int $customerId = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            pickupLat: (float) $request->input("pickup_lat", 0.0),
            pickupLon: (float) $request->input("pickup_lon", 0.0),
            pickupAddress: (string) $request->input("pickup_address", ""),
            dropoffLat: (float) $request->input("dropoff_lat", 0.0),
            dropoffLon: (float) $request->input("dropoff_lon", 0.0),
            dropoffAddress: (string) $request->input("dropoff_address", ""),
            vehicleClass: (string) $request->input("vehicle_class", "standard"),
            correlationId: (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            customerId: auth()->id() ?: null
        );
    }
}
