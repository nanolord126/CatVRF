<?php declare(strict_types=1);

namespace App\Jobs\CarRental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupExpiredBookingsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * correlation_id transfer across queue.
         */
        public readonly string $correlationId;

        public function __construct(string $correlationId = null)
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * handle() with DB::transaction() (Canon Rule 2026).
         */
        public function handle(): void
        {
            Log::channel('audit')->info('[CarRentalCleanup] Job Started', [
                'correlation_id' => $this->correlationId,
            ]);

            try {
                DB::transaction(function () {
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

                        Log::channel('audit')->info('[CarRentalCleanup] Booking Auto-Cancelled', [
                            'booking_uuid' => $booking->uuid,
                            'car_uuid' => $booking->car->uuid,
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                });

            } catch (\Throwable $e) {
                Log::channel('audit')->error('[CarRentalCleanup] Job Failed State', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

            Log::channel('audit')->info('[CarRentalCleanup] Job Completed Successfully', [
                'correlation_id' => $this->correlationId,
            ]);
        }
}
