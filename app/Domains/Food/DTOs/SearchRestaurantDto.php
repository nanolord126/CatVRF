<?php
declare(strict_types=1);

namespace App\Domains\Food\DTOs;

use Illuminate\Http\Request;

final readonly class SearchRestaurantDto
{
    public function __construct(
        public float $lat,
        public float $lon,
        public float $radiusKm,
        public string $correlationId,
        public ?string $query = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            lat: (float) $request->input("lat", 0.0),
            lon: (float) $request->input("lon", 0.0),
            radiusKm: (float) $request->input("radius_km", 10.0),
            correlationId: (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            query: $request->input("query")
        );
    }
}
