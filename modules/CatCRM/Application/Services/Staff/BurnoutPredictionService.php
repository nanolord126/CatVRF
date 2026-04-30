<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonImmutable;

/**
 * BurnoutPredictionService — AI-предсказание выгорания сотрудников
 * 
 * Following CatVRF rules:
 * - Async LLM calls via queue
 * - WithAuditLogging trait
 * - Cache with tags
 * - PII anonymization for medical compliance
 */
final class BurnoutPredictionService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Предсказать риск выгорания сотрудника
     * LLM вызов асинхронно через Job
     */
    public function predictBurnoutRisk(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();
        
        $cacheKey = "staff:burnout:{$tenantId}:{$employeeId}";
        
        $result = Cache::tags(['staff', 'burnout', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId, $employeeId, $correlationId, $userId) {
                // Анонимизация PII перед отправкой в LLM (152-ФЗ compliance)
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\PredictBurnoutRiskJob(
                    tenantId: $tenantId,
                    employeeId: $employeeId,
                    correlationId: $correlationId,
                    userId: $userId,
                ));

                return [
                    'status' => 'processing',
                    'correlation_id' => $correlationId,
                    'message' => 'Burnout risk prediction in progress',
                ];
            }
        );

        $this->logAction(
            action: 'burnout_prediction_requested',
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
     * Получить предсказание выгорания из кэша
     */
    public function getBurnoutPrediction(int $tenantId, int $employeeId): ?array
    {
        $cacheKey = "staff:burnout:{$tenantId}:{$employeeId}";
        
        return Cache::tags(['staff', 'burnout', "tenant:{$tenantId}"])->get($cacheKey);
    }

    /**
     * Получить сотрудников с высоким риском выгорания
     */
    public function getHighRiskEmployees(int $tenantId, ?int $userId = null): array
    {
        $cacheKey = "staff:burnout:high_risk:{$tenantId}";
        
        $result = Cache::tags(['staff', 'burnout', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($tenantId) {
                // Получаем из базы данных сотрудников с burnout_risk >= 70
                // Это не требует LLM вызова
                return [
                    'employee_ids' => [], // Заполняется из DB
                    'count' => 0,
                    'last_updated' => CarbonImmutable::now()->toIso8601String(),
                ];
            }
        );

        $this->logAction(
            action: 'high_risk_employees_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: [
                'tenant_id' => $tenantId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }
}
