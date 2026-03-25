<?php declare(strict_types=1);

namespace App\Domains\Courses\Jobs;

use App\Domains\Courses\Models\Enrollment;
use App\Domains\Courses\Notifications\EnrollmentReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

final class EnrollmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId;
        $this->onQueue('notifications');

    }

    public function handle(): void
    {
        try {
            $this->log->channel('audit')->info('Running enrollment reminder job', [
                'correlation_id' => $this->correlationId,
            ]);

            // Find enrollments inactive for more than 7 days
            $stalledEnrollments = Enrollment::where('status', 'active')
                ->where('last_accessed_at', '<', Carbon::now()->subDays(7))
                ->get();

            foreach ($stalledEnrollments as $enrollment) {
                try {
                    $enrollment->student->notify(
                        new EnrollmentReminderNotification($enrollment)
                    );

                    $this->log->channel('audit')->info('Reminder sent to student', [
                        'enrollment_id' => $enrollment->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                } catch (Throwable $e) {
                    $this->log->channel('audit')->error('Failed to send reminder', [
                        'enrollment_id' => $enrollment->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            }

            $this->log->channel('audit')->info('Enrollment reminder job completed', [
                'reminders_sent' => $stalledEnrollments->count(),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Enrollment reminder job failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            $this->fail($e);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }
}

