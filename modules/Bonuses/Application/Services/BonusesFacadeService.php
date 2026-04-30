<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Services;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Bonuses\Domain\Entities\BonusAggregate;
use Modules\Bonuses\Domain\Entities\BonusProgram;
use Modules\Bonuses\Domain\Enums\BonusStatus;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\Events\BonusAwarded;
use Modules\Bonuses\Domain\Events\BonusConsumed;
use Modules\Bonuses\Domain\Events\BonusExpired;
use Modules\Bonuses\Domain\Events\BonusFrozen;
use Modules\Bonuses\Domain\Events\LoyaltyLevelChanged;
use Modules\Bonuses\Domain\Interfaces\BonusRuleEngineInterface;
use Modules\Bonuses\Domain\Interfaces\LoyaltyCalculatorInterface;
use Modules\Bonuses\Domain\Repositories\BonusProgramRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\LoyaltyStatusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;
use Modules\Bonuses\Domain\ValueObjects\CorrelationId;
use Modules\Bonuses\Domain\ValueObjects\ExpirationDate;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Class BonusesFacadeService
 *
 * Main facade service for all bonus operations in the Bonuses vertical.
 * Provides a unified interface for awarding, consuming, and managing bonuses.
 * Integrates with fraud detection, audit logging, cache, and external services.
 * Follows Clean Architecture with domain-driven design principles.
 */
