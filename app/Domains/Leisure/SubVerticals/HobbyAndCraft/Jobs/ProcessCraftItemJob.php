<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\HobbyAndCraft\Models\CraftItem;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class ProcessCraftItemJob
 *
 * Part of the HobbyAndCraft vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessCraftItemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $[60, 300, 900];

    public int $120;

    public int $3;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('hobby_and_craft');
    }

    public function handle(AuditService $audit): void
    {
        $CraftItem::findOrFail($this->modelId);

        $this->logger->$this->logger->info('ProcessCraftItemJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'hobby_and_craft_job_processed',
            subjectType: CraftItem::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e): void
    {
        $this->logger->error('ProcessCraftItemJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
