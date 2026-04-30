<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Carbon\CarbonImmutable;

final readonly class WebSocketConnectionService
{
    use WithAuditLogging;

    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Register new connection
     */
    public function registerConnection(string $connectionId, int $userId, int $tenantId): bool
    {
        try {
            $correlationId = Str::uuid()->toString();
            $key = "ws:connection:{$connectionId}";

            $this->cache->put($key, [
                'connection_id' => $connectionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'connected_at' => CarbonImmutable::now()->toIso8601String(),
                'last_heartbeat' => CarbonImmutable::now()->toIso8601String(),
                'subscriptions' => [],
                'correlation_id' => $correlationId,
            ], self::CACHE_TTL_SECONDS);

            $countKey = "ws:connections:count:{$tenantId}";
            $this->cache->increment($countKey);
            $this->cache->put($countKey, (int) $this->cache->get($countKey, 0), self::CACHE_TTL_SECONDS);

            $this->logger->channel('audit')->info('WebSocket connection registered', [
                'connection_id' => $connectionId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to register connection', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unregister connection
     */
    public function unregisterConnection(string $connectionId, int $tenantId): bool
    {
        try {
            $correlationId = Str::uuid()->toString();
            $key = "ws:connection:{$connectionId}";
            $this->cache->forget($key);

            $countKey = "ws:connections:count:{$tenantId}";
            $current = max(0, (int) $this->cache->get($countKey, 0) - 1);
            $this->cache->put($countKey, $current, self::CACHE_TTL_SECONDS);

            $this->logger->channel('audit')->info('WebSocket connection unregistered', [
                'connection_id' => $connectionId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to unregister connection', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update heartbeat
     */
    public function heartbeat(string $connectionId): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = $this->cache->get($key);

            if ($connection) {
                $connection['last_heartbeat'] = CarbonImmutable::now()->toIso8601String();
                $this->cache->put($key, $connection, self::CACHE_TTL_SECONDS);

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Heartbeat failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get connection count
     */
    public function getConnectionCount(int $tenantId): int
    {
        $key = "ws:connections:count:{$tenantId}";

        return (int) $this->cache->get($key, 0);
    }

    /**
     * Add subscription to connection
     */
    public function addSubscription(string $connectionId, string $channel): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = $this->cache->get($key);

            if ($connection) {
                if (! in_array($channel, $connection['subscriptions'], true)) {
                    $connection['subscriptions'][] = $channel;
                    $this->cache->put($key, $connection, self::CACHE_TTL_SECONDS);
                }

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to add subscription', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove subscription from connection
     */
    public function removeSubscription(string $connectionId, string $channel): bool
    {
        try {
            $key = "ws:connection:{$connectionId}";
            $connection = $this->cache->get($key);

            if ($connection) {
                $connection['subscriptions'] = array_filter(
                    $connection['subscriptions'],
                    fn ($ch) => $ch !== $channel
                );
                $this->cache->put($key, $connection, self::CACHE_TTL_SECONDS);

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to remove subscription', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get connection metrics
     */
    public function getMetrics(int $tenantId): array
    {
        return [
            'active_connections' => $this->getConnectionCount($tenantId),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
