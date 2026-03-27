<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AdvancedAnalyticsService — продвинутая аналитика с прогнозами и метриками
 * 
 * Методы:
 * - getMetricsByPeriod(tenantId, metricType, startDate, endDate)
 * - predictFutureTrend(tenantId, metricType, daysAhead)
 * - generateCustomReport(tenantId, filters)
 * - calculateKPIs(tenantId)
 * - getComparativeAnalysis(tenantId, period1, period2)
 * - exportDataToCSV(data)
 * - exportDataToJSON(data)
 */
final class AdvancedAnalyticsService
{
    private const CACHE_TTL_METRICS = 3600;      // 1 hour
    private const CACHE_TTL_FORECAST = 86400;    // 24 hours
    private const MAX_FORECAST_DAYS = 90;
    private const SAMPLE_DATA_POINTS = 30;

    /**
     * Получить метрики за период (доход, заказы, конверсия, средний чек)
     */
    public function getMetricsByPeriod(
        int $tenantId,
        string $metricType,
        string $startDate,
        string $endDate,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $cacheKey = "analytics:metrics:{$tenantId}:{$metricType}:{$startDate}:{$endDate}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::channel('analytics')->info('Metrics retrieved from cache', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'metric_type' => $metricType,
                'cache_hit' => true,
            ]);
            return $cached;
        }

        $metrics = [];

        switch ($metricType) {
            case 'revenue':
                $metrics = $this->calculateRevenueMetrics($tenantId, $startDate, $endDate);
                break;
            case 'orders':
                $metrics = $this->calculateOrderMetrics($tenantId, $startDate, $endDate);
                break;
            case 'conversion':
                $metrics = $this->calculateConversionMetrics($tenantId, $startDate, $endDate);
                break;
            case 'aov': // Average Order Value
                $metrics = $this->calculateAOVMetrics($tenantId, $startDate, $endDate);
                break;
            default:
                Log::channel('analytics')->warning('Unknown metric type', [
                    'correlation_id' => $correlationId,
                    'metric_type' => $metricType,
                ]);
                return ['error' => "Unknown metric type: {$metricType}"];
        }

        Cache::put($cacheKey, $metrics, self::CACHE_TTL_METRICS);

        Log::channel('analytics')->info('Metrics calculated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'metric_type' => $metricType,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $metrics;
    }

    /**
     * Предсказать тренд на N дней вперёд (базовый линейный прогноз)
     */
    public function predictFutureTrend(
        int $tenantId,
        string $metricType,
        int $daysAhead = 30,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        if ($daysAhead > self::MAX_FORECAST_DAYS) {
            $daysAhead = self::MAX_FORECAST_DAYS;
        }

        $cacheKey = "analytics:forecast:{$tenantId}:{$metricType}:{$daysAhead}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Получить исторические данные (последние 30 дней)
        $endDate = now()->format('Y-m-d');
        $startDate = now()->subDays(self::SAMPLE_DATA_POINTS)->format('Y-m-d');

        $historicalMetrics = $this->getMetricsByPeriod($tenantId, $metricType, $startDate, $endDate, $context);

        if (isset($historicalMetrics['error'])) {
            return $historicalMetrics;
        }

        // Простой линейный прогноз
        $trend = $this->calculateLinearTrend($historicalMetrics, $daysAhead);

        Cache::put($cacheKey, $trend, self::CACHE_TTL_FORECAST);

        Log::channel('analytics')->info('Forecast generated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'metric_type' => $metricType,
            'days_ahead' => $daysAhead,
        ]);

        return $trend;
    }

    /**
     * Сгенерировать кастомный отчёт с фильтрами
     */
    public function generateCustomReport(
        int $tenantId,
        array $filters = [],
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $report = [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'sections' => [],
        ];

        // Раздел 1: KPIs
        $report['sections']['kpis'] = $this->calculateKPIs($tenantId, $context);

        // Раздел 2: Сравнение периодов
        if (isset($filters['compare_periods']) && $filters['compare_periods']) {
            $report['sections']['comparison'] = $this->getComparativeAnalysis(
                $tenantId,
                $filters['period_1'] ?? '7_days_ago',
                $filters['period_2'] ?? 'current',
                $context
            );
        }

        // Раздел 3: Тренды
        if (isset($filters['include_trends']) && $filters['include_trends']) {
            $report['sections']['trends'] = [
                'revenue_trend' => $this->predictFutureTrend($tenantId, 'revenue', 30, $context),
                'orders_trend' => $this->predictFutureTrend($tenantId, 'orders', 30, $context),
            ];
        }

        Log::channel('analytics')->info('Custom report generated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'sections_count' => count($report['sections']),
        ]);

        return $report;
    }

    /**
     * Рассчитать ключевые KPI (выручка, ROI, LTV, Churn)
     */
    public function calculateKPIs(int $tenantId, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $kpis = [
            'correlation_id' => $correlationId,
            'calculated_at' => now()->toIso8601String(),
            'metrics' => [
                'total_revenue_30d' => $this->getTotalRevenue($tenantId, 30),
                'total_orders_30d' => $this->getTotalOrders($tenantId, 30),
                'avg_order_value' => $this->getAverageOrderValue($tenantId, 30),
                'conversion_rate' => $this->getConversionRate($tenantId, 30),
                'ltv_estimate' => $this->estimateLTV($tenantId),
                'churn_rate' => $this->getChurnRate($tenantId, 30),
                'roi_estimate' => $this->estimateROI($tenantId),
            ],
        ];

        Log::channel('analytics')->info('KPIs calculated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        return $kpis;
    }

    /**
     * Сравнительный анализ двух периодов
     */
    public function getComparativeAnalysis(
        int $tenantId,
        string $period1,
        string $period2,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $dates1 = $this->parsePeriodString($period1);
        $dates2 = $this->parsePeriodString($period2);

        $metrics1 = [
            'revenue' => $this->getTotalRevenueForRange($tenantId, $dates1['start'], $dates1['end']),
            'orders' => $this->getTotalOrdersForRange($tenantId, $dates1['start'], $dates1['end']),
            'aov' => $this->getAOVForRange($tenantId, $dates1['start'], $dates1['end']),
        ];

        $metrics2 = [
            'revenue' => $this->getTotalRevenueForRange($tenantId, $dates2['start'], $dates2['end']),
            'orders' => $this->getTotalOrdersForRange($tenantId, $dates2['start'], $dates2['end']),
            'aov' => $this->getAOVForRange($tenantId, $dates2['start'], $dates2['end']),
        ];

        $comparison = [
            'correlation_id' => $correlationId,
            'period_1' => ['dates' => $dates1, 'metrics' => $metrics1],
            'period_2' => ['dates' => $dates2, 'metrics' => $metrics2],
            'deltas' => [
                'revenue_change_percent' => $this->calculatePercentChange($metrics1['revenue'], $metrics2['revenue']),
                'orders_change_percent' => $this->calculatePercentChange($metrics1['orders'], $metrics2['orders']),
                'aov_change_percent' => $this->calculatePercentChange($metrics1['aov'], $metrics2['aov']),
            ],
        ];

        Log::channel('analytics')->info('Comparative analysis completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        return $comparison;
    }

    /**
     * Экспортировать данные в CSV
     */
    public function exportDataToCSV(array $data): string {
        $csv = "key,value,timestamp\n";

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $csv .= "\"{$key}\",\"{$value}\"," . now()->toIso8601String() . "\n";
        }

        return $csv;
    }

    /**
     * Экспортировать данные в JSON
     */
    public function exportDataToJSON(array $data): string {
        return json_encode(array_merge($data, [
            'exported_at' => now()->toIso8601String(),
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ========== PRIVATE HELPERS ==========

    private function calculateRevenueMetrics(int $tenantId, string $startDate, string $endDate): array {
        return [
            'total_revenue' => $this->getTotalRevenueForRange($tenantId, $startDate, $endDate),
            'daily_average' => $this->getDailyAverageRevenue($tenantId, $startDate, $endDate),
            'peak_day' => $this->getPeakRevenueDay($tenantId, $startDate, $endDate),
        ];
    }

    private function calculateOrderMetrics(int $tenantId, string $startDate, string $endDate): array {
        return [
            'total_orders' => $this->getTotalOrdersForRange($tenantId, $startDate, $endDate),
            'daily_average' => $this->getDailyAverageOrders($tenantId, $startDate, $endDate),
            'peak_day' => $this->getPeakOrderDay($tenantId, $startDate, $endDate),
        ];
    }

    private function calculateConversionMetrics(int $tenantId, string $startDate, string $endDate): array {
        return [
            'conversion_rate' => $this->getConversionRateForRange($tenantId, $startDate, $endDate),
            'daily_average' => $this->getDailyAverageConversion($tenantId, $startDate, $endDate),
        ];
    }

    private function calculateAOVMetrics(int $tenantId, string $startDate, string $endDate): array {
        return [
            'average_order_value' => $this->getAOVForRange($tenantId, $startDate, $endDate),
            'daily_average' => $this->getDailyAverageAOV($tenantId, $startDate, $endDate),
        ];
    }

    private function calculateLinearTrend(array $metrics, int $daysAhead): array {
        $trend = [
            'forecast_days' => $daysAhead,
            'confidence' => 0.75,
            'predictions' => [],
        ];

        // Линейная экстраполяция на основе последних значений
        $lastValues = array_values(array_filter($metrics, 'is_numeric'));
        $count = count($lastValues);
        $avg = $count > 0 ? array_sum($lastValues) / $count : 0;
        $trend_slope = $count > 1 ? ($lastValues[$count - 1] - $lastValues[0]) / max($count - 1, 1) : 0;

        for ($i = 1; $i <= $daysAhead; $i++) {
            $trend['predictions'][] = [
                'day' => $i,
                'value' => (int) max(0, round($avg + $trend_slope * $i)),
                'confidence' => max(0.30, 0.75 - ($i * 0.01)),
            ];
        }

        return $trend;
    }

    private function parsePeriodString(string $period): array {
        return match ($period) {
            '7_days_ago' => ['start' => now()->subDays(7)->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
            '30_days_ago' => ['start' => now()->subDays(30)->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
            'current' => ['start' => now()->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
            default => ['start' => now()->subDays(30)->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
        };
    }

    private function calculatePercentChange(mixed $old, mixed $new): float {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return (float)(($new - $old) / $old * 100);
    }

    private function getTotalRevenue(int $tenantId, int $days): int
    {
        return (int) DB::table('balance_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'deposit')
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('amount');
    }

    private function getTotalOrders(int $tenantId, int $days): int
    {
        return (int) DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    private function getAverageOrderValue(int $tenantId, int $days): float
    {
        return (float) DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->where('created_at', '>=', now()->subDays($days))
            ->avg('amount') ?? 0.0;
    }

    private function getConversionRate(int $tenantId, int $days): float
    {
        $total = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        if ($total === 0) return 0.0;
        $captured = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        return round($captured / $total, 3);
    }

    private function estimateLTV(int $tenantId): float
    {
        return (float) DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->avg('amount') ?? 0.0;
    }

    private function getChurnRate(int $tenantId, int $days): float
    {
        $prevPeriodUsers = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<', now()->subDays($days))
            ->where('created_at', '>=', now()->subDays($days * 2))
            ->distinct('client_id')
            ->count('client_id');
        if ($prevPeriodUsers === 0) return 0.0;
        $retained = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct('client_id')
            ->count('client_id');
        return round(max(0, ($prevPeriodUsers - $retained) / $prevPeriodUsers), 3);
    }

    private function estimateROI(int $tenantId): float
    {
        $revenue = $this->getTotalRevenue($tenantId, 30);
        $costs = (int) DB::table('balance_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'commission')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');
        if ($costs === 0) return 0.0;
        return round($revenue / $costs, 2);
    }

    private function getTotalRevenueForRange(int $tenantId, string $start, string $end): int
    {
        return (int) DB::table('balance_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    private function getTotalOrdersForRange(int $tenantId, string $start, string $end): int
    {
        return (int) DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function getAOVForRange(int $tenantId, string $start, string $end): float
    {
        return (float) DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->whereBetween('created_at', [$start, $end])
            ->avg('amount') ?? 0.0;
    }

    private function getDailyAverageRevenue(int $tenantId, string $start, string $end): float
    {
        $days = max(1, (int) now()->parse($start)->diffInDays($end));
        return round($this->getTotalRevenueForRange($tenantId, $start, $end) / $days, 2);
    }

    private function getDailyAverageOrders(int $tenantId, string $start, string $end): float
    {
        $days = max(1, (int) now()->parse($start)->diffInDays($end));
        return round($this->getTotalOrdersForRange($tenantId, $start, $end) / $days, 2);
    }

    private function getPeakRevenueDay(int $tenantId, string $start, string $end): string
    {
        $row = DB::table('balance_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();
        return $row?->day ?? now()->format('Y-m-d');
    }

    private function getPeakOrderDay(int $tenantId, string $start, string $end): string
    {
        $row = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();
        return $row?->day ?? now()->format('Y-m-d');
    }

    private function getConversionRateForRange(int $tenantId, string $start, string $end): float
    {
        $total = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        if ($total === 0) return 0.0;
        $captured = DB::table('payment_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'captured')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        return round($captured / $total, 3);
    }

    private function getDailyAverageConversion(int $tenantId, string $start, string $end): float
    {
        $days = max(1, (int) now()->parse($start)->diffInDays($end));
        return round($this->getConversionRateForRange($tenantId, $start, $end) / $days, 4);
    }

    private function getDailyAverageAOV(int $tenantId, string $start, string $end): float
    {
        return $this->getAOVForRange($tenantId, $start, $end);
    }
}
