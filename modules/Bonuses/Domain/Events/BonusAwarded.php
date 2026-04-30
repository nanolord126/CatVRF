<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;
use DateTimeImmutable;

/**
 * Event BonusAwarded
 *
 * Dispatched when a bonus is successfully awarded to a user.
 * Triggers wallet integration, loyalty point calculations, and notifications.
 * Used for analytics, audit trails, and real-time user notifications.
 */
final class BonusAwarded implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $queue = 'bonuses';

    /**
     * @param  string  $bonusId  Unique identifier of the awarded bonus aggregate.
     * @param  string  $ownerId  Entity that received the bonus (user ID).
     * @param  BonusAmount  $amount  Amount that was awarded.
     * @param  BonusType  $type  Type of bonus that was awarded.
     * @param  string  $correlationId  Correlation ID for tracing the entire award flow.
     * @param  DateTimeImmutable|null  $expiresAt  Optional expiration timestamp for the bonus.
     * @param  string|null  $sourceId  Optional source entity ID that triggered the award.
     * @param  string|null  $sourceType  Optional source entity type (referral, order, etc.).
     * @param  array  $metadata  Additional metadata for analytics and tracking.
     */
    public function __construct(
        public readonly string $bonusId,
        public readonly string $ownerId,
        public readonly BonusAmount $amount,
        public readonly BonusType $type,
        public readonly string $correlationId,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly ?string $sourceId = null,
        public readonly ?string $sourceType = null,
        public readonly array $metadata = []
    ) {
        $this->validate();
    }

    /**
     * Validates event data integrity.
     */
    private function validate(): void
    {
        if (empty($this->bonusId)) {
            throw new \InvalidArgumentException('Bonus ID cannot be empty');
        }

        if (empty($this->ownerId)) {
            throw new \InvalidArgumentException('Owner ID cannot be empty');
        }

        if (empty($this->correlationId)) {
            throw new \InvalidArgumentException('Correlation ID cannot be empty');
        }

        if ($this->amount->getAmount() <= 0) {
            throw new \InvalidArgumentException('Awarded amount must be positive');
        }
    }

    /**
     * Determines if this is a high-value bonus award that warrants special notification.
     */
    public function isHighValueAward(): bool
    {
        return $this->amount->getAmount() >= 100000; // 1,000.00
    }

    /**
     * Determines if this bonus has an expiration date.
     */
    public function hasExpiration(): bool
    {
        return $this->expiresAt !== null;
    }

    /**
     * Calculates days until expiration.
     * Returns null if bonus doesn't expire.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $interval = $now->diff($this->expiresAt);
        
        return $interval->days;
    }

    /**
     * Determines if this bonus will expire soon (within 7 days).
     */
    public function expiresSoon(): bool
    {
        $days = $this->getDaysUntilExpiration();
        
        return $days !== null && $days <= 7;
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
        return 'bonus.awarded';
    }

    /**
     * Returns the data to be broadcast.
     * Excludes sensitive information from the broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'amount' => $this->amount->getAmount(),
            'type' => $this->type->value,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'expires_soon' => $this->expiresSoon(),
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Converts event to array for logging and analytics.
     */
    public function toArray(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'owner_id' => $this->ownerId,
            'amount' => $this->amount->getAmount(),
            'type' => $this->type->value,
            'correlation_id' => $this->correlationId,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'metadata' => $this->metadata,
            'is_high_value' => $this->isHighValueAward(),
            'expires_soon' => $this->expiresSoon(),
        ];
    }
}
