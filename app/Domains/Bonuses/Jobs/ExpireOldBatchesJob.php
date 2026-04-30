<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Models\LockedBonusBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * ExpireOldBatchesJob - Job for expiring old bonus batches
 * 
 * Runs periodically to clean up old fully vested batches.
 */
final readonly class ExpireOldBatchesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes

    public function __construct(
        private readonly ?int $tenantId = null,
        private readonly int $daysOld = 90,
    ) {}

    public function handle(LoggerInterface $logger): void
    {
        $logger->info('Starting old batch expiration', [
            'tenant_id' => $this->tenantId,
            'days_old' => $this->daysOld,
        ]);

        $cutoffDate = now()->subDays($this->daysOld);

        $query = LockedBonusBatch::query()
            ->where('remaining_locked', '<=', 0)
            ->orWhere('vested_until', '<', now()->toDateString())
            ->where('created_at', '<', $cutoffDate);

        if ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        $batches = $query->get();
        
        $expiredCount = 0;
        $totalAmount = 0.0;

        foreach ($batches as $batch) {
            try {
                $totalAmount += $batch->original_amount;
                $batch->delete();
                $expiredCount++;

                $logger->debug('Batch expired', [
                    'batch_id' => $batch->id,
                    'user_id' => $batch->user_id,
                    'tenant_id' => $batch->tenant_id,
                    'original_amount' => $batch->original_amount,
                ]);
            } catch (\Exception $e) {
                $logger->error('Failed to expire batch', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logger->info('Old batch expiration completed', [
            'tenant_id' => $this->tenantId,
            'days_old' => $this->daysOld,
            'expired_count' => $expiredCount,
            'total_amount' => $totalAmount,
        ]);
    }
}
