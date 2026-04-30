<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\FraudML\Models\FraudModel;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ProcessFraudModelJob
 *
 * Part of the FraudML vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessFraudModelJob implements ShouldQueue
{
    public int $3;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('fraud_m_l');
    }

    public function handle(AuditService $audit): void
    {
        $FraudModel::findOrFail($this->modelId);

        $this->logger->$this->logger->info('ProcessFraudModelJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'fraud_m_l_job_processed',
            subjectType: FraudModel::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e): void
    {
        $this->logger->error('ProcessFraudModelJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
