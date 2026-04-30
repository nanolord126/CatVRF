<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Analytics;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonImmutable;

/**
 * ScheduleOptimizationService — Сервис оптимизации расписания
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - Async AI analysis via queue
 */
final class ScheduleOptimizationService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Оптимизировать расписание с AI
     */
    public function optimizeSchedule(
        int $tenantId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        $cacheKey = "staff:schedule_optimization:{$tenantId}:{$startDate->toDateString()}";

        $result = Cache::tags(['staff', 'optimization', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($tenantId, $startDate, $endDate, $correlationId, $userId) {
                // Dispatch AI job for async optimization
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\OptimizeScheduleJob(
                    tenantId: $tenantId,
                    startDate: $startDate,
                    endDate: $endDate,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                ];
            }
        );

        $this->logAction(
            action: 'schedule_optimization_requested',
            entityType: 'tenant',
            entityId: $tenantId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }

    /**
     * Получить рекомендации по оптимизации
     */
    public function getOptimizationRecommendations(int $tenantId, ?int $userId = null): array
    {
        $cacheKey = "staff:optimization_recommendations:{$tenantId}";

        $recommendations = Cache::tags(['staff', 'optimization', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId) {
                // TODO: Fetch from database
                return [
                    'understaffed_periods' => [],
                    'overstaffed_periods' => [],
                    'suggestions' => [],
                ];
            }
        );

        $this->logAction(
            action: 'optimization_recommendations_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: ['tenant_id' => $tenantId],
            userId: $userId,
            tenantId: $tenantId
        );

        return $recommendations;
    }
}
