<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Services\DailyActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ProcessStreakJob - Job for processing user streaks
 * 
 * Runs daily to update streaks and calculate acceleration bonuses.
 */
final readonly class ProcessStreakJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes

    public function __construct(
        private readonly ?int $tenantId = null,
        private readonly ?string $date = null,
    ) {}

    public function handle(
        DailyActivityService $activityService,
        LoggerInterface $logger,
    ): void {
        $date = $this->date ?? now()->toDateString();
        
        $logger->info('Starting streak processing', [
            'tenant_id' => $this->tenantId,
            'date' => $date,
        ]);

        $users = $this->getUsersWithActivity($this->tenantId, $date);
        
        $totalUsers = 0;
        $totalAccelerationDays = 0;
        $milestoneCount = 0;

        foreach ($users as $user) {
            try {
                $result = $activityService->processStreak(
                    userId: $user->user_id,
                    tenantId: $user->tenant_id,
                    correlationId: Str::uuid()->toString(),
                );

                $totalUsers++;
                $totalAccelerationDays += $result->accelerationDays;

                if ($result->streakDays >= 7 && $result->streakDays % 7 === 0) {
                    $milestoneCount++;
                }

                $logger->debug('Streak processed for user', [
                    'user_id' => $user->user_id,
                    'tenant_id' => $user->tenant_id,
                    'streak_days' => $result->streakDays,
                    'streak_level' => $result->streakLevel->getLevel(),
                    'multiplier' => $result->multiplier,
                    'acceleration_days' => $result->accelerationDays,
                ]);
            } catch (\Exception $e) {
                $logger->error('Failed to process streak for user', [
                    'user_id' => $user->user_id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logger->info('Streak processing completed', [
            'tenant_id' => $this->tenantId,
            'date' => $date,
            'total_users' => $totalUsers,
            'total_acceleration_days' => $totalAccelerationDays,
            'milestone_count' => $milestoneCount,
        ]);
    }

    private function getUsersWithActivity(?int $tenantId, string $date): Collection
    {
        $query = DB::table('daily_activity_logs')
            ->select('user_id', 'tenant_id')
            ->where('activity_date', $date)
            ->where('action_count', '>=', 3);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }
}
