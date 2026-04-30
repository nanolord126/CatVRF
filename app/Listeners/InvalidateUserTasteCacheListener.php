<?php

declare(strict_types=1);

namespace App\Listeners;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class InvalidateUserTasteCacheListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class InvalidateUserTasteCacheListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(object $event): void
    {
        if (! isset($event->userId)) {
            return;
        }

        try {
            $cacheTag = "user_taste_{$event->userId}";
            $this->cache->store('redis')->tags([$cacheTag])->flush();

            $this->logger->channel('audit')->$this->logger->info('User taste cache invalidated', [
                'user_id' => $event->userId,
                'event' => class_basename($event),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to invalidate user taste cache', [
                'user_id' => $event->userId ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }
}
