<?php declare(strict_types=1);

namespace App\Jobs\CarRental;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class CleanupExpiredBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * correlation_id transfer across queue.
         */
        private string $correlationId;

        public function __construct(string $correlationId = null,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * handle() with $this->db->transaction() (Canon Rule 2026).
         */
        public function handle(): void
        {
            $this->logger->channel('audit')->info('[CarRentalCleanup] Job Started', [
                'correlation_id' => $this->correlationId,
            ]);

            try {
                $this->db->transaction(function () {
                    // 1. Find Expired Pending Bookings (Canon Rule: 4-hour validity)
                    $expiredBookings = Booking::where('status', 'pending')
                        ->where('created_at', '<', now()->subHours(4))
                        ->lockForUpdate()
                        ->get();

                    foreach ($expiredBookings as $booking) {
                        // 2. State Transition: Booking -> Cancelled
                        $booking->update([
                            'status' => 'cancelled',
                            'metadata' => array_merge($booking->metadata ?? [], [
                                'cancellation_reason' => 'Auto-expired due to no pick-up (4h timeout)',
                                'cancelled_at' => now()->toIso8601String(),
                            ]),
                            'correlation_id' => $this->correlationId,
                        ]);

                        // 3. Fleet Recovery: Car -> Available
                        $booking->car->update([
                            'status' => 'available',
                            'correlation_id' => $this->correlationId,
                        ]);

                        $this->logger->channel('audit')->info('[CarRentalCleanup] Booking Auto-Cancelled', [
                            'booking_uuid' => $booking->uuid,
                            'car_uuid' => $booking->car->uuid,
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                });

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('[CarRentalCleanup] Job Failed State', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

            $this->logger->channel('audit')->info('[CarRentalCleanup] Job Completed Successfully', [
                'correlation_id' => $this->correlationId,
            ]);
        }
}
