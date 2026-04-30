<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\UnlockBonusDto;
use App\Domains\Bonuses\Events\LockedBonusUnlocked;
use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use App\Domains\Bonuses\Models\BonusWallet;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * LockedBonusUnlockService - Service for unlocking locked bonuses
 * 
 * Handles daily unlock and instant unlock operations.
 * Integrates with wallet crediting, fraud detection, and audit logging.
 */
final readonly class LockedBonusUnlockService
{
    public function __construct(
        private readonly LockedBonusRepositoryInterface $repository,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(UnlockBonusDto $dto): float
    {
        $dto->validate();

        if ($dto->instant) {
            return $this->executeInstantUnlock($dto);
        }

        return $this->executeDailyUnlock($dto);
    }

    private function executeDailyUnlock(UnlockBonusDto $dto): float
    {
        $batch = $this->repository->findById($dto->batchId);

        if (!$batch) {
            throw new \InvalidArgumentException("Batch not found: {$dto->batchId}");
        }

        if ($batch->user_id !== $dto->userId) {
            throw new \InvalidArgumentException("Batch does not belong to user");
        }

        if ($batch->isFullyVested()) {
            return 0.0;
        }

        return $this->db->transaction(function () use ($batch, $dto) {
            $unlockedAmount = $batch->unlockDaily();

            if ($unlockedAmount > 0) {
                // Credit to bonus wallet
                $wallet = BonusWallet::getOrCreateForUser($batch->user_id);
                $wallet->creditAvailable((int) ($unlockedAmount * 100)); // Convert to kopecks

                // Log audit
                $this->audit->record(
                    'locked_bonus_daily_unlock',
                    LockedBonusBatch::class,
                    $batch->id,
                    [],
                    [
                        'user_id' => $batch->user_id,
                        'tenant_id' => $batch->tenant_id,
                        'unlocked_amount' => $unlockedAmount,
                        'remaining_locked' => $batch->remaining_locked,
                        'batch_id' => $batch->id,
                    ],
                    $dto->correlationId,
                );

                // Dispatch event
                event(new LockedBonusUnlocked($batch, $unlockedAmount, false));

                $this->logger->info('Locked bonus daily unlock', [
                    'batch_id' => $batch->id,
                    'user_id' => $batch->user_id,
                    'unlocked_amount' => $unlockedAmount,
                    'remaining_locked' => $batch->remaining_locked,
                    'correlation_id' => $dto->correlationId,
                ]);
            }

            return $unlockedAmount;
        });
    }

    private function executeInstantUnlock(UnlockBonusDto $dto): float
    {
        // Fraud check for instant unlock
        $this->fraud->check([
            'operation_type' => 'instant_bonus_unlock',
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'batch_id' => $dto->batchId,
            'price' => $dto->instantUnlockPrice,
            'correlation_id' => $dto->correlationId,
        ]);

        $batch = $this->repository->findById($dto->batchId);

        if (!$batch) {
            throw new \InvalidArgumentException("Batch not found: {$dto->batchId}");
        }

        if ($batch->user_id !== $dto->userId) {
            throw new \InvalidArgumentException("Batch does not belong to user");
        }

        return $this->db->transaction(function () use ($batch, $dto) {
            $unlockedAmount = $batch->instantUnlock();

            // Credit to bonus wallet
            $wallet = BonusWallet::getOrCreateForUser($batch->user_id);
            $wallet->creditAvailable((int) ($unlockedAmount * 100)); // Convert to kopecks

            // Log audit
            $this->audit->record(
                'locked_bonus_instant_unlock',
                LockedBonusBatch::class,
                $batch->id,
                [],
                [
                    'user_id' => $batch->user_id,
                    'tenant_id' => $batch->tenant_id,
                    'unlocked_amount' => $unlockedAmount,
                    'instant_price' => $dto->instantUnlockPrice,
                    'batch_id' => $batch->id,
                ],
                $dto->correlationId,
            );

            // Dispatch event
            event(new LockedBonusUnlocked($batch, $unlockedAmount, true, $dto->instantUnlockPrice));

            $this->logger->info('Locked bonus instant unlock', [
                'batch_id' => $batch->id,
                'user_id' => $batch->user_id,
                'unlocked_amount' => $unlockedAmount,
                'instant_price' => $dto->instantUnlockPrice,
                'correlation_id' => $dto->correlationId,
            ]);

            return $unlockedAmount;
        });
    }

    public function processDailyUnlockForUser(int $userId, int $tenantId): float
    {
        $batches = $this->repository->getActiveBatchesForUser($userId, $tenantId);
        $totalUnlocked = 0.0;

        foreach ($batches as $batch) {
            $dto = new UnlockBonusDto(
                userId: $userId,
                tenantId: $tenantId,
                batchId: $batch->id,
                instant: false,
                correlationId: null,
            );

            $totalUnlocked += $this->execute($dto);
        }

        return $totalUnlocked;
    }
}
