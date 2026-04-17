<?php declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

final readonly class TaxiPricingResultDto
{
    public function __construct(
        public int $basePrice,
        public float $surgeMultiplier,
        public int $totalPrice,
        public int $platformCommission,
        public int $fleetCommission,
        public array $priceBreakdown,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            basePrice: (int) $data['base_price'],
            surgeMultiplier: (float) $data['surge_multiplier'],
            totalPrice: (int) $data['total_price'],
            platformCommission: (int) $data['platform_commission'],
            fleetCommission: (int) $data['fleet_commission'],
            priceBreakdown: (array) $data['price_breakdown'],
        );
    }

    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice,
            'surge_multiplier' => $this->surgeMultiplier,
            'total_price' => $this->totalPrice,
            'platform_commission' => $this->platformCommission,
            'fleet_commission' => $this->fleetCommission,
            'price_breakdown' => $this->priceBreakdown,
        ];
    }
}
