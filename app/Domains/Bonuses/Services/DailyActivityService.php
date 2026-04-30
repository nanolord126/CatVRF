<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\StreakResultDto;
use App\Domains\Bonuses\Events\StreakUpdated;
use App\Domains\Bonuses\Interfaces\DailyActivityRepositoryInterface;
use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Models\DailyActivityLog;
use App\Domains\Bonuses\ValueObjects\StreakLevel;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * DailyActivityService - Service for processing daily user activity
 * 
 * Handles activity tracking, streak calculation, and hold acceleration.
 * Integrates with audit logging and event dispatching.
 */
final readonly class DailyActivityService
{
    public function __construct(
        private readonly DailyActivityRepositoryInterface $activityRepository,
        private readonly LockedBonusRepositoryInterface $bonusRepository,
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    public function processActivity(int $userId, int $tenantId, string $actionType, ?array $metadata = null): DailyActivityLog
    {
        return $this->db->transaction(function () use ($userId, $tenantId, $actionType, $metadata) {
            $log = $this->activityRepository->firstOrCreateForToday($userId, $tenantId);
            $this->activityRepository->addAction($log, $actionType, $metadata);

            $this->logger->debug('Activity logged', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'action_type' => $actionType,
                'action_count' => $log->action_count,
            ]);

            return $log;
        });
    }

    public function processStreak(int $userId, int $tenantId, ?string $correlationId = null): StreakResultDto
    {
        return $this->db->transaction(function () use ($userId, $tenantId, $correlationId) {
            $log = $this->activityRepository->firstOrCreateForToday($userId, $tenantId);
            
            $previousStreak = $log->streak_days;
            $streakDays = $this->activityRepository->calculateStreak($userId, $tenantId);
            $streakLevel = StreakLevel::fromDays($streakDays);
            
            $accelerationDays = $log->getAccelerationDays();
            $actionCount = $log->action_count;

            // Update log with new streak
            $log->streak_days = $streakDays;
            $log->acceleration_days = $accelerationDays;
            $this->activityRepository->update($log);

            // Apply acceleration if eligible
            if ($accelerationDays > 0) {
                $this->applyAcceleration($userId, $tenantId, $accelerationDays);
            }

            $dto = StreakResultDto::create(
                userId: $userId,
                tenantId: $tenantId,
                streakDays: $streakDays,
                actionCount: $actionCount,
                accelerationDays: $accelerationDays,
                correlationId: $correlationId,
            );

            $previousLevel = $previousStreak > 0 ? StreakLevel::fromDays($previousStreak) : null;

            // Dispatch event
            event(new StreakUpdated($dto, $previousLevel));

            // Log audit
            $this->audit->record(
                'streak_updated',
                DailyActivityLog::class,
                $log->id,
                [],
                $dto->toAuditContext(),
                $correlationId,
            );

            $this->logger->info('Streak processed', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'streak_days' => $streakDays,
                'streak_level' => $streakLevel->getLevel(),
                'multiplier' => $streakLevel->getMultiplier(),
                'acceleration_days' => $accelerationDays,
                'action_count' => $actionCount,
                'correlation_id' => $correlationId,
            ]);

            return $dto;
        });
    }

    private function applyAcceleration(int $userId, int $tenantId, int $days): void
    {
        $batches = $this->bonusRepository->getActiveBatchesForUser($userId, $tenantId);

        foreach ($batches as $batch) {
            $batch->accelerate($days);
        }

        $this->logger->info('Acceleration applied', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'acceleration_days' => $days,
            'batches_affected' => $batches->count(),
        ]);
    }

    public function claimYield(int $userId, int $tenantId): bool
    {
        return $this->db->transaction(function () use ($userId, $tenantId) {
            $log = $this->activityRepository->firstOrCreateForToday($userId, $tenantId);

            if ($log->has_claimed_yield) {
                return false;
            }

            $this->activityRepository->markYieldClaimed($log);

            $this->logger->info('Yield claimed', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'activity_date' => $log->activity_date,
            ]);

            return true;
        });
    }

    public function getStreakMultiplier(int $userId, int $tenantId): float
    {
        $streakDays = $this->activityRepository->getCurrentStreak($userId, $tenantId);
        $streakLevel = StreakLevel::fromDays($streakDays);
        
        return $streakLevel->getMultiplier();
    }
}
