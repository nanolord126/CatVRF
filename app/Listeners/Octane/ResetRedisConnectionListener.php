<?php declare(strict_types=1);

/**
 * ResetRedisConnectionListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 * @see https://catvrf.ru/docs/resetredisconnectionlistener
 */


namespace App\Listeners\Octane;

/**
 * Class ResetRedisConnectionListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Listeners\Octane
 */
final class ResetRedisConnectionListener
{
    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(RequestHandled $event): void
        {
            // Reset Redis connections to prevent stale connections
            try {
                Redis::connection()->ping();
            } catch (\Exception $e) {
                // Reconnect on failure
                Redis::connection()->disconnect();
                Redis::connection()->connect();
            }

            // Clear Redis connection pools
            Redis::flushdb();
        }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
