<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Log::channel('audit')->info('Running enrollment reminder job', [
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

                        Log::channel('audit')->info('Reminder sent to student', [
                            'enrollment_id' => $enrollment->id,
                            'correlation_id' => $this->correlationId,
                        ]);
                    } catch (Throwable $e) {
                        Log::channel('audit')->error('Failed to send reminder', [
                            'enrollment_id' => $enrollment->id,
                            'error' => $e->getMessage(),
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                }

                Log::channel('audit')->info('Enrollment reminder job completed', [
                    'reminders_sent' => $stalledEnrollments->count(),
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Enrollment reminder job failed', [
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
