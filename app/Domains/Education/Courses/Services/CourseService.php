<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;


use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Services\Payment\PaymentService;
use Illuminate\Cache\RateLimiter;
use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CourseService
{


    public function __construct(private readonly FraudControlService $fraud,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Запись на курс (Enrollment).
         */
        public function enroll(int $userId, int $courseId, bool $isB2B = false, array $meta = [], string $correlationId = ""): Enrollment
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от массовых бесплатных записей
            if ($this->rateLimiter->tooManyAttempts("courses:enroll:{$userId}", 5)) {
                throw new \RuntimeException("Слишком много попыток записи. Попробуйте позже.", 429);
            }
            $this->rateLimiter->hit("courses:enroll:{$userId}", 3600);

            return $this->db->transaction(function () use ($userId, $courseId, $isB2B, $meta, $correlationId) {
                $course = Course::findOrFail($courseId);

                // Расчет цены с учетом B2B скидки (КАНОН 20%)
                $priceKopecks = $isB2B ? (int)($course->price_kopecks * 0.8) : $course->price_kopecks;

                // 2. Fraud Check (проверка на отмыв бонусов через курсы)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->warning("Courses Security Block", ["user_id" => $userId, "score" => $fraud["score"]]);
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

                $this->logger->info("Courses: user enrolled", ["enrollment_id" => $enrollment->id, "corr" => $correlationId]);

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

            $this->db->transaction(function () use ($enrollment, $lessonId, $course, $correlationId) {
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
                        "issued_at" => Carbon::now(),
                        "correlation_id" => $correlationId,
                        "verification_code" => Str::upper(Str::random(8))
                    ]);

                    $enrollment->update(["certificate_id" => $certificate->id]);
                    $this->logger->info("Courses: certificate issued", ["user_id" => $enrollment->user_id, "cert" => $certificate->uuid]);
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
            $this->logger->info("Courses: access live room", ["user_id" => $userId, "lesson_id" => $lessonId]);

            return "https://meet.catvrf.io/" . $lesson->uuid . "?token=" . Str::random(32);
        }
}
