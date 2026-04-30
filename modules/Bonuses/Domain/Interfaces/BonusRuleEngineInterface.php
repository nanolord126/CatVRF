<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Interfaces;

use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Interface BonusRuleEngineInterface
 *
 * Defines the contract for evaluating bonus awarding rules and business constraints.
 * Implementations should check eligibility, calculate amounts, and enforce limits.
 * All rule evaluations must be deterministic and auditable for compliance.
 */
interface BonusRuleEngineInterface
{
    /**
     * Determines if a bonus can be awarded based on type, amount, and context.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus to award.
     * @param  BonusAmount  $amount  The amount of the bonus.
     * @param  array  $context  Additional context for rule evaluation (source, vertical, etc.).
     * @return bool True if the bonus can be awarded, false otherwise.
     */
    public function canAward(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): bool;

    /**
     * Calculates the actual bonus amount after applying rules and multipliers.
     * May adjust the requested amount based on tier multipliers, caps, or other rules.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus to award.
     * @param  BonusAmount  $requestedAmount  The requested bonus amount.
     * @param  array  $context  Additional context for calculation.
     * @return BonusAmount The calculated bonus amount after rule application.
     */
    public function calculateAmount(string $ownerId, BonusType $type, BonusAmount $requestedAmount, array $context = []): BonusAmount;

    /**
     * Determines the expiration date for a bonus based on type and context.
     * Applies default expiration periods or calculates custom dates based on rules.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus.
     * @param  array  $context  Additional context for expiration calculation.
     * @return \DateTimeImmutable|null The expiration date, or null if the bonus never expires.
     */
    public function calculateExpiration(string $ownerId, BonusType $type, array $context = []): ?\DateTimeImmutable;

    /**
     * Checks if the owner has reached the bonus cap for the given type and period.
     *
     * @param  string  $ownerId  The entity to check.
     * @param  BonusType  $type  The bonus type to check caps for.
     * @param  string  $period  The period to check (daily, weekly, monthly, yearly, lifetime).
     * @param  int  $additionalAmount  Additional amount to add to current total for checking.
     * @return bool True if the cap has been reached, false otherwise.
     */
    public function hasReachedCap(string $ownerId, BonusType $type, string $period, int $additionalAmount = 0): bool;

    /**
     * Returns the remaining bonus capacity for the owner, type, and period.
     *
     * @param  string  $ownerId  The entity to check.
     * @param  BonusType  $type  The bonus type to check capacity for.
     * @param  string  $period  The period to check (daily, weekly, monthly, yearly, lifetime).
     * @return int The remaining capacity in smallest currency unit.
     */
    public function getRemainingCapacity(string $ownerId, BonusType $type, string $period): int;

    /**
     * Validates that all required conditions are met for the bonus award.
     * Throws exceptions with specific error messages for validation failures.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus.
     * @param  BonusAmount  $amount  The bonus amount.
     * @param  array  $context  Additional context for validation.
     * @throws \InvalidArgumentException When validation fails.
     */
    public function validateAward(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): void;

    /**
     * Applies tier-based multipliers to the bonus amount.
     * Higher loyalty tiers may receive bonus multipliers on certain bonus types.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus.
     * @param  BonusAmount  $baseAmount  The base amount before multipliers.
     * @return BonusAmount The amount after applying tier multipliers.
     */
    public function applyTierMultiplier(string $ownerId, BonusType $type, BonusAmount $baseAmount): BonusAmount;

    /**
     * Checks if the bonus type requires additional verification before awarding.
     * For example, referral bonuses may require referral completion validation.
     *
     * @param  BonusType  $type  The bonus type to check.
     * @param  array  $context  Context data for verification requirements.
     * @return bool True if verification is required, false otherwise.
     */
    public function requiresVerification(BonusType $type, array $context = []): bool;

    /**
     * Performs the verification check for bonuses that require it.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The bonus type requiring verification.
     * @param  array  $context  Context data for verification (e.g., referral ID, order ID).
     * @return bool True if verification passes, false otherwise.
     */
    public function performVerification(string $ownerId, BonusType $type, array $context = []): bool;

    /**
     * Returns a detailed explanation of why a bonus award would be denied.
     * Useful for user-facing error messages and debugging.
     *
     * @param  string  $ownerId  The entity to receive the bonus.
     * @param  BonusType  $type  The type of bonus.
     * @param  BonusAmount  $amount  The bonus amount.
     * @param  array  $context  Additional context for explanation generation.
     * @return array Array of denial reasons with human-readable messages.
     */
    public function getDenialReasons(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): array;

    /**
     * Checks if the bonus type is restricted to specific verticals or services.
     *
     * @param  BonusType  $type  The bonus type to check.
     * @param  string|null  $vertical  The vertical to check against.
     * @return bool True if the bonus is restricted and the vertical doesn't match, false otherwise.
     */
    public function isVerticalRestricted(BonusType $type, ?string $vertical): bool;

    /**
     * Returns the list of allowed verticals for a given bonus type.
     * Returns empty array if the bonus type is universal (can be used in any vertical).
     *
     * @param  BonusType  $type  The bonus type to check.
     * @return array Array of allowed vertical identifiers.
     */
    public function getAllowedVerticals(BonusType $type): array;
}
