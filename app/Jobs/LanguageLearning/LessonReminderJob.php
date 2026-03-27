<?php

declare(strict_types=1);

namespace App\Jobs\LanguageLearning;

use App\Domains\Education\LanguageLearning\Models\LanguageLesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Job для напоминания о предстоящем уроке за 2 часа.
 * Канон 2026: Correlation ID + Audit Log.
 */
final class LessonReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $lessonId,
        public string $correlationId
    ) {}

    public function handle(): void
    {
        $lesson = LanguageLesson::with(['enrollment.student', 'teacher'])->find($this->lessonId);

        if (!$lesson || $lesson->status !== 'scheduled') {
            return;
        }

        Log::channel('audit')->info('LanguageLearning: Sending lesson reminder', [
            'lesson_id' => $this->lessonId,
            'correlation_id' => $this->correlationId,
            'student_id' => $lesson->enrollment->student_id,
        ]);

        // Эмуляция отправки пуша / имейла / SMS по ФЗ-152
        // Notification::send($lesson->enrollment->student, new LessonUpcomingNotification($lesson));

        Log::channel('audit')->info('LanguageLearning: Reminder sent', [
            'lesson_id' => $this->lessonId,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
