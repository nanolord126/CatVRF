<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\ClickHouse;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\DTOs\BaseEventDTO;

/**
 * ClickHouse Service for Big Data Operations
 *
 * High-level service for ClickHouse operations:
 * - Event ingestion
 * - Metrics queries
 * - Seller analytics
 * - CLV predictions
 * - A/B test analysis
 */
final readonly class ClickHouseService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly ClickHouseClient $client,
    ) {}

    /**
     * Insert a single event into raw_events table
     */
    public function insertEvent(BaseEventDTO $event): void
    {
        $data = $event->toArray();

        $this->client->insert('ch_raw_events', [[
            'event_id' => $data['event_id'],
            'event_type' => $data['event_type'],
            'event_category' => $data['event_category'],
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'],
            'seller_id' => $data['seller_id'],
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'],
            'session_id' => $data['session_id'],
            'vertical' => $data['vertical'],
            'properties' => json_encode($data['properties']),
            'monetary_value' => $data['monetary_value'],
            'context' => json_encode($data['context']),
            'correlation_id' => $data['correlation_id'],
            'user_agent' => $data['user_agent'],
            'ip_address' => $data['ip_address'],
            'device_type' => $data['device_type'],
            'created_at' => $data['timestamp'],
        ]]);
    }

    /**
     * Batch insert events (for high-throughput ingestion)
     *
     * @param array<BaseEventDTO> $events
     */
    public function insertEventsBatch(array $events): void
    {
        if (empty($events)) {
            return;
        }

        $data = array_map(fn (BaseEventDTO $e) => [
            'event_id' => $e->eventId,
            'event_type' => $e->eventType->value,
            'event_category' => $e->eventType->getCategory(),
            'tenant_id' => $e->tenantId,
            'user_id' => $e->userId,
            'seller_id' => $e->sellerId,
            'product_id' => $e->productId,
            'order_id' => $e->orderId,
            'session_id' => $e->sessionId,
            'vertical' => $e->vertical,
            'properties' => json_encode($e->properties),
            'monetary_value' => $e->monetaryValue,
            'context' => json_encode($e->context),
            'correlation_id' => $e->correlationId,
            'user_agent' => $e->userAgent,
            'ip_address' => $e->ipAddress,
            'device_type' => $e->deviceType,
            'created_at' => $e->timestamp->toIso8601String(),
        ], $events);

        $this->client->insert('ch_raw_events', $data);
    }

    /**
     * Get seller daily metrics
     */
    public function getSellerMetrics(
        int $tenantId,
        int $sellerId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $cacheKey = "bigdata:seller_metrics:{$tenantId}:{$sellerId}:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $tenantId,
            $sellerId,
            $startDate,
            $endDate,
        ) {
            $query = <<<'SQL'
                SELECT
                    metric_date,
                    orders_count,
                    orders_completed,
                    orders_cancelled,
                    gmv_total,
                    revenue_total,
                    commission_total,
                    customers_unique,
                    customers_new,
                    avg_order_value,
                    fulfillment_rate,
                    cancellation_rate,
                    avg_rating,
                    review_count,
                    customer_ltv_avg
                FROM ch_seller_daily_metrics
                WHERE tenant_id = :tenant_id
                    AND seller_id = :seller_id
                    AND metric_date BETWEEN :start_date AND :end_date
                ORDER BY metric_date DESC
                SQL;

            return $this->client->select($query, [
                'tenant_id' => $tenantId,
                'seller_id' => $sellerId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        });
    }

    /**
     * Get seller summary metrics (aggregated)
     */
    public function getSellerSummary(int $tenantId, int $sellerId, int $days = 30): array
    {
        $cacheKey = "bigdata:seller_summary:{$tenantId}:{$sellerId}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $tenantId,
            $sellerId,
            $days,
        ) {
            $query = <<<'SQL'
                SELECT
                    SUM(orders_count) AS total_orders,
                    SUM(orders_completed) AS completed_orders,
                    SUM(gmv_total) AS total_gmv,
                    SUM(revenue_total) AS total_revenue,
                    SUM(commission_total) AS total_commission,
                    AVG(avg_order_value) AS avg_order_value,
                    AVG(fulfillment_rate) AS avg_fulfillment_rate,
                    AVG(cancellation_rate) AS avg_cancellation_rate,
                    AVG(avg_rating) AS avg_rating,
                    SUM(review_count) AS total_reviews,
                    AVG(customer_ltv_avg) AS avg_clv
                FROM ch_seller_daily_metrics
                WHERE tenant_id = :tenant_id
                    AND seller_id = :seller_id
                    AND metric_date >= now() - INTERVAL :days DAY
                SQL;

            $result = $this->client->selectOne($query, [
                'tenant_id' => $tenantId,
                'seller_id' => $sellerId,
                'days' => $days,
            ]);

            return $result ?: [];
        });
    }

    /**
     * Get daily GMV for a period
     */
    public function getDailyGMV(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $cacheKey = "bigdata:daily_gmv:{$tenantId}:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $tenantId,
            $startDate,
            $endDate,
        ) {
            $query = <<<'SQL'
                SELECT
                    metric_date,
                    SUM(gmv_total) AS gmv_total,
                    SUM(orders_total) AS orders_total,
                    SUM(users_active) AS users_active
                FROM ch_daily_metrics
                WHERE tenant_id = :tenant_id
                    AND metric_date BETWEEN :start_date AND :end_date
                GROUP BY metric_date
                ORDER BY metric_date
                SQL;

            return $this->client->select($query, [
                'tenant_id' => $tenantId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        });
    }

    /**
     * Get CLV prediction for a user
     */
    public function getCLVPrediction(int $tenantId, int $userId): ?array
    {
        $cacheKey = "bigdata:clv:{$tenantId}:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $tenantId,
            $userId,
        ) {
            $query = <<<'SQL'
                SELECT
                    clv_12m,
                    clv_24m,
                    clv_lifetime,
                    clv_segment,
                    rfm_segment,
                    churn_probability,
                    recency_score,
                    frequency_score,
                    monetary_score,
                    model_version,
                    model_confidence
                FROM ch_clv_predictions
                WHERE tenant_id = :tenant_id
                    AND user_id = :user_id
                ORDER BY prediction_date DESC
                LIMIT 1
                SQL;

            return $this->client->selectOne($query, [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Get top sellers by GMV
     */
    public function getTopSellersByGMV(int $tenantId, int $days = 30, int $limit = 10): array
    {
        $cacheKey = "bigdata:top_sellers:{$tenantId}:{$days}:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $tenantId,
            $days,
            $limit,
        ) {
            $query = <<<'SQL'
                SELECT
                    seller_id,
                    SUM(gmv_total) AS total_gmv,
                    SUM(orders_count) AS total_orders,
                    AVG(avg_rating) AS avg_rating,
                    AVG(fulfillment_rate) AS avg_fulfillment_rate
                FROM ch_seller_daily_metrics
                WHERE tenant_id = :tenant_id
                    AND metric_date >= now() - INTERVAL :days DAY
                GROUP BY seller_id
                ORDER BY total_gmv DESC
                LIMIT :limit
                SQL;

            return $this->client->select($query, [
                'tenant_id' => $tenantId,
                'days' => $days,
                'limit' => $limit,
            ]);
        });
    }

    /**
     * Get A/B test results
     */
    public function getABTestResults(string $testId, int $tenantId): array
    {
        $cacheKey = "bigdata:abtest:{$tenantId}:{$testId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $testId,
            $tenantId,
        ) {
            $query = <<<'SQL'
                SELECT
                    variant_id,
                    variant_name,
                    is_control,
                    SUM(sample_size) AS total_sample,
                    SUM(users_converted) AS total_converted,
                    AVG(conversion_rate) AS avg_conversion_rate,
                    AVG(revenue_per_user) AS avg_revenue_per_user,
                    AVG(p_value) AS avg_p_value,
                    AVG(uplift_vs_control) AS avg_uplift
                FROM ch_abtest_results
                WHERE test_id = :test_id
                    AND tenant_id = :tenant_id
                GROUP BY variant_id, variant_name, is_control
                ORDER BY is_control DESC, variant_id
                SQL;

            return $this->client->select($query, [
                'test_id' => $testId,
                'tenant_id' => $tenantId,
            ]);
        });
    }

    /**
     * Get event count by type (for monitoring)
     */
    public function getEventCountsByType(int $tenantId, CarbonImmutable $since): array
    {
        $query = <<<'SQL'
            SELECT
                event_type,
                event_category,
                COUNT(*) AS event_count,
                uniq(user_id) AS unique_users,
                sum(monetary_value) AS total_value
            FROM ch_raw_events
            WHERE tenant_id = :tenant_id
                AND created_at >= :since
            GROUP BY event_type, event_category
            ORDER BY event_count DESC
            SQL;

        return $this->client->select($query, [
            'tenant_id' => $tenantId,
            'since' => $since->toIso8601String(),
        ]);
    }

    /**
     * Get buyer-seller affinity features
     */
    public function getBuyerSellerFeatures(int $tenantId, int $buyerId, int $sellerId): ?array
    {
        $query = <<<'SQL'
            SELECT
                buyer_clv_segment,
                buyer_clv_score,
                buyer_total_orders,
                buyer_total_spent,
                seller_rating_avg,
                seller_fulfillment_rate,
                buyer_seller_order_count,
                buyer_seller_total_spent,
                overall_affinity_score
            FROM ch_buyer_seller_features
            WHERE tenant_id = :tenant_id
                AND buyer_id = :buyer_id
                AND seller_id = :seller_id
            ORDER BY feature_date DESC
            LIMIT 1
            SQL;

        return $this->client->selectOne($query, [
            'tenant_id' => $tenantId,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
        ]);
    }

    /**
     * Execute custom query (for Query Explorer)
     *
     * WARNING: This should be restricted to admin users only
     */
    public function executeCustomQuery(string $query, array $params = []): array
    {
        // Security: Only allow SELECT queries
        if (!preg_match('/^\s*SELECT\s/i', $query)) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed');
        }

        // Prevent dangerous operations
        $dangerous = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'INSERT', 'UPDATE'];
        foreach ($dangerous as $keyword) {
            if (stripos($query, $keyword) !== false) {
                throw new \InvalidArgumentException("Dangerous keyword '{$keyword}' not allowed");
            }
        }

        // Limit query execution time
        $query = preg_replace('/SELECT\s/i', 'SELECT /* max_execution_time=30 */ ', $query, 1);

        Log::info('Executing custom ClickHouse query', [
            'query' => substr($query, 0, 500),
            'params' => $params,
        ]);

        return $this->client->select($query, $params);
    }

    /**
     * Get table statistics (for health monitoring)
     */
    public function getTableStats(): array
    {
        $tables = [
            'ch_raw_events',
            'ch_daily_metrics',
            'ch_seller_daily_metrics',
            'ch_clv_predictions',
            'ch_abtest_assignments',
            'ch_abtest_results',
            'ch_buyer_seller_features',
        ];

        $stats = [];

        foreach ($tables as $table) {
            try {
                $count = $this->client->countRows($table);
                $stats[$table] = [
                    'rows' => $count,
                    'status' => 'ok',
                ];
            } catch (\Exception $e) {
                $stats[$table] = [
                    'rows' => 0,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Health check
     */
    public function healthCheck(): array
    {
        try {
            $version = $this->client->getVersion();
            $ping = $this->client->ping();

            return [
                'status' => 'healthy',
                'version' => $version,
                'ping' => $ping,
                'tables' => $this->getTableStats(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
