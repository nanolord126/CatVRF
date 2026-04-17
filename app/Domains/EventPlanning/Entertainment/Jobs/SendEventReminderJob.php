<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class SendEventReminderJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private ?int $scheduleId = null,
            private readonly ?string $correlationId = null, private readonly LoggerInterface $logger) {
            $this->onQueue('notifications');

        }

        public function handle(): void
        {
            try {
                $schedule = EventSchedule::find($this->scheduleId);

                if (!$schedule) {
                    return;
                }

                $hoursUntilEvent = Carbon::now()->diffInHours($schedule->start_time);

                if ($hoursUntilEvent <= 2 && $hoursUntilEvent >= 0) {
                    $bookings = $schedule->bookings()
                        ->where('status', '!=', 'cancelled')
                        ->get();

                    foreach ($bookings as $booking) {
                        $this->logger->info('Event reminder sent', [
                            'schedule_id' => $this->scheduleId,
                            'booking_id' => $booking->id,
                            'customer_id' => $booking->customer_id,
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send event reminders', [
                    'schedule_id' => $this->scheduleId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(4);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

