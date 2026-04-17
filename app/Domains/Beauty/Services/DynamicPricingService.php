<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\DynamicPricingDto;
use App\Domains\Beauty\Events\PriceUpdatedEvent;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautyService;
use App\Services\AI\DemandForecastService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class DynamicPricingService
{
    private const CACHE_TTL = 300;
    private const SURGE_THRESHOLD = 0.7;
    private const FLASH_DISCOUNT_THRESHOLD = 0.3;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DemandForecastService $demandForecast,
    ) {}

    public function calculate(DynamicPricingDto $dto): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'beauty_dynamic_pricing',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('User-Agent'),
            correlationId: $dto->correlationId,
        );

        $cacheKey = $this->getCacheKey($dto);
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            Log::channel('audit')->info('Dynamic pricing cache hit', [
                'correlation_id' => $dto->correlationId,
                'master_id' => $dto->masterId,
            ]);

            return json_decode($cached, true);
        }

        return DB::transaction(function () use ($dto, $cacheKey) {
            $master = Master::findOrFail($dto->masterId);
            $service = BeautyService::findOrFail($dto->serviceId);

            $basePrice = $dto->basePrice ?? $service->price;
            $forecast = $this->demandForecast->forecastForItem(
                itemId: $dto->serviceId,
                dateFrom: now(),
                dateTo: now()->addHours(2),
                context: ['vertical' => 'beauty'],
                correlationId: $dto->correlationId,
            );
            $demandScore = $forecast['demand_score'] ?? 0.5;

            $surgeMultiplier = $this->calculateSurgeMultiplier($demandScore);
            $flashDiscount = $this->calculateFlashDiscount($demandScore, $dto->timeSlot);

            $finalPrice = $this->applyPricingRules($basePrice, $surgeMultiplier, $flashDiscount, $dto->isB2B);

            $result = [
                'success' => true,
                'base_price' => $basePrice,
                'demand_score' => $demandScore,
                'surge_multiplier' => $surgeMultiplier,
                'flash_discount_percent' => $flashDiscount,
                'final_price' => $finalPrice,
                'is_surge_pricing' => $surgeMultiplier > 1.0,
                'is_flash_discount' => $flashDiscount > 0,
                'correlation_id' => $dto->correlationId,
            ];

            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($result));

            Log::channel('audit')->info('Dynamic pricing calculated', [
                'correlation_id' => $dto->correlationId,
                'master_id' => $dto->masterId,
                'service_id' => $dto->serviceId,
                'final_price' => $finalPrice,
                'demand_score' => $demandScore,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new PriceUpdatedEvent(
                masterId: $dto->masterId,
                serviceId: $dto->serviceId,
                oldPrice: $basePrice,
                newPrice: $finalPrice,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'beauty_dynamic_pricing',
                subjectType: BeautyService::class,
                subjectId: $dto->serviceId,
                oldValues: ['price' => $basePrice],
                newValues: [
                    'final_price' => $finalPrice,
                    'surge_multiplier' => $surgeMultiplier,
                    'flash_discount' => $flashDiscount,
                ],
                correlationId: $dto->correlationId,
            );

            return $result;
        });
    }

    private function calculateSurgeMultiplier(float $demandScore): float
    {
        if ($demandScore < self::SURGE_THRESHOLD) {
            return 1.0;
        }

        $excess = $demandScore - self::SURGE_THRESHOLD;
        $multiplier = 1.0 + ($excess * 2.0);

        return min(2.5, $multiplier);
    }

    private function calculateFlashDiscount(float $demandScore, ?string $timeSlot): int
    {
        if ($demandScore > self::FLASH_DISCOUNT_THRESHOLD) {
            return 0;
        }

        $hour = now()->hour;
        $isOffPeak = $hour < 10 || $hour > 19;

        if (!$isOffPeak) {
            return 0;
        }

        $discountPercent = (int) round((1.0 - $demandScore) * 30);

        return min(40, max(0, $discountPercent));
    }

    private function applyPricingRules(
        int $basePrice,
        float $surgeMultiplier,
        int $flashDiscount,
        ?bool $isB2B,
    ): int {
        $price = $basePrice;

        if ($isB2B) {
            $price = (int) ($price * 0.85);
        }

        $price = (int) round($price * $surgeMultiplier);

        if ($flashDiscount > 0) {
            $discount = (int) round($price * ($flashDiscount / 100));
            $price -= $discount;
        }

        return max(100, $price);
    }

    private function getCacheKey(DynamicPricingDto $dto): string
    {
        return sprintf(
            'beauty:dynamic_pricing:%d:%d:%d:%s',
            $dto->tenantId,
            $dto->masterId,
            $dto->serviceId,
            $dto->timeSlot ?? 'now',
        );
    }
}
