<?php declare(strict_types=1);

namespace App\Jobs\LanguageLearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LessonReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
