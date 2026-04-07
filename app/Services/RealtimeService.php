<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class RealtimeService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

    private const PRESENCE_TTL_SECONDS = 3600;

    /**
         * Track user presence
         * @param int $userId
         * @param int $tenantId
         * @param array $data
         * @return bool
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
                    'online_at' => now()->toIso8601String(),
                    'correlation_id' => $correlationId,
                ]);

                $this->cache->put($key, $payload, self::PRESENCE_TTL_SECONDS);

                $index = $this->cache->get($indexKey, []);
                $index[$userId] = $key;
                $this->cache->put($indexKey, $index, self::PRESENCE_TTL_SECONDS);

                $this->logger->channel('audit')->info('User presence tracked', [
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
         * @param int $tenantId
         * @return array
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
         * @param string $channel
         * @param string $event
         * @param array $data
         * @param string $correlationId
         * @return bool
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
                    'timestamp' => now()->toIso8601String(),
                ], 300);

                $this->logger->channel('audit')->info('Broadcast sent', [
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
         * @param int $userId
         * @param string $channel
         * @return bool
         */
        public function subscribe(int $userId, string $channel): bool
        {
            $key = "subscription:user.{$userId}:{$channel}";
            $this->cache->put($key, true, self::PRESENCE_TTL_SECONDS);

            $this->logger->channel('audit')->info('User subscribed to channel', [
                'user_id' => $userId,
                'channel' => $channel,
            ]);

            return true;
        }

        /**
         * Unsubscribe user from channel
         * @param int $userId
         * @param string $channel
         * @return bool
         */
        public function unsubscribe(int $userId, string $channel): bool
        {
            $key = "subscription:user.{$userId}:{$channel}";
            $this->cache->forget($key);

            $this->logger->channel('audit')->info('User unsubscribed from channel', [
                'user_id' => $userId,
                'channel' => $channel,
            ]);

            return true;
        }
}
