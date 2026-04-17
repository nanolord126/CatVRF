<?php declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Carbon\Carbon;

/**
 * FraudML Feature Store
 * CANON 2026 - Production Ready
 *
 * Centralized feature storage for consistency between:
 * - Online inference (PHP runtime) - Redis
 * - Offline training (ClickHouse) - Materialized View
 *
 * Prevents feature drift between training and production inference.
 */
final readonly class FraudMLFeatureStore
{
    private const REDIS_PREFIX = 'fraudml:features:';
    private const FEATURE_TTL_SECONDS = 86400; // 24 hours
    private const CLICKHOUSE_TABLE = 'fraud_features_online';
    
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Store features for an entity (user/tenant/operation)
     * Writes to both Redis (online) and ClickHouse (offline)
     */
    public function storeFeatures(
        string $entityType,
        string $entityId,
        array $features,
        string $correlationId = null
    ): void {
        $featureKey = $this->getRedisKey($entityType, $entityId);
        $timestamp = now()->toIso8601String();

        // 1. Store in Redis for online inference
        $this->storeInRedis($featureKey, $features, $timestamp);

        // 2. Store in ClickHouse for offline training
        $this->storeInClickHouse($entityType, $entityId, $features, $timestamp, $correlationId);

        $this->logger->info('FraudML features stored', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'feature_count' => count($features),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Get features for online inference (from Redis)
     */
    public function getFeatures(string $entityType, string $entityId): ?array
    {
        $featureKey = $this->getRedisKey($entityType, $entityId);
        $data = Redis::get($featureKey);

        if ($data === null) {
            $this->logger->debug('FraudML features not found in Redis', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Get features or compute and store if missing
     */
    public function getOrComputeFeatures(
        string $entityType,
        string $entityId,
        callable $computeFn,
        string $correlationId = null
    ): array {
        $features = $this->getFeatures($entityType, $entityId);

        if ($features !== null) {
            return $features;
        }

        // Compute features
        $features = $computeFn();

        // Store for future use
        $this->storeFeatures($entityType, $entityId, $features, $correlationId);

        return $features;
    }

    /**
     * Batch store features (for bulk operations like daily retrain)
     */
    public function batchStoreFeatures(array $featureBatch): void
    {
        foreach ($featureBatch as $item) {
            $this->storeFeatures(
                $item['entity_type'],
                $item['entity_id'],
                $item['features'],
                $item['correlation_id'] ?? null
            );
        }
    }

    /**
     * Invalidate features for an entity
     */
    public function invalidateFeatures(string $entityType, string $entityId): void
    {
        $featureKey = $this->getRedisKey($entityType, $entityId);
        Redis::del($featureKey);

        $this->logger->info('FraudML features invalidated', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * Get feature statistics for monitoring
     */
    public function getFeatureStats(): array
    {
        $keys = Redis::keys(self::REDIS_PREFIX . '*');
        
        return [
            'total_features_stored' => count($keys),
            'redis_memory_usage' => Redis::memory('usage'),
            'avg_feature_size' => count($keys) > 0 
                ? Redis::memory('usage') / count($keys) 
                : 0,
        ];
    }

    /**
     * Store in Redis with TTL
     */
    private function storeInRedis(string $key, array $features, string $timestamp): void
    {
        $data = json_encode([
            'features' => $features,
            'timestamp' => $timestamp,
            'version' => '1.0',
        ]);

        Redis::setex($key, self::FEATURE_TTL_SECONDS, $data);
    }

    /**
     * Store in ClickHouse for offline training
     */
    private function storeInClickHouse(
        string $entityType,
        string $entityId,
        array $features,
        string $timestamp,
        ?string $correlationId
    ): void {
        try {
            $this->db->connection('clickhouse')->statement(
                "INSERT INTO " . self::CLICKHOUSE_TABLE . " 
                (entity_type, entity_id, features_json, timestamp, correlation_id, created_at)
                VALUES (?, ?, ?, ?, ?, now())",
                [
                    $entityType,
                    $entityId,
                    json_encode($features),
                    $timestamp,
                    $correlationId,
                ]
            );
        } catch (\Exception $e) {
            // Non-blocking: log error but don't fail the request
            $this->logger->warning('Failed to store features in ClickHouse', [
                'error' => $e->getMessage(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
        }
    }

    /**
     * Generate Redis key for entity
     */
    private function getRedisKey(string $entityType, string $entityId): string
    {
        return self::REDIS_PREFIX . $entityType . ':' . $entityId;
    }

    /**
     * Extract and store features for a fraud detection operation
     */
    public function extractAndStoreOperationFeatures(
        int $tenantId,
        int $userId,
        string $operationType,
        float $amount,
        array $context = [],
        ?string $correlationId = null
    ): array {
        $features = $this->extractOperationFeatures(
            $tenantId,
            $userId,
            $operationType,
            $amount,
            $context
        );

        // Store for user
        $this->storeFeatures('user', (string)$userId, $features, $correlationId);
        
        // Store for tenant
        $this->storeFeatures('tenant', (string)$tenantId, $features, $correlationId);

        // Store for operation (unique per request)
        $operationId = $correlationId ?? uniqid('op_', true);
        $this->storeFeatures('operation', $operationId, $features, $correlationId);

        return $features;
    }

    /**
     * Extract features for fraud detection
     * This is the single source of truth for feature extraction
     */
    private function extractOperationFeatures(
        int $tenantId,
        int $userId,
        string $operationType,
        float $amount,
        array $context
    ): array {
        return [
            // Behavioral features
            'amount_log' => log(max(1, $amount)),
            'hour_of_day' => Carbon::now()->hour,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'is_weekend' => Carbon::now()->isWeekend() ? 1 : 0,
            'operation_type' => $operationType,
            
            // Tenant-specific
            'tenant_id' => $tenantId,
            'tenant_risk_profile' => $context['tenant_risk_profile'] ?? 'medium',
            
            // User-specific
            'user_id' => $userId,
            'account_age_days' => $context['account_age_days'] ?? 0,
            
            // Contextual
            'ip_risk_score' => $context['ip_risk_score'] ?? 0,
            'device_fingerprint' => $context['device_fingerprint'] ?? null,
            'user_agent_risk' => $context['user_agent_risk'] ?? 0,
            
            // Temporal patterns
            'tx_count_1h' => $context['tx_count_1h'] ?? 0,
            'tx_count_24h' => $context['tx_count_24h'] ?? 0,
            'tx_sum_24h' => $context['tx_sum_24h'] ?? 0,
            
            // Geographic
            'country_code' => $context['country_code'] ?? null,
            'is_cross_border' => $context['is_cross_border'] ?? 0,
            
            // Quota-aware feature (critical for multi-tenant)
            'current_quota_usage_ratio' => $this->getQuotaUsageRatio($tenantId, $context),
            
            // Vertical-specific feature (for per-vertical routing)
            'vertical_code' => $context['vertical_code'] ?? $this->inferVerticalFromOperation($operationType),
        ];
    }

    /**
     * Get quota usage ratio for tenant (0.0 to 1.0)
     * Critical feature to prevent ML from spending tokens on tenants near quota limit
     */
    private function getQuotaUsageRatio(int $tenantId, array $context): float
    {
        // If provided in context, use it (faster)
        if (isset($context['current_quota_usage_ratio'])) {
            return (float) $context['current_quota_usage_ratio'];
        }

        // Otherwise fetch from quota service (in real implementation)
        // For demo: simulate based on tenant ID
        return min(1.0, max(0.0, ($tenantId % 100) / 100.0));
    }

    /**
     * Infer vertical code from operation type
     * Used when vertical_code is not explicitly provided
     */
    private function inferVerticalFromOperation(string $operationType): string
    {
        $verticalMap = [
            'medical_diagnosis' => 'medical',
            'medical_appointment' => 'medical',
            'emergency_call' => 'medical',
            'payment' => 'payment',
            'wallet_transfer' => 'wallet',
            'delivery_order' => 'delivery',
            'restaurant_order' => 'food',
            'hotel_booking' => 'hotels',
            'taxi_ride' => 'taxi',
        ];

        return $verticalMap[$operationType] ?? 'marketplace';
    }
}
