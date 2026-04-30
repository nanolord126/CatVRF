<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

use InvalidArgumentException;

/**
 * Enum BonusStatus
 *
 * Represents the lifecycle states of a bonus aggregate from creation to final consumption or expiration.
 * Each state has specific business rules governing allowed transitions and operations.
 * Status transitions are strictly enforced to maintain data integrity and audit trails.
 */
enum BonusStatus: string
{
    /**
     * Bonus has been awarded and is available for consumption.
     * This is the initial state after successful bonus creation and persistence.
     * Bonuses in this state can be consumed according to type-specific rules.
     */
    case ACTIVE = 'active';

    /**
     * Bonus has been fully consumed and is no longer available.
     * This is a terminal state - bonuses cannot transition out of CONSUMED.
     * Fully consumed bonuses are retained for audit and analytics purposes.
     */
    case CONSUMED = 'consumed';

    /**
     * Bonus has expired due to passing its expiration date.
     * This is a terminal state - expired bonuses cannot be reactivated.
     * Expired bonuses are automatically marked by scheduled jobs.
     */
    case EXPIRED = 'expired';

    /**
     * Bonus has been cancelled by an administrator or automated fraud detection.
     * This is a terminal state used for fraud mitigation or manual corrections.
     * Cancelled bonuses require audit trail justification.
     */
    case CANCELLED = 'cancelled';

    /**
     * Bonus is temporarily frozen pending verification or investigation.
     * This is an intermediate state for fraud checks or manual review.
     * Frozen bonuses cannot be consumed until status changes.
     */
    case FROZEN = 'frozen';

    /**
     * Bonus is being processed in a transaction and temporarily locked.
     * This is a transient state during consumption operations.
     * Processing bonuses are automatically released back to ACTIVE or transitioned to CONSUMED.
     */
    case PROCESSING = 'processing';

    /**
     * Verification wrapper strictly enforcing valid status transitions.
     *
     * @param  string  $value  The status value to validate.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    /**
     * Transforms string value to enum with proper error handling.
     *
     * @param  string  $value  The status value to convert.
     * @throws InvalidArgumentException When value is invalid.
     */
    public static function fromString(string $value): self
    {
        $status = self::tryFrom($value);
        
        if ($status === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid bonus status: %s. Valid statuses are: %s', 
                    $value,
                    implode(', ', array_column(self::cases(), 'value'))
                )
            );
        }
        
        return $status;
    }

    /**
     * Determines if this status allows bonus consumption.
     * Only ACTIVE bonuses can be consumed (excluding those in PROCESSING state).
     */
    public function allowsConsumption(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Determines if this is a terminal state (no further transitions allowed).
     * Terminal states are CONSUMED, EXPIRED, and CANCELLED.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::CONSUMED, self::EXPIRED, self::CANCELLED => true,
            self::ACTIVE, self::FROZEN, self::PROCESSING => false,
        };
    }

    /**
     * Determines if this status can transition to ACTIVE.
     * Used for unfreezing bonuses or completing processing.
     */
    public function canTransitionToActive(): bool
    {
        return match ($this) {
            self::FROZEN, self::PROCESSING => true,
            self::ACTIVE, self::CONSUMED, self::EXPIRED, self::CANCELLED => false,
        };
    }

    /**
     * Determines if this status can transition to CONSUMED.
     * Only ACTIVE bonuses can be consumed.
     */
    public function canTransitionToConsumed(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Determines if this status can transition to EXPIRED.
     * Only ACTIVE and FROZEN bonuses can expire.
     */
    public function canTransitionToExpired(): bool
    {
        return match ($this) {
            self::ACTIVE, self::FROZEN => true,
            self::CONSUMED, self::EXPIRED, self::CANCELLED, self::PROCESSING => false,
        };
    }

    /**
     * Determines if this status can transition to CANCELLED.
     * Non-terminal states can be cancelled for fraud or manual corrections.
     */
    public function canTransitionToCancelled(): bool
    {
        return match ($this) {
            self::ACTIVE, self::FROZEN => true,
            self::CONSUMED, self::EXPIRED, self::CANCELLED, self::PROCESSING => false,
        };
    }

    /**
     * Determines if this status can transition to FROZEN.
     * Used for fraud detection or manual review.
     */
    public function canTransitionToFrozen(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::CONSUMED, self::EXPIRED, self::CANCELLED, self::FROZEN, self::PROCESSING => false,
        };
    }

    /**
     * Determines if this status can transition to PROCESSING.
     * Used during consumption transactions to prevent race conditions.
     */
    public function canTransitionToProcessing(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Returns the human-readable label for this status.
     * Used in UI displays and audit logs.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CONSUMED => 'Consumed',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::FROZEN => 'Frozen',
            self::PROCESSING => 'Processing',
        };
    }

    /**
     * Returns array of all valid status values for validation.
     */
    public static function getValidValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns statuses that should be excluded from balance calculations.
     * Terminal states and processing bonuses don't contribute to available balance.
     */
    public function isExcludedFromBalance(): bool
    {
        return match ($this) {
            self::CONSUMED, self::EXPIRED, self::CANCELLED, self::PROCESSING => true,
            self::ACTIVE, self::FROZEN => false,
        };
    }

    /**
     * Determines if this status requires admin intervention to change.
     * Frozen and Cancelled statuses typically require manual review.
     */
    public function requiresAdminIntervention(): bool
    {
        return match ($this) {
            self::FROZEN, self::CANCELLED => true,
            self::ACTIVE, self::CONSUMED, self::EXPIRED, self::PROCESSING => false,
        };
    }
}
