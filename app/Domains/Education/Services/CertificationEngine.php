<?php

namespace App\Domains\Education\Services;

use App\Models\User;
use App\Domains\Education\Models\{Course, Enrollment, Certification, QuizAttempt};
use App\Domains\Common\Services\Marketing\AchievementEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Exception;
use Throwable;

class CertificationEngine
{
    private string $correlationId;
    private ?int $tenantId;

    public function __construct(
        protected AchievementEngine $achievements
    ) {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Выдача сертификата по окончании курса.
     */
    public function issueCertification(User $user, Course $course): Certification
    {
        try {
            Log::channel('education')->info('Certification issuance started', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($user, $course) {
                try {
                    $enrollment = Enrollment::where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->first();

                    if (!$enrollment) {
                        Log::warning('Enrollment not found', [
                            'user_id' => $user->id,
                            'course_id' => $course->id,
                            'correlation_id' => $this->correlationId,
                        ]);
                        throw new Exception("Запись студента не найдена.");
                    }

                    if ($enrollment->progress_percent < 100) {
                        Log::warning('Course not completed', [
                            'enrollment_id' => $enrollment->id,
                            'progress_percent' => $enrollment->progress_percent,
                            'correlation_id' => $this->correlationId,
                        ]);
                        throw new Exception("Курс не завершен на 100%.");
                    }

                    // Проверка всех квизов (Quizzes)
                    $unpassedQuizzes = $course->lessons()->with('quiz')
                        ->get()
                        ->pluck('quiz')
                        ->filter()
                        ->filter(fn($q) => !QuizAttempt::where('user_id', $user->id)
                            ->where('quiz_id', $q->id)
                            ->where('is_passed', true)
                            ->exists()
                        );

                    if ($unpassedQuizzes->isNotEmpty()) {
                        Log::warning('Unpassed quizzes detected', [
                            'user_id' => $user->id,
                            'course_id' => $course->id,
                            'unpassed_count' => $unpassedQuizzes->count(),
                            'correlation_id' => $this->correlationId,
                        ]);
                        throw new Exception("Не все промежуточные тесты пройдены.");
                    }

                    // Расчет финальной оценки
                    $finalScore = $this->calculateFinalGrade($user, $course);

                    // Выпуск сертификата
                    $cert = Certification::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                        'issued_at' => now(),
                        'expires_at' => now()->addYears(2),
                        'correlation_id' => $this->correlationId,
                        'tenant_id' => $this->tenantId,
                        'final_score' => $finalScore,
                        'metadata' => [
                            'final_score' => $finalScore,
                            'teacher' => $course->teacher?->name ?? 'Unknown',
                            'lessons_completed' => $course->lessons()->count(),
                        ]
                    ]);

                    // Аудит выдачи сертификата
                    AuditLog::create([
                        'entity_type' => Certification::class,
                        'entity_id' => $cert->id,
                        'action' => 'issued',
                        'user_id' => Auth::id(),
                        'tenant_id' => $this->tenantId,
                        'correlation_id' => $this->correlationId,
                        'changes' => [],
                        'metadata' => [
                            'student_id' => $user->id,
                            'course_id' => $course->id,
                            'final_score' => $finalScore,
                            'certificate_number' => $cert->certificate_number,
                        ],
                    ]);

                    // Награда в системе лояльности (Achievement)
                    try {
                        $this->achievements->grantAchievement($user, 'course_certified', [
                            'course_id' => $course->id,
                            'correlation_id' => $this->correlationId,
                        ]);
                    } catch (Throwable $e) {
                        Log::warning('Achievement grant failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                            'correlation_id' => $this->correlationId,
                        ]);
                    }

                    Log::channel('education')->info('Certification issued successfully', [
                        'certification_id' => $cert->id,
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'final_score' => $finalScore,
                        'correlation_id' => $this->correlationId,
                    ]);

                    return $cert;
                } catch (Throwable $e) {
                    Log::error('Certification issuance failed', [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);
                    throw $e;
                }
            });
        } catch (Throwable $e) {
            Log::error('Certification transaction failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Расчет финальной оценки по курсу.
     */
    protected function calculateFinalGrade(User $user, Course $course): float
    {
        try {
            $quizIds = $course->lessons()
                ->with('quiz')
                ->get()
                ->pluck('quiz.id')
                ->filter()
                ->toArray();

            if (empty($quizIds)) {
                Log::debug('No quizzes found for course', [
                    'course_id' => $course->id,
                    'correlation_id' => $this->correlationId,
                ]);
                return 100.0; // Если нет квизов, ставим максимальную оценку
            }

            $avgScore = QuizAttempt::where('user_id', $user->id)
                ->whereIn('quiz_id', $quizIds)
                ->avg('score') ?? 0.0;

            Log::debug('Final grade calculated', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'avg_score' => $avgScore,
                'correlation_id' => $this->correlationId,
            ]);

            return (float) $avgScore;
        } catch (Throwable $e) {
            Log::error('Final grade calculation failed', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            return 0.0;
        }
    }
}
