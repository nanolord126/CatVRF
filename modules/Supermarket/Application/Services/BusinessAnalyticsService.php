<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Traits\WithAnalyticsTracking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

/**
 * BusinessAnalyticsService — Сервис бизнес-аналитики для продавцов
 * 
 * Предоставляет метрики продаж, выручки, маржи, конверсии и т.д.
 */
final class BusinessAnalyticsService
{
    use WithAuditLogging;
    use WithTelemetry;
    use WithAnalyticsTracking;

    /**
     * Получить сводку бизнеса
     */
    public function getBusinessSummary(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_analytics.business_summary',
            function () use ($tenantId, $businessGroupId, $days) {
                $cacheKey = "supermarket:analytics:summary:{$tenantId}:{$businessGroupId}:{$days}";
                
                return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId, $businessGroupId, $days) {
                    $startDate = now()->subDays($days);
                    $endDate = now();

                    return [
                        'period' => [
                            'start' => $startDate->toIso8601String(),
                            'end' => $endDate->toIso8601String(),
                            'days' => $days,
                        ],
                        'revenue' => $this->getRevenueMetrics($tenantId, $businessGroupId, $startDate, $endDate),
                        'orders' => $this->getOrderMetrics($tenantId, $businessGroupId, $startDate, $endDate),
                        'customers' => $this->getCustomerMetrics($tenantId, $businessGroupId, $startDate, $endDate),
                        'products' => $this->getProductMetrics($tenantId, $businessGroupId, $startDate, $endDate),
                        'conversion' => $this->getConversionMetrics($tenantId, $businessGroupId, $startDate, $endDate),
                        'generated_at' => now()->toIso8601String(),
                    ];
                });
            },
            $this->getStandardAttributes('supermarket', 'analytics_summary')
        );
    }

    /**
     * Метрики выручки
     */
    private function getRevenueMetrics(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        // TODO: Запрос к БД
        return [
            'total' => 1500000, // RUB
            'gross' => 1800000,
            'refunds' => 300000,
            'net' => 1500000,
            'average_order_value' => 1250,
            'revenue_per_day' => 50000,
            'trend' => 'increasing',
            'trend_percentage' => 15.5,
            'by_payment_method' => [
                'card' => ['amount' => 900000, 'percentage' => 60],
                'sbp' => ['amount' => 450000, 'percentage' => 30],
                'cash' => ['amount' => 150000, 'percentage' => 10],
            ],
        ];
    }

    /**
     * Метрики заказов
     */
    private function getOrderMetrics(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        return [
            'total' => 1200,
            'completed' => 1100,
            'cancelled' => 80,
            'refunded' => 20,
            'completion_rate' => 91.7,
            'cancellation_rate' => 6.7,
            'refund_rate' => 1.7,
            'average_preparation_time' => 25, // minutes
            'average_delivery_time' => 45, // minutes
            'on_time_delivery_rate' => 94.5,
            'by_delivery_type' => [
                'courier' => ['count' => 800, 'percentage' => 66.7],
                'pickup' => ['count' => 400, 'percentage' => 33.3],
            ],
            'by_status' => [
                'pending' => 50,
                'confirmed' => 100,
                'processing' => 150,
                'ready' => 100,
                'delivering' => 200,
                'delivered' => 600,
            ],
        ];
    }

    /**
     * Метрики клиентов
     */
    private function getCustomerMetrics(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        return [
            'total' => 450,
            'new' => 150,
            'returning' => 300,
            'loyal' => 100, // >5 orders
            'churn_rate' => 8.5,
            'retention_rate' => 91.5,
            'average_ltv' => 8500,
            'average_orders_per_customer' => 2.7,
            'by_loyalty_tier' => [
                'bronze' => 300,
                'silver' => 100,
                'gold' => 40,
                'platinum' => 10,
            ],
        ];
    }

    /**
     * Метрики продуктов
     */
    private function getProductMetrics(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        return [
            'total_products' => 250,
            'active_products' => 220,
            'out_of_stock' => 15,
            'low_stock' => 30,
            'top_selling' => [
                ['product_id' => 1, 'name' => 'Молоко 3.2%', 'sales' => 500, 'revenue' => 50000],
                ['product_id' => 2, 'name' => 'Хлеб белый', 'sales' => 400, 'revenue' => 20000],
                ['product_id' => 3, 'name' => 'Яйца 10шт', 'sales' => 350, 'revenue' => 35000],
            ],
            'by_category' => [
                ['category' => 'Молочные', 'sales' => 1500, 'revenue' => 150000],
                ['category' => 'Выпечка', 'sales' => 1200, 'revenue' => 60000],
                ['category' => 'Овощи', 'sales' => 1000, 'revenue' => 80000],
            ],
            'inventory_turnover' => 4.5, // раз в месяц
            'average_margin' => 25.5, // %
        ];
    }

    /**
     * Метрики конверсии
     */
    private function getConversionMetrics(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        return [
            'visitors' => 15000,
            'product_views' => 8000,
            'add_to_cart' => 3000,
            'checkout' => 2000,
            'orders' => 1200,
            'view_to_cart_rate' => 37.5,
            'cart_to_checkout_rate' => 66.7,
            'checkout_to_order_rate' => 60.0,
            'overall_conversion' => 8.0,
            'funnel_dropoffs' => [
                'product_to_cart' => 5000,
                'cart_to_checkout' => 1000,
                'checkout_to_order' => 800,
            ],
        ];
    }

    /**
     * Получить прогноз выручки
     */
    public function getRevenueForecast(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_analytics.revenue_forecast',
            function () use ($tenantId, $businessGroupId, $days) {
                $cacheKey = "supermarket:analytics:forecast:{$tenantId}:{$businessGroupId}:{$days}";
                
                return Cache::remember($cacheKey, now()->addHours(6), function () use ($tenantId, $businessGroupId, $days) {
                    $historicalData = $this->getHistoricalRevenue($tenantId, $businessGroupId, 90);
                    
                    // Простая линейная регрессия для прогноза
                    $trend = $this->calculateTrend($historicalData);
                    $seasonalFactor = $this->getSeasonalFactor();
                    
                    $forecast = [];
                    $baseRevenue = end($historicalData)['revenue'];
                    
                    for ($i = 1; $i <= $days; $i++) {
                        $predictedRevenue = $baseRevenue * (1 + $trend) * $seasonalFactor;
                        $forecast[] = [
                            'date' => now()->addDays($i)->toIso8601String(),
                            'predicted_revenue' => round($predictedRevenue),
                            'confidence' => max(0.7, 0.95 - ($i * 0.008)), // Убывает со временем
                        ];
                        $baseRevenue = $predictedRevenue;
                    }

                    return [
                        'forecast_period_days' => $days,
                        'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
                        'trend_percentage' => round($trend * 100, 2),
                        'seasonal_factor' => $seasonalFactor,
                        'total_forecast' => array_sum(array_column($forecast, 'predicted_revenue')),
                        'daily_forecast' => $forecast,
                        'generated_at' => now()->toIso8601String(),
                    ];
                });
            },
            $this->getStandardAttributes('supermarket', 'analytics_forecast')
        );
    }

    /**
     * Получить историческую выручку
     */
    private function getHistoricalRevenue(int $tenantId, ?int $businessGroupId, int $days): array
    {
        // TODO: Запрос к БД
        $data = [];
        for ($i = $days; $i > 0; $i--) {
            $data[] = [
                'date' => now()->subDays($i)->toIso8601String(),
                'revenue' => 45000 + rand(-5000, 5000),
            ];
        }
        return $data;
    }

    /**
     * Рассчитать тренд
     */
    private function calculateTrend(array $data): float
    {
        if (count($data) < 2) return 0;
        
        $midPoint = (int) floor(count($data) / 2);
        $firstHalf = array_slice($data, 0, $midPoint);
        $secondHalf = array_slice($data, $midPoint);
        
        $avgFirst = array_sum(array_column($firstHalf, 'revenue')) / count($firstHalf);
        $avgSecond = array_sum(array_column($secondHalf, 'revenue')) / count($secondHalf);
        
        return ($avgSecond - $avgFirst) / $avgFirst;
    }

    /**
     * Получить сезонный фактор
     */
    private function getSeasonalFactor(): float
    {
        $month = now()->month;
        $factors = [
            1 => 0.9, 2 => 0.95, 3 => 1.0, 4 => 1.0, 5 => 1.05,
            6 => 1.1, 7 => 1.1, 8 => 1.05, 9 => 1.0, 10 => 1.0,
            11 => 1.1, 12 => 1.15,
        ];
        
        return $factors[$month] ?? 1.0;
    }
}
