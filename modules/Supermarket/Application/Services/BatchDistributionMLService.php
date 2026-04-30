<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonImmutable;

/**
 * BatchDistributionMLService — ML сервис для распределения партий на торговые объекты
 * 
 * Использует алгоритмы для рекомендаций по распределению товаров:
 * - Исторический спрос по точкам
 * - Сезонность
 * - Прогнозирование спроса
 * - Оптимизация логистики
 */
final class BatchDistributionMLService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    // ========================
    // BATCH DISTRIBUTION RECOMMENDATIONS
    // ========================

    /**
     * Получить рекомендации по распределению партии
     */
    public function getDistributionRecommendations(
        int $tenantId,
        ?int $businessGroupId = null,
        array $batchItems,
        array $storeLocations
    ): array {
        return $this->withSpan(
            'supermarket_ml.get_distribution_recommendations',
            function () use ($tenantId, $businessGroupId, $batchItems, $storeLocations) {
                $recommendations = [];

                foreach ($batchItems as $item) {
                    $productId = $item['product_id'];
                    $quantity = $item['quantity'];
                    
                    // Get historical demand for each store
                    $demandForecast = $this->predictDemand($tenantId, $businessGroupId, $productId, 30);
                    
                    // Calculate optimal distribution
                    $distribution = $this->calculateOptimalDistribution(
                        $quantity,
                        $demandForecast,
                        $storeLocations
                    );

                    $recommendations[$productId] = [
                        'product_id' => $productId,
                        'total_quantity' => $quantity,
                        'distribution' => $distribution,
                        'confidence_score' => $this->calculateConfidenceScore($demandForecast),
                        'reasoning' => $this->generateReasoning($distribution, $demandForecast),
                    ];
                }

                $this->logAction('distribution_recommendations_generated', null, [
                    'tenant_id' => $tenantId,
                    'items_count' => count($batchItems),
                    'stores_count' => count($storeLocations),
                ], null, $tenantId);

                return $recommendations;
            },
            $this->getStandardAttributes('supermarket', 'ml_distribution_recommendations'),
        );
    }

    /**
     * Предсказать спрос для продукта по точкам
     */
    private function predictDemand(
        int $tenantId,
        ?int $businessGroupId,
        int $productId,
        int $days
    ): array {
        $cacheKey = "supermarket:demand:{$tenantId}:{$businessGroupId}:{$productId}:{$days}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($tenantId, $businessGroupId, $productId, $days) {
            // TODO: Implement actual ML prediction
            // For now, use historical data with seasonal adjustment
            
            $historicalData = $this->getHistoricalSales($tenantId, $businessGroupId, $productId, $days);
            $seasonalFactor = $this->getSeasonalFactor($productId);
            $trend = $this->calculateTrend($historicalData);

            $predictions = [];
            foreach ($historicalData as $storeId => $sales) {
                $baseDemand = array_sum($sales) / count($sales);
                $predictedDemand = ($baseDemand * $seasonalFactor) + $trend;
                
                $predictions[$storeId] = [
                    'store_id' => $storeId,
                    'predicted_demand' => max(0, round($predictedDemand)),
                    'historical_avg' => round($baseDemand),
                    'trend' => $trend,
                    'seasonal_factor' => $seasonalFactor,
                    'confidence' => $this->calculatePredictionConfidence($sales),
                ];
            }

            return $predictions;
        });
    }

    /**
     * Получить исторические продажи
     */
    private function getHistoricalSales(
        int $tenantId,
        ?int $businessGroupId,
        int $productId,
        int $days
    ): array {
        // TODO: Implement actual query to get historical sales data
        // For now, return mock data
        return [
            1 => [15, 18, 20, 17, 22, 19, 21],
            2 => [10, 12, 11, 13, 14, 12, 15],
            3 => [25, 28, 30, 27, 32, 29, 31],
        ];
    }

    /**
     * Получить сезонный коэффициент
     */
    private function getSeasonalFactor(int $productId): float
    {
        $month = now()->month;
        
        // Seasonal factors based on month
        $seasonalFactors = [
            1 => 0.9,  // January - post-holiday low
            2 => 0.95,
            3 => 1.0,
            4 => 1.05,
            5 => 1.1,
            6 => 1.15, // Summer peak
            7 => 1.15,
            8 => 1.1,
            9 => 1.05,
            10 => 1.0,
            11 => 1.1,  // Pre-holiday
            12 => 1.2,  // Holiday peak
        ];

        return $seasonalFactors[$month] ?? 1.0;
    }

    /**
     * Рассчитать тренд
     */
    private function calculateTrend(array $salesData): float
    {
        if (count($salesData) < 2) {
            return 0;
        }

        $firstHalf = array_slice($salesData, 0, floor(count($salesData) / 2));
        $secondHalf = array_slice($salesData, floor(count($salesData) / 2));

        $avgFirst = array_sum($firstHalf) / count($firstHalf);
        $avgSecond = array_sum($secondHalf) / count($secondHalf);

        return $avgSecond - $avgFirst;
    }

    /**
     * Рассчитать оптимальное распределение
     */
    private function calculateOptimalDistribution(
        int $totalQuantity,
        array $demandForecast,
        array $storeLocations
    ): array {
        $totalPredictedDemand = array_sum(array_column($demandForecast, 'predicted_demand'));
        
        if ($totalPredictedDemand === 0) {
            // Equal distribution if no demand data
            $equalShare = floor($totalQuantity / count($storeLocations));
            $distribution = [];
            foreach ($storeLocations as $store) {
                $distribution[$store['id']] = [
                    'store_id' => $store['id'],
                    'quantity' => $equalShare,
                    'percentage' => round(($equalShare / $totalQuantity) * 100, 2),
                    'reason' => 'equal_distribution_no_data',
                ];
            }
            return $distribution;
        }

        $distribution = [];
        $remainingQuantity = $totalQuantity;

        // Sort stores by predicted demand
        uasort($demandForecast, function ($a, $b) {
            return $b['predicted_demand'] <=> $a['predicted_demand'];
        });

        foreach ($demandForecast as $storeId => $forecast) {
            $predictedDemand = $forecast['predicted_demand'];
            $demandPercentage = $predictedDemand / $totalPredictedDemand;
            
            // Calculate quantity based on demand percentage
            $suggestedQuantity = round($totalQuantity * $demandPercentage);
            
            // Apply minimum and maximum constraints
            $minQuantity = max(1, floor($totalQuantity * 0.05)); // At least 5%
            $maxQuantity = ceil($totalQuantity * 0.5); // At most 50%
            
            $quantity = max($minQuantity, min($maxQuantity, $suggestedQuantity));
            
            // Adjust for remaining quantity
            if ($quantity > $remainingQuantity) {
                $quantity = $remainingQuantity;
            }

            $distribution[$storeId] = [
                'store_id' => $storeId,
                'quantity' => $quantity,
                'percentage' => round(($quantity / $totalQuantity) * 100, 2),
                'predicted_demand' => $predictedDemand,
                'demand_satisfaction' => round(($quantity / $predictedDemand) * 100, 2),
                'reason' => 'demand_based',
            ];

            $remainingQuantity -= $quantity;

            if ($remainingQuantity <= 0) {
                break;
            }
        }

        // Distribute remaining quantity to stores with highest unsatisfied demand
        if ($remainingQuantity > 0) {
            $this->distributeRemainder($distribution, $remainingQuantity, $demandForecast);
        }

        return $distribution;
    }

    /**
     * Распределить остаток
     */
    private function distributeRemainder(array &$distribution, int $remainingQuantity, array $demandForecast): void
    {
        // Sort by demand satisfaction (lowest first)
        uasort($distribution, function ($a, $b) {
            return ($a['demand_satisfaction'] ?? 100) <=> ($b['demand_satisfaction'] ?? 100);
        });

        foreach ($distribution as $storeId => &$item) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $additionalQuantity = min(5, $remainingQuantity); // Add max 5 at a time
            $item['quantity'] += $additionalQuantity;
            $item['percentage'] = round(($item['quantity'] / array_sum(array_column($distribution, 'quantity'))) * 100, 2);
            $item['demand_satisfaction'] = round(($item['quantity'] / $item['predicted_demand']) * 100, 2);
            $remainingQuantity -= $additionalQuantity;
        }
    }

    /**
     * Рассчитать оценку уверенности
     */
    private function calculateConfidenceScore(array $demandForecast): float
    {
        if (empty($demandForecast)) {
            return 0.5;
        }

        $confidences = array_column($demandForecast, 'confidence');
        $avgConfidence = array_sum($confidences) / count($confidences);

        return round($avgConfidence, 2);
    }

    /**
     * Рассчитать уверенность предсказания
     */
    private function calculatePredictionConfidence(array $sales): float
    {
        if (count($sales) < 3) {
            return 0.3;
        }

        $mean = array_sum($sales) / count($sales);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $sales)) / count($sales);
        
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $stdDev / $mean;

        // Lower variance = higher confidence
        $confidence = max(0.3, min(0.95, 1 - $coefficientOfVariation));
        
        return round($confidence, 2);
    }

    /**
     * Сгенерировать объяснение
     */
    private function generateReasoning(array $distribution, array $demandForecast): string
    {
        $topStore = null;
        $maxPercentage = 0;

        foreach ($distribution as $storeId => $item) {
            if ($item['percentage'] > $maxPercentage) {
                $maxPercentage = $item['percentage'];
                $topStore = $storeId;
            }
        }

        $reasoning = "Распределение основано на прогнозе спроса. ";
        $reasoning .= "Точка #{$topStore} имеет наибольший прогноз спроса ({$maxPercentage}%). ";
        
        $avgSatisfaction = array_sum(array_column($distribution, 'demand_satisfaction')) / count($distribution);
        $reasoning .= "Среднее удовлетворение спроса: " . round($avgSatisfaction, 1) . "%. ";

        return $reasoning;
    }

    // ========================
    // DEMAND ANALYSIS
    // ========================

    /**
     * Проанализировать спрос за период
     */
    public function analyzeDemand(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_ml.analyze_demand',
            function () use ($tenantId, $businessGroupId, $days) {
                // TODO: Implement actual demand analysis
                $totalSales = $this->getTotalSales($tenantId, $businessGroupId, $days);
                $topProducts = $this->getTopProducts($tenantId, $businessGroupId, $days, 10);
                $topStores = $this->getTopStores($tenantId, $businessGroupId, $days, 5);
                $demandTrends = $this->getDemandTrends($tenantId, $businessGroupId, $days);

                return [
                    'total_sales' => $totalSales,
                    'top_products' => $topProducts,
                    'top_stores' => $topStores,
                    'demand_trends' => $demandTrends,
                    'period_days' => $days,
                    'generated_at' => now()->toIso8601String(),
                ];
            },
            $this->getStandardAttributes('supermarket', 'ml_demand_analysis'),
        );
    }

    /**
     * Получить общие продажи
     */
    private function getTotalSales(int $tenantId, ?int $businessGroupId, int $days): array
    {
        // TODO: Implement actual query
        return [
            'revenue' => 1500000,
            'quantity' => 5000,
            'orders' => 1200,
        ];
    }

    /**
     * Получить топ продукты
     */
    private function getTopProducts(int $tenantId, ?int $businessGroupId, int $days, int $limit): array
    {
        // TODO: Implement actual query
        return [
            ['product_id' => 1, 'name' => 'Молоко', 'sales' => 500, 'revenue' => 50000],
            ['product_id' => 2, 'name' => 'Хлеб', 'sales' => 400, 'revenue' => 20000],
            ['product_id' => 3, 'name' => 'Яйца', 'sales' => 350, 'revenue' => 35000],
        ];
    }

    /**
     * Получить топ точки
     */
    private function getTopStores(int $tenantId, ?int $businessGroupId, int $days, int $limit): array
    {
        // TODO: Implement actual query
        return [
            ['store_id' => 1, 'name' => 'Точка #1', 'sales' => 800, 'revenue' => 800000],
            ['store_id' => 2, 'name' => 'Точка #2', 'sales' => 600, 'revenue' => 600000],
        ];
    }

    /**
     * Получить тренды спроса
     */
    private function getDemandTrends(int $tenantId, ?int $businessGroupId, int $days): array
    {
        // TODO: Implement actual trend analysis
        return [
            'overall_trend' => 'increasing',
            'trend_percentage' => 15.5,
            'categories' => [
                ['category' => 'Молочные', 'trend' => 'increasing', 'percentage' => 12],
                ['category' => 'Выпечка', 'trend' => 'stable', 'percentage' => 2],
                ['category' => 'Овощи', 'trend' => 'increasing', 'percentage' => 20],
            ],
        ];
    }

    // ========================
    // STOCK OPTIMIZATION
    // ========================

    /**
     * Оптимизировать стоки по точкам
     */
    public function optimizeStockLevels(
        int $tenantId,
        ?int $businessGroupId = null,
        array $currentStock,
        array $demandForecast
    ): array {
        return $this->withSpan(
            'supermarket_ml.optimize_stock',
            function () use ($tenantId, $businessGroupId, $currentStock, $demandForecast) {
            $optimizations = [];

            foreach ($currentStock as $storeId => $products) {
                foreach ($products as $productId => $stockLevel) {
                    $predictedDemand = $demandForecast[$storeId][$productId]['predicted_demand'] ?? 0;
                    
                    // Calculate optimal stock level (safety stock + lead time demand)
                    $leadTimeDays = 3;
                    $safetyStockDays = 7;
                    $optimalStock = ceil(($predictedDemand / 30) * ($leadTimeDays + $safetyStockDays));
                    
                    $currentStockValue = $stockLevel['quantity'] ?? 0;
                    
                    if ($currentStockValue < $optimalStock * 0.3) {
                        // Critical low stock - urgent replenishment needed
                        $priority = 'critical';
                        $recommendedQuantity = $optimalStock - $currentStockValue;
                    } elseif ($currentStockValue < $optimalStock * 0.7) {
                        // Low stock - replenishment recommended
                        $priority = 'high';
                        $recommendedQuantity = ceil($optimalStock * 0.5) - $currentStockValue;
                    } elseif ($currentStockValue > $optimalStock * 1.5) {
                        // Overstock - consider redistribution
                        $priority = 'overstock';
                        $recommendedQuantity = 0;
                    } else {
                        // Optimal level
                        $priority = 'optimal';
                        $recommendedQuantity = 0;
                    }

                    $optimizations[$storeId][$productId] = [
                        'current_stock' => $currentStockValue,
                        'optimal_stock' => $optimalStock,
                        'predicted_daily_demand' => round($predictedDemand / 30, 2),
                        'priority' => $priority,
                        'recommended_quantity' => $recommendedQuantity,
                        'reason' => $this->getStockOptimizationReason($priority, $currentStockValue, $optimalStock),
                    ];
                }
            }

            return $optimizations;
        },
            $this->getStandardAttributes('supermarket', 'ml_stock_optimization'),
        );
    }

    /**
     * Получить причину оптимизации стока
     */
    private function getStockOptimizationReason(string $priority, int $current, int $optimal): string
    {
        switch ($priority) {
            case 'critical':
                return "Критически низкий уровень стока ({$current} из {$optimal}). Требуется срочное пополнение.";
            case 'high':
                return "Низкий уровень стока ({$current} из {$optimal}). Рекомендуется пополнение.";
            case 'overstock':
                return "Избыточный сток ({$current} из {$optimal}). Рассмотрите перераспределение.";
            case 'optimal':
                return "Оптимальный уровень стока.";
            default:
                return "";
        }
    }
}
