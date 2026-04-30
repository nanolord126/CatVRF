<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;

/**
 * SkillMatchingService — Автоматическое сопоставление навыков с задачами
 * 
 * Following CatVRF rules:
 * - Async LLM calls via queue
 * - WithAuditLogging trait
 * - Cache with tags
 */
final class SkillMatchingService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Найти подходящих сотрудников для задачи на основе навыков
     */
    public function matchEmployeesToTask(
        int $tenantId,
        array $requiredSkills,
        string $taskCategory,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:skill_match:{$tenantId}:" . md5(json_encode($requiredSkills));
        
        $result = Cache::tags(['staff', 'skill_matching', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(2),
            function () use ($tenantId, $requiredSkills, $taskCategory, $correlationId, $userId) {
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\MatchSkillsToTaskJob(
                    tenantId: $tenantId,
                    requiredSkills: $requiredSkills,
                    taskCategory: $taskCategory,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Skill matching in progress',
                ];
            }
        );

        $this->logAction(
            action: 'skill_matching_requested',
            entityType: 'task',
            entityId: null,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'required_skills' => $requiredSkills,
                'task_category' => $taskCategory,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }

    /**
     * Получить рекомендации по развитию навыков для сотрудника
     */
    public function recommendSkillDevelopment(
        int $tenantId,
        int $employeeId,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:skill_recommendations:{$tenantId}:{$employeeId}";
        
        $result = Cache::tags(['staff', 'skill_recommendations', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addDays(7),
            function () use ($tenantId, $employeeId, $correlationId, $userId) {
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\RecommendSkillDevelopmentJob(
                    tenantId: $tenantId,
                    employeeId: $employeeId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Skill development recommendations in progress',
                ];
            }
        );

        $this->logAction(
            action: 'skill_recommendations_requested',
            entityType: 'employee',
            entityId: $employeeId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }
}
