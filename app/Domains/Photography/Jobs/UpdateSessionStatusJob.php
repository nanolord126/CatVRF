<?php declare(strict_types=1);

namespace App\Domains\Photography\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FraudControlService;

/**
 * Class UpdateSessionStatusJob
 *
 * Part of the Photography vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\Photography\Jobs
 */
final class UpdateSessionStatusJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private readonly PhotoSession $session,
        private readonly string $newStatus,
        private readonly string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function handle(): void
    {
        try {
            $this->db->transaction(function () {
                $this->session->update(['status' => $this->newStatus]);

                $this->logger->info('Photography: Session status auto-updated', [
                    'session_id' => $this->session->id,
                    'new_status' => $this->newStatus,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Photography: Session status update failed', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}

