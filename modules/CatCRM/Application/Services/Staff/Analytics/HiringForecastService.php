<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Analytics;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;

/**
 * HiringForecastService — Сервис прогнозирования найма
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - Async AI analysis via queue
 */
final class HiringForecastService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Прогнозировать потребность в найме с AI
     */
    public function forecastHiringNeeds(
        int $tenantId,
        int $monthsAhead = 6,
        ?int $userId = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        $cacheKey = "staff:hiring_forecast:{$tenantId}:{$monthsAhead}months";

        $result = Cache::tags(['staff', 'forecast', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addDays(7),
            function () use ($tenantId, $monthsAhead, $correlationId, $userId) {
                // Dispatch AI job for async forecasting
                dispatch(new \Modules\CatCRM\Infrastructure\Jobs\GenerateHiringForecastJob(
                    tenantId: $tenantId,
                    monthsAhead: $monthsAhead,
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
            action: 'hiring_forecast_requested',
            entityType: 'tenant',
            entityId: $tenantId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'months_ahead' => $monthsAhead,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $result;
    }

    /**
     * Получить прогноз найма
     */
    public function getHiringForecast(int $tenantId, ?int $userId = null): ?array
    {
        $cacheKey = "staff:hiring_forecast:{$tenantId}:6months";

        $forecast = Cache::tags(['staff', 'forecast', "tenant:{$tenantId}"])->get($cacheKey);

        if (!$forecast) {
            return null;
        }

        $this->logAction(
            action: 'hiring_forecast_viewed',
            entityType: 'tenant',
            entityId: $tenantId,
            context: ['tenant_id' => $tenantId],
            userId: $userId,
            tenantId: $tenantId
        );

        return $forecast;
    }
}
