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
 * Event BonusFrozen
 *
 * Dispatched when a bonus is frozen due to fraud detection, manual review, or investigation.
 * Prevents consumption while investigation is ongoing.
 * Used for fraud mitigation, compliance, and audit trails.
 */
final class BonusFrozen implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $queue = 'bonuses';

    /**
     * @param  string  $bonusId  Unique identifier of the frozen bonus aggregate.
     * @param  string  $ownerId  Entity that owns the bonus (user ID).
     * @param  BonusAmount  $frozenAmount  Amount that is currently frozen.
     * @param  BonusType  $type  Type of bonus that was frozen.
     * @param  DateTimeImmutable  $frozenAt  Timestamp when the bonus was frozen.
     * @param  string  $correlationId  Correlation ID for tracing the freeze process.
     * @param  string|null  $reason  Reason for freezing (fraud_suspicion, manual_review, compliance_check, etc.).
     * @param  string|null  $frozenBy  User ID or system component that initiated the freeze.
     */
    public function __construct(
        public readonly string $bonusId,
        public readonly string $ownerId,
        public readonly BonusAmount $frozenAmount,
        public readonly BonusType $type,
        public readonly DateTimeImmutable $frozenAt,
        public readonly string $correlationId,
        public readonly ?string $reason = null,
        public readonly ?string $frozenBy = null
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

        if ($this->frozenAmount->getAmount() <= 0) {
            throw new \InvalidArgumentException('Frozen amount must be positive');
        }
    }

    /**
     * Determines if this freeze was initiated by automated fraud detection.
     */
    public function isAutomatedFraudDetection(): bool
    {
        return $this->frozenBy === 'fraud_detection_system' || $this->reason === 'fraud_suspicion';
    }

    /**
     * Determines if this freeze was initiated manually by an administrator.
     */
    public function isManualFreeze(): bool
    {
        return $this->frozenBy !== null && $this->frozenBy !== 'fraud_detection_system';
    }

    /**
     * Determines if this is a compliance-related freeze.
     */
    public function isComplianceFreeze(): bool
    {
        return $this->reason === 'compliance_check' || $this->reason === 'legal_review';
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
        return 'bonus.frozen';
    }

    /**
     * Returns the data to be broadcast.
     * Excludes sensitive information from the broadcast payload.
     */
    public function broadcastWith(): array
    {
        return [
            'bonus_id' => $this->bonusId,
            'frozen_amount' => $this->frozenAmount->getAmount(),
            'type' => $this->type->value,
            'frozen_at' => $this->frozenAt->format('Y-m-d H:i:s'),
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
            'frozen_amount' => $this->frozenAmount->getAmount(),
            'type' => $this->type->value,
            'frozen_at' => $this->frozenAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'reason' => $this->reason,
            'frozen_by' => $this->frozenBy,
            'is_automated' => $this->isAutomatedFraudDetection(),
            'is_manual' => $this->isManualFreeze(),
            'is_compliance' => $this->isComplianceFreeze(),
        ];
    }
}
