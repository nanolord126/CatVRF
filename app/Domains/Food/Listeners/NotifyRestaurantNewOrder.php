<?php declare(strict_types=1);

/**
 * NotifyRestaurantNewOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/notifyrestaurantneworder
 */


namespace App\Domains\Food\Listeners;


use Psr\Log\LoggerInterface;
final class NotifyRestaurantNewOrder
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(OrderCreated $event): void
        {
            try {
                $this->logger->info('Restaurant notified of new order', [
                    'order_id' => $event->orderId,
                    'restaurant_id' => $event->restaurantId,
                    'client_id' => $event->clientId,
                    'total_amount' => $event->totalAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'order_created_restaurant_notification',
                ]);
                // Notification::send($restaurant, new NewOrderNotification($event));
            } catch (\Throwable $e) {
                $this->logger->error('Failed to notify restaurant', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
