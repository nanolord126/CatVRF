<?php

declare(strict_types=1);

namespace App\Domains\Referral\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\Referral\Models\Referral;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ProcessReferralJob
 *
 * Part of the Referral vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessReferralJob implements ShouldQueue
{
    public int $3;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('referral');
    }

    public function handle(AuditService $audit): void
    {
        $Referral::findOrFail($this->modelId);

        $this->logger->$this->logger->info('ProcessReferralJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'referral_job_processed',
            subjectType: Referral::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e): void
    {
        $this->logger->error('ProcessReferralJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
