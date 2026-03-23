<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AutoCheckOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?Booking $booking = null,
        public readonly string $correlationId = '',
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Auto check-out processing', [
                'booking_id' => $this->booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            // Auto-complete booking if guest hasn't checked out by checkout_date + 2 hours
            if ($this->booking->booking_status !== 'checked_out' && now()->isAfter($this->booking->check_out_date)) {
                $this->booking->update([
                    'booking_status' => 'checked_out',
                    'checked_out_at' => now(),
                ]);

                Log::channel('audit')->info('Booking auto-checked-out', [
                    'booking_id' => $this->booking->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Auto check-out failed', [
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function tags(): array
    {
        return ['hotels', 'checkout'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }
}
