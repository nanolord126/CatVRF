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
 * Event BonusExpired
 *
 * Dispatched when a bonus reaches its expiration date and is marked as expired.
 * Triggered by scheduled jobs that scan for expiring bonuses.
 * Used for notifications, analytics, and cleanup operations.
 */
final class BonusExpired implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $queue = 'bonuses';

    /**
     * @param  string  $bonusId  Unique identifier of the expired bonus aggregate.
     * @param  string  $ownerId  Entity that owned the bonus (user ID).
     * @param  BonusAmount  $expiredAmount  Amount that was lost due to expiration.
     * @param  BonusType  $type  Type of bonus that expired.
     * @param  DateTimeImmutable  $expiredAt  Timestamp when the bonus actually expired.
     * @param  string  $correlationId  Correlation ID for tracing the expiration process.
     */
    public function __construct(
        public readonly string $bonusId,
        public readonly string $ownerId,
        public readonly BonusAmount $expiredAmount,
        public readonly BonusType $type,
        public readonly DateTimeImmutable $expiredAt,
        public readonly string $correlationId
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

        if ($this->expiredAmount->getAmount() <= 0) {
            throw new \InvalidArgumentException('Expired amount must be positive');
        }
    }

    /**
     * Determines if this is a high-value bonus expiration that warrants special notification.
     */
    public function isHighValueExpiration(): bool
    {
        return $this->expiredAmount->getAmount() >= 100000; // 1,000.00
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
        return 'bonus.expired';
    }

    /**
     * Returns the data to be broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'expired_amount' => $this->expiredAmount->getAmount(),
            'type' => $this->type->value,
            'expired_at' => $this->expiredAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'is_high_value' => $this->isHighValueExpiration(),
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
            'expired_amount' => $this->expiredAmount->getAmount(),
            'type' => $this->type->value,
            'expired_at' => $this->expiredAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'is_high_value' => $this->isHighValueExpiration(),
        ];
    }
}
