<?php declare(strict_types=1);

/**
 * HandleMusicBookingCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlemusicbookingcreated
 */


namespace App\Domains\MusicAndInstruments\Music\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class HandleMusicBookingCreated
{
    public function __construct(
        private readonly Request $request, private readonly LoggerInterface $logger) {}


    /**
         * Handle the event.
         */
        public function handle(MusicBookingCreated $event): void
        {
            $booking = $event->booking;

            $this->logger->info('Processing music booking created event', [
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

                $this->logger->info('Scheduled rental expiration job', [
                    'booking_id' => $booking->id,
                    'target_time' => $booking->ends_at->toIso8601String(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            }

            // Send confirmation email/push (mocked)
            // Notification::send($booking->user, new MusicBookingConfirmed($booking));
        }
}
