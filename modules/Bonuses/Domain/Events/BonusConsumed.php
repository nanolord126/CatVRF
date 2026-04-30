<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Event BonusConsumed
 *
 * Dispatched when a bonus is successfully consumed (partially or fully).
 * Triggers wallet updates, loyalty point calculations, and notifications.
 * Used for analytics, audit trails, and real-time user notifications.
 */
final class BonusConsumed implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $queue = 'bonuses';

    /**
     * @param  string  $bonusId  Unique identifier of the consumed bonus aggregate.
     * @param  string  $ownerId  Entity that owns the bonus (user ID).
     * @param  BonusAmount  $consumedAmount  Amount that was consumed from this bonus.
     * @param  BonusAmount  $remainingAmount  Amount remaining in the bonus after consumption.
     * @param  BonusType  $type  Type of bonus that was consumed.
     * @param  string  $correlationId  Correlation ID for tracing the entire consumption flow.
     * @param  string|null  $transactionId  External transaction ID if consumption was part of a payment.
     * @param  string|null  $vertical  Vertical/service where the bonus was consumed.
     */
    public function __construct(
        public readonly string $bonusId,
        public readonly string $ownerId,
        public readonly BonusAmount $consumedAmount,
        public readonly BonusAmount $remainingAmount,
        public readonly BonusType $type,
        public readonly string $correlationId,
        public readonly ?string $transactionId = null,
        public readonly ?string $vertical = null
    ) {
        $this->validate();
    }

    /**
     * Validates event data integrity.
     * Ensures all required fields are present and logically consistent.
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

        if ($this->consumedAmount->getAmount() <= 0) {
            throw new \InvalidArgumentException('Consumed amount must be positive');
        }

        if ($this->remainingAmount->getAmount() < 0) {
            throw new \InvalidArgumentException('Remaining amount cannot be negative');
        }
    }

    /**
     * Determines if the bonus was fully consumed.
     */
    public function isFullyConsumed(): bool
    {
        return $this->remainingAmount->isZero();
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
        return 'bonus.consumed';
    }

    /**
     * Returns the data to be broadcast.
     * Excludes sensitive information from the broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'consumed_amount' => $this->consumedAmount->getAmount(),
            'remaining_amount' => $this->remainingAmount->getAmount(),
            'type' => $this->type->value,
            'is_fully_consumed' => $this->isFullyConsumed(),
            'correlation_id' => $this->correlationId,
            'vertical' => $this->vertical,
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
            'consumed_amount' => $this->consumedAmount->getAmount(),
            'remaining_amount' => $this->remainingAmount->getAmount(),
            'type' => $this->type->value,
            'correlation_id' => $this->correlationId,
            'transaction_id' => $this->transactionId,
            'vertical' => $this->vertical,
            'is_fully_consumed' => $this->isFullyConsumed(),
        ];
    }
}
