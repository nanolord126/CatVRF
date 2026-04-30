<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Training;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateTrainingCourseDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TrainingCourseService — Сервис курсов обучения
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class TrainingCourseService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Создать курс обучения
     */
    public function createCourse(CreateTrainingCourseDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create course via repository
            $courseId = 1; // Placeholder

            Cache::tags(['staff', 'training', "tenant:{$dto->tenantId}"])->flush();

            $this->logCreated(
                entityType: 'training_course',
                entityId: $courseId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'course_title' => $dto->title,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'course_id' => $courseId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Записать сотрудника на курс
     */
    public function enrollEmployee(int $tenantId, int $courseId, int $employeeId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $courseId, $employeeId, $correlationId, $userId) {
            // TODO: Create enrollment in database
            $enrollmentId = 1; // Placeholder

            Cache::tags(['staff', 'training', "tenant:{$tenantId}"])->flush();

            $this->logAction(
                action: 'course_enrolled',
                entityType: 'course_enrollment',
                entityId: $enrollmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'course_id' => $courseId,
                    'employee_id' => $employeeId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'enrollment_id' => $enrollmentId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить курсы тенанта
     */
    public function getCourses(int $tenantId): array
    {
        $cacheKey = "staff:training:courses:{$tenantId}";

        return Cache::tags(['staff', 'training', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId) {
                // TODO: Fetch courses from database
                return [];
            }
        );
    }
}
