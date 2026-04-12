<?php declare(strict_types=1);

namespace App\Domains\AI\Jobs;


use App\Domains\AI\Models\AIModel;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Class ProcessAIModelJob
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing of AI model operations.
 * Maintains correlation_id for full traceability across queue workers.
 * Logger is resolved via method injection in handle() to avoid serialization issues.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\AI\Jobs
 */
final class ProcessAIModelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * Note: LoggerInterface is NOT injected here to avoid serialization issues.
     * It is resolved via method injection in handle().
     */
    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('a_i');
    }

    /**
     * Execute the job.
     *
     * Logger and AuditService are resolved via method injection
     * to ensure proper instantiation in the queue worker context.
     */
    public function handle(LoggerInterface $logger, AuditService $audit): void
    {
        $model = AIModel::findOrFail($this->modelId);

        $logger->info('ProcessAIModelJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'a_i_job_processed',
            subjectType: AIModel::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    /**
     * Handle a job failure.
     *
     * Logger is resolved from the container since it cannot be
     * stored as a property on a serializable job.
     */
    public function failed(\Throwable $e): void
    {
        report(new \RuntimeException(
            sprintf(
                'ProcessAIModelJob failed [model_id=%d, correlation_id=%s]: %s',
                $this->modelId,
                $this->correlationId,
                $e->getMessage(),
            ),
            previous: $e,
        ));
    }
}
