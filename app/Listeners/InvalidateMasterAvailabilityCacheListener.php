<?php

declare(strict_types=1);

namespace App\Listeners;

use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class InvalidateMasterAvailabilityCacheListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class InvalidateMasterAvailabilityCacheListener
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
        if (! isset($event->masterId)) {
            return;
        }

        try {
            $cacheTag = "master_availability_{$event->masterId}";
            $this->cache->store('redis')->tags([$cacheTag])->flush();

            $this->logger->channel('audit')->$this->logger->info('Master availability cache invalidated', [
                'master_id' => $event->masterId,
                'event' => class_basename($event),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to invalidate master availability cache', [
                'master_id' => $event->masterId ?? null,
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
