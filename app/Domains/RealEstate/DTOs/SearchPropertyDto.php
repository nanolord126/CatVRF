<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class SearchPropertyDto
{
    public function __construct(
        public float $lat,
        public float $lon,
        public float $radiusKm,
        public ?string $type,
        public ?float $minPrice,
        public ?float $maxPrice,
        public string $correlationId
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            lat: (float) $request->input("lat", 0.0),
            lon: (float) $request->input("lon", 0.0),
            radiusKm: (float) $request->input("radius_km", 10.0),
            type: $request->input("type"),
            minPrice: $request->has("min_price") ? (float) $request->input("min_price") : null,
            maxPrice: $request->has("max_price") ? (float) $request->input("max_price") : null,
            correlationId: (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid())
        );
    }
}
