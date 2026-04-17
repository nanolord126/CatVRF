<?php declare(strict_types=1);

namespace App\Domains\Legal\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\Legal\Models\Lawyer;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
/**
 * Class ProcessLawyerJob
 *
 * Part of the Legal vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\Legal\Jobs
 */
final class ProcessLawyerJob implements ShouldQueue
{

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
        $this->onQueue('legal');
    }

    public function handle(AuditService $audit): void
    {
        $model = Lawyer::findOrFail($this->modelId);

        $this->logger->info('ProcessLawyerJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'legal_job_processed',
            subjectType: Lawyer::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $e): void
    {
        $this->logger->error('ProcessLawyerJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}