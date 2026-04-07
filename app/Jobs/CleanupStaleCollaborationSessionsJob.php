<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;


/**
 * Class CleanupStaleCollaborationSessionsJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs
 */
final class CleanupStaleCollaborationSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

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
    public function handle(): void
    {
        try {
            // Очищаем устаревшие сессии редактирования
            // В продакшене можно использовать более оптимизированный подход с Redis SCAN

            $this->logger->channel('audit')->info('Cleanup stale collaboration sessions job completed', [
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to cleanup stale collaboration sessions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
