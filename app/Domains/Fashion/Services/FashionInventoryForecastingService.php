<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Inventory Management with Demand Forecasting для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Прогнозирование спроса, оптимизация запасов,
        автоматические заказы, анализ Out-of-Stock.
 */
final readonly class FashionInventoryForecastingService
{
    private const FORECAST_DAYS = 30;
    private const REORDER_THRESHOLD = 0.2;
    private const SAFETY_STOCK_DAYS = 14;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Прогнозировать спрос на товар.
     */
    public function forecastDemand(
        int $productId,
        int $daysAhead = self::FORECAST_DAYS,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found', 404);
        }

        $historicalSales = $this->getHistoricalSales($productId, $tenantId, 90);
        $trend = $this->calculateTrend($historicalSales);
        $seasonality = $this->calculateSeasonality($productId, $tenantId);
        $baseDemand = $this->calculateBaseDemand($historicalSales);

        $forecast = [];
        for ($day = 1; $day <= $daysAhead; $day++) {
            $forecastDate = Carbon::now()->addDays($day);
            $dayOfWeek = $forecastDate->dayOfWeek;
            $seasonMultiplier = $seasonality[$dayOfWeek] ?? 1.0;
            
            $dailyDemand = $baseDemand * $trend * $seasonMultiplier;
            $forecast[] = [
                'date' => $forecastDate->toIso8601String(),
                'predicted_demand' => round($dailyDemand),
                'cumulative_demand' => round(array_sum(array_column(array_slice($forecast, 0, $day), 'predicted_demand'))),
            ];
        }

        $totalForecastDemand = array_sum(array_column($forecast, 'predicted_demand'));
        $currentStock = $product['stock_quantity'];
        $stockoutDate = $this->calculateStockoutDate($currentStock, $forecast);

        $this->saveDemandForecast($productId, $tenantId, $forecast, $correlationId);

        $this->audit->record(
            action: 'fashion_demand_forecasted',
            subjectType: 'fashion_product',
            subjectId: $productId,
            oldValues: [],
            newValues: [
                'forecast_days' => $daysAhead,
                'total_demand' => $totalForecastDemand,
                'stockout_date' => $stockoutDate,
            ],
            correlationId: $correlationId
        );

        return [
            'product_id' => $productId,
            'current_stock' => $currentStock,
            'forecast' => $forecast,
            'total_forecast_demand' => $totalForecastDemand,
            'stockout_date' => $stockoutDate,
            'reorder_recommendation' => $this->getReorderRecommendation($currentStock, $totalForecastDemand),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить товары требующие пополнения.
     */
    public function getReorderRecommendations(string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $products = $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->toArray();

        $reorderList = [];
        foreach ($products as $product) {
            $forecast = $this->forecastDemand((int) $product['id'], self::FORECAST_DAYS, $correlationId);
            
            if ($forecast['reorder_recommendation']['should_reorder']) {
                $reorderList[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'current_stock' => $product['stock_quantity'],
                    'recommended_order_quantity' => $forecast['reorder_recommendation']['quantity'],
                    'stockout_date' => $forecast['stockout_date'],
                ];
            }
        }

        return [
            'tenant_id' => $tenantId,
            'products_requiring_reorder' => $reorderList,
            'total_products' => count($reorderList),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Оптимизировать уровень запасов.
     */
    public function optimizeInventoryLevels(string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $products = $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->toArray();

        $optimizations = [];
        foreach ($products as $product) {
            $historicalSales = $this->getHistoricalSales((int) $product['id'], $tenantId, 90);
            $avgDailySales = array_sum($historicalSales) / max(count($historicalSales), 1);
            
            $optimalStock = $avgDailySales * self::SAFETY_STOCK_DAYS;
            $currentStock = $product['stock_quantity'];
            
            if ($currentStock < $optimalStock * 0.5) {
                $optimizations[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'current_stock' => $currentStock,
                    'optimal_stock' => round($optimalStock),
                    'action' => 'increase',
                    'recommended_quantity' => round($optimalStock - $currentStock),
                ];
            } elseif ($currentStock > $optimalStock * 2) {
                $optimizations[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'current_stock' => $currentStock,
                    'optimal_stock' => round($optimalStock),
                    'action' => 'decrease',
                    'excess_quantity' => round($currentStock - $optimalStock),
                ];
            }
        }

        return [
            'tenant_id' => $tenantId,
            'optimizations' => $optimizations,
            'total_optimizations' => count($optimizations),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить статистику Out-of-Stock.
     */
    public function getOutOfStockStats(int $days = 30, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $outOfStockEvents = $this->db->table('fashion_out_of_stock_events')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get()
            ->toArray();

        $totalLostSales = array_sum(array_column($outOfStockEvents, 'estimated_lost_sales'));
        $affectedProducts = array_unique(array_column($outOfStockEvents, 'product_id'));

        $topOutOfStockProducts = $this->db->table('fashion_out_of_stock_events')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('product_id, COUNT(*) as out_of_stock_count, SUM(estimated_lost_sales) as total_lost_sales')
            ->groupBy('product_id')
            ->orderBy('total_lost_sales', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'total_events' => count($outOfStockEvents),
            'total_lost_sales' => $totalLostSales,
            'affected_products_count' => count($affectedProducts),
            'top_products' => $topOutOfStockProducts,
            'correlation_id' => $correlationId,
        ];
    }

    private function getHistoricalSales(int $productId, int $tenantId, int $days): array
    {
        $sales = $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->where('oi.product_id', $productId)
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'completed')
            ->where('o.created_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('DATE(o.created_at) as sale_date, SUM(oi.quantity) as quantity')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->toArray();

        $dailySales = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $sale = collect($sales)->firstWhere('sale_date', $date);
            $dailySales[] = $sale ? (int) $sale['quantity'] : 0;
        }

        return $dailySales;
    }

    private function calculateTrend(array $historicalSales): float
    {
        if (count($historicalSales) < 7) {
            return 1.0;
        }

        $recentAvg = array_sum(array_slice($historicalSales, -7)) / 7;
        $olderAvg = array_sum(array_slice($historicalSales, 0, 7)) / 7;

        if ($olderAvg === 0) {
            return 1.0;
        }

        return $recentAvg / $olderAvg;
    }

    private function calculateSeasonality(int $productId, int $tenantId): array
    {
        $salesByDay = $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->where('oi.product_id', $productId)
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'completed')
            ->where('o.created_at', '>=', Carbon::now()->subDays(90))
            ->selectRaw('DAYOFWEEK(o.created_at) as day_of_week, SUM(oi.quantity) as quantity')
            ->groupBy('day_of_week')
            ->get()
            ->keyBy('day_of_week')
            ->toArray();

        $totalQuantity = array_sum(array_column($salesByDay, 'quantity'));
        
        $seasonality = [];
        for ($day = 1; $day <= 7; $day++) {
            $daySales = $salesByDay[$day]['quantity'] ?? 0;
            $seasonality[$day] = $totalQuantity > 0 ? ($daySales / $totalQuantity) * 7 : 1.0;
        }

        return $seasonality;
    }

    private function calculateBaseDemand(array $historicalSales): float
    {
        if (empty($historicalSales)) {
            return 1.0;
        }

        return array_sum($historicalSales) / count($historicalSales);
    }

    private function calculateStockoutDate(int $currentStock, array $forecast): ?string
    {
        $cumulativeDemand = 0;
        foreach ($forecast as $dayForecast) {
            $cumulativeDemand += $dayForecast['predicted_demand'];
            if ($cumulativeDemand >= $currentStock) {
                return $dayForecast['date'];
            }
        }

        return null;
    }

    private function getReorderRecommendation(int $currentStock, float $totalForecastDemand): array
    {
        $shouldReorder = $currentStock < ($totalForecastDemand * self::REORDER_THRESHOLD);
        $quantity = max(0, round($totalForecastDemand - $currentStock));

        return [
            'should_reorder' => $shouldReorder,
            'quantity' => $quantity,
        ];
    }

    private function saveDemandForecast(int $productId, int $tenantId, array $forecast, string $correlationId): void
    {
        $this->db->table('fashion_demand_forecasts')->updateOrInsert(
            ['product_id' => $productId, 'tenant_id' => $tenantId],
            [
                'forecast_data' => json_encode($forecast, JSON_UNESCAPED_UNICODE),
                'forecasted_at' => Carbon::now(),
                'correlation_id' => $correlationId,
                'updated_at' => Carbon::now(),
            ]
        );
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
