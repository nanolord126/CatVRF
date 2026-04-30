<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\Bonuses\Domain\Enums\LoyaltyTier;
use DateTimeImmutable;

/**
 * Event LoyaltyLevelChanged
 *
 * Dispatched when a user's loyalty tier changes due to point accumulation or decay.
 * Triggers benefit updates, notifications, and analytics tracking.
 * Used for gamification, retention strategies, and personalized offers.
 */
final class LoyaltyLevelChanged implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $queue = 'bonuses';

    /**
     * @param  string  $ownerId  Entity whose loyalty level changed (user ID).
     * @param  LoyaltyTier  $previousTier  The tier before the change.
     * @param  LoyaltyTier  $newTier  The tier after the change.
     * @param  int  $totalPoints  Total loyalty points after the change.
     * @param  DateTimeImmutable  $changedAt  Timestamp when the tier change occurred.
     * @param  string  $correlationId  Correlation ID for tracing the tier change process.
     * @param  string|null  $reason  Reason for the tier change (points_earned, points_lost, manual_adjustment, etc.).
     */
    public function __construct(
        public readonly string $ownerId,
        public readonly LoyaltyTier $previousTier,
        public readonly LoyaltyTier $newTier,
        public readonly int $totalPoints,
        public readonly DateTimeImmutable $changedAt,
        public readonly string $correlationId,
        public readonly ?string $reason = null
    ) {
        $this->validate();
    }

    /**
     * Validates event data integrity.
     */
    private function validate(): void
    {
        if (empty($this->ownerId)) {
            throw new \InvalidArgumentException('Owner ID cannot be empty');
        }

        if (empty($this->correlationId)) {
            throw new \InvalidArgumentException('Correlation ID cannot be empty');
        }

        if ($this->totalPoints < 0) {
            throw new \InvalidArgumentException('Total points cannot be negative');
        }
    }

    /**
     * Determines if this is a tier upgrade (moving to a higher tier).
     */
    public function isUpgrade(): bool
    {
        return $this->newTier->isHigherThan($this->previousTier);
    }

    /**
     * Determines if this is a tier downgrade (moving to a lower tier).
     */
    public function isDowngrade(): bool
    {
        return $this->newTier->isLowerThan($this->previousTier);
    }

    /**
     * Determines if this tier change is significant (skipped one or more tiers).
     */
    public function isSignificantChange(): bool
    {
        $ordered = LoyaltyTier::getOrderedTiers();
        $previousIndex = array_search($this->previousTier, $ordered, true);
        $newIndex = array_search($this->newTier, $ordered, true);
        
        return abs($newIndex - $previousIndex) > 1;
    }

    /**
     * Returns the discount percentage change.
     * Positive for upgrades, negative for downgrades.
     */
    public function getDiscountChange(): int
    {
        return $this->newTier->getDiscountPercentage() - $this->previousTier->getDiscountPercentage();
    }

    /**
     * Returns the channel for broadcasting to the specific user.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->ownerId)];
    }

    /**
     * Returns the event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'loyalty.level_changed';
    }

    /**
     * Returns the data to be broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'previous_tier' => $this->previousTier->value,
            'new_tier' => $this->newTier->value,
            'previous_discount' => $this->previousTier->getDiscountPercentage(),
            'new_discount' => $this->newTier->getDiscountPercentage(),
            'total_points' => $this->totalPoints,
            'is_upgrade' => $this->isUpgrade(),
            'is_downgrade' => $this->isDowngrade(),
            'discount_change' => $this->getDiscountChange(),
            'is_significant' => $this->isSignificantChange(),
            'changed_at' => $this->changedAt->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
        ];
    }

    /**
     * Converts event to array for logging and analytics.
     */
    public function toArray(): array
    {
        return [
            'owner_id' => $this->ownerId,
            'previous_tier' => $this->previousTier->value,
            'new_tier' => $this->newTier->value,
            'total_points' => $this->totalPoints,
            'is_upgrade' => $this->isUpgrade(),
            'is_downgrade' => $this->isDowngrade(),
            'discount_change' => $this->getDiscountChange(),
            'is_significant' => $this->isSignificantChange(),
            'changed_at' => $this->changedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'reason' => $this->reason,
        ];
    }
}
