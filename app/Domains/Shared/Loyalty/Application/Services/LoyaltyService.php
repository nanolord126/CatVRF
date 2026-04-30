<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\Services;

use DateTimeImmutable;
use DomainException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Events\Dispatcher;
use Modules\Loyalty\Application\DTOs\CalculatePointsDTO;
use Modules\Loyalty\Application\DTOs\EnrollGuestDTO;
use Modules\Loyalty\Application\DTOs\PointsCalculationResultDTO;
use Modules\Loyalty\Application\DTOs\RedeemRewardDTO;
use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\Entities\LoyaltyProgram;
use Modules\Loyalty\Domain\Entities\LoyaltyReward;
use Modules\Loyalty\Domain\Entities\LoyaltyRule;
use Modules\Loyalty\Domain\Entities\LoyaltyTier;
use Modules\Loyalty\Domain\Entities\LoyaltyTransaction;
use Modules\Loyalty\Domain\Events\GuestEnrolled;
use Modules\Loyalty\Domain\Events\PointsEarned;
use Modules\Loyalty\Domain\Events\RewardRedeemed;
use Modules\Loyalty\Domain\Events\TierUpgraded;
use Modules\Loyalty\Domain\Enums\LoyaltyTransactionType;
use Modules\Loyalty\Domain\Enums\LoyaltyRuleType;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyRewardRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyRuleRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyTransactionRepositoryInterface;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;
use Ramsey\Uuid\Uuid;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * LoyaltyService — Сервис для управления программами лояльности
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class LoyaltyService
{
    use WithAuditLogging;

    public function __construct(
        private LoyaltyProgramRepositoryInterface $programRepository,
        private LoyaltyTierRepositoryInterface $tierRepository,
        private GuestLoyaltyProfileRepositoryInterface $profileRepository,
        private LoyaltyTransactionRepositoryInterface $transactionRepository,
        private LoyaltyRuleRepositoryInterface $ruleRepository,
        private LoyaltyRewardRepositoryInterface $rewardRepository,
        private readonly AuditService $auditService,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly Dispatcher $event,
    ) {}

    public function enrollGuest(EnrollGuestDTO $dto): GuestLoyaltyProfile
    {
        return $this->db->transaction(function () use ($dto) {
            $program = $this->programRepository->findById($dto->programId);
            if ($program === null) {
                throw new DomainException('Loyalty program not found');
            }

            if (!$program->isCurrentlyActive()) {
                throw new DomainException('Loyalty program is not active');
            }

            $existingProfile = $this->profileRepository->findByGuestAndProgram(
                $dto->guestId,
                $dto->programId
            );

            if ($existingProfile !== null) {
                return $existingProfile;
            }

            $signupBonus = Points::fromFloat($program->getSignupBonusPoints());
            $profile = GuestLoyaltyProfile::enroll(
                uuid: Uuid::uuid4()->toString(),
                tenantId: $program->getTenantId(),
                loyaltyProgramId: $program->getId(),
                guestId: $dto->guestId,
                userId: $dto->userId,
                signupBonus: $signupBonus,
                birthday: $dto->birthday,
                referredBy: $dto->referredBy,
                preferences: $dto->preferences,
                metadata: null,
                correlationId: $dto->correlationId
            );

            $savedProfile = $this->profileRepository->save($profile);

            if ($signupBonus->getValue() > 0) {
                $this->recordTransaction(
                    profile: $savedProfile,
                    program: $program,
                    type: LoyaltyTransactionType::BONUS,
                    pointsChange: $signupBonus,
                    description: 'Signup bonus',
                    sourceType: 'signup',
                    sourceId: null
                );
            }

            Event::dispatch(new GuestEnrolled(GuestEnrolled::fromProfile($savedProfile)));

            return $savedProfile;
        });
    }

    public function calculatePoints(CalculatePointsDTO $dto): PointsCalculationResultDTO
    {
        $program = $this->programRepository->findById($dto->programId);
        if ($program === null) {
            throw new DomainException('Loyalty program not found');
        }

        $basePoints = Points::fromFloat(
            $program->calculatePointsFromAmount($dto->orderAmount)
        );

        $bonusPoints = Points::zero();
        $multiplier = 1.0;
        $appliedRules = [];

        $rules = $this->ruleRepository->findActiveByProgramId($dto->programId);

        foreach ($rules as $rule) {
            if (!$this->ruleApplies($rule, $dto)) {
                continue;
            }

            $rulePoints = $this->calculateRulePoints($rule, $dto, $basePoints);
            $bonusPoints = $bonusPoints->add($rulePoints);
            $multiplier *= $rule->getPointMultiplier();
            $appliedRules[] = $rule->getName();

            // Increment rule usage
            $this->ruleRepository->save($rule->incrementUsage());
        }

        $tierMultiplier = 1.0;
        if ($dto->tierSlug !== null && $program->isTierSystemEnabled()) {
            $tiers = $this->tierRepository->findByProgramIdSorted($dto->programId);
            foreach ($tiers as $tier) {
                if ($tier->getSlug() === $dto->tierSlug) {
                    $tierMultiplier = $tier->getPointMultiplier();
                    break;
                }
            }
        }

        $totalMultiplier = $multiplier * $tierMultiplier;

        $description = $this->generateDescription(
            $dto->orderAmount->getValue(),
            $basePoints->getValue(),
            $bonusPoints->getValue(),
            $totalMultiplier
        );

        return PointsCalculationResultDTO::create(
            basePoints: $basePoints,
            bonusPoints: $bonusPoints,
            multiplier: $totalMultiplier,
            appliedRules: $appliedRules,
            description: $description
        );
    }

    public function processOrderLoyalty(
        int $guestId,
        string $programId,
        CurrencyAmount $orderAmount,
        ?string $tierSlug = null,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): GuestLoyaltyProfile {
        return $this->db->transaction(function () use ($guestId, $programId, $orderAmount, $tierSlug, $sourceType, $sourceId) {
            $profile = $this->profileRepository->findByGuestAndProgram($guestId, $programId);
            if ($profile === null) {
                throw new DomainException('Guest profile not found. Enroll guest first.');
            }

            $program = $this->programRepository->findById($programId);
            if ($program === null) {
                throw new DomainException('Loyalty program not found');
            }

            $dto = CalculatePointsDTO::fromArray([
                'program_id' => $programId,
                'guest_id' => $guestId,
                'order_amount' => $orderAmount->getValue(),
                'tier_slug' => $tierSlug,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            $result = $this->calculatePoints($dto);

            $profile = $this->recordTransaction(
                profile: $profile,
                program: $program,
                type: LoyaltyTransactionType::EARNED,
                pointsChange: $result->totalPoints,
                description: $result->description,
                sourceType: $sourceType,
                sourceId: $sourceId,
                orderAmount: $orderAmount->getValue(),
                pointMultiplier: $result->multiplier
            );

            $profile = $this->profileRepository->save($profile->withSpendAdded($orderAmount));

            $this->checkTierUpgrade($profile);

            return $profile->fresh();
        });
    }

    public function applyReward(RedeemRewardDTO $dto): GuestLoyaltyProfile
    {
        return $this->db->transaction(function () use ($dto) {
            $profile = $this->profileRepository->findById($dto->profileId);
            if ($profile === null) {
                throw new DomainException('Guest profile not found');
            }

            $reward = $this->rewardRepository->findById($dto->rewardId);
            if ($reward === null) {
                throw new DomainException('Reward not found');
            }

            if (!$reward->isCurrentlyAvailable()) {
                throw new DomainException('Reward is not available');
            }

            if (!$profile->canSpendPoints($dto->pointsCost)) {
                throw new DomainException('Insufficient points balance');
            }

            $profile = $this->recordTransaction(
                profile: $profile,
                program: $this->programRepository->findById($profile->getLoyaltyProgramId()),
                type: LoyaltyTransactionType::REDEEMED,
                pointsChange: $dto->pointsCost->multiply(-1),
                description: "Reward redeemed: {$reward->getName()}",
                sourceType: 'reward',
                sourceId: (int) $reward->getId()
            );

            $profile = $this->profileRepository->save($profile->withPointsRedeemed($dto->pointsCost));
            $this->rewardRepository->save($reward->incrementRedemption());

            Event::dispatch(new RewardRedeemed(
                RewardRedeemed::fromRedemption($profile, $reward, $profile->getAvailablePoints())
            ));

            return $profile;
        });
    }

    public function checkTierUpgrade(GuestLoyaltyProfile $profile): ?LoyaltyTier
    {
        $program = $this->programRepository->findById($profile->getLoyaltyProgramId());
        if ($program === null || !$program->isTierSystemEnabled()) {
            return null;
        }

        $tiers = $this->tierRepository->findByProgramIdSorted($profile->getLoyaltyProgramId());

        $currentTier = null;
        if ($profile->getCurrentTierId() !== null) {
            $currentTier = $this->tierRepository->findById($profile->getCurrentTierId());
        }

        $newTier = null;
        foreach ($tiers as $tier) {
            if ($tier->qualifies(
                $profile->getEarnedPoints(),
                $profile->getTotalSpend(),
                $profile->getTotalVisits()
            )) {
                $newTier = $tier;
            }
        }

        if ($newTier !== null && ($currentTier === null || $newTier->getId() !== $currentTier->getId())) {
            $profile = $this->profileRepository->save($profile->withTierUpdated($newTier->getId()));

            Event::dispatch(new TierUpgraded(
                TierUpgraded::fromProfile($profile, $currentTier?->getId(), $newTier)
            ));

            return $newTier;
        }

        return null;
    }

    public function giveBirthdayBonus(int $guestId, string $programId): GuestLoyaltyProfile
    {
        return $this->db->transaction(function () use ($guestId, $programId) {
            $profile = $this->profileRepository->findByGuestAndProgram($guestId, $programId);
            if ($profile === null) {
                throw new DomainException('Guest profile not found');
            }

            $currentYear = (int) (new DateTimeImmutable())->format('Y');
            if (!$profile->isEligibleForBirthdayBonus($currentYear)) {
                throw new DomainException('Guest not eligible for birthday bonus');
            }

            $program = $this->programRepository->findById($programId);
            if ($program === null) {
                throw new DomainException('Loyalty program not found');
            }

            $bonusPoints = Points::fromFloat($program->getBirthdayBonusPoints());
            if ($bonusPoints->getValue() <= 0) {
                throw new DomainException('Birthday bonus not configured');
            }

            $profile = $this->recordTransaction(
                profile: $profile,
                program: $program,
                type: LoyaltyTransactionType::BONUS,
                pointsChange: $bonusPoints,
                description: 'Birthday bonus',
                sourceType: 'birthday',
                sourceId: null
            );

            $profile = $this->profileRepository->save($profile->withBirthdayBonusMarked($currentYear));

            return $profile;
        });
    }

    private function ruleApplies(LoyaltyRule $rule, CalculatePointsDTO $dto): bool
    {
        if (!$rule->appliesToTier($dto->tierSlug ?? '')) {
            return false;
        }

        if ($rule->getType() === LoyaltyRuleType::FIRST_VISIT && !$dto->isFirstVisit) {
            return false;
        }

        if ($rule->getType() === LoyaltyRuleType::BIRTHDAY && !$dto->isBirthday) {
            return false;
        }

        if ($rule->getType() === LoyaltyRuleType::TIME_BASED && $dto->dayOfWeek !== null) {
            if (!$rule->matchesCondition('day_of_week', $dto->dayOfWeek)) {
                return false;
            }
        }

        if ($rule->getType() === LoyaltyRuleType::ITEM_BASED && $dto->itemIds !== null) {
            $ruleItems = $rule->getConditions()['item_ids'] ?? [];
            if (empty(array_intersect($dto->itemIds, $ruleItems))) {
                return false;
            }
        }

        if ($rule->getType() === LoyaltyRuleType::ORDER_BASED) {
            $minAmount = $rule->getConditions()['min_amount'] ?? 0;
            if ($dto->orderAmount->getValue() < $minAmount) {
                return false;
            }
        }

        return true;
    }

    private function calculateRulePoints(LoyaltyRule $rule, CalculatePointsDTO $dto, Points $basePoints): Points
    {
        return match ($rule->getCalculationType()) {
            'fixed' => Points::fromFloat($rule->getPointsValue() ?? 0),
            'percentage' => $basePoints->multiply(($rule->getPointsValue() ?? 0) / 100),
            'multiplier' => $basePoints->multiply($rule->getPointMultiplier() - 1),
            default => Points::zero(),
        };
    }

    private function recordTransaction(
        GuestLoyaltyProfile $profile,
        LoyaltyProgram $program,
        LoyaltyTransactionType $type,
        Points $pointsChange,
        string $description,
        ?string $sourceType,
        ?int $sourceId,
        ?float $orderAmount = null,
        float $pointMultiplier = 1.0
    ): GuestLoyaltyProfile {
        $balanceBefore = $profile->getAvailablePoints();
        $balanceAfter = $balanceBefore->add($pointsChange);

        $expiresAt = null;
        if ($program->doPointsExpire() && $type === LoyaltyTransactionType::EARNED) {
            $expiresAt = (new DateTimeImmutable())
                ->modify("+{$program->getPointsExpirationDays()} days");
        }

        $transaction = LoyaltyTransaction::create(
            uuid: Uuid::uuid4()->toString(),
            tenantId: $profile->getTenantId(),
            businessGroupId: $program->getBusinessGroupId(),
            loyaltyProgramId: $program->getId(),
            guestLoyaltyProfileId: $profile->getId(),
            type: $type,
            pointsChange: $pointsChange,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            sourceType: $sourceType,
            sourceId: $sourceId,
            orderAmount: $orderAmount,
            pointMultiplier: $pointMultiplier,
            description: $description,
            notes: null,
            expiresAt: $expiresAt
        );

        $this->transactionRepository->save($transaction);

        if ($type === LoyaltyTransactionType::EARNED || $type === LoyaltyTransactionType::BONUS) {
            $profile = $profile->withPointsAdded($pointsChange);
            Event::dispatch(new PointsEarned(PointsEarned::fromTransaction($profile, $transaction)));
        }

        return $profile;
    }

    private function generateDescription(
        float $orderAmount,
        float $basePoints,
        float $bonusPoints,
        float $multiplier
    ): string {
        $parts = [];
        $parts[] = sprintf('+%.2f points for %.2f ₽ order', $basePoints * $multiplier, $orderAmount);

        if ($bonusPoints > 0) {
            $parts[] = sprintf('+%.2f bonus points', $bonusPoints * $multiplier);
        }

        if ($multiplier > 1.0) {
            $parts[] = sprintf('(%.1fx multiplier)', $multiplier);
        }

        return implode(' ', $parts);
    }

    public function getProfileBalance(int $guestId, string $programId): ?Points
    {
        $profile = $this->profileRepository->findByGuestAndProgram($guestId, $programId);
        return $profile?->getAvailablePoints();
    }
}
