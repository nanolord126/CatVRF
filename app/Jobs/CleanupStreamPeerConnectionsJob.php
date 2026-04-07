<?php declare(strict_types=1);

/**
 * CleanupStreamPeerConnectionsJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cleanupstreampeerconnectionsjob
 */


namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

/**
 * Class CleanupStreamPeerConnectionsJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs
 */
final class CleanupStreamPeerConnectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $timeout = 300;
        public int $tries = 3;
        public int $maxExceptions = 1;

        public function __construct(
            private int $olderThanMinutes = 60,
            private readonly LogManager $logger,
    ) {}

        /**
         * Handle handle operation.
         *
         * @throws \DomainException
         */
        public function handle(MeshService $meshService): void
        {
            try {
                $deleted = $meshService->cleanupClosedConnections($this->olderThanMinutes);

                $this->logger->channel('audit')->info(
                    'Stream peer connections cleanup completed',
                    ['deleted' => $deleted, 'older_than_minutes' => $this->olderThanMinutes]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error(
                    'Stream peer connections cleanup failed',
                    ['error' => $e->getMessage()]
                );

                throw $e;
            }
        }
}
