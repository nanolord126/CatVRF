<?php
declare(strict_types=1);

namespace App\Domains\Food\DTOs;

use Illuminate\Http\Request;

final readonly class CreateFoodOrderDto
{
    public function __construct(
        public int $restaurantId,
        public int $customerId,
        public array $items,
        public float $deliveryLat,
        public float $deliveryLon,
        public string $deliveryAddress,
        public ?string $specialInstructions,
        public string $correlationId
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            restaurantId: (int) $request->input("restaurant_id", 0),
            customerId: (int) (auth()->id() ?? 0),
            items: (array) $request->input("items", []),
            deliveryLat: (float) $request->input("delivery_lat", 0.0),
            deliveryLon: (float) $request->input("delivery_lon", 0.0),
            deliveryAddress: (string) $request->input("delivery_address", ""),
            specialInstructions: $request->input("special_instructions"),
            correlationId: (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid())
        );
    }
}
