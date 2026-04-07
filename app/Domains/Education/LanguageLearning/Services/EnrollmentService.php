<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use App\Domains\Education\LanguageLearning\Models\LanguageCourse;
use App\Domains\Education\LanguageLearning\Models\LanguageEnrollment;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class EnrollmentService
{


    public function __construct(private FraudControlService $fraud,
            private WalletService $walletService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Записать пользователя на курс (B2C/B2B).
         */
        public function enroll(int $userId, int $courseId, string $type, string $correlationId): LanguageEnrollment
        {
            return $this->db->transaction(function () use ($userId, $courseId, $type, $correlationId) {
                $course = LanguageCourse::findOrFail($courseId);

                // Проверка фрода и лимитов
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'enroll_course', amount: 0, correlationId: $correlationId ?? '');

                if ($course->enrollments()->count() >= $course->max_students) {
                    throw new \DomainException('Course is full');
                }

                $this->logger->info('User enrollment initiated', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'correlation_id' => $correlationId,
                ]);

                // Цена зависит от типа (B2C полная, B2B с корпоративной скидкой в settings школы)
                $price = ($type === 'b2b')
                    ? (int)($course->price_total * 0.85)
                    : $course->price_total;

                // Списание с кошелька (копейки)
                $this->walletService->debit($userId, $price, 'language_enrollment', $correlationId);

                $enrollment = LanguageEnrollment::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'paid_amount' => $price,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'progress_data' => ['percent' => 0, 'completed_lessons' => 0],
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('User enrollment completed', [
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
            $this->db->transaction(function () use ($enrollmentId, $lessonId, $correlationId) {
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

                $this->logger->info('Enrollment progress updated', [
                    'enrollment_id' => $enrollmentId,
                    'percent' => $percent,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
