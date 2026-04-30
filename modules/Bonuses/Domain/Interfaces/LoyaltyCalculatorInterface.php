<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Interfaces;

use Modules\Bonuses\Domain\Enums\LoyaltyTier;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Interface LoyaltyCalculatorInterface
 *
 * Defines the contract for loyalty point calculations and tier progression.
 * Handles point accrual, tier transitions, and benefit calculations.
 * All calculations must be deterministic and auditable for compliance.
 */
interface LoyaltyCalculatorInterface
{
    /**
     * Calculates loyalty points earned from a transaction or activity.
     *
     * @param  string  $ownerId  The entity earning points.
     * @param  float  $transactionAmount  The transaction amount in base currency.
     * @param  string  $activityType  The type of activity (purchase, review, referral, etc.).
     * @param  array  $context  Additional context for point calculation (vertical, multiplier, etc.).
     * @return int The calculated loyalty points.
     */
    public function calculatePointsEarned(string $ownerId, float $transactionAmount, string $activityType, array $context = []): int;

    /**
     * Calculates the loyalty tier based on total accumulated points.
     *
     * @param  int  $totalPoints  The total loyalty points.
     * @return LoyaltyTier The calculated loyalty tier.
     */
    public function calculateTier(int $totalPoints): LoyaltyTier;

    /**
     * Calculates the new loyalty status after adding points.
     *
     * @param  string  $ownerId  The entity receiving points.
     * @param  LoyaltyStatus  $currentStatus  The current loyalty status.
     * @param  int  $pointsToAdd  The points to add.
     * @return LoyaltyStatus The new loyalty status.
     */
    public function calculateNewStatus(string $ownerId, LoyaltyStatus $currentStatus, int $pointsToAdd): LoyaltyStatus;

    /**
     * Calculates points needed to reach the next loyalty tier.
     *
     * @param  LoyaltyStatus  $currentStatus  The current loyalty status.
     * @return int The points needed to reach the next tier, or 0 if at maximum tier.
     */
    public function calculatePointsToNextTier(LoyaltyStatus $currentStatus): int;

    /**
     * Calculates the discount percentage for the given loyalty status.
     *
     * @param  LoyaltyStatus  $status  The loyalty status.
     * @param  string|null  $vertical  Optional vertical for vertical-specific discounts.
     * @return float The discount percentage (0-100).
     */
    public function calculateDiscountPercentage(LoyaltyStatus $status, ?string $vertical = null): float;

    /**
     * Calculates the bonus amount after applying loyalty tier multipliers.
     *
     * @param  string  $ownerId  The entity receiving the bonus.
     * @param  BonusAmount  $baseAmount  The base bonus amount.
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @return BonusAmount The bonus amount after multiplier application.
     */
    public function applyBonusMultiplier(string $ownerId, BonusAmount $baseAmount, LoyaltyStatus $status): BonusAmount;

    /**
     * Checks if points should decay based on inactivity rules.
     * Some loyalty programs reduce points after periods of inactivity.
     *
     * @param  string  $ownerId  The entity to check.
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @return int The points to decay, or 0 if no decay applies.
     */
    public function calculatePointDecay(string $ownerId, LoyaltyStatus $status): int;

    /**
     * Calculates the bonus expiration period based on loyalty tier.
     * Higher tiers may have longer bonus expiration periods.
     *
     * @param  LoyaltyStatus  $status  The loyalty status.
     * @return int The number of days until bonus expiration.
     */
    public function calculateBonusExpirationDays(LoyaltyStatus $status): int;

    /**
     * Calculates the monthly bonus cap for the loyalty tier.
     *
     * @param  LoyaltyStatus  $status  The loyalty status.
     * @return int The monthly bonus cap in smallest currency unit.
     */
    public function calculateMonthlyBonusCap(LoyaltyStatus $status): int;

    /**
     * Determines if a tier change event should be triggered.
     * Compares current status with previous status to detect tier changes.
     *
     * @param  LoyaltyStatus  $currentStatus  The current loyalty status.
     * @param  LoyaltyStatus  $previousStatus  The previous loyalty status.
     * @return bool True if tier has changed, false otherwise.
     */
    public function hasTierChanged(LoyaltyStatus $currentStatus, LoyaltyStatus $previousStatus): bool;

    /**
     * Calculates loyalty points earned from a bonus award.
     * Bonuses may also contribute to loyalty point accumulation.
     *
     * @param  string  $ownerId  The entity receiving the bonus.
     * @param  BonusAmount  $bonusAmount  The bonus amount awarded.
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @return int The loyalty points earned from the bonus.
     */
    public function calculatePointsFromBonus(string $ownerId, BonusAmount $bonusAmount, LoyaltyStatus $status): int;

    /**
     * Calculates the effective bonus amount considering loyalty benefits.
     * May include tier multipliers, special promotions, or other benefits.
     *
     * @param  string  $ownerId  The entity receiving the bonus.
     * @param  BonusAmount  $requestedAmount  The requested bonus amount.
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @param  array  $context  Additional context for calculation.
     * @return BonusAmount The effective bonus amount after all loyalty benefits.
     */
    public function calculateEffectiveBonus(string $ownerId, BonusAmount $requestedAmount, LoyaltyStatus $status, array $context = []): BonusAmount;

    /**
     * Checks if the owner is eligible for a specific loyalty benefit.
     *
     * @param  string  $ownerId  The entity to check.
     * @param  string  $benefit  The benefit to check (priority_support, exclusive_promotions, etc.).
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @return bool True if eligible, false otherwise.
     */
    public function isEligibleForBenefit(string $ownerId, string $benefit, LoyaltyStatus $status): bool;

    /**
     * Returns a summary of loyalty benefits for the given status.
     *
     * @param  string  $ownerId  The entity.
     * @param  LoyaltyStatus  $status  The loyalty status.
     * @return array Array of available benefits with descriptions.
     */
    public function getBenefitsSummary(string $ownerId, LoyaltyStatus $status): array;

    /**
     * Calculates the loyalty tier progression trajectory.
     * Projects future tier based on current point accrual rate.
     *
     * @param  string  $ownerId  The entity.
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @param  float  $monthlyPointRate  The estimated monthly point accrual rate.
     * @return array Array with projected tier and months to reach each tier.
     */
    public function calculateTierTrajectory(string $ownerId, LoyaltyStatus $status, float $monthlyPointRate): array;

    /**
     * Resets monthly tracking for the loyalty status.
     * Called at the start of a new billing month.
     *
     * @param  LoyaltyStatus  $status  The current loyalty status.
     * @return LoyaltyStatus The status with monthly tracking reset.
     */
    public function resetMonthlyTracking(LoyaltyStatus $status): LoyaltyStatus;
}
