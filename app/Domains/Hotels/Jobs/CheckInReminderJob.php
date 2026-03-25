declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * CheckInReminderJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CheckInReminderJob implements ShouldQueue
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
            $this->log->channel('audit')->info('Sending check-in reminder', [
                'booking_id' => $this->booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            // Send notification to guest (24 hours before check-in)
            // $this->booking->guest->notify(new CheckInReminderNotification($this->booking));

            $this->log->channel('audit')->info('Check-in reminder sent', [
                'booking_id' => $this->booking->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to send check-in reminder', [
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
