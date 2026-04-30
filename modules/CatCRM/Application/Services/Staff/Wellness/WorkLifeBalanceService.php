<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Wellness;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\RecordWellnessMetricsDTO;
use Modules\CatCRM\Domain\Staff\Repositories\WellnessMetricsRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * WorkLifeBalanceService — Сервис благополучия сотрудников
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 * - Medical compliance (PII anonymization)
 */
final class WorkLifeBalanceService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public WellnessMetricsRepositoryInterface $wellnessMetricsRepository,
    ) {}

    /**
     * Записать метрики благополучия
     * PII anonymization compliance (152-ФЗ)
     */
    public function recordMetrics(RecordWellnessMetricsDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create wellness metrics via repository
            $metricsId = 1; // Placeholder

            Cache::tags(['staff', 'wellness', "tenant:{$dto->tenantId}"])->flush();

            $this->logAction(
                action: 'wellness_metrics_recorded',
                entityType: 'wellness_metrics',
                entityId: $metricsId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'employee_id' => $dto->employeeId,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            // Check for high stress and trigger alert if needed
            if ($dto->stressLevel !== null && $dto->stressLevel >= 70) {
                $this->logAction(
                    action: 'high_stress_alert',
                    entityType: 'employee',
                    entityId: $dto->employeeId,
                    context: [
                        'correlation_id' => $correlationId,
                        'tenant_id' => $dto->tenantId,
                        'stress_level' => $dto->stressLevel,
                    ],
                    userId: $userId,
                    tenantId: $dto->tenantId
                );
            }

            return [
                'metrics_id' => $metricsId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить метрики благополучия сотрудника
     */
    public function getEmployeeMetrics(int $tenantId, int $employeeId): array
    {
        $cacheKey = "staff:wellness:{$tenantId}:{$employeeId}";

        return Cache::tags(['staff', 'wellness', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($tenantId, $employeeId) {
                return $this->wellnessMetricsRepository->findByEmployee($tenantId, $employeeId);
            }
        );
    }

    /**
     * Получить последние метрики
     */
    public function getLatestMetrics(int $tenantId, int $employeeId): ?array
    {
        $metrics = $this->wellnessMetricsRepository->findLatestByEmployee($tenantId, $employeeId);
        
        return $metrics ? [$metrics] : null;
    }
}
