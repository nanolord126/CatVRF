<?php declare(strict_types=1);

namespace App\Domains\Fitness\Jobs;

use App\Domains\Fitness\Models\ClassSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendClassReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ?int $scheduleId = null,
        public ?string $correlationId = null,
    ) {
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
                $this->log->channel('audit')->info('Class reminder sent', [
                    'schedule_id' => $this->scheduleId,
                    'member_id' => $attendance->member_id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to send class reminder', [
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
