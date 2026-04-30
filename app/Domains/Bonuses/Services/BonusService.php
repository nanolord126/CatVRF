<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use App\Domains\Bonuses\DTOs\BonusBalanceDto;
use App\Domains\Bonuses\Models\BonusTransaction;
use App\Domains\Bonuses\Models\BonusWallet;
use App\Domains\Bonuses\Models\BonusRule;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

/**
 * BonusService - Application service for bonus operations
 * 
 * Main orchestrator for bonus domain operations.
 * Integrates domain services, fraud detection, audit logging, and event dispatching.
 */
final readonly class BonusService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly BonusCalculationService $calculation,
        private readonly BonusEligibilityService $eligibility,
        private readonly BonusWithdrawalService $withdrawal,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Award bonus to user with hold period
     */
    public function award(AwardBonusDto $dto): BonusTransaction
    {
        // Get or create bonus wallet
        $wallet = BonusWallet::getOrCreateForUser((string) $dto->userId);

        // Fraud check
        $this->fraud->check([
            'operation_type' => "bonus_award_{$dto->type->value}",
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'bonus_type' => $dto->type->value,
            'correlation_id' => $dto->correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $wallet) {
            // Create bonus transaction
            $transaction = BonusTransaction::create([
                'id' => (string) Str::uuid(),
                'uuid' => (string) Str::uuid(),
                'tenant_id' => (string) $dto->tenantId,
                'wallet_id' => $wallet->id,
                'user_id' => (string) $dto->userId,
                'type' => BonusTransaction::TYPE_AWARD,
                'amount' => $dto->amount,
                'balance_after' => $wallet->pending_balance + $dto->amount,
                'status' => BonusTransaction::STATUS_PENDING,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId ? (string) $dto->sourceId : null,
                'hold_until' => CarbonImmutable::now()->addDays(14),
                'expires_at' => CarbonImmutable::now()->addDays(365),
                'metadata' => $dto->metadata,
                'correlation_id' => $dto->correlationId,
                'tags' => ['bonus', 'pending', $dto->type->value, $dto->verticalCode ?? 'global'],
            ]);

            // Update wallet pending balance
            $wallet->creditPending($dto->amount);

            // Audit log
            $this->audit->record(
                'bonus_awarded',
                BonusTransaction::class,
                null,
                [],
                array_merge($dto->toAuditContext(), [
                    'transaction_id' => $transaction->id,
                ]),
                $dto->correlationId,
            );

            // Dispatch event
            $this->eventDispatcher->dispatch(
                new \App\Domains\Bonuses\Events\BonusAwarded($transaction, $dto)
            );

            $this->logger->info('Bonus awarded', [
                'transaction_id' => $transaction->id,
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'correlation_id' => $dto->correlationId,
            ]);

            return $transaction;
        });
    }

    /**
     * Unlock expired holds
     */
    public function unlockExpiredHolds(?string $correlationId = null): int
    {
        $correlationId ??= Str::uuid()->toString();
        $unlockedCount = 0;

        $pendingBonuses = BonusTransaction::query()
            ->readyToUnlock()
            ->limit(100)
            ->get();

        foreach ($pendingBonuses as $bonus) {
            try {
                $this->db->transaction(function () use ($bonus, $correlationId, &$unlockedCount) {
                    $wallet = BonusWallet::findOrFail($bonus->wallet_id);

                    // Mark as credited
                    $bonus->markAsCredited();
                    $bonus->balance_after = $wallet->available_balance + $bonus->amount;
                    $bonus->save();

                    // Move from pending to available
                    $wallet->moveFromPendingToAvailable($bonus->amount);

                    // Audit log
                    $this->audit->record(
                        'bonus_unlocked',
                        BonusTransaction::class,
                        null,
                        [],
                        [
                            'user_id' => $bonus->user_id,
                            'amount' => $bonus->amount,
                        ],
                        $correlationId,
                    );

                    // Dispatch event
                    $this->eventDispatcher->dispatch(
                        new \App\Domains\Bonuses\Events\BonusUnlocked($bonus)
                    );

                    $unlockedCount++;
                });
            } catch (\Exception $e) {
                $this->logger->error('Bonus unlock failed', [
                    'bonus_id' => $bonus->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $this->logger->info('Expired holds unlocked', [
            'unlocked_count' => $unlockedCount,
            'correlation_id' => $correlationId,
        ]);

        return $unlockedCount;
    }

    /**
     * Spend bonuses
     */
    public function spend(SpendBonusDto $dto): void
    {
        // Get bonus wallet
        $wallet = BonusWallet::forUser((string) $dto->userId)->firstOrFail();

        // Fraud check
        $this->fraud->check([
            'operation_type' => 'bonus_spend',
            'amount' => $dto->amount,
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'reason' => $dto->reason,
            'correlation_id' => $dto->correlationId,
        ]);

        $this->db->transaction(function () use ($dto, $wallet) {
            // Create spend transaction
            $transaction = BonusTransaction::create([
                'id' => (string) Str::uuid(),
                'uuid' => (string) Str::uuid(),
                'tenant_id' => (string) $dto->tenantId,
                'wallet_id' => $wallet->id,
                'user_id' => (string) $dto->userId,
                'type' => BonusTransaction::TYPE_SPEND,
                'amount' => $dto->amount,
                'balance_after' => $wallet->available_balance - $dto->amount,
                'status' => BonusTransaction::STATUS_SPENT,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId ? (string) $dto->sourceId : null,
                'metadata' => array_merge($dto->metadata, [
                    'reason' => $dto->reason,
                ]),
                'correlation_id' => $dto->correlationId,
                'tags' => ['bonus', 'spent'],
            ]);

            // Debit from wallet
            $wallet->debitAvailable($dto->amount);

            // Audit log
            $this->audit->record(
                'bonus_spent',
                BonusTransaction::class,
                null,
                [],
                array_merge($dto->toAuditContext(), [
                    'transaction_id' => $transaction->id,
                ]),
                $dto->correlationId,
            );

            // Dispatch event
            $this->eventDispatcher->dispatch(
                new \App\Domains\Bonuses\Events\BonusSpent($transaction, $dto)
            );

            $this->logger->info('Bonus spent', [
                'transaction_id' => $transaction->id,
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'correlation_id' => $dto->correlationId,
            ]);
        });
    }

    /**
     * Withdraw bonuses to real money (B2B only)
     */
    public function withdraw(WithdrawBonusDto $dto): BonusTransaction
    {
        // Get bonus wallet
        $wallet = BonusWallet::forUser($dto->userId)->firstOrFail();

        return $this->withdrawal->requestWithdrawal($dto, $wallet, $dto->correlationId ?? Str::uuid()->toString());
    }

    /**
     * Get bonus balance
     */
    public function getBalance(string $userId): BonusBalanceDto
    {
        $wallet = BonusWallet::forUser($userId)->firstOrFail();

        return BonusBalanceDto::fromModel($wallet);
    }

    /**
     * Get bonus history
     */
    public function getHistory(string $userId, int $perPage = 20)
    {
        return BonusTransaction::query()
            ->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Expire old bonuses
     */
    public function expireOldBonuses(?string $correlationId = null): int
    {
        $correlationId ??= Str::uuid()->toString();
        $expiredCount = 0;

        $expiredBonuses = BonusTransaction::query()
            ->expired()
            ->limit(1000)
            ->get();

        foreach ($expiredBonuses as $bonus) {
            try {
                $this->db->transaction(function () use ($bonus, $correlationId, &$expiredCount) {
                    $wallet = BonusWallet::findOrFail($bonus->wallet_id);

                    $bonus->markAsExpired();
                    $wallet->expire($bonus->amount);

                    // Audit log
                    $this->audit->record(
                        'bonus_expired',
                        BonusTransaction::class,
                        null,
                        [],
                        [
                            'user_id' => $bonus->user_id,
                            'amount' => $bonus->amount,
                        ],
                        $correlationId,
                    );

                    $expiredCount++;
                });
            } catch (\Exception $e) {
                $this->logger->error('Bonus expiry failed', [
                    'bonus_id' => $bonus->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $this->logger->info('Old bonuses expired', [
            'expired_count' => $expiredCount,
            'correlation_id' => $correlationId,
        ]);

        return $expiredCount;
    }

    /**
     * Calculate bonus for order
     */
    public function calculateBonusForOrder(
        float $orderAmount,
        string $ruleType,
        ?string $verticalCode = null,
        ?string $userType = null,
    ): int {
        return $this->calculation->calculateBonus($orderAmount, $ruleType, $verticalCode, $userType);
    }

    /**
     * Get available rules for vertical
     */
    public function getRulesForVertical(string $ruleType, ?string $verticalCode = null): \Illuminate\Support\Collection
    {
        return $this->calculation->getActiveRules($ruleType, $verticalCode);
    }
}
