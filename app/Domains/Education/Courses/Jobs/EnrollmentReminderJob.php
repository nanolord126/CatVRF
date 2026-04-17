<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class EnrollmentReminderJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        private ?string $correlationId;

        public function __construct(string $correlationId = '', private readonly LoggerInterface $logger)
        {
            $this->correlationId = $correlationId;
            $this->onQueue('notifications');

        }

        public function handle(): void
        {
            try {
                $this->logger->info('Running enrollment reminder job', [
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

                        $this->logger->info('Reminder sent to student', [
                            'enrollment_id' => $enrollment->id,
                            'correlation_id' => $this->correlationId,
                        ]);
                    } catch (Throwable $e) {
                        $this->logger->error('Failed to send reminder', [
                            'enrollment_id' => $enrollment->id,
                            'error' => $e->getMessage(),
                            'correlation_id' => $this->correlationId,
                        ]);
                    }
                }

                $this->logger->info('Enrollment reminder job completed', [
                    'reminders_sent' => $stalledEnrollments->count(),
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Enrollment reminder job failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(6);
        }
}

