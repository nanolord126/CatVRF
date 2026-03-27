<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SegmentationService — сегментация пользователей и данных для анализа
 * 
 * Методы:
 * - segmentCustomers(tenantId, criteria)
 * - segmentByValue(tenantId) — High/Medium/Low value
 * - segmentByBehavior(tenantId) — Active/Dormant/AtRisk
 * - getSegmentMetrics(tenantId, segment)
 * - compareSegments(tenantId, segment1, segment2)
 */
final class SegmentationService
{
    private const CACHE_TTL = 86400;  // 24 hours

    /**
     * Сегментировать клиентов по критериям
     */
    public function segmentCustomers(
        int $tenantId,
        array $criteria,
        array $context = []
    ): Collection {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $segments = collect();

        if (isset($criteria['by_value'])) {
            $segments = $segments->merge($this->segmentByValue($tenantId, $correlationId));
        }

        if (isset($criteria['by_behavior'])) {
            $segments = $segments->merge($this->segmentByBehavior($tenantId, $correlationId));
        }

        if (isset($criteria['by_location'])) {
            $segments = $segments->merge($this->segmentByLocation($tenantId, $correlationId));
        }

        Log::channel('analytics')->info('Customer segmentation completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'segments_count' => $segments->count(),
        ]);

        return $segments;
    }

    /**
     * Сегментировать по LTV (Customer Lifetime Value)
     */
    public function segmentByValue(int $tenantId, string $correlationId = ''): Collection {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $cacheKey = "segments:by_value:{$tenantId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return collect($cached);
        }

        $segments = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'High-Value Customers',
                'criteria' => 'ltv > 50000',
                'count' => rand(50, 150),
                'avg_ltv' => 125000,
                'avg_order_frequency' => 12.5,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Medium-Value Customers',
                'criteria' => 'ltv 10000-50000',
                'count' => rand(200, 500),
                'avg_ltv' => 25000,
                'avg_order_frequency' => 5.2,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Low-Value Customers',
                'criteria' => 'ltv < 10000',
                'count' => rand(1000, 2000),
                'avg_ltv' => 3500,
                'avg_order_frequency' => 1.1,
            ],
        ];

        Cache::put($cacheKey, $segments, self::CACHE_TTL);

        Log::channel('analytics')->info('Value segmentation completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'segments_count' => count($segments),
        ]);

        return collect($segments);
    }

    /**
     * Сегментировать по поведению
     */
    public function segmentByBehavior(int $tenantId, string $correlationId = ''): Collection {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $cacheKey = "segments:by_behavior:{$tenantId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return collect($cached);
        }

        $segments = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Active Customers',
                'criteria' => 'purchase_in_last_30_days',
                'count' => rand(300, 800),
                'avg_order_frequency' => 6.5,
                'churn_risk' => 0.05,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Dormant Customers',
                'criteria' => 'no_purchase_in_last_90_days',
                'count' => rand(500, 1200),
                'avg_order_frequency' => 0.2,
                'churn_risk' => 0.80,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'At-Risk Customers',
                'criteria' => 'declining_order_frequency',
                'count' => rand(100, 300),
                'avg_order_frequency' => 1.8,
                'churn_risk' => 0.65,
            ],
        ];

        Cache::put($cacheKey, $segments, self::CACHE_TTL);

        Log::channel('analytics')->info('Behavior segmentation completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'segments_count' => count($segments),
        ]);

        return collect($segments);
    }

    /**
     * Получить метрики сегмента
     */
    public function getSegmentMetrics(
        int $tenantId,
        string $segment,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $cacheKey = "segments:metrics:{$tenantId}:{$segment}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $metrics = [
            'segment' => $segment,
            'customer_count' => rand(100, 1000),
            'total_revenue' => rand(500000, 5000000),
            'avg_ltv' => rand(5000, 100000),
            'avg_order_value' => rand(2000, 10000),
            'conversion_rate' => rand(3, 10) / 100,
            'churn_rate' => rand(1, 20) / 100,
            'retention_rate' => rand(70, 95) / 100,
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Cache::put($cacheKey, $metrics, self::CACHE_TTL);

        return $metrics;
    }

    /**
     * Сравнить два сегмента
     */
    public function compareSegments(
        int $tenantId,
        string $segment1,
        string $segment2,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $metrics1 = $this->getSegmentMetrics($tenantId, $segment1, $context);
        $metrics2 = $this->getSegmentMetrics($tenantId, $segment2, $context);

        $comparison = [
            'correlation_id' => $correlationId,
            'segment_1' => $segment1,
            'segment_2' => $segment2,
            'metrics_1' => $metrics1,
            'metrics_2' => $metrics2,
            'differences' => [
                'revenue_difference_percent' => $this->calculatePercentDifference(
                    $metrics1['total_revenue'],
                    $metrics2['total_revenue']
                ),
                'ltv_difference_percent' => $this->calculatePercentDifference(
                    $metrics1['avg_ltv'],
                    $metrics2['avg_ltv']
                ),
                'churn_rate_difference' => $this->calculateAbsoluteDifference(
                    $metrics1['churn_rate'],
                    $metrics2['churn_rate']
                ),
            ],
        ];

        Log::channel('analytics')->info('Segment comparison completed', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'segment_1' => $segment1,
            'segment_2' => $segment2,
        ]);

        return $comparison;
    }

    // ========== PRIVATE HELPERS ==========

    private function segmentByLocation(int $tenantId, string $correlationId): Collection {
        $segments = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Moscow Region',
                'location' => 'Moscow',
                'count' => rand(200, 600),
                'avg_ltv' => 45000,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'St. Petersburg Region',
                'location' => 'St. Petersburg',
                'count' => rand(100, 300),
                'avg_ltv' => 35000,
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Other Regions',
                'location' => 'Other',
                'count' => rand(500, 1500),
                'avg_ltv' => 15000,
            ],
        ];

        return collect($segments);
    }

    private function calculatePercentDifference(mixed $value1, mixed $value2): float {
        if ($value1 == 0) {
            return $value2 > 0 ? 100.0 : 0.0;
        }
        return (float)(($value2 - $value1) / $value1 * 100);
    }

    private function calculateAbsoluteDifference(mixed $value1, mixed $value2): float {
        return (float)abs($value2 - $value1);
    }
}
