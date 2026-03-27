<?php declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use App\Domains\Hotels\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: Booking Created Listener (Layer 4)
 * 
 * Логирование и аудит.
 */
final class LogBookingCreated implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        Log::channel('audit')->info('Hotel Booking Created Audit', [
            'booking_uuid' => $event->booking->uuid,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->booking->tenant_id,
        ]);
    }
}
