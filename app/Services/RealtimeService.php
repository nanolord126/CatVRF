<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Carbon\CarbonImmutable;

final readonly class RealtimeService
{
    private const PRESENCE_TTL_SECONDS = 3600;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,) {}

    /**
     * Track user presence
     */
    public function trackPresence(int $userId, int $tenantId, array $data = []): bool
    {
        $correlationId = Str::uuid()->toString();

        try {
            $key = "presence:tenant.{$tenantId}:user.{$userId}";
            $indexKey = "presence:index:tenant.{$tenantId}";
            $payload = array_merge($data, [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'online_at' => CarbonImmutable::now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            $this->cache->put($key, $payload, self::PRESENCE_TTL_SECONDS);

            $index = $this->cache->get($indexKey, []);
            $index[$userId] = $key;
            $this->cache->put($indexKey, $index, self::PRESENCE_TTL_SECONDS);

            $this->logger->channel('audit')->$this->logger->info('User presence tracked', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to track presence', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }
    }

    /**
     * Get online users for tenant
     */
    public function getOnlineUsers(int $tenantId): array
    {
        $indexKey = "presence:index:tenant.{$tenantId}";
        $index = $this->cache->get($indexKey, []);
        $users = [];

        foreach ($index as $userId => $cacheKey) {
            $presence = $this->cache->get($cacheKey);
            if (is_array($presence)) {
                $users[(int) $userId] = $presence;
            }
        }

        return $users;
    }

    /**
     * Broadcast live update
     */
    public function broadcast(
        string $channel,
        string $event,
        array $data,
        string $correlationId
    ): bool {
        try {
            // In production: use Pusher/Ably/Laravel Echo
            // For now: store in cache for testing
            $key = "broadcast:{$channel}:{$event}";
            $this->cache->put($key, [
                'event' => $event,
                'data' => $data,
                'correlation_id' => $correlationId,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ], 300);

            $this->logger->channel('audit')->$this->logger->info('Broadcast sent', [
                'channel' => $channel,
                'event' => $event,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Broadcast failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }
    }

    /**
     * Subscribe user to channel
     */
    public function subscribe(int $userId, string $channel): bool
    {
        $key = "subscription:user.{$userId}:{$channel}";
        $this->cache->put($key, true, self::PRESENCE_TTL_SECONDS);

        $this->logger->channel('audit')->$this->logger->info('User subscribed to channel', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }

    /**
     * Unsubscribe user from channel
     */
    public function unsubscribe(int $userId, string $channel): bool
    {
        $key = "subscription:user.{$userId}:{$channel}";
        $this->cache->forget($key);

        $this->logger->channel('audit')->$this->logger->info('User unsubscribed from channel', [
            'user_id' => $userId,
            'channel' => $channel,
        ]);

        return true;
    }
}
