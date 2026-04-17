<?php declare(strict_types=1);

namespace App\Services\Cache;

use App\Services\Tenancy\TenantCacheService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unified Cache Service
 *
 * Production 2026 CANON - Centralized Cache Management
 *
 * Provides unified cache operations with:
 * - Automatic tenant prefix (via TenantCacheService)
 * - Cache stampede protection (atomic locks)
 * - Tag-based invalidation
 * - Vertical-specific invalidation
 * - User-specific invalidation
 * - Diagnostic-specific invalidation
 * - Prometheus metrics integration
 * - Layered cache fallback (Redis → File)
 *
 * All cache operations MUST go through this service.
 * Direct Cache:: calls are forbidden in production code.
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final readonly class CacheService
{
    // Cache TTL constants (in seconds)
    public const TTL_MEDICAL_DIAGNOSIS = 300;      // 5 minutes - reduced from 3600s
    public const TTL_MEDICAL_HEALTH_SCORE = 600;   // 10 minutes
    public const TTL_RECOMMENDATIONS = 900;        // 15 minutes
    public const TTL_SLOTS = 60;                   // 1 minute
    public const TTL_DYNAMIC_PRICE = 300;          // 5 minutes
    public const TTL_EMBEDDINGS = 86400;           // 24 hours
    public const TTL_QUOTA_COUNTERS = 300;         // 5 minutes

    // Lock timeout for stampede protection (in seconds)
    private const LOCK_TIMEOUT = 5;
    private const LOCK_WAIT_TIME = 500; // milliseconds

    public function __construct(
        private readonly TenantCacheService $tenantCache,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly CacheMetricsService $metrics,
    ) {}

    /**
     * Remember value with tags and stampede protection
     *
     * @param int|null $tenantId
     * @param string $key
     * @param int $ttl
     * @param array $tags
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberWithTags(
        ?int $tenantId,
        string $key,
        int $ttl,
        array $tags,
        \Closure $callback
    ): mixed {
        $prefixedKey = $this->tenantCache->getPrefixedKey($tenantId, $key);
        $prefixedTags = $this->tenantCache->getTags($tenantId, $tags);

        $startTime = microtime(true);

        try {
            // Try to get from cache first
            $cached = $this->cache->tags($prefixedTags)->get($prefixedKey);

            if ($cached !== null) {
                $this->recordHit($prefixedKey, $tags);
                return $cached;
            }

            // Stampede protection with atomic lock
            $lock = $this->cache->lock("lock:{$prefixedKey}", self::LOCK_TIMEOUT);
            
            try {
                $lock->block(self::LOCK_WAIT_TIME / 1000);

                // Double-check after acquiring lock
                $cached = $this->cache->tags($prefixedTags)->get($prefixedKey);
                if ($cached !== null) {
                    $this->recordHit($prefixedKey, $tags);
                    return $cached;
                }

                // Execute callback and cache result
                $result = $callback();
                $this->cache->tags($prefixedTags)->put($prefixedKey, $result, $ttl);
                
                $this->recordMiss($prefixedKey, $tags, $ttl, microtime(true) - $startTime);
                
                return $result;
            } finally {
                $lock?->release();
            }
        } catch (LockTimeoutException $e) {
            $this->logger->warning('Cache lock timeout, executing callback', [
                'key' => $prefixedKey,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            $this->metrics->recordCacheLockTimeout($prefixedKey, self::LOCK_TIMEOUT);

            // Fallback: execute callback without caching on lock timeout
            return $callback();
        } catch (\Exception $e) {
            $this->logger->error('Cache remember error, trying layered fallback', [
                'key' => $prefixedKey,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            $this->metrics->recordCacheError('remember', $prefixedKey, $e->getMessage());

            // Layered cache fallback: try file cache
            return $this->rememberWithFileFallback($tenantId, $key, $ttl, $callback);
        }
    }

    /**
     * Layered cache fallback using file cache when Redis is unavailable
     *
     * @param int|null $tenantId
     * @param string $key
     * @param int $ttl
     * @param \Closure $callback
     * @return mixed
     */
    private function rememberWithFileFallback(?int $tenantId, string $key, int $ttl, \Closure $callback): mixed
    {
        $prefixedKey = $this->tenantCache->getPrefixedKey($tenantId, $key);

        try {
            $fileCache = $this->cache->store('file');
            $cached = $fileCache->get($prefixedKey);

            if ($cached !== null) {
                $this->logger->info('Cache hit from file fallback', [
                    'key' => $prefixedKey,
                ]);
                return $cached;
            }

            $result = $callback();
            $fileCache->put($prefixedKey, $result, $ttl);

            $this->logger->info('Cache written to file fallback', [
                'key' => $prefixedKey,
                'ttl' => $ttl,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('File cache fallback failed, executing callback', [
                'key' => $prefixedKey,
                'error' => $e->getMessage(),
            ]);

            // Final fallback: execute callback without caching
            return $callback();
        }
    }

    /**
     * Invalidate cache by tags
     *
     * @param int|null $tenantId
     * @param array $tags
     * @return bool
     */
    public function invalidate(?int $tenantId, array $tags): bool
    {
        $prefixedTags = $this->tenantCache->getTags($tenantId, $tags);

        $this->logger->info('Cache invalidation by tags', [
            'tenant_id' => $tenantId,
            'tags' => $prefixedTags,
        ]);

        try {
            $this->cache->tags($prefixedTags)->flush();
            $this->metrics->recordCacheInvalidation($tags, 'manual');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cache invalidation error', [
                'tenant_id' => $tenantId,
                'tags' => $prefixedTags,
                'error' => $e->getMessage(),
            ]);
            $this->metrics->recordCacheError('invalidate', implode(':', $prefixedTags), $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate all cache for a specific vertical
     *
     * @param int|null $tenantId
     * @param string $vertical
     * @return bool
     */
    public function invalidateVertical(?int $tenantId, string $vertical): bool
    {
        return $this->invalidate($tenantId, [$vertical]);
    }

    /**
     * Invalidate all cache for a specific user
     *
     * @param int|null $tenantId
     * @param int $userId
     * @param string|null $vertical
     * @return bool
     */
    public function invalidateUser(?int $tenantId, int $userId, ?string $vertical = null): bool
    {
        $tags = ["user:{$userId}"];
        
        if ($vertical !== null) {
            $tags[] = $vertical;
        }

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate diagnostic cache for a user
     *
     * @param int|null $tenantId
     * @param int $userId
     * @return bool
     */
    public function invalidateDiagnostic(?int $tenantId, int $userId): bool
    {
        $tags = [
            'medical',
            'diagnosis',
            "user:{$userId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate health score cache for a user
     *
     * @param int|null $tenantId
     * @param int $userId
     * @return bool
     */
    public function invalidateHealthScore(?int $tenantId, int $userId): bool
    {
        $tags = [
            'medical',
            'health_score',
            "user:{$userId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate recommendations cache for a user
     *
     * @param int|null $tenantId
     * @param int $userId
     * @return bool
     */
    public function invalidateRecommendations(?int $tenantId, int $userId): bool
    {
        $tags = [
            'recommendations',
            "user:{$userId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate doctor-related cache
     *
     * @param int|null $tenantId
     * @param int $doctorId
     * @return bool
     */
    public function invalidateDoctor(?int $tenantId, int $doctorId): bool
    {
        $tags = [
            'medical',
            'doctor',
            "doctor:{$doctorId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate clinic-related cache
     *
     * @param int|null $tenantId
     * @param int $clinicId
     * @return bool
     */
    public function invalidateClinic(?int $tenantId, int $clinicId): bool
    {
        $tags = [
            'medical',
            'clinic',
            "clinic:{$clinicId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate slots cache
     *
     * @param int|null $tenantId
     * @param int|null $doctorId
     * @param int|null $clinicId
     * @return bool
     */
    public function invalidateSlots(?int $tenantId, ?int $doctorId = null, ?int $clinicId = null): bool
    {
        $tags = ['slots'];

        if ($doctorId !== null) {
            $tags[] = "doctor:{$doctorId}";
        }

        if ($clinicId !== null) {
            $tags[] = "clinic:{$clinicId}";
        }

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Invalidate dynamic price cache
     *
     * @param int|null $tenantId
     * @param string $entityType
     * @param int $entityId
     * @return bool
     */
    public function invalidateDynamicPrice(?int $tenantId, string $entityType, int $entityId): bool
    {
        $tags = [
            'dynamic_price',
            $entityType,
            "{$entityType}:{$entityId}",
        ];

        return $this->invalidate($tenantId, $tags);
    }

    /**
     * Get cache key with strict naming convention
     *
     * Format: tenant:{tenantId}:vertical:{vertical}:{entity}:{identifier}
     *
     * @param int|null $tenantId
     * @param string $vertical
     * @param string $entity
     * @param string $identifier
     * @return string
     */
    public function getKey(?int $tenantId, string $vertical, string $entity, string $identifier): string
    {
        return "{$vertical}:{$entity}:{$identifier}";
    }

    /**
     * Get diagnostic cache key
     *
     * @param int|null $tenantId
     * @param int $userId
     * @param string $symptomsHash
     * @return string
     */
    public function getDiagnosticKey(?int $tenantId, int $userId, string $symptomsHash): string
    {
        return $this->getKey($tenantId, 'medical', 'diagnosis', "{$userId}:{$symptomsHash}");
    }

    /**
     * Get health score cache key
     *
     * @param int|null $tenantId
     * @param int $userId
     * @return string
     */
    public function getHealthScoreKey(?int $tenantId, int $userId): string
    {
        return $this->getKey($tenantId, 'medical', 'health_score', (string) $userId);
    }

    /**
     * Get recommendations cache key
     *
     * @param int|null $tenantId
     * @param string $vertical
     * @param int $userId
     * @param string $contextHash
     * @return string
     */
    public function getRecommendationsKey(?int $tenantId, string $vertical, int $userId, string $contextHash): string
    {
        return $this->getKey($tenantId, $vertical, 'recommendations', "{$userId}:{$contextHash}");
    }

    /**
     * Get slots cache key
     *
     * @param int|null $tenantId
     * @param int $doctorId
     * @param string $date
     * @return string
     */
    public function getSlotsKey(?int $tenantId, int $doctorId, string $date): string
    {
        return $this->getKey($tenantId, 'medical', 'slots', "{$doctorId}:{$date}");
    }

    /**
     * Get dynamic price cache key
     *
     * @param int|null $tenantId
     * @param string $entityType
     * @param int $entityId
     * @return string
     */
    public function getDynamicPriceKey(?int $tenantId, string $entityType, int $entityId): string
    {
        return $this->getKey($tenantId, 'pricing', 'dynamic', "{$entityType}:{$entityId}");
    }

    /**
     * Get embedding cache key
     *
     * @param int|null $tenantId
     * @param string $textHash
     * @return string
     */
    public function getEmbeddingKey(?int $tenantId, string $textHash): string
    {
        return $this->getKey($tenantId, 'embeddings', 'vector', $textHash);
    }

    /**
     * Remember embedding with proper tags and TTL
     *
     * @param int|null $tenantId
     * @param string $textHash
     * @param \Closure $callback
     * @return array
     */
    public function rememberEmbedding(?int $tenantId, string $textHash, \Closure $callback): array
    {
        $key = $this->getEmbeddingKey($tenantId, $textHash);
        $tags = ['embeddings', 'vectors'];

        return $this->rememberWithTags(
            $tenantId,
            $key,
            self::TTL_EMBEDDINGS,
            $tags,
            $callback
        );
    }

    /**
     * Invalidate embeddings cache for a tenant
     *
     * @param int|null $tenantId
     * @return bool
     */
    public function invalidateEmbeddings(?int $tenantId): bool
    {
        return $this->invalidate($tenantId, ['embeddings', 'vectors']);
    }

    /**
     * Record cache hit for metrics
     *
     * @param string $key
     * @param array $tags
     * @return void
     */
    private function recordHit(string $key, array $tags): void
    {
        $this->logger->debug('Cache hit', [
            'key' => $key,
            'tags' => $tags,
        ]);

        $this->metrics->recordCacheHit($tags, $key);
    }

    /**
     * Record cache miss for metrics
     *
     * @param string $key
     * @param array $tags
     * @param int $ttl
     * @param float $latency
     * @return void
     */
    private function recordMiss(string $key, array $tags, int $ttl, float $latency): void
    {
        $this->logger->debug('Cache miss', [
            'key' => $key,
            'tags' => $tags,
            'ttl' => $ttl,
            'latency_ms' => round($latency * 1000, 2),
        ]);

        $this->metrics->recordCacheMiss($tags, $key, $ttl);
        $this->metrics->recordCacheWriteLatency($latency, $tags, $key);
    }

    /**
     * Get cache statistics (for observability)
     *
     * @param int|null $tenantId
     * @param string $pattern
     * @return array
     */
    public function getStats(?int $tenantId, string $pattern = '*'): array
    {
        $fullPattern = $this->tenantCache->getPrefixedKey($tenantId, $pattern);

        if ($this->cache->getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = $this->cache->getStore()->connection();
            $keys = $redis->keys($fullPattern);
            
            $stats = [
                'count' => count($keys),
                'keys' => array_slice($keys, 0, 100), // Limit to 100 keys
                'total_keys' => count($keys),
            ];

            // Calculate memory usage if available
            try {
                $memory = 0;
                foreach ($keys as $key) {
                    $memory += $redis->memory('usage', $key) ?? 0;
                }
                $stats['memory_bytes'] = $memory;
                $stats['memory_mb'] = round($memory / 1024 / 1024, 2);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to calculate cache memory usage', [
                    'error' => $e->getMessage(),
                ]);
            }

            return $stats;
        }

        return [
            'count' => 0,
            'keys' => [],
            'message' => 'Memory stats only available for Redis store',
        ];
    }
}
