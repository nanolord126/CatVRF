<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Jobs;

use App\Domains\Advertising\Models\AdCampaign;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Job for async processing of ad campaign operations.
 *
 * Maintains correlation_id for full traceability.
 * LoggerInterface resolved in handle() — NOT stored in constructor (serialization safety).
 *
 * @package App\Domains\Advertising\Jobs
 */
final class ProcessAdCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * Do NOT inject LoggerInterface here — not serializable.
     */
    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('advertising');
    }

    /**
     * Execute the job.
     *
     * Logger and AuditService resolved via DI — safe for queue workers.
     */
    public function handle(LoggerInterface $logger, AuditService $audit): void
    {
        $model = AdCampaign::findOrFail($this->modelId);

        $logger->info('ProcessAdCampaignJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id,
        ]);

        $audit->log(
            action: 'advertising_job_processed',
            subjectType: AdCampaign::class,
            subjectId: $model->id,
            old: [],
            new: $model->toArray(),
            correlationId: $this->correlationId,
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'ProcessAdCampaignJob failed [model_id=%d, correlation_id=%s]: %s',
                $this->modelId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
