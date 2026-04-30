<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Services\LockedBonusUnlockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * ProcessDailyUnlockJob - Job for processing daily bonus unlocks
 * 
 * Runs daily at 00:05 to unlock bonuses for all users.
 * Processes all active locked bonus batches.
 */
final readonly class ProcessDailyUnlockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        private readonly ?int $tenantId = null,
        private readonly ?string $date = null,
    ) {}

    public function handle(
        LockedBonusUnlockService $unlockService,
        LoggerInterface $logger,
    ): void {
        $date = $this->date ?? now()->toDateString();
        
        $logger->info('Starting daily bonus unlock processing', [
            'tenant_id' => $this->tenantId,
            'date' => $date,
        ]);

        $users = $this->getUsersWithActiveBatches($this->tenantId);
        
        $totalUnlocked = 0.0;
        $totalUsers = 0;
        $totalBatches = 0;

        foreach ($users as $user) {
            try {
                $unlocked = $unlockService->processDailyUnlockForUser(
                    userId: $user->user_id,
                    tenantId: $user->tenant_id,
                );

                $totalUnlocked += $unlocked;
                $totalUsers++;
                
                $logger->debug('Daily unlock processed for user', [
                    'user_id' => $user->user_id,
                    'tenant_id' => $user->tenant_id,
                    'unlocked_amount' => $unlocked,
                ]);
            } catch (\Exception $e) {
                $logger->error('Failed to process daily unlock for user', [
                    'user_id' => $user->user_id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logger->info('Daily bonus unlock processing completed', [
            'tenant_id' => $this->tenantId,
            'date' => $date,
            'total_users' => $totalUsers,
            'total_batches' => $totalBatches,
            'total_unlocked' => $totalUnlocked,
        ]);
    }

    private function getUsersWithActiveBatches(?int $tenantId): Collection
    {
        $query = DB::table('locked_bonus_batches')
            ->select('user_id', 'tenant_id')
            ->where('remaining_locked', '>', 0)
            ->where('vested_until', '>=', now()->toDateString())
            ->distinct();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }
}
