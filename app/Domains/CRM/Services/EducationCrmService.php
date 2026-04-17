<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmEducationProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * EducationCrmService — CRM-логика для вертикали Образование.
 *
 * Курсы, прогресс, сертификаты, навыки, обучающие пути,
 * расписание, предпочтения формата обучения.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class EducationCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать education-профиль CRM-клиента.
     */
    public function createEducationProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $educationLevel = null,
        array $learningGoals = [],
        array $preferredSubjects = [],
        ?string $learningStyle = null,
        ?string $preferredLanguage = null,
        ?string $preferredFormat = null,
        ?string $notes = null
    ): CrmEducationProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_education_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $educationLevel,
            $learningGoals, $preferredSubjects, $learningStyle,
            $preferredLanguage, $preferredFormat, $notes
    ): CrmEducationProfile {
            $profile = CrmEducationProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'education_level' => $educationLevel,
                'learning_goals' => $learningGoals,
                'preferred_subjects' => $preferredSubjects,
                'learning_style' => $learningStyle,
                'preferred_language' => $preferredLanguage,
                'preferred_format' => $preferredFormat,
                'completed_courses' => [],
                'active_enrollments' => [],
                'schedule_preferences' => [],
                'certifications' => [],
                'skills_acquired' => [],
                'total_spent_on_education' => 0,
                'courses_completed_count' => 0,
                'notes' => $notes,
            ]);

            $this->logger->info('Education CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'education_level' => $educationLevel,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_education_profile_created',
                CrmEducationProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Записать на курс.
     */
    public function enrollInCourse(
        CrmEducationProfile $profile,
        string $courseId,
        string $courseName,
        float $price,
        string $correlationId,
        ?string $startDate = null
    ): CrmEducationProfile {
        return $this->db->transaction(function () use ($profile, $courseId, $courseName, $price, $correlationId, $startDate): CrmEducationProfile {
            $enrollments = $profile->active_enrollments ?? [];
            $enrollments[] = [
                'course_id' => $courseId,
                'course_name' => $courseName,
                'price' => $price,
                'start_date' => $startDate ?? now()->toDateString(),
                'progress_pct' => 0,
                'enrolled_at' => now()->toDateTimeString(),
            ];

            $profile->update([
                'active_enrollments' => $enrollments,
                'total_spent_on_education' => ($profile->total_spent_on_education ?? 0) + $price,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'marketplace',
                    direction: 'inbound',
                    content: "Запись на курс: {$courseName}",
                    metadata: ['course_id' => $courseId, 'price' => $price]
    )
    );

            $this->audit->log(
                'crm_education_enrolled',
                CrmEducationProfile::class,
                $profile->id,
                [],
                ['course_id' => $courseId, 'course_name' => $courseName],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Завершить курс и выдать сертификат.
     */
    public function completeCourse(
        CrmEducationProfile $profile,
        string $courseId,
        string $correlationId,
        ?string $certificateUrl = null,
        array $skillsAcquired = []
    ): CrmEducationProfile {
        return $this->db->transaction(function () use ($profile, $courseId, $correlationId, $certificateUrl, $skillsAcquired): CrmEducationProfile {
            $enrollments = $profile->active_enrollments ?? [];
            $completed = $profile->completed_courses ?? [];
            $certifications = $profile->certifications ?? [];
            $skills = $profile->skills_acquired ?? [];

            $courseData = null;
            $newEnrollments = [];

            foreach ($enrollments as $enrollment) {
                if (($enrollment['course_id'] ?? '') === $courseId) {
                    $courseData = $enrollment;
                    $courseData['completed_at'] = now()->toDateTimeString();
                    $courseData['progress_pct'] = 100;
                    $completed[] = $courseData;
                } else {
                    $newEnrollments[] = $enrollment;
                }
            }

            if ($certificateUrl !== null) {
                $certifications[] = [
                    'course_id' => $courseId,
                    'course_name' => $courseData['course_name'] ?? $courseId,
                    'certificate_url' => $certificateUrl,
                    'issued_at' => now()->toDateString(),
                ];
            }

            $skills = array_unique(array_merge($skills, $skillsAcquired));

            $profile->update([
                'active_enrollments' => $newEnrollments,
                'completed_courses' => $completed,
                'certifications' => $certifications,
                'skills_acquired' => array_values($skills),
                'courses_completed_count' => count($completed),
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Education course completed', [
                'profile_id' => $profile->id,
                'course_id' => $courseId,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить прогресс курса.
     */
    public function updateCourseProgress(
        CrmEducationProfile $profile,
        string $courseId,
        int $progressPct,
        string $correlationId
    ): CrmEducationProfile {
        return $this->db->transaction(function () use ($profile, $courseId, $progressPct, $correlationId): CrmEducationProfile {
            $enrollments = $profile->active_enrollments ?? [];

            foreach ($enrollments as &$enrollment) {
                if (($enrollment['course_id'] ?? '') === $courseId) {
                    $enrollment['progress_pct'] = min($progressPct, 100);
                    break;
                }
            }
            unset($enrollment);

            $profile->update(['active_enrollments' => $enrollments]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Рекомендации следующих курсов на основе профиля.
     */
    public function getNextCourseRecommendations(CrmEducationProfile $profile): array
    {
        $completed = array_column($profile->completed_courses ?? [], 'course_id');
        $goals = $profile->learning_goals ?? [];
        $skills = $profile->skills_acquired ?? [];
        $subjects = $profile->preferred_subjects ?? [];

        return [
            'completed_count' => count($completed),
            'skills_count' => count($skills),
            'goals' => $goals,
            'preferred_subjects' => $subjects,
            'recommendation_type' => count($completed) < 3 ? 'beginner_path' : 'advanced_path',
        ];
    }

    /**
     * «Спящие» education-клиенты (бросили обучение).
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 30): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('education')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }
}
