<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private WalletService $walletService
        ) {}

        /**
         * Записать пользователя на курс (B2C/B2B).
         */
        public function enroll(int $userId, int $courseId, string $type, string $correlationId): LanguageEnrollment
        {
            return DB::transaction(function () use ($userId, $courseId, $type, $correlationId) {
                $course = LanguageCourse::findOrFail($courseId);

                // Проверка фрода и лимитов
                $this->fraudControl->check(['operation' => 'enroll_course', 'user_id' => $userId]);

                if ($course->enrollments()->count() >= $course->max_students) {
                    throw new \Exception('Course is full');
                }

                Log::channel('audit')->info('User enrollment initiated', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'correlation_id' => $correlationId,
                ]);

                // Цена зависит от типа (B2C полная, B2B с корпоративной скидкой в settings школы)
                $price = ($type === 'b2b')
                    ? (int)($course->price_total * 0.85)
                    : $course->price_total;

                // Списание с кошелька (копейки)
                $this->walletService->debit($userId, $price, "Enrollment to course: {$course->title}", $correlationId);

                $enrollment = LanguageEnrollment::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'paid_amount' => $price,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'progress_data' => ['percent' => 0, 'completed_lessons' => 0],
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('User enrollment completed', [
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $enrollment;
            });
        }

        /**
         * Обновление прогресса ученика.
         */
        public function updateProgress(int $enrollmentId, int $lessonId, string $correlationId): void
        {
            DB::transaction(function () use ($enrollmentId, $lessonId, $correlationId) {
                $enrollment = LanguageEnrollment::findOrFail($enrollmentId);
                $course = $enrollment->course;

                $completedCount = ($enrollment->progress_data['completed_lessons'] ?? 0) + 1;
                $percent = (int)(($completedCount / $course->lessons()->count()) * 100);

                $enrollment->update([
                    'progress_data' => [
                        'completed_lessons' => $completedCount,
                        'percent' => $percent,
                        'last_lesson_id' => $lessonId,
                    ],
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Enrollment progress updated', [
                    'enrollment_id' => $enrollmentId,
                    'percent' => $percent,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
