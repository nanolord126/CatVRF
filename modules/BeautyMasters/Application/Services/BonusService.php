<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Services;

use Modules\BeautyMasters\Domain\Entities\LoyaltyProfile;
use Modules\BeautyMasters\Domain\Entities\LoyaltyTier;
use Modules\BeautyMasters\Domain\Repositories\LoyaltyProfileRepositoryInterface;
use Modules\BeautyMasters\Domain\DTOs\LoyaltyTransactionDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

final readonly class BonusService
{
    use WithAuditLogging;

    private const POINTS_PER_RUBLE = 1;
    private const BONUS_MULTIPLIER = 0.01; // 1% of spent amount

    public function __construct(
        private LoyaltyProfileRepositoryInterface $bonusProfileRepository,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getUserBonusProfile(int $userId, int $venueId): LoyaltyProfile
    {
        $cacheKey = "beauty:bonus_profile:{$userId}:{$venueId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($userId, $venueId) {
            $profile = $this->bonusProfileRepository->findByClientAndVenue($userId, $venueId);

            if (!$profile) {
                return $this->createBonusProfile($userId, $venueId);
            }

            return $profile;
        });
    }

    public function addBonusPoints(
        int $userId,
        int $venueId,
        int $appointmentId,
        float $spentAmount
    ): LoyaltyProfile {
        return DB::transaction(function () use ($userId, $venueId, $appointmentId, $spentAmount) {
            $profile = $this->getUserBonusProfile($userId, $venueId);
            $tierMultiplier = LoyaltyTier::getMultiplier($profile->tier);

            $points = (int) ($spentAmount * self::BONUS_MULTIPLIER * self::POINTS_PER_RUBLE * $tierMultiplier);

            $newBalance = $profile->pointsBalance + $points;
            $newEarned = $profile->pointsEarned + $points;
            $newTotalSpent = $profile->totalSpent + $spentAmount;
            $newTotalVisits = $profile->totalVisits + 1;

            $newTier = $this->calculateTier($newTotalSpent);

            $this->bonusProfileRepository->updateBalance($profile->id, $newBalance);

            if ($newTier !== $profile->tier) {
                $this->bonusProfileRepository->updateTier($profile->id, $newTier);
            }

            $this->createTransaction(
                $profile->id,
                $appointmentId,
                'earned',
                $points,
                "Начисление бонусов за визит",
                [
                    'spent_amount' => $spentAmount,
                    'tier_multiplier' => $tierMultiplier,
                ]
            );

            $this->invalidateUserBonusCache($userId, $venueId);

            $this->logger->info('Bonus points added', [
                'user_id' => $userId,
                'venue_id' => $venueId,
                'appointment_id' => $appointmentId,
                'points' => $points,
                'tier' => $newTier,
            ]);

            $this->logCreated('LoyaltyTransaction', $profile->id, [
                'user_id' => $userId,
                'points' => $points,
                'appointment_id' => $appointmentId,
                'spent_amount' => $spentAmount,
            ], $userId, $venueId);

            return $this->bonusProfileRepository->findById($profile->id);
        });
    }

    public function redeemBonusPoints(
        int $userId,
        int $venueId,
        int $points,
        ?int $appointmentId = null,
        string $description = 'Списание бонусов'
    ): LoyaltyProfile {
        return DB::transaction(function () use ($userId, $venueId, $points, $appointmentId, $description) {
            $profile = $this->getUserBonusProfile($userId, $venueId);

            if (!$profile->canRedeemPoints($points)) {
                throw new \RuntimeException('Insufficient bonus points');
            }

            $newBalance = $profile->pointsBalance - $points;
            $newRedeemed = $profile->pointsRedeemed + $points;

            $this->bonusProfileRepository->updateBalance($profile->id, $newBalance);

            $this->createTransaction(
                $profile->id,
                $appointmentId,
                'redeemed',
                -$points,
                $description,
                null
            );

            $this->invalidateUserBonusCache($userId, $venueId);

            $this->logger->info('Bonus points redeemed', [
                'user_id' => $userId,
                'venue_id' => $venueId,
                'points' => $points,
                'appointment_id' => $appointmentId,
            ]);

            $this->logAction('bonus_points_redeemed', 'LoyaltyProfile', $profile->id, [
                'user_id' => $userId,
                'points' => $points,
                'appointment_id' => $appointmentId,
            ], $userId, $venueId);

            return $this->bonusProfileRepository->findById($profile->id);
        });
    }

    public function adjustBonusPoints(
        int $userId,
        int $venueId,
        int $points,
        string $description,
        ?array $metadata = null
    ): LoyaltyProfile {
        return DB::transaction(function () use ($userId, $venueId, $points, $description, $metadata) {
            $profile = $this->getUserBonusProfile($userId, $venueId);

            $newBalance = $profile->pointsBalance + $points;

            if ($newBalance < 0) {
                throw new \RuntimeException('Cannot adjust to negative balance');
            }

            $this->bonusProfileRepository->updateBalance($profile->id, $newBalance);

            $this->createTransaction(
                $profile->id,
                null,
                'adjusted',
                $points,
                $description,
                $metadata
            );

            $this->invalidateUserBonusCache($userId, $venueId);

            return $this->bonusProfileRepository->findById($profile->id);
        });
    }

    public function getBonusConversionRate(int $userId, int $venueId): float
    {
        $profile = $this->getUserBonusProfile($userId, $venueId);
        $tierMultiplier = LoyaltyTier::getMultiplier($profile->tier);

        return self::BONUS_MULTIPLIER * self::POINTS_PER_RUBLE * $tierMultiplier;
    }

    public function calculateDiscountFromPoints(int $userId, int $venueId, int $points): float
    {
        $profile = $this->getUserBonusProfile($userId, $venueId);

        if (!$profile->canRedeemPoints($points)) {
            throw new \RuntimeException('Insufficient bonus points');
        }

        $tierMultiplier = LoyaltyTier::getMultiplier($profile->tier);
        $discount = $points / (self::POINTS_PER_RUBLE * self::BONUS_MULTIPLIER * $tierMultiplier);

        return round($discount, 2);
    }

    public function upgradeUserTier(int $userId, int $venueId): ?string
    {
        $profile = $this->getUserBonusProfile($userId, $venueId);
        $newTier = $this->calculateTier($profile->totalSpent);

        if ($newTier !== $profile->tier) {
            $this->bonusProfileRepository->updateTier($profile->id, $newTier);
            $this->invalidateUserBonusCache($userId, $venueId);

            $this->logger->info('User tier upgraded', [
                'user_id' => $userId,
                'venue_id' => $venueId,
                'old_tier' => $profile->tier,
                'new_tier' => $newTier,
            ]);

            return $newTier;
        }

        return null;
    }

    public function getTopUsersByBonuses(int $venueId, int $limit = 10): array
    {
        return $this->bonusProfileRepository
            ->findByVenueId($venueId)
            ->sortByDesc(fn($profile) => $profile->pointsBalance)
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function getUsersByTier(int $venueId, string $tier): array
    {
        return $this->bonusProfileRepository
            ->findByTier($venueId, $tier)
            ->toArray();
    }

    private function createBonusProfile(int $userId, int $venueId): LoyaltyProfile
    {
        $profile = new LoyaltyProfile(
            id: 0,
            clientId: $userId,
            venueId: $venueId,
            pointsBalance: 0,
            pointsEarned: 0,
            pointsRedeemed: 0,
            tier: LoyaltyTier::BRONZE,
            totalSpent: 0.0,
            totalVisits: 0,
            tierUpdatedAt: null,
            lastActivityAt: new \DateTimeImmutable(),
            birthdayGiftSentYear: null,
            preferences: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
        );

        return $this->bonusProfileRepository->save($profile);
    }

    private function calculateTier(float $totalSpent): string
    {
        if ($totalSpent >= LoyaltyTier::getMinSpentForTier(LoyaltyTier::PLATINUM)) {
            return LoyaltyTier::PLATINUM;
        }

        if ($totalSpent >= LoyaltyTier::getMinSpentForTier(LoyaltyTier::GOLD)) {
            return LoyaltyTier::GOLD;
        }

        if ($totalSpent >= LoyaltyTier::getMinSpentForTier(LoyaltyTier::SILVER)) {
            return LoyaltyTier::SILVER;
        }

        return LoyaltyTier::BRONZE;
    }

    private function createTransaction(
        int $profileId,
        ?int $appointmentId,
        string $type,
        int $points,
        string $description,
        ?array $metadata
    ): void {
        $transaction = new LoyaltyTransactionDTO(
            loyaltyProfileId: $profileId,
            appointmentId: $appointmentId,
            type: $type,
            points: $points,
            description: $description,
            metadata: $metadata,
        );

        DB::table('beauty_loyalty_transactions')->insert($transaction->toArray());
    }

    private function invalidateUserBonusCache(int $userId, int $venueId): void
    {
        $cacheKey = "beauty:bonus_profile:{$userId}:{$venueId}";
        Cache::forget($cacheKey);
    }
}
