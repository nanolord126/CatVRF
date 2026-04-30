<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Bonuses\Domain\Repositories\LoyaltyStatusRepositoryInterface;

/**
 * Job ResetMonthlyLoyaltyTrackingJob
 *
 * Scheduled job that resets monthly point tracking for all owners.
 * Runs on the first day of each month to reset monthly caps and counters.
 * Ensures fair application of monthly bonus limits across all users.
 */
final class ResetMonthlyLoyaltyTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'bonuses';
    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly LoyaltyStatusRepositoryInterface $loyaltyRepository
    ) {}

    /**
     * Executes the monthly tracking reset process.
     */
    public function handle(): void
    {
        try {
            $resetCount = $this->loyaltyRepository->resetMonthlyTracking();

            Log::channel('bonuses')->info('Monthly loyalty tracking reset completed', [
                'reset_count' => $resetCount,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::channel('audit')->info('Monthly loyalty tracking reset completed', [
                'reset_count' => $resetCount,
                'job_id' => $this->job?->getJobId(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('bonuses')->error('Monthly loyalty tracking reset failed', [
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
        Log::channel('bonuses')->error('Monthly loyalty tracking reset failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