final class BonusesFacadeService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600;
    private const BALANCE_CACHE_PREFIX = 'bonus_balance:';
    private const LOYALTY_CACHE_PREFIX = 'loyalty_status:';

    public function __construct(
        private readonly BonusRepositoryInterface $bonusRepository,
        private readonly BonusProgramRepositoryInterface $programRepository,
        private readonly LoyaltyStatusRepositoryInterface $loyaltyRepository,
        private readonly BonusRuleEngineInterface $ruleEngine,
        private readonly LoyaltyCalculatorInterface $loyaltyCalculator,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly UuidInterface $uuid,
        private readonly ?object $fraudControl = null
    ) {}

    /**
     * Awards a bonus to an owner with full validation and fraud checks.
     *
     * @param  string  $ownerId  The entity receiving the bonus.
     * @param  BonusType  $type  The type of bonus.
     * @param  int  $amount  The bonus amount in smallest currency unit.
     * @param  array  $context  Additional context (source, vertical, etc.).
     * @return BonusAggregate The awarded bonus aggregate.
     * @throws DomainException When validation or fraud checks fail.
     */
    public function awardBonus(string $ownerId, BonusType $type, int $amount, array $context = []): BonusAggregate
    {
        // Fraud check before awarding bonus
        if ($this->fraudControl !== null) {
            $fraudResult = $this->fraudControl->checkBonusAward($ownerId, $type, $amount, $context);
            if ($fraudResult->isFraud()) {
                $this->logger->warning('Bonus award blocked by fraud control', [
                    'owner_id' => $ownerId,
                    'type' => $type->value,
                    'amount' => $amount,
                    'reason' => $fraudResult->getReason(),
                ]);
                throw new DomainException('Bonus award blocked: ' . $fraudResult->getReason());
            }
        }

        $bonusAmount = new BonusAmount($amount);
        $correlationId = CorrelationId::generate('bonus');

        // Validate using rule engine
        $this->ruleEngine->validateAward($ownerId, $type, $bonusAmount, $context);

        // Calculate final amount after rules and multipliers
        $calculatedAmount = $this->ruleEngine->calculateAmount($ownerId, $type, $bonusAmount, $context);

        // Get loyalty status for tier multipliers
        $loyaltyStatus = $this->getLoyaltyStatus($ownerId);
        $finalAmount = $this->loyaltyCalculator->applyBonusMultiplier($ownerId, $calculatedAmount, $loyaltyStatus);

        // Calculate expiration
        $expiresAt = $this->ruleEngine->calculateExpiration($ownerId, $type, $context);

        // Create and save bonus aggregate
        $bonus = BonusAggregate::award(
            id: $this->uuid->toString(),
            ownerId: $ownerId,
            amount: $finalAmount,
            type: $type,
            correlationId: $correlationId->toString(),
            expiresAt: $expiresAt,
            sourceId: $context['source_id'] ?? null,
            sourceType: $context['source_type'] ?? null,
            metadata: $context['metadata'] ?? []
        );

        $this->bonusRepository->save($bonus);

        // Invalidate cache
        $this->invalidateBalanceCache($ownerId);
        $this->invalidateLoyaltyCache($ownerId);

        // Dispatch event
        event(new BonusAwarded(
            bonusId: $bonus->getId(),
            ownerId: $ownerId,
            amount: $finalAmount,
            type: $type,
            correlationId: $correlationId->toString(),
            expiresAt: $expiresAt,
            sourceId: $context['source_id'] ?? null,
            sourceType: $context['source_type'] ?? null,
            metadata: $context['metadata'] ?? []
        ));

        // Audit log
        $this->logger->info('Bonus awarded', [
            'bonus_id' => $bonus->getId(),
            'owner_id' => $ownerId,
            'amount' => $finalAmount->getAmount(),
            'type' => $type->value,
            'correlation_id' => $correlationId->toString(),
        ]);

        // Update loyalty points
        $pointsFromBonus = $this->loyaltyCalculator->calculatePointsFromBonus($ownerId, $finalAmount, $loyaltyStatus);
        $newLoyaltyStatus = $loyaltyStatus->addPoints($pointsFromBonus)->recordBonusAward();
        $this->updateLoyaltyStatus($ownerId, $loyaltyStatus, $newLoyaltyStatus);

        return $bonus;
    }

    /**
     * Consumes bonuses for an owner with proper locking and validation.
     *
     * @param  string  $ownerId  The entity consuming bonuses.
     * @param  int  $amount  The amount to consume.
     * @param  array  $context  Additional context (transaction_id, vertical, etc.).
     * @return array Array of consumed bonus IDs.
     * @throws DomainException When insufficient balance or validation fails.
     */
    public function consumeBonus(string $ownerId, int $amount, array $context = []): array
    {
        // Fraud check before consuming bonus
        if ($this->fraudControl !== null) {
            $fraudResult = $this->fraudControl->checkBonusConsumption($ownerId, $amount, $context);
            if ($fraudResult->isFraud()) {
                $this->logger->warning('Bonus consumption blocked by fraud control', [
                    'owner_id' => $ownerId,
                    'amount' => $amount,
                    'reason' => $fraudResult->getReason(),
                ]);
                throw new DomainException('Bonus consumption blocked: ' . $fraudResult->getReason());
            }
        }

        $correlationId = CorrelationId::generate('consume');
        $requestedAmount = new BonusAmount($amount);

        // Check available balance
        $availableBalance = $this->bonusRepository->getAvailableBalance($ownerId);
        if ($availableBalance < $amount) {
            throw new DomainException('Insufficient bonus balance');
        }

        // Get active bonuses ordered by consumption priority
        $activeBonuses = $this->bonusRepository->findActiveByOwnerId($ownerId);
        usort($activeBonuses, function ($a, $b) {
            return $a->getType()->getConsumptionPriority() <=> $b->getType()->getConsumptionPriority();
        });

        $remainingToConsume = $amount;
        $consumedIds = [];
        $now = new DateTimeImmutable();

        foreach ($activeBonuses as $bonus) {
            if ($remainingToConsume <= 0) {
                break;
            }

            // Lock for processing
            $lockedBonus = $this->bonusRepository->lockById($bonus->getId());
            if (!$lockedBonus) {
                continue;
            }

            $lockedBonus->lockForProcessing();

            try {
                $availableInBonus = $lockedBonus->getRemainingAmount()->getAmount();
                if ($availableInBonus === 0) {
                    continue;
                }

                $toConsumeFromThis = min($availableInBonus, $remainingToConsume);
                $consumeAmount = new BonusAmount($toConsumeFromThis);

                $lockedBonus->consume($consumeAmount, $now);
                $this->bonusRepository->save($lockedBonus);

                $consumedIds[] = $lockedBonus->getId();
                $remainingToConsume -= $toConsumeFromThis;

                // Dispatch consumption event
                event(new BonusConsumed(
                    bonusId: $lockedBonus->getId(),
                    ownerId: $ownerId,
                    consumedAmount: $consumeAmount,
                    remainingAmount: $lockedBonus->getRemainingAmount(),
                    type: $lockedBonus->getType(),
                    correlationId: $correlationId->toString(),
                    transactionId: $context['transaction_id'] ?? null,
                    vertical: $context['vertical'] ?? null
                ));

            } catch (\Exception $e) {
                $lockedBonus->releaseProcessingLock();
                $this->bonusRepository->save($lockedBonus);
                throw $e;
            }
        }

        if ($remainingToConsume > 0) {
            throw new DomainException('Failed to consume full amount due to race condition');
        }

        // Invalidate cache
        $this->invalidateBalanceCache($ownerId);

        // Audit log
        $this->logger->info('Bonus consumed', [
            'owner_id' => $ownerId,
            'amount' => $amount,
            'consumed_bonus_ids' => $consumedIds,
            'correlation_id' => $correlationId->toString(),
        ]);

        return $consumedIds;
    }

    /**
     * Gets the available bonus balance for an owner.
     *
     * @param  string  $ownerId  The entity to check balance for.
     * @return int The available balance in smallest currency unit.
     */
    public function getAvailableBalance(string $ownerId): int
    {
        $cacheKey = self::BALANCE_CACHE_PREFIX . $ownerId;

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($ownerId) {
            return $this->bonusRepository->getAvailableBalance($ownerId);
        });
    }

    /**
     * Gets the balance breakdown by bonus type.
     *
     * @param  string  $ownerId  The entity to check balance for.
     * @return array Array mapping bonus types to balances.
     */
    public function getBalanceByType(string $ownerId): array
    {
        return $this->bonusRepository->getBalanceByType($ownerId);
    }

    /**
     * Gets the loyalty status for an owner.
     *
     * @param  string  $ownerId  The entity to check loyalty status for.
     * @return LoyaltyStatus The loyalty status.
     */
    public function getLoyaltyStatus(string $ownerId): LoyaltyStatus
    {
        $cacheKey = self::LOYALTY_CACHE_PREFIX . $ownerId;

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($ownerId) {
            $status = $this->loyaltyRepository->findByOwnerId($ownerId);

            if ($status === null) {
                return LoyaltyStatus::fromPoints(0);
            }

            return $status;
        });
    }

    /**
     * Adds loyalty points to an owner.
     *
     * @param  string  $ownerId  The entity receiving points.
     * @param  float  $transactionAmount  The transaction amount.
     * @param  string  $activityType  The type of activity.
     * @param  array  $context  Additional context.
     * @return LoyaltyStatus The new loyalty status.
     */
    public function addLoyaltyPoints(string $ownerId, float $transactionAmount, string $activityType, array $context = []): LoyaltyStatus
    {
        $currentStatus = $this->getLoyaltyStatus($ownerId);
        $pointsEarned = $this->loyaltyCalculator->calculatePointsEarned($ownerId, $transactionAmount, $activityType, $context);
        $newStatus = $this->loyaltyCalculator->calculateNewStatus($ownerId, $currentStatus, $pointsEarned);

        $this->updateLoyaltyStatus($ownerId, $currentStatus, $newStatus);

        return $newStatus;
    }

    /**
     * Expires bonuses that have passed their expiration date.
     * Called by scheduled jobs.
     *
     * @return int The number of bonuses expired.
     */
    public function expireBonuses(): int
    {
        $now = new DateTimeImmutable();
        $expiredBonuses = $this->bonusRepository->findExpiredBefore($now);
        $expiredCount = 0;

        foreach ($expiredBonuses as $bonus) {
            if ($bonus->expireIfPast($now)) {
                $this->bonusRepository->save($bonus);

                event(new BonusExpired(
                    bonusId: $bonus->getId(),
                    ownerId: $bonus->getOwnerId(),
                    expiredAmount: $bonus->getRemainingAmount(),
                    type: $bonus->getType(),
                    expiredAt: $now,
                    correlationId: $bonus->getCorrelationId()
                ));

                $this->invalidateBalanceCache($bonus->getOwnerId());
                $expiredCount++;
            }
        }

        return $expiredCount;
    }

    /**
     * Freezes a bonus pending investigation.
     *
     * @param  string  $bonusId  The bonus ID to freeze.
     * @param  string  $reason  The reason for freezing.
     * @param  string|null  $frozenBy  Who initiated the freeze.
     */
    public function freezeBonus(string $bonusId, string $reason, ?string $frozenBy = null): void
    {
        $bonus = $this->bonusRepository->findById($bonusId);
        if (!$bonus) {
            throw new DomainException('Bonus not found');
        }

        $bonus->freeze();
        $this->bonusRepository->save($bonus);

        event(new BonusFrozen(
            bonusId: $bonus->getId(),
            ownerId: $bonus->getOwnerId(),
            frozenAmount: $bonus->getRemainingAmount(),
            type: $bonus->getType(),
            frozenAt: new DateTimeImmutable(),
            correlationId: $bonus->getCorrelationId(),
            reason: $reason,
            frozenBy: $frozenBy
        ));

        $this->invalidateBalanceCache($bonus->getOwnerId());
    }

    /**
     * Unfreezes a previously frozen bonus.
     *
     * @param  string  $bonusId  The bonus ID to unfreeze.
     */
    public function unfreezeBonus(string $bonusId): void
    {
        $bonus = $this->bonusRepository->findById($bonusId);
        if (!$bonus) {
            throw new DomainException('Bonus not found');
        }

        $bonus->unfreeze();
        $this->bonusRepository->save($bonus);

        $this->invalidateBalanceCache($bonus->getOwnerId());
    }

    /**
     * Cancels a bonus for fraud or manual correction.
     *
     * @param  string  $bonusId  The bonus ID to cancel.
     */
    public function cancelBonus(string $bonusId): void
    {
        $bonus = $this->bonusRepository->findById($bonusId);
        if (!$bonus) {
            throw new DomainException('Bonus not found');
        }

        $bonus->cancel();
        $this->bonusRepository->save($bonus);

        $this->invalidateBalanceCache($bonus->getOwnerId());
    }

    /**
     * Gets all bonuses for an owner with optional filtering.
     *
     * @param  string  $ownerId  The entity to get bonuses for.
     * @param  BonusStatus|null  $status  Optional status filter.
     * @return array Array of bonus aggregates.
     */
    public function getBonusesForOwner(string $ownerId, ?BonusStatus $status = null): array
    {
        return $this->bonusRepository->findByOwnerId($ownerId, $status);
    }

    /**
     * Gets bonuses expiring soon for notifications.
     *
     * @param  int  $daysWithin  Number of days to look ahead.
     * @return array Array of expiring bonuses.
     */
    public function getExpiringBonuses(int $daysWithin = 7): array
    {
        return $this->bonusRepository->findExpiringWithinDays($daysWithin);
    }

    /**
     * Updates loyalty status and handles tier change events.
     */
    private function updateLoyaltyStatus(string $ownerId, LoyaltyStatus $previousStatus, LoyaltyStatus $newStatus): void
    {
        $this->loyaltyRepository->save($ownerId, $newStatus);
        $this->invalidateLoyaltyCache($ownerId);

        if ($this->loyaltyCalculator->hasTierChanged($newStatus, $previousStatus)) {
            event(new LoyaltyLevelChanged(
                ownerId: $ownerId,
                previousTier: $previousStatus->tier,
                newTier: $newStatus->tier,
                totalPoints: $newStatus->points,
                changedAt: new DateTimeImmutable(),
                correlationId: CorrelationId::generate('loyalty')->toString(),
                reason: 'points_earned'
            ));

            $this->logger->info('Loyalty level changed', [
                'owner_id' => $ownerId,
                'previous_tier' => $previousStatus->tier->value,
                'new_tier' => $newStatus->tier->value,
                'total_points' => $newStatus->points,
            ]);
        }
    }

    /**
     * Invalidates balance cache for an owner.
     */
    private function invalidateBalanceCache(string $ownerId): void
    {
        $this->cache->forget(self::BALANCE_CACHE_PREFIX . $ownerId);
    }

    /**
     * Invalidates loyalty cache for an owner.
     */
    private function invalidateLoyaltyCache(string $ownerId): void
    {
        $this->cache->forget(self::LOYALTY_CACHE_PREFIX . $ownerId);
    }
}
