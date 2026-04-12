<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Jobs;




use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;
use App\Domains\CleaningServices\Models\CleaningOrder;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
/**
 * Class ProcessCleaningOrderJob
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\CleaningServices\Jobs
 */
final class ProcessCleaningOrderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly int $modelId,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
        $this->onQueue('cleaning_services');
    }

    public function handle(AuditService $audit): void
    {
        $model = CleaningOrder::findOrFail($this->modelId);

        $this->logger->info('ProcessCleaningOrderJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'cleaning_services_job_processed',
            subjectType: CleaningOrder::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $e): void
    {
        $this->logger->error('ProcessCleaningOrderJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}