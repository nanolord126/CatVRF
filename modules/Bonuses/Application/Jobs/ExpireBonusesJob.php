<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Application\Services\BonusesFacadeService;

/**
 * Job ExpireBonusesJob
 *
 * Scheduled job that expires bonuses that have passed their expiration date.
 * Runs daily to mark expired bonuses and dispatch expiration events.
 * Uses queued job for performance and to prevent timeouts with large datasets.
 */
final class ExpireBonusesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'bonuses';
    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly BonusesFacadeService $bonusesFacade
    ) {}

    /**
     * Executes the bonus expiration process.
     */
    public function handle(): void
    {
        try {
            $expiredCount = $this->bonusesFacade->expireBonuses();

            Log::channel('bonuses')->info('Bonuses expiration job completed', [
                'expired_count' => $expiredCount,
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($expiredCount > 0) {
                Log::channel('audit')->info('Bulk bonus expiration completed', [
                    'expired_count' => $expiredCount,
                    'job_id' => $this->job?->getJobId(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Bonuses expiration job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handles job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('bonuses')->error('Bonuses expiration job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
