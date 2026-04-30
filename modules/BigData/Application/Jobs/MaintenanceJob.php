<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataMonitoringFacade;

/**
 * Maintenance Job
 *
 * Queued job that runs ClickHouse OPTIMIZE on tables with too many parts.
 * Scheduled via ObservabilityServiceProvider weekly (Sunday 03:00).
 *
 * Features:
 * - Exponential backoff on retry (30s, 60s)
 * - Tagged for monitoring in Horizon
 * - Audit-logged with correlation ID
 * - Reports per-table success/failure
 */
class MaintenanceJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;
    public bool $failOnTimeout = true;
    public array $tags = ['bigdata', 'maintenance'];

    /** Seconds to wait before retrying */
    public function backoff(): array
    {
        return [30, 60];
    }

    public function __construct(
        public readonly ?string $correlationId = null,
    ) {}

    public function handle(BigDataMonitoringFacade $monitoring): void
    {
        $startTime = microtime(true);

        Log::info('BigData: MaintenanceJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        try {
            $results = $monitoring->runMaintenance();
            $duration = round(microtime(true) - $startTime, 3);

            $successCount = count(array_filter($results));
            $failCount = count($results) - $successCount;

            Log::info('BigData: MaintenanceJob completed', [
                'tables_optimized' => count($results),
                'success' => $successCount,
                'failed' => $failCount,
                'duration_seconds' => $duration,
                'results' => $results,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BigData: MaintenanceJob failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a permanent job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('BigData: MaintenanceJob permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
