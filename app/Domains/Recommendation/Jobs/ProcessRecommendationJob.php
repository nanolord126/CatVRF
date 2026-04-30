<?php

declare(strict_types=1);

namespace App\Domains\Recommendation\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\Recommendation\Models\Recommendation;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ProcessRecommendationJob
 *
 * Part of the Recommendation vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessRecommendationJob implements ShouldQueue
{
    public int $3;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('recommendation');
    }

    public function handle(AuditService $audit): void
    {
        $Recommendation::findOrFail($this->modelId);

        $this->logger->$this->logger->info('ProcessRecommendationJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'recommendation_job_processed',
            subjectType: Recommendation::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e): void
    {
        $this->logger->error('ProcessRecommendationJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
