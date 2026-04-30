<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Events\BonusBatchAwarded;
use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * LockedBonusAwardService - Service for awarding locked bonus batches
 * 
 * Handles creation of locked bonus batches with 15-day Smart Hold.
 * Integrates with fraud detection, audit logging, and event dispatching.
 */
final readonly class LockedBonusAwardService
{
    public function __construct(
        private readonly LockedBonusRepositoryInterface $repository,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(AwardLockedBonusDto $dto): LockedBonusBatch
    {
        $dto->validate();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'locked_bonus_award',
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'source' => $dto->source->getValue(),
            'correlation_id' => $dto->correlationId,
        ]);

        return $this->db->transaction(function () use ($dto) {
            $vestingUntil = now()->addDays($dto->vestingCurve->getDays());

            $batch = LockedBonusBatch::create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'original_amount' => $dto->amount,
                'remaining_locked' => $dto->amount,
                'daily_unlock_rate' => $dto->vestingCurve->getDailyRate(),
                'vested_until' => $vestingUntil,
                'acceleration_history' => [],
                'source' => $dto->source->getValue(),
                'correlation_id' => $dto->correlationId ?? Str::uuid()->toString(),
            ]);

            // Log audit
            $this->audit->record(
                'locked_bonus_batch_awarded',
                LockedBonusBatch::class,
                $batch->id,
                [],
                $dto->toAuditContext(),
                $dto->correlationId,
            );

            // Dispatch event
            event(new BonusBatchAwarded($batch, $dto));

            $this->logger->info('Locked bonus batch awarded', [
                'batch_id' => $batch->id,
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'source' => $dto->source->getValue(),
                'vesting_curve' => $dto->vestingCurve->getType(),
                'vested_until' => $vestingUntil->toDateString(),
                'correlation_id' => $dto->correlationId,
            ]);

            return $batch;
        });
    }

    public function getAvailableBalance(int $userId, int $tenantId): float
    {
        $totalUnlocked = $this->repository->getTotalUnlockedForUser($userId, $tenantId);
        return $totalUnlocked;
    }

    public function getLockedBalance(int $userId, int $tenantId): float
    {
        return $this->repository->getTotalLockedForUser($userId, $tenantId);
    }
}
