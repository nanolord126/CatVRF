<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class CleanupStaleCollaborationSessionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('cleanup');
    }

    public function tags(): array
    {
        return ['cleanup', 'collaboration'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(1);
    }

    public function handle(): void
    {
        $this->logger->channel('audit')->$this->logger->info('[CleanupStaleCollaborationJob] Started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $staleThreshold = CarbonImmutable::now()->subHours(24);

            $deletedCount = $this->db->table('collaboration_sessions')
                ->where('updated_at', '<', $staleThreshold)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'expired_at' => CarbonImmutable::now(),
                ]);

            $this->db->table('collaboration_cleanup_logs')->insert([
                'deleted_count' => $deletedCount,
                'correlation_id' => $this->correlationId,
                'completed_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[CleanupStaleCollaborationJob] Completed', [
                'expired_sessions' => $deletedCount,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[CleanupStaleCollaborationJob] Failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[CleanupStaleCollaborationJob] Failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
