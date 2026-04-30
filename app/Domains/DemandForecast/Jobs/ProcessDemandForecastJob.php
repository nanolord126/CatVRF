<?php

declare(strict_types=1);

namespace App\Domains\DemandForecast\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\DemandForecast\Models\DemandForecast;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ProcessDemandForecastJob
 *
 * Part of the DemandForecast vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessDemandForecastJob implements ShouldQueue
{
    public int $3;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('demand_forecast');
    }

    public function handle(AuditService $audit): void
    {
        $DemandForecast::findOrFail($this->modelId);

        $this->logger->$this->logger->info('ProcessDemandForecastJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'demand_forecast_job_processed',
            subjectType: DemandForecast::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e): void
    {
        $this->logger->error('ProcessDemandForecastJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
