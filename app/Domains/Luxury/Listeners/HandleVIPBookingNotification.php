<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Listeners;

use App\Domains\Luxury\Events\VIPBookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HandleVIPBookingNotification
 *
 * Layer 6: Events & Listeners
 * Слушатель событий, асинхронно уведомляющий персонального консьержа.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class HandleVIPBookingNotification implements ShouldQueue
{
    /**
     * Обработка уведомления
     */
    public function handle(VIPBookingCreated $event): void
    {
        try {
            // 1. Audit log в Listener
            Log::channel('audit')->info('VIP Booking Listener Triggered', [
                'booking_uuid' => $event->booking->uuid,
                'correlation_id' => $event->correlationId,
            ]);

            // 2. Имитация оправки уведомления консьержу
            // Notification::send($concierge, new ConciergeNewBookingNotification($event->booking));

        } catch (Throwable $e) {
            Log::channel('audit')->error('VIP Booking Notification Error', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
