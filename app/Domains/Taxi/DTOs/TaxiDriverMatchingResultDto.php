<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;

final readonly class TaxiDriverMatchingResultDto
{
    public function __construct(
        public ?TaxiDriver $driver,
        public ?TaxiVehicle $vehicle,
        public int $predictedEta,
        public float $driverScore,
        public float $distanceToPickup,
        public array $matchingCriteria,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            driver: $data['driver'] ?? null,
            vehicle: $data['vehicle'] ?? null,
            predictedEta: (int) $data['predicted_eta'],
            driverScore: (float) $data['driver_score'],
            distanceToPickup: (float) $data['distance_to_pickup'],
            matchingCriteria: (array) $data['matching_criteria'],
        );
    }

    public function toArray(): array
    {
        return [
            'driver_id' => $this->driver?->id,
            'vehicle_id' => $this->vehicle?->id,
            'predicted_eta' => $this->predictedEta,
            'driver_score' => $this->driverScore,
            'distance_to_pickup' => $this->distanceToPickup,
            'matching_criteria' => $this->matchingCriteria,
        ];
    }
}
