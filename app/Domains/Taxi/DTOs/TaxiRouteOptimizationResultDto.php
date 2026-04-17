<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

final readonly class TaxiRouteOptimizationResultDto
{
    public function __construct(
        public float $distanceKm,
        public int $estimatedMinutes,
        public array $waypoints,
        public float $trafficFactor,
        public float $weatherFactor,
        public string $optimizedRouteJson,
        public string $modelVersion,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            distanceKm: (float) $data['distance_km'],
            estimatedMinutes: (int) $data['estimated_minutes'],
            waypoints: (array) $data['waypoints'],
            trafficFactor: (float) $data['traffic_factor'],
            weatherFactor: (float) $data['weather_factor'],
            optimizedRouteJson: (string) $data['optimized_route_json'],
            modelVersion: (string) $data['model_version'],
        );
    }

    public function toArray(): array
    {
        return [
            'distance_km' => $this->distanceKm,
            'estimated_minutes' => $this->estimatedMinutes,
            'waypoints' => $this->waypoints,
            'traffic_factor' => $this->trafficFactor,
            'weather_factor' => $this->weatherFactor,
            'optimized_route_json' => $this->optimizedRouteJson,
            'model_version' => $this->modelVersion,
        ];
    }
}
