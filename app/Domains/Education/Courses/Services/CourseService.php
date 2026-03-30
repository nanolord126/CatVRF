<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
        ) {}

        /**
         * Запись на курс (Enrollment).
         */
        public function enroll(int $userId, int $courseId, bool $isB2B = false, array $meta = [], string $correlationId = ""): Enrollment
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от массовых бесплатных записей
            if (RateLimiter::tooManyAttempts("courses:enroll:{$userId}", 5)) {
                throw new \RuntimeException("Слишком много попыток записи. Попробуйте позже.", 429);
            }
            RateLimiter::hit("courses:enroll:{$userId}", 3600);

            return DB::transaction(function () use ($userId, $courseId, $isB2B, $meta, $correlationId) {
                $course = Course::findOrFail($courseId);

                // Расчет цены с учетом B2B скидки (КАНОН 20%)
                $priceKopecks = $isB2B ? (int)($course->price_kopecks * 0.8) : $course->price_kopecks;

                // 2. Fraud Check (проверка на отмыв бонусов через курсы)
                $fraud = $this->fraud->check([
                    "user_id" => $userId,
                    "operation_type" => "course_enroll",
                    "amount" => $priceKopecks,
                    "correlation_id" => $correlationId,
                    "meta" => ["course_id" => $courseId, "is_b2b" => $isB2B]
                ]);

                if ($fraud["decision"] === "block") {
                    Log::channel("audit")->warning("Courses Security Block", ["user_id" => $userId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Запись заблокирована службой безопасности.", 403);
                }

                // 3. Создание записи
                $enrollment = Enrollment::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $course->tenant_id,
                    "user_id" => $userId,
                    "course_id" => $courseId,
                    "status" => "active",
                    "price_paid" => $priceKopecks,
                    "progress_percent" => 0,
                    "correlation_id" => $correlationId,
                    "tags" => ["vertical:education", $isB2B ? "segment:b2b" : "segment:b2c"]
                ]);

                Log::channel("audit")->info("Courses: user enrolled", ["enrollment_id" => $enrollment->id, "corr" => $correlationId]);

                return $enrollment;
            });
        }

        /**
         * Обновление прогресса и выдача сертификата (КАНОН).
         */
        public function updateProgress(int $enrollmentId, int $lessonId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $enrollment = Enrollment::findOrFail($enrollmentId);
            $course = $enrollment->course;

            DB::transaction(function () use ($enrollment, $lessonId, $course, $correlationId) {
                $totalLessons = $course->lessons()->count();
                $completedLessons = $enrollment->completed_lessons_count + 1;
                $newProgress = (int)(($completedLessons / $totalLessons) * 100);

                $enrollment->update([
                    "progress_percent" => min(100, $newProgress),
                    "completed_lessons_count" => $completedLessons
                ]);

                // Автоматическая выдача сертификата при 100% (КАНОН)
                if ($newProgress >= 100 && !$enrollment->certificate_id) {
                    $certificate = Certificate::create([
                        "uuid" => (string) Str::uuid(),
                        "tenant_id" => $enrollment->tenant_id,
                        "user_id" => $enrollment->user_id,
                        "course_id" => $course->id,
                        "enrollment_id" => $enrollment->id,
                        "issued_at" => now(),
                        "correlation_id" => $correlationId,
                        "verification_code" => Str::upper(Str::random(8))
                    ]);

                    $enrollment->update(["certificate_id" => $certificate->id]);
                    Log::channel("audit")->info("Courses: certificate issued", ["user_id" => $enrollment->user_id, "cert" => $certificate->uuid]);
                }
            });
        }

        /**
         * Генерация WebRTC ссылки для живого урока.
         */
        public function getLiveLessonLink(int $lessonId, int $userId): string
        {
            $lesson = Lesson::findOrFail($lessonId);
            if (!$lesson->is_live) {
                throw new \RuntimeException("Этот урок не является живым вебинаром.", 422);
            }

            // Логирование доступа к живому уроку
            Log::channel("audit")->info("Courses: access live room", ["user_id" => $userId, "lesson_id" => $lessonId]);

            return "https://meet.catvrf.io/" . $lesson->uuid . "?token=" . Str::random(32);
        }
}
