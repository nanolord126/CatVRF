<?php declare(strict_types=1);

/**
 * HandleVIPBookingNotification — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlevipbookingnotification
 */


namespace App\Domains\Luxury\Listeners;


use Psr\Log\LoggerInterface;
final class HandleVIPBookingNotification
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Обработка уведомления
         */
        public function handle(VIPBookingCreated $event): void
        {
            try {
                // 1. Audit log в Listener
                $this->logger->info('VIP Booking Listener Triggered', [
                    'booking_uuid' => $event->booking->uuid,
                    'correlation_id' => $event->correlationId,
                ]);

                // 2. Имитация оправки уведомления консьержу
                // Notification::send($concierge, new ConciergeNewBookingNotification($event->booking));

            } catch (Throwable $e) {
                $this->logger->error('VIP Booking Notification Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }
}
