<?php

declare(strict_types=1);

namespace App\Domains\Shared\Realtime\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * RealtimeScalingService — horizontal scaling service for WebSocket connections across 28 verticals.
 *
 * Provides:
 * - Channel prefixing by vertical for Redis sharding
 * - Dynamic rate limiting per vertical (prevents spam)
 * - Connection statistics per vertical (monitoring)
 * - Priority-based resource allocation
 *
 * @version 2026.1
 */
final readonly class RealtimeScalingService
{
    private const CACHE_PREFIX = 'realtime:scaling:';
    private const STATS_TTL = 300; // 5 minutes
    private const RATE_LIMIT_TTL = 60; // 1 minute

    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get sharded channel name with vertical prefix
     *
     * Format: v_{vertical}:{channel}
     * Example: v_taxi:order.123.tracking
     *
     * @param string $vertical Vertical name (e.g., 'taxi', 'supermarket')
     * @param string $channel Original channel name
     * @return string Prefixed channel name for Redis sharding
     */
    public function getShardedChannel(string $vertical, string $channel): string
    {
        return "v_{$vertical}:{$channel}";
    }

    /**
     * Get buyer channel with vertical prefix
     */
    public function getBuyerChannel(string $vertical, int|string $orderId): string
    {
        return $this->getShardedChannel($vertical, "order.{$orderId}.tracking");
    }

    /**
     * Get courier channel with vertical prefix
     */
    public function getCourierChannel(string $vertical, int|string $orderId): string
    {
        return $this->getShardedChannel($vertical, "order.{$orderId}.courier");
    }

    /**
     * Get presence channel with vertical prefix
     */
    public function getPresenceChannel(string $vertical, int|string $orderId): string
    {
        return $this->getShardedChannel($vertical, "presence.order.{$orderId}");
    }

    /**
     * Get vertical events channel
     */
    public function getVerticalEventsChannel(string $vertical): string
    {
        return $this->getShardedChannel($vertical, "vertical.events");
    }

    /**
     * Check rate limit for vertical broadcast
     *
     * @param string $vertical Vertical name
     * @param string $entityType Entity type (e.g., 'order', 'ride')
     * @param int|string $entityId Entity ID
     * @return bool True if within rate limit, false if exceeded
     */
    public function checkRateLimit(string $vertical, string $entityType, int|string $entityId): bool
    {
        $config = $this->getVerticalConfig($vertical);
        $maxMessagesPerMinute = $config['rate_limit']['max_messages_per_minute'] ?? 30;

        $key = $this->getRateLimitKey($vertical, $entityType, $entityId);
        $current = (int) $this->cache->get($key, 0);

        if ($current >= $maxMessagesPerMinute) {
            $this->logger->warning('Rate limit exceeded for vertical broadcast', [
                'vertical' => $vertical,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'current' => $current,
                'limit' => $maxMessagesPerMinute,
            ]);

            return false;
        }

        // Increment counter with TTL
        $this->cache->increment($key);
        $this->cache->put($key, $this->cache->get($key), self::RATE_LIMIT_TTL);

        return true;
    }

    /**
     * Record connection statistics for vertical
     *
     * @param string $vertical Vertical name
     * @param string $action Action: 'connect', 'disconnect', 'message'
     * @return void
     */
    public function recordStats(string $vertical, string $action): void
    {
        $key = $this->getStatsKey($vertical, $action);
        $this->cache->increment($key);
        $this->cache->put($key, $this->cache->get($key), self::STATS_TTL);
    }

    /**
     * Get connection statistics for vertical
     *
     * @param string $vertical Vertical name
     * @return array Statistics for the vertical
     */
    public function getStats(string $vertical): array
    {
        return [
            'connects' => (int) $this->cache->get($this->getStatsKey($vertical, 'connect'), 0),
            'disconnects' => (int) $this->cache->get($this->getStatsKey($vertical, 'disconnect'), 0),
            'messages' => (int) $this->cache->get($this->getStatsKey($vertical, 'message'), 0),
        ];
    }

    /**
     * Get all verticals statistics
     *
     * @return array Statistics for all active verticals
     */
    public function getAllStats(): array
    {
        $verticals = config('verticals.verticals', []);
        $stats = [];

        foreach ($verticals as $slug => $config) {
            if (!($config['active'] ?? false)) {
                continue;
            }

            $stats[$slug] = $this->getStats($slug);
        }

        return $stats;
    }

    /**
     * Get vertical configuration
     *
     * @param string $vertical Vertical name
     * @return array Vertical realtime configuration
     */
    public function getVerticalConfig(string $vertical): array
    {
        $verticals = config('verticals.verticals', []);
        $verticalConfig = $verticals[$vertical] ?? [];

        return $verticalConfig['realtime'] ?? [
            'enabled' => false,
            'update_interval' => 10,
            'priority' => 'medium',
            'rate_limit' => [
                'max_messages_per_minute' => 30,
            ],
            'max_connections_per_entity' => 4,
        ];
    }

    /**
     * Check if vertical has realtime enabled
     */
    public function isRealtimeEnabled(string $vertical): bool
    {
        $config = $this->getVerticalConfig($vertical);
        return (bool) ($config['enabled'] ?? false);
    }

    /**
     * Get update interval for vertical (in seconds)
     */
    public function getUpdateInterval(string $vertical): int
    {
        $config = $this->getVerticalConfig($vertical);
        return (int) ($config['update_interval'] ?? 10);
    }

    /**
     * Get priority level for vertical
     *
     * @return string 'critical', 'high', 'medium', or 'low'
     */
    public function getPriority(string $vertical): string
    {
        $config = $this->getVerticalConfig($vertical);
        return $config['priority'] ?? 'medium';
    }

    /**
     * Get max connections per entity for vertical
     */
    public function getMaxConnectionsPerEntity(string $vertical): int
    {
        $config = $this->getVerticalConfig($vertical);
        return (int) ($config['max_connections_per_entity'] ?? 4);
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey(string $vertical, string $entityType, int|string $entityId): string
    {
        return self::CACHE_PREFIX . "rate:{$vertical}:{$entityType}:{$entityId}";
    }

    /**
     * Get stats key
     */
    private function getStatsKey(string $vertical, string $action): string
    {
        return self::CACHE_PREFIX . "stats:{$vertical}:{$action}";
    }
}
