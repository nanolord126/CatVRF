<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Jobs;


use Psr\Log\LoggerInterface;
final class SendClassReminderJob
{

    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private ?int $scheduleId = null,
            private readonly ?string $correlationId = null, private readonly LoggerInterface $logger) {
            $this->onQueue('notifications');
        }

        public function tags(): array
        {
            return ['fitness', 'reminders', "schedule_{$this->scheduleId}"];
        }

        public function handle(): void
        {
            try {
                $schedule = ClassSchedule::find($this->scheduleId);
                if (!$schedule) {
                    return;
                }

                $hoursUntilClass = now()->diffInHours($schedule->scheduled_at);

                if ($hoursUntilClass > 2) {
                    return;
                }

                $attendees = $schedule->attendances()->where('status', '!=', 'cancelled')->get();

                foreach ($attendees as $attendance) {
                    $this->logger->info('Class reminder sent', [
                        'schedule_id' => $this->scheduleId,
                        'member_id' => $attendance->member_id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->error('Failed to send class reminder', [
                    'schedule_id' => $this->scheduleId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        public function retryUntil()
        {
            return now()->addHours(4);
        }
}
