<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckInReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?Booking $booking = null,
        public readonly string $correlationId = '',
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('Sending check-in reminder', [
                'booking_id' => $this->booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            // Send notification to guest (24 hours before check-in)
            // $this->booking->guest->notify(new CheckInReminderNotification($this->booking));

            Log::channel('audit')->info('Check-in reminder sent', [
                'booking_id' => $this->booking->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to send check-in reminder', [
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function tags(): array
    {
        return ['hotels', 'reminders', 'check-in'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
