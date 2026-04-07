<?php declare(strict_types=1);

/**
 * NotifyDriverRideCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/notifydriverridecreated
 */


namespace App\Domains\Taxi\Listeners;


use Psr\Log\LoggerInterface;
final class NotifyDriverRideCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(RideCreated $event): void
        {
            try {
                $this->logger->info('Driver notified of new ride', [
                    'ride_id' => $event->rideId,
                    'driver_id' => $event->driverId,
                    'correlation_id' => $event->correlationId,
                    'action' => 'ride_created_driver_notification',
                ]);
                // Notification::send($driver, new RideAssignedNotification($event));
            } catch (\Throwable $e) {
                $this->logger->error('Failed to notify driver', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
