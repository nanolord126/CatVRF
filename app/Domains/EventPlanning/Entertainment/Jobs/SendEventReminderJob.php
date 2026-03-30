<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendEventReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public ?int $scheduleId = null,
            public ?string $correlationId = null,
        ) {
            $this->onQueue('notifications');

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
