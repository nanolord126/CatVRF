<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class HandleRentalExpirationJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        /**
         * Create a new job instance.
         */
        public function __construct(public int $bookingId,
            public string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Execute the job.
         */
        public function handle(): void
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () {
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

                    $this->logger->info('Expired rental auto-completed', [
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

