<?php

declare(strict_types=1);

namespace App\Jobs\CarRental;

use App\Models\CarRental\Booking;
use App\Models\CarRental\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CleanupExpiredBookingsJob (CarRental).
 * Implementation: Layer 8 (Jobs/Automation Layer).
 * Purpose: Automatically releases cars that were 'pending' but not picked up.
 * Business Rule: Pending status expires after 4 hours of inactivity.
 */
final class CleanupExpiredBookingsJob implements ShouldQueue
{
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
