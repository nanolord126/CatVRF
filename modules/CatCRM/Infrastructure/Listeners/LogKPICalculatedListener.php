<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\KPICalculated;
use App\Services\AuditService;

/**
 * LogKPICalculatedListener — Listener для логирования расчета KPI
 */
final class LogKPICalculatedListener
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function handle(KPICalculated $event): void
    {
        $kpi = $event->kpi;

        $this->auditService->record(
            action: 'kpi_calculated',
            entityType: 'ManagerKPI',
            entityId: $kpi->id,
            context: [
                'manager_id' => $kpi->manager_id,
                'previous_score' => $event->previousScore,
                'new_score' => $kpi->score,
                'period_type' => $kpi->period_type,
                'period_start' => $kpi->period_start->toDateString(),
                'period_end' => $kpi->period_end->toDateString(),
            ],
            userId: $kpi->manager_id,
            tenantId: $kpi->tenant_id
        );
    }
}
