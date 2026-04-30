<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Jobs;

use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Class ProcessAnalyticsEventJob
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessAnalyticsEventJob implements ShouldQueue
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
    ) {
        $this->onQueue('analytics');
    }

    public function handle(AuditService $audit, LoggerInterface $logger): void
    {
        $AnalyticsEvent::findOrFail($this->modelId);

        $logger->$this->logger->info('ProcessAnalyticsEventJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'analytics_job_processed',
            subjectType: AnalyticsEvent::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(Exception $e, LoggerInterface $logger): void
    {
        $logger->error('ProcessAnalyticsEventJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
