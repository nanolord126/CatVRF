<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Listeners;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Events\MusicBookingCreated;
use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Jobs\HandleRentalExpirationJob;
use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicInstrument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleMusicBookingCreated manages secondary actions after booking.
 */
final class HandleMusicBookingCreated implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(MusicBookingCreated $event): void
    {
        $booking = $event->booking;

        Log::channel('audit')->info('Processing music booking created event', [
            'booking_id' => $booking->id,
            'bookable_type' => $booking->bookable_type,
            'correlation_id' => $event->correlationId,
        ]);

        // If it's a rental (instrument), schedule an expiration check
        if ($booking->bookable_type === MusicInstrument::class) {
            HandleRentalExpirationJob::dispatch(
                $booking->id,
                $event->correlationId
            )->delay($booking->ends_at);

            Log::channel('audit')->info('Scheduled rental expiration job', [
                'booking_id' => $booking->id,
                'target_time' => $booking->ends_at->toIso8601String(),
            ]);
        }

        // Send confirmation email/push (mocked)
        // Notification::send($booking->user, new MusicBookingConfirmed($booking));
    }
}
