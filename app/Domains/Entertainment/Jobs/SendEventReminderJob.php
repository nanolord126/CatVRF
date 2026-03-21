<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Jobs;

use App\Domains\Entertainment\Models\EventSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class SendEventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $scheduleId,
        public string $correlationId,
    ) {
        $this->onQueue('notifications');
        $this->tags(['entertainment', 'reminders', "schedule_{$scheduleId}"]);
    }

    public function handle(): void
    {
        try {
            $schedule = EventSchedule::find($this->scheduleId);

            if (!$schedule) {
                return;
            }

            $hoursUntilEvent = now()->diffInHours($schedule->start_time);

            if ($hoursUntilEvent <= 2 && $hoursUntilEvent >= 0) {
                $bookings = $schedule->bookings()
                    ->where('status', '!=', 'cancelled')
                    ->get();

                foreach ($bookings as $booking) {
                    Log::channel('audit')->info('Event reminder sent', [
                        'schedule_id' => $this->scheduleId,
                        'booking_id' => $booking->id,
                        'customer_id' => $booking->customer_id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to send event reminders', [
                'schedule_id' => $this->scheduleId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(4);
    }
}
