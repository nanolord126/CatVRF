<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Services\Payment\IdempotencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class CleanupExpiredIdempotencyRecordsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('cleanup');
    }

    public function tags(): array
    {
        return ['cleanup', 'idempotency'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(1);
    }

    public function handle(IdempotencyService $service): void
    {
        $this->logger->channel('audit')->$this->logger->info('[CleanupIdempotencyJob] Started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $deletedCount = $service->cleanup();

            $this->db->table('idempotency_cleanup_logs')->insert([
                'deleted_records' => $deletedCount,
                'correlation_id' => $this->correlationId,
                'completed_at' => CarbonImmutable::now(),
            ]);

            $this->logger->channel('audit')->$this->logger->info('[CleanupIdempotencyJob] Completed', [
                'deleted_records' => $deletedCount,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('[CleanupIdempotencyJob] Failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('[CleanupIdempotencyJob] Failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
