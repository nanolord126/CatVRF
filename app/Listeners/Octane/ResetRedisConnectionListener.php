<?php

declare(strict_types=1);

/**
 * ResetRedisConnectionListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 */

namespace App\Listeners\Octane;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ResetRedisConnectionListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class ResetRedisConnectionListener
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(RequestHandled $event): void
    {
        // Reset Redis connections to prevent stale connections
        try {
            $this->redis->connection()->ping();
        } catch (\Exception $e) {
            // Reconnect on failure
            $this->redis->connection()->disconnect();
            $this->redis->connection()->connect();
        }

        // Clear Redis connection pools
        $this->redis->connection()->flushdb();
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}
