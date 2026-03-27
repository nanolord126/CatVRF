<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Jobs;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicBooking;
use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicInstrument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HandleRentalExpirationJob clears expired rentals and releases hold stock.
 */
final class HandleRentalExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $bookingId,
        public string $correlationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $booking = MusicBooking::lockForUpdate()
                ->where('bookable_type', MusicInstrument::class)
                ->where('status', 'confirmed')
                ->find($this->bookingId);

            if (!$booking || $booking->ends_at > now()) {
                return;
            }

            $instrument = MusicInstrument::lockForUpdate()->find($booking->bookable_id);

            if ($instrument && $instrument->hold_stock > 0) {
                $instrument->decrement('hold_stock');
                $booking->update(['status' => 'completed']);

                Log::channel('audit')->info('Expired rental auto-completed', [
                    'booking_id' => $this->bookingId,
                    'instrument_id' => $instrument->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });
    }

    /**
     * Get tags for the job.
     */
    public function tags(): array
    {
        return ['music', 'rental', 'expiration', 'booking:' . $this->bookingId];
    }
}
