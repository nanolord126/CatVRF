<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Application\Services;

use Illuminate\Cache\CacheManager;
use Psr\Log\LoggerInterface;

final readonly class CacheInvalidationService
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}
    public function invalidateOrderCache(string $orderId, string $userId, string $restaurantId): void
    {
        try {
            $this->cache->tags(['food', 'orders', "order:{$orderId}"])->flush();
            $this->cache->tags(['food', 'restaurants', "restaurant:{$restaurantId}", "restaurant:{$restaurantId}:orders"])->flush();

            $this->logger->$this->logger->info('Cache invalidated for food order', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'restaurant_id' => $restaurantId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate cache for food order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
