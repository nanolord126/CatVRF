<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Analytics\DemandForecastMLService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final readonly class RealEstateDynamicPricingService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const FLASH_DISCOUNT_THRESHOLD = 0.85;
    private const HIGH_DEMAND_THRESHOLD = 0.80;
    private const PRICE_INCREASE_PERCENTAGE = 0.05;
    private const FLASH_DISCOUNT_MAX_PERCENTAGE = 0.15;
    private const B2B_DISCOUNT_PERCENTAGE = 0.08;
    private const MIN_PRICE_VARIATION = 0.02;
    private const MAX_PRICE_VARIATION = 0.20;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit,
        private DemandForecastMLService $demandForecast
    ) {}

    public function calculateDynamicPrice(
        Property $property,
        bool $isB2B,
        int $userId,
        string $correlationId,
        ?string $idempotencyKey = null
    ): array {
        $this->fraudControl->check(
            $userId,
            'calculate_dynamic_price',
            (int) $property->price,
            null,
            null,
            $correlationId
        );

        if ($idempotencyKey !== null) {
            $cached = Cache::get("pricing:{$property->id}:{$idempotencyKey}");
            if ($cached !== null) {
                return json_decode($cached, true);
            }
        }

        $result = DB::transaction(function () use ($property, $isB2B, $correlationId) {
            $forecast = $this->demandForecast->forecastForItem(
                $property->id,
                now(),
                now()->addDays(7),
                ['vertical' => 'real_estate']
            );
            $demandScore = $forecast['confidence_score'] ?? 0.5;

            $basePrice = $property->price;
            $priceMultiplier = $this->calculatePriceMultiplier($demandScore, $isB2B);

            $finalPrice = $basePrice * $priceMultiplier;
            $discountPercentage = $priceMultiplier < 1.0 ? ((1 - $priceMultiplier) * 100) : 0;

            $isFlashDiscount = $priceMultiplier < (1.0 - self::MIN_PRICE_VARIATION);
            $isHighDemand = $demandScore > self::HIGH_DEMAND_THRESHOLD;

            $pricing = [
                'property_id' => $property->id,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'demand_score' => $demandScore,
                'price_multiplier' => $priceMultiplier,
                'discount_percentage' => round($discountPercentage, 2),
                'is_flash_discount' => $isFlashDiscount,
                'is_high_demand' => $isHighDemand,
                'is_b2b' => $isB2B,
                'valid_until' => now()->addHours(24)->toIso8601String(),
                'calculated_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $this->audit->record(
                'dynamic_price_calculated',
                'App\Domains\RealEstate\Models\Property',
                $property->id,
                ['price' => $basePrice],
                [
                    'final_price' => $finalPrice,
                    'demand_score' => $demandScore,
                    'is_b2b' => $isB2B,
                ],
                $correlationId
            );

            return $pricing;
        });

        if ($idempotencyKey !== null) {
            Cache::put("pricing:{$property->id}:{$idempotencyKey}", json_encode($result), self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    public function getBulkPricing(
        array $propertyIds,
        bool $isB2B,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_bulk_pricing',
            0,
            null,
            null,
            $correlationId
        );

        $properties = Property::whereIn('id', $propertyIds)->get();
        $pricingResults = [];

        foreach ($properties as $property) {
            $pricingResults[$property->id] = $this->calculateDynamicPrice(
                $property,
                $isB2B,
                $userId,
                $correlationId
            );
        }

        $this->audit->record(
            'bulk_pricing_calculated',
            'App\Domains\RealEstate\Models\Property',
            null,
            [],
            [
                'property_count' => count($propertyIds),
                'is_b2b' => $isB2B,
            ],
            $correlationId
        );

        return [
            'property_pricing' => $pricingResults,
            'total_properties' => count($pricingResults),
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    public function applyFlashDiscount(
        int $propertyId,
        float $discountPercentage,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'apply_flash_discount',
            0,
            null,
            null,
            $correlationId
        );

        if ($discountPercentage < 0 || $discountPercentage > self::FLASH_DISCOUNT_MAX_PERCENTAGE * 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and ' . (self::FLASH_DISCOUNT_MAX_PERCENTAGE * 100));
        }

        $property = Property::findOrFail($propertyId);

        $result = DB::transaction(function () use ($property, $discountPercentage, $correlationId) {
            $discountMultiplier = 1 - ($discountPercentage / 100);
            $discountedPrice = $property->price * $discountMultiplier;

            $property->update([
                'metadata->flash_discount_active' => true,
                'metadata->flash_discount_percentage' => $discountPercentage,
                'metadata->flash_discount_price' => $discountedPrice,
                'metadata->flash_discount_until' => now()->addHours(48)->toIso8601String(),
            ]);

            $flashDiscountData = [
                'property_id' => $property->id,
                'original_price' => $property->price,
                'discounted_price' => $discountedPrice,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $property->price - $discountedPrice,
                'valid_until' => $property->metadata['flash_discount_until'],
                'applied_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $this->audit->record(
                'flash_discount_applied',
                'App\Domains\RealEstate\Models\Property',
                $property->id,
                ['price' => $property->price],
                [
                    'discounted_price' => $discountedPrice,
                    'discount_percentage' => $discountPercentage,
                ],
                $correlationId
            );

            return $flashDiscountData;
        });

        return $result;
    }

    public function getPriceHistory(
        int $propertyId,
        int $days,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_price_history',
            0,
            null,
            null,
            $correlationId
        );

        $property = Property::findOrFail($propertyId);
        $priceHistory = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $mockDemandScore = 0.5 + (sin($i * 0.5) * 0.3);
            $mockMultiplier = $this->calculatePriceMultiplier($mockDemandScore, false);
            $mockPrice = $property->price * $mockMultiplier;

            $priceHistory[] = [
                'date' => $date->toIso8601String(),
                'price' => $mockPrice,
                'demand_score' => $mockDemandScore,
                'price_multiplier' => $mockMultiplier,
            ];
        }

        return [
            'property_id' => $propertyId,
            'price_history' => array_reverse($priceHistory),
            'current_price' => $property->price,
            'days_analyzed' => $days,
        ];
    }

    public function getMarketComparison(
        int $propertyId,
        int $userId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'get_market_comparison',
            0,
            null,
            null,
            $correlationId
        );

        $property = Property::findOrFail($propertyId);

        $similarProperties = Property::where('id', '!=', $propertyId)
            ->where('type', $property->type)
            ->where('area_sqm', '>=', $property->area_sqm * 0.9)
            ->where('area_sqm', '<=', $property->area_sqm * 1.1)
            ->limit(10)
            ->get();

        $avgPrice = $similarProperties->avg('price') ?? $property->price;
        $minPrice = $similarProperties->min('price') ?? $property->price;
        $maxPrice = $similarProperties->max('price') ?? $property->price;

        $pricePosition = $this->calculatePricePosition($property->price, $minPrice, $maxPrice);
        $isCompetitive = $pricePosition < 0.6;

        $comparison = [
            'property_id' => $propertyId,
            'property_price' => $property->price,
            'market_avg_price' => $avgPrice,
            'market_min_price' => $minPrice,
            'market_max_price' => $maxPrice,
            'price_position' => $pricePosition,
            'is_competitive' => $isCompetitive,
            'similar_properties_count' => $similarProperties->count(),
            'recommended_price' => $avgPrice * 0.95,
            'correlation_id' => $correlationId,
        ];

        $this->audit->record(
            'market_comparison_retrieved',
            'App\Domains\RealEstate\Models\Property',
            $property->id,
            [],
            [
                'is_competitive' => $isCompetitive,
                'price_position' => $pricePosition,
            ],
            $correlationId
        );

        return $comparison;
    }

    private function calculatePriceMultiplier(float $demandScore, bool $isB2B): float
    {
        $multiplier = 1.0;

        if ($demandScore > self::HIGH_DEMAND_THRESHOLD) {
            $multiplier = 1.0 + self::PRICE_INCREASE_PERCENTAGE;
        } elseif ($demandScore < self::FLASH_DISCOUNT_THRESHOLD) {
            $discountIntensity = (self::FLASH_DISCOUNT_THRESHOLD - $demandScore) / self::FLASH_DISCOUNT_THRESHOLD;
            $multiplier = 1.0 - ($discountIntensity * self::FLASH_DISCOUNT_MAX_PERCENTAGE);
        }

        if ($isB2B) {
            $multiplier *= (1.0 - self::B2B_DISCOUNT_PERCENTAGE);
        }

        $multiplier = max(1.0 - self::MAX_PRICE_VARIATION, min(1.0 + self::MAX_PRICE_VARIATION, $multiplier));

        return round($multiplier, 4);
    }

    private function calculatePricePosition(float $price, float $minPrice, float $maxPrice): float
    {
        if ($maxPrice === $minPrice) {
            return 0.5;
        }

        return ($price - $minPrice) / ($maxPrice - $minPrice);
    }
}
