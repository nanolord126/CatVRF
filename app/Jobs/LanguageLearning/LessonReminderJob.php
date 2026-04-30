<?php

declare(strict_types=1);

namespace App\Jobs\LanguageLearning;

use Psr\Log\LoggerInterface;

use App\Models\LanguageLearning\LanguageLesson;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class LessonReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        public readonly int $lessonId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('notification');
    }

    public function tags(): array
    {
        return ['language-learning', 'reminder', 'lesson:' . $this->lessonId];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addMinutes(30);
    }

    public function handle(NotificationDispatcher $notificationDispatcher): void
    {
        $this->logger->channel('audit')->$this->logger->info('[LessonReminderJob] Started', [
            'lesson_id' => $this->lessonId,
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $lesson = LanguageLesson::query()
                ->with(['enrollment.student', 'teacher'])
                ->find($this->lessonId);

            if ($lesson === null || $lesson->status !== 'scheduled') {
                $this->logger->channel('audit')->$this->logger->info('[LessonReminderJob] Lesson skipped', [
                    'lesson_id' => $this->lessonId,
                    'correlation_id' => $this->correlationId,
                ]);

                return;
            }

            $notificationDispatcher->send(
                recipient: $lesson->enrollment->student,
                type: 'language_learning.lesson_reminder',
                data: [
                    'lesson_id' => $this->lessonId,
                    'scheduled_at' => $lesson->scheduled_at?->toIso8601String(),
                ],
                correlationId: $this->correlationId,
            );

            $this->db->table('language_lesson_reminder_logs')->insert([
                'lesson_id' => $this->lessonId,
                'correlation_id' => $this->correlationId,
                'sent_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[LessonReminderJob] Completed', [
                'lesson_id' => $this->lessonId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[LessonReminderJob] Failed', [
                'lesson_id' => $this->lessonId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[LessonReminderJob] Failed permanently', [
            'lesson_id' => $this->lessonId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
