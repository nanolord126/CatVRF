<?php declare(strict_types=1);

namespace App\Jobs;


use App\Services\Payment\IdempotencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;


/**
 * Class CleanupExpiredIdempotencyRecordsJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs
 */
final class CleanupExpiredIdempotencyRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;  // 5 минут

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly LogManager $logger,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(IdempotencyService $service): void
    {
        try {
            $deletedCount = $service->cleanup();

            $this->logger->channel('audit')->info('Idempotency cleanup job completed', [
                'deleted_records' => $deletedCount,
                'job_id' => $this->job?->getJobId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Idempotency cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
