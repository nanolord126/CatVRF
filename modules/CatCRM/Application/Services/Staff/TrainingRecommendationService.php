<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;

/**
 * TrainingRecommendationService — AI-рекомендации по обучению и карьерному росту
 * 
 * Following CatVRF rules:
 * - Async LLM calls via queue
 * - WithAuditLogging trait
 * - Cache with tags
 */
final class TrainingRecommendationService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Получить персонализированные рекомендации по обучению
     */
    public function getTrainingRecommendations(
        int $tenantId,
        int $employeeId,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:training_recommendations:{$tenantId}:{$employeeId}";
        
        $result = Cache::tags(['staff', 'training', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addDays(7),
            function () use ($tenantId, $employeeId, $correlationId, $userId) {
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\GenerateTrainingRecommendationsJob(
                    tenantId: $tenantId,
                    employeeId: $employeeId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Training recommendations in progress',
                ];
            }
        );

        $this->logAction(
            action: 'training_recommendations_requested',
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

    /**
     * Получить карьерный путь и рекомендации по росту
     */
    public function getCareerPathRecommendations(
        int $tenantId,
        int $employeeId,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:career_path:{$tenantId}:{$employeeId}";
        
        $result = Cache::tags(['staff', 'career', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addDays(30),
            function () use ($tenantId, $employeeId, $correlationId, $userId) {
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\GenerateCareerPathJob(
                    tenantId: $tenantId,
                    employeeId: $employeeId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Career path recommendations in progress',
                ];
            }
        );

        $this->logAction(
            action: 'career_path_requested',
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
