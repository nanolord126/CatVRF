<?php

declare(strict_types=1);

namespace App\Jobs\CarRental;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class CleanupExpiredBookingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * correlation_id transfer across queue.
     */
    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        ?string $correlationId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = $correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * handle() with $this->db->transaction() (Canon Rule 2026).
     */
    public function handle(): void
    {
        $this->logger->channel('audit')->$this->logger->info('[CarRentalCleanup] Job Started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $this->db->transaction(function () {
                // 1. Find Expired Pending Bookings (Canon Rule: 4-hour validity)
                $Booking::where('status', 'pending')
                    ->where('created_at', '<', CarbonImmutable::now()->subHours(4))
                    ->lockForUpdate()
                    ->get();

                foreach ($expiredBookings as $booking) {
                    // 2. State Transition: Booking -> Cancelled
                    $booking->update([
                        'status' => 'cancelled',
                        'metadata' => array_merge($booking->metadata ?? [], [
                            'cancellation_reason' => 'Auto-expired due to no pick-up (4h timeout)',
                            'cancelled_at' => CarbonImmutable::now()->toIso8601String(),
                        ]),
                        'correlation_id' => $this->correlationId,
                    ]);

                    // 3. Fleet Recovery: Car -> Available
                    $booking->car->update([
                        'status' => 'available',
                        'correlation_id' => $this->correlationId,
                    ]);

                    $this->logger->channel('audit')->$this->logger->info('[CarRentalCleanup] Booking Auto-Cancelled', [
                        'booking_uuid' => $booking->uuid,
                        'car_uuid' => $booking->car->uuid,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            });

        } catch (Exception $e) {
            $this->logger->channel('audit')->error('[CarRentalCleanup] Job Failed State', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        $this->logger->channel('audit')->$this->logger->info('[CarRentalCleanup] Job Completed Successfully', [
            'correlation_id' => $this->correlationId,
        ]);
    }
}
