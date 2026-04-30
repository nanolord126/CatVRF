<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * DynamicPricingService — Сервис динамического ценообразования
 * 
 * Продавец устанавливает пороги. Если не установлены - цена не опускается ниже базовой,
 * но может расти. Разница роста остается платформе.
 */
final class DynamicPricingService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    // Факторы ценообразования
    private const FACTOR_DEMAND = 'demand';
    private const FACTOR_COMPETITION = 'competition';
    private const FACTOR_TIME = 'time';
    private const FACTOR_STOCK = 'stock';
    private const FACTOR_SEASONAL = 'seasonal';

    /**
     * Рассчитать динамическую цену для продукта
     */
    public function calculateDynamicPrice(
        int $tenantId,
        int $productId,
        int $basePrice,
        array $sellerSettings = []
    ): array {
        return $this->withSpan(
            'supermarket_pricing.calculate_dynamic_price',
            function () use ($tenantId, $productId, $basePrice, $sellerSettings) {
                $cacheKey = "supermarket:dynamic_price:{$tenantId}:{$productId}";
                
                return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $productId, $basePrice, $sellerSettings) {
                    $factors = $this->calculatePricingFactors($tenantId, $productId);
                    
                    $priceMultiplier = 1.0;
                    $adjustments = [];
                    
                    // Применяем каждый фактор
                    foreach ($factors as $factorName => $factor) {
                        if ($factor['weight'] > 0) {
                            $adjustment = $this->applyFactor($factorName, $factor, $basePrice);
                            $priceMultiplier *= (1 + $adjustment['multiplier_change']);
                            $adjustments[$factorName] = $adjustment;
                        }
                    }

                    // Получаем настройки продавца
                    $minPrice = $sellerSettings['min_price'] ?? $basePrice; // Если не задано - минимум = базовая цена
                    $maxPrice = $sellerSettings['max_price'] ?? round($basePrice * 1.5); // Если не задано - максимум +50%
                    $platformMarginPercent = $sellerSettings['platform_margin'] ?? 30; // По умолчанию 30% от роста
                    
                    $dynamicPrice = round($basePrice * $priceMultiplier);
                    
                    // Ограничение по настройкам продавца
                    $dynamicPrice = max($minPrice, min($maxPrice, $dynamicPrice));
                    
                    // Рассчитываем маржу платформы
                    $priceIncrease = $dynamicPrice - $basePrice;
                    $platformMargin = 0;
                    
                    if ($priceIncrease > 0) {
                        // Маржа платформы от роста цены
                        $platformMargin = round($priceIncrease * ($platformMarginPercent / 100));
                        $dynamicPrice = $basePrice + $priceIncrease + $platformMargin;
                    }
                    
                    $priceChange = round(($dynamicPrice - $basePrice) / $basePrice * 100, 2);

                    return [
                        'product_id' => $productId,
                        'base_price' => $basePrice,
                        'dynamic_price' => $dynamicPrice,
                        'price_change_percentage' => $priceChange,
                        'price_increase' => $priceIncrease,
                        'platform_margin' => $platformMargin,
                        'seller_amount' => $dynamicPrice - $platformMargin,
                        'seller_settings' => [
                            'min_price' => $minPrice,
                            'max_price' => $maxPrice,
                            'platform_margin_percent' => $platformMarginPercent,
                        ],
                        'factors' => $factors,
                        'adjustments' => $adjustments,
                        'calculated_at' => now()->toIso8601String(),
                        'valid_until' => now()->addMinutes(15)->toIso8601String(),
                    ];
                });
            },
            $this->getStandardAttributes('supermarket', 'pricing_dynamic_price')
        );
    }

    /**
     * Рассчитать факторы ценообразования
     */
    private function calculatePricingFactors(int $tenantId, int $productId, array $context): array
    {
        return [
            self::FACTOR_DEMAND => $this->calculateDemandFactor($tenantId, $productId),
            self::FACTOR_STOCK => $this->calculateStockFactor($tenantId, $productId),
            self::FACTOR_TIME => $this->calculateTimeFactor(),
            self::FACTOR_SEASONAL => $this->calculateSeasonalFactor($productId),
            self::FACTOR_COMPETITION => $this->calculateCompetitionFactor($productId),
        ];
    }

    /**
     * Фактор спроса
     */
    private function calculateDemandFactor(int $tenantId, int $productId): array
    {
        $cacheKey = "supermarket:demand:{$tenantId}:{$productId}";
        
        $demandData = Cache::remember($cacheKey, now()->addHours(1), function () use ($tenantId, $productId) {
            // TODO: Запросить реальные данные продаж за последние 7 дней
            return [
                'sales_7d' => 150,
                'sales_14d' => 280,
                'sales_30d' => 550,
                'trend' => 'increasing', // increasing, stable, decreasing
                'trend_percentage' => 15.5,
            ];
        });

        $trendMultiplier = match($demandData['trend']) {
            'increasing' => 0.1,
            'stable' => 0,
            'decreasing' => -0.05,
            default => 0,
        };

        return [
            'weight' => 0.3,
            'value' => $demandData['trend_percentage'],
            'trend' => $demandData['trend'],
            'multiplier' => $trendMultiplier,
            'reason' => "Спрос {$demandData['trend']} на {$demandData['trend_percentage']}%",
        ];
    }

    /**
     * Фактор остатков на складе
     */
    private function calculateStockFactor(int $tenantId, int $productId): array
    {
        $cacheKey = "supermarket:stock:{$tenantId}:{$productId}";
        
        $stockData = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId, $productId) {
            // TODO: Запросить реальные данные о складе
            return [
                'current_stock' => 45,
                'optimal_stock' => 100,
                'reserved' => 15,
                'available' => 30,
            ];
        });

        $stockRatio = $stockData['current_stock'] / $stockData['optimal_stock'];
        
        if ($stockRatio <= 0.1) {
            $multiplier = 0.15; // Очень мало - повышаем цену
            $reason = 'Критически низкий остаток';
        } elseif ($stockRatio <= 0.3) {
            $multiplier = 0.08;
            $reason = 'Низкий остаток';
        } elseif ($stockRatio >= 1.5) {
            $multiplier = -0.1; // Избыток - снижаем цену
            $reason = 'Избыток товара';
        } else {
            $multiplier = 0;
            $reason = 'Нормальный остаток';
        }

        return [
            'weight' => 0.25,
            'value' => $stockRatio,
            'multiplier' => $multiplier,
            'reason' => $reason,
        ];
    }

    /**
     * Фактор времени суток
     */
    private function calculateTimeFactor(): array
    {
        $hour = now()->hour;
        
        // Пиковые часы: 12-14 (обед) и 18-20 (ужин)
        if (in_array($hour, range(12, 14)) || in_array($hour, range(18, 20))) {
            $multiplier = 0.05;
            $reason = 'Пиковый час';
        } elseif (in_array($hour, range(22, 6))) {
            $multiplier = -0.03;
            $reason = 'Ночное время';
        } else {
            $multiplier = 0;
            $reason = 'Обычное время';
        }

        return [
            'weight' => 0.1,
            'value' => $hour,
            'multiplier' => $multiplier,
            'reason' => $reason,
        ];
    }

    /**
     * Сезонный фактор
     */
    private function calculateSeasonalFactor(int $productId): array
    {
        $month = now()->month;
        
        $seasonalFactors = [
            1 => 0.9,  // Январь - после праздников
            2 => 0.95,
            3 => 1.0,
            4 => 1.0,
            5 => 1.05,
            6 => 1.1,  // Лето
            7 => 1.1,
            8 => 1.05,
            9 => 1.0,
            10 => 1.0,
            11 => 1.1,  // Предновогодний
            12 => 1.15, // Декабрь
        ];

        $factor = $seasonalFactors[$month] ?? 1.0;
        $multiplier = ($factor - 1.0) * 0.5; // 50% влияния сезонности

        return [
            'weight' => 0.2,
            'value' => $factor,
            'multiplier' => $multiplier,
            'reason' => "Сезонный коэффициент: {$factor}",
        ];
    }

    /**
     * Фактор конкуренции
     */
    private function calculateCompetitionFactor(int $productId): array
    {
        $cacheKey = "supermarket:competition:{$productId}";
        
        $competitionData = Cache::remember($cacheKey, now()->addHours(6), function () use ($productId) {
            // TODO: Парсинг цен конкурентов
            return [
                'avg_competitor_price' => 150,
                'min_competitor_price' => 130,
                'max_competitor_price' => 180,
                'competitors_count' => 5,
            ];
        });

        // TODO: Получить текущую цену продукта
        $currentPrice = 145;
        
        if ($currentPrice < $competitionData['min_competitor_price']) {
            $multiplier = 0.05; // Мы дешевле всех - можно повысить
            $reason = 'Цена ниже конкурентов';
        } elseif ($currentPrice > $competitionData['max_competitor_price']) {
            $multiplier = -0.08; // Мы дороже всех - нужно снизить
            $reason = 'Цена выше конкурентов';
        } else {
            $multiplier = 0;
            $reason = 'Цена в рынке';
        }

        return [
            'weight' => 0.15,
            'value' => $currentPrice / $competitionData['avg_competitor_price'],
            'multiplier' => $multiplier,
            'reason' => $reason,
            'competitor_data' => $competitionData,
        ];
    }

    /**
     * Применить фактор
     */
    private function applyFactor(string $factorName, array $factor, int $basePrice): array
    {
        $priceChange = round($basePrice * $factor['multiplier']);
        
        return [
            'factor' => $factorName,
            'weight' => $factor['weight'],
            'multiplier_change' => $factor['multiplier'],
            'price_change' => $priceChange,
            'reason' => $factor['reason'],
        ];
    }

    /**
     * Массовый пересчёт цен для категории
     */
    public function batchRecalculatePrices(int $tenantId, string $category): array
    {
        return $this->withSpan(
            'supermarket_pricing.batch_recalculate',
            function () use ($tenantId, $category) {
                // TODO: Получить все продукты категории
                $products = []; // Product::where('category', $category)->get();
                
                $results = [];
                foreach ($products as $product) {
                    $results[$product->id] = $this->calculateDynamicPrice(
                        $tenantId,
                        $product->id,
                        $product->base_price
                    );
                }

                $this->logAction('batch_price_recalculation', null, [
                    'tenant_id' => $tenantId,
                    'category' => $category,
                    'products_count' => count($products),
                ], null, $tenantId);

                return $results;
            },
            $this->getStandardAttributes('supermarket', 'pricing_batch_recalculate')
        );
    }
}
