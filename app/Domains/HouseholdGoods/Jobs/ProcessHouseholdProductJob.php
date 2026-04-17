<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\HouseholdGoods\Models\HouseholdProduct;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
/**
 * Class ProcessHouseholdProductJob
 *
 * Part of the HouseholdGoods vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\HouseholdGoods\Jobs
 */
final class ProcessHouseholdProductJob implements ShouldQueue
{

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
        $this->onQueue('household_goods');
    }

    public function handle(AuditService $audit): void
    {
        $model = HouseholdProduct::findOrFail($this->modelId);

        $this->logger->info('ProcessHouseholdProductJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'household_goods_job_processed',
            subjectType: HouseholdProduct::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $e): void
    {
        $this->logger->error('ProcessHouseholdProductJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}