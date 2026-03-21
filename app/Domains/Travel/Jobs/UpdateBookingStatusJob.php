<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;

use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateBookingStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    public function __construct(
        public int $bookingId,
        public string $newStatus,
        public string $correlationId,
        public string $queue = 'travel',
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $booking = TravelBooking::findOrFail($this->bookingId);

                $booking->update([
                    'status' => $this->newStatus,
                ]);

                Log::channel('audit')->info('Travel booking status updated', [
                    'booking_id' => $this->bookingId,
                    'booking_number' => $booking->booking_number,
                    'new_status' => $this->newStatus,
                    'correlation_id' => $this->correlationId,
                    'timestamp' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Travel booking status update failed', [
                'booking_id' => $this->bookingId,
                'new_status' => $this->newStatus,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['travel', 'booking', 'status-update'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
