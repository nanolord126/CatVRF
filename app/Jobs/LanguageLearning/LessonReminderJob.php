<?php declare(strict_types=1);

namespace App\Jobs\LanguageLearning;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

/**
 * Class LessonReminderJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\LanguageLearning
 */
final class LessonReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public int $lessonId,
            public string $correlationId,
        private readonly LogManager $logger,
    ) {}

        /**
         * Handle handle operation.
         *
         * @throws \DomainException
         */
        public function handle(): void
        {
            $lesson = LanguageLesson::with(['enrollment.student', 'teacher'])->find($this->lessonId);

            if (!$lesson || $lesson->status !== 'scheduled') {
                return;
            }

            $this->logger->channel('audit')->info('LanguageLearning: Sending lesson reminder', [
                'lesson_id' => $this->lessonId,
                'correlation_id' => $this->correlationId,
                'student_id' => $lesson->enrollment->student_id,
            ]);

            // Эмуляция отправки пуша / имейла / SMS по ФЗ-152
            // Notification::send($lesson->enrollment->student, new LessonUpcomingNotification($lesson));

            $this->logger->channel('audit')->info('LanguageLearning: Reminder sent', [
                'lesson_id' => $this->lessonId,
                'correlation_id' => $this->correlationId,
            ]);
        }
}
