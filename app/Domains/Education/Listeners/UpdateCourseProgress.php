<?php

declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use App\Domains\Education\Events\LessonCompleted;
use App\Domains\Education\Models\Enrollment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: UpdateCourseProgress (Education).
 * Обновляет процент завершения курса у студента.
 */
final class UpdateCourseProgress
{
    /**
     * Обработка завершения урока
     */
    public function handle(LessonCompleted $event): void
    {
        $correlationId = $event->correlation_id;
        $userId = $event->userId;
        $courseId = $event->lesson->module->course_id;

        // 1. Поиск записи о зачислении
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return;
        }

        // 2. Расчет нового прогресса
        // (Для примера: simple lesson count / total lessons)
        $course = $enrollment->course;
        $totalLessons = $course->modules()->withCount('lessons')->get()->sum('lessons_count');
        
        if ($totalLessons === 0) {
            return;
        }

        // Для точности, уроки (completed_lessons_ids) должны храниться в JSON поле прогресса
        $progress = $enrollment->progress ?? [];
        $completedLessons = $progress['completed_lesson_ids'] ?? [];
        
        if (!in_array($event->lesson->id, $completedLessons)) {
            $completedLessons[] = $event->lesson->id;
        }

        $newProgressPercent = (int) (count($completedLessons) / $totalLessons * 100);

        // 3. Сохранение изменений
        $enrollment->update([
            'progress' => [
                'completed_lesson_ids' => $completedLessons,
                'last_lesson_id' => $event->lesson->id,
                'percent' => $newProgressPercent,
                'updated_at' => now()->toIso8601String(),
            ],
            'correlation_id' => $correlationId,
        ]);

        // 4. Логирование прогресса
        Log::channel('audit')->info('Student course progress updated', [
            'enrollment_id' => $enrollment->id,
            'user_id' => $userId,
            'progress_percent' => $newProgressPercent,
            'correlation_id' => $correlationId,
        ]);

        // 5. Выдача сертификата, если 100%
        if ($newProgressPercent >= 100) {
            // Dispatch CertificateJob (logic implementation)
            Log::channel('audit')->info('Course 100% Completed - Certificate eligible', [
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);
        }
    }
}
