declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\RealtimeAnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Aggregate daily analytics
 * Runs: Every day at 00:05
 * 
 * @package App\Jobs
 */
final class AggregateDailyAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(RealtimeAnalyticsService $analyticsService): void
    {
        $yesterday = yesterday()->format('Y-m-d');

        try {
            $analyticsService->aggregateDailyStats($this->tenantId, $yesterday);

            $this->log->channel('audit')->info('Daily analytics aggregated', [
                'tenant_id' => $this->tenantId,
                'date' => $yesterday,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to aggregate daily analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
