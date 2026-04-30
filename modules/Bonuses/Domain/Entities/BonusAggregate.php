<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Enums\BonusStatus;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class BonusAggregate
 *
 * Core domain aggregate handling specific bonus entity accrual instances bounding them
 * uniquely tying owners, strict allocation conditions and tracking absolute consumption metrics.
 * Ensures rules like expiration, status transitions, and maximum burn rates validate completely transparently internally.
 * Implements full state machine for bonus lifecycle with proper invariants enforcement.
 */
final class BonusAggregate
{
    /**
     * @param  string  $id  Unique universally distinguishable instance identifier.
     * @param  string  $ownerId  Entity owning definitively tying consumption bounds safely.
     * @param  BonusAmount  $initialAmount  The absolute starting baseline quantity granted explicitly.
     * @param  BonusAmount  $remainingAmount  Live balancing dynamically decaying quantity bounds.
     * @param  BonusType  $type  Strategic categorization restricting integration mappings internally.
     * @param  BonusStatus  $status  Current lifecycle state of the bonus aggregate.
     * @param  string  $correlationId  Unified traceable operational audit sequence tracking strings.
     * @param  DateTimeImmutable  $issuedAt  Granular timestamp noting exact genesis tracking.
     * @param  DateTimeImmutable|null  $expiresAt  Hard stop temporal limit automatically invalidating balance bounds.
     * @param  string|null  $sourceId  Optional source entity ID that triggered the award.
     * @param  string|null  $sourceType  Optional source entity type (referral, order, etc.).
     * @param  array  $metadata  Additional metadata for analytics and tracking.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $ownerId,
        private readonly BonusAmount $initialAmount,
        private BonusAmount $remainingAmount,
        private readonly BonusType $type,
        private BonusStatus $status,
        private readonly string $correlationId,
        private readonly DateTimeImmutable $issuedAt,
        private readonly ?DateTimeImmutable $expiresAt = null,
        private readonly ?string $sourceId = null,
        private readonly ?string $sourceType = null,
        private readonly array $metadata = []
    ) {
        $this->validate();
    }

    /**
     * Validates aggregate invariants to ensure data integrity.
     */
    private function validate(): void
    {
        if (empty($this->id) || empty($this->ownerId) || empty($this->correlationId)) {
            throw new DomainException('Bonus aggregates critically require fully qualified identity strings fundamentally.');
        }

        if ($this->remainingAmount->getAmount() > $this->initialAmount->getAmount()) {
            throw new DomainException('Remaining active bonus quantities logically cannot exceed intrinsic allocated baselines safely.');
        }

        if ($this->remainingAmount->getAmount() < 0) {
            throw new DomainException('Remaining bonus amount cannot be negative.');
        }

        if ($this->expiresAt !== null && $this->expiresAt <= $this->issuedAt) {
            throw new DomainException('Expiration date must be after issue date.');
        }

        // Validate status consistency with remaining amount
        if ($this->status === BonusStatus::CONSUMED && !$this->remainingAmount->isZero()) {
            throw new DomainException('Consumed bonuses must have zero remaining amount.');
        }

        if ($this->status === BonusStatus::ACTIVE && $this->remainingAmount->isZero()) {
            throw new DomainException('Active bonuses must have positive remaining amount.');
        }
    }

    /**
     * Instantiates fresh explicitly newly issued bonus sequences bounding directly natively.
     */
    public static function award(
        string $id,
        string $ownerId,
        BonusAmount $amount,
        BonusType $type,
        string $correlationId,
        ?DateTimeImmutable $expiresAt = null,
        ?string $sourceId = null,
        ?string $sourceType = null,
        array $metadata = []
    ): self {
        return new self(
            $id,
            $ownerId,
            $amount,
            $amount,
            $type,
            BonusStatus::ACTIVE,
            $correlationId,
            new DateTimeImmutable(),
            $expiresAt,
            $sourceId,
            $sourceType,
            $metadata
        );
    }

    /**
     * Reconstructs aggregate from persistence data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            ownerId: $data['owner_id'],
            initialAmount: new BonusAmount((int) $data['initial_amount']),
            remainingAmount: new BonusAmount((int) $data['remaining_amount']),
            type: BonusType::fromString($data['type']),
            status: BonusStatus::fromString($data['status']),
            correlationId: $data['correlation_id'],
            issuedAt: new DateTimeImmutable($data['issued_at']),
            expiresAt: $data['expires_at'] ? new DateTimeImmutable($data['expires_at']) : null,
            sourceId: $data['source_id'] ?? null,
            sourceType: $data['source_type'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Validates and executes intrinsic consumption mechanisms applying partial mapping bounds safely.
     *
     * @param  BonusAmount  $amount  Quantity demanded actively consuming bounded limits.
     * @param  DateTimeImmutable  $now  Standardized contextual temporal checkpoint sequences.
     * @throws DomainException
     */
    public function consume(BonusAmount $amount, DateTimeImmutable $now = new DateTimeImmutable()): void
    {
        if (!$this->status->allowsConsumption()) {
            throw new DomainException(
                sprintf('Cannot consume bonus with status: %s', $this->status->value)
            );
        }

        if ($this->isExpired($now)) {
            throw new DomainException('Cannot safely consume explicitly expired temporal bounds mapping instances internally.');
        }

        if (!$this->type->allowsPartialConsumption() && !$this->remainingAmount->equals($amount)) {
            throw new DomainException(
                sprintf('Bonus type %s must be consumed in full or not at all.', $this->type->value)
            );
        }

        if ($this->remainingAmount->getAmount() < $amount->getAmount()) {
            throw new DomainException('Active consumption demand strongly exceeds functionally available bounded quantities intrinsically.');
        }

        $this->remainingAmount = $this->remainingAmount->subtract($amount);

        // Auto-transition to CONSUMED if fully consumed
        if ($this->remainingAmount->isZero()) {
            $this->transitionTo(BonusStatus::CONSUMED);
        }
    }

    /**
     * Transitions the bonus to a new status with proper validation.
     *
     * @param  BonusStatus  $newStatus  The target status.
     * @throws DomainException When transition is invalid.
     */
    public function transitionTo(BonusStatus $newStatus): void
    {
        if ($this->status === $newStatus) {
            return; // Already in target status
        }

        $validTransition = match ([$this->status, $newStatus]) {
            [BonusStatus::ACTIVE, BonusStatus::CONSUMED] => true,
            [BonusStatus::ACTIVE, BonusStatus::EXPIRED] => true,
            [BonusStatus::ACTIVE, BonusStatus::CANCELLED] => true,
            [BonusStatus::ACTIVE, BonusStatus::FROZEN] => true,
            [BonusStatus::ACTIVE, BonusStatus::PROCESSING] => true,
            [BonusStatus::PROCESSING, BonusStatus::ACTIVE] => true,
            [BonusStatus::PROCESSING, BonusStatus::CONSUMED] => true,
            [BonusStatus::FROZEN, BonusStatus::ACTIVE] => true,
            [BonusStatus::FROZEN, BonusStatus::CANCELLED] => true,
            default => false,
        };

        if (!$validTransition) {
            throw new DomainException(
                sprintf('Invalid status transition from %s to %s', $this->status->value, $newStatus->value)
            );
        }

        $this->status = $newStatus;
    }

    /**
     * Expires the bonus if the current time is past the expiration date.
     *
     * @param  DateTimeImmutable  $now  The current time to check against.
     * @return bool True if the bonus was expired, false otherwise.
     */
    public function expireIfPast(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->isExpired($now) && $this->status->canTransitionToExpired()) {
            $this->transitionTo(BonusStatus::EXPIRED);
            return true;
        }

        return false;
    }

    /**
     * Cancels the bonus for fraud or manual correction reasons.
     *
     * @throws DomainException When cancellation is not allowed.
     */
    public function cancel(): void
    {
        if (!$this->status->canTransitionToCancelled()) {
            throw new DomainException(
                sprintf('Cannot cancel bonus with status: %s', $this->status->value)
            );
        }

        $this->transitionTo(BonusStatus::CANCELLED);
    }

    /**
     * Freezes the bonus pending investigation or review.
     *
     * @throws DomainException When freezing is not allowed.
     */
    public function freeze(): void
    {
        if (!$this->status->canTransitionToFrozen()) {
            throw new DomainException(
                sprintf('Cannot freeze bonus with status: %s', $this->status->value)
            );
        }

        $this->transitionTo(BonusStatus::FROZEN);
    }

    /**
     * Unfreezes a frozen bonus, returning it to active state.
     *
     * @throws DomainException When unfreezing is not allowed.
     */
    public function unfreeze(): void
    {
        if (!$this->status->canTransitionToActive()) {
            throw new DomainException(
                sprintf('Cannot unfreeze bonus with status: %s', $this->status->value)
            );
        }

        $this->transitionTo(BonusStatus::ACTIVE);
    }

    /**
     * Locks the bonus for processing to prevent race conditions during consumption.
     *
     * @throws DomainException When locking is not allowed.
     */
    public function lockForProcessing(): void
    {
        if (!$this->status->canTransitionToProcessing()) {
            throw new DomainException(
                sprintf('Cannot lock for processing with status: %s', $this->status->value)
            );
        }

        $this->transitionTo(BonusStatus::PROCESSING);
    }

    /**
     * Releases the processing lock, returning to active state.
     *
     * @throws DomainException When releasing is not allowed.
     */
    public function releaseProcessingLock(): void
    {
        if (!$this->status->canTransitionToActive()) {
            throw new DomainException(
                sprintf('Cannot release processing lock with status: %s', $this->status->value)
            );
        }

        $this->transitionTo(BonusStatus::ACTIVE);
    }

    /**
     * Validates temporal bounds defining if allocation logically persists natively securely.
     */
    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $now > $this->expiresAt;
    }

    /**
     * Confirms structurally if bounded sequence fully natively empty rendering unusable functionally.
     */
    public function isFullyConsumed(): bool
    {
        return $this->remainingAmount->isZero() || $this->status === BonusStatus::CONSUMED;
    }

    /**
     * Determines if the bonus is in a terminal state (no further transitions allowed).
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Determines if the bonus is currently available for consumption.
     */
    public function isAvailable(): bool
    {
        return $this->status === BonusStatus::ACTIVE && !$this->isExpired();
    }

    /**
     * Calculates days until expiration.
     * Returns null if bonus doesn't expire.
     */
    public function getDaysUntilExpiration(DateTimeImmutable $now = new DateTimeImmutable()): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        if ($this->isExpired($now)) {
            return 0;
        }

        $interval = $now->diff($this->expiresAt);
        return $interval->days;
    }

    /**
     * Extracts UUID mapping instance purely validating uniquely.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retrieves owner uniquely assigning consumption scopes distinctly directly.
     */
    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    /**
     * Fetches current state dynamically mapping bounded limits securely actively.
     */
    public function getRemainingAmount(): BonusAmount
    {
        return $this->remainingAmount;
    }

    /**
     * Returns the initial amount awarded.
     */
    public function getInitialAmount(): BonusAmount
    {
        return $this->initialAmount;
    }

    /**
     * Returns the consumed amount (initial - remaining).
     */
    public function getConsumedAmount(): BonusAmount
    {
        return $this->initialAmount->subtract($this->remainingAmount);
    }

    /**
     * Yields structurally typing parameters effectively dynamically isolating mappings.
     */
    public function getType(): BonusType
    {
        return $this->type;
    }

    /**
     * Returns the current status of the bonus.
     */
    public function getStatus(): BonusStatus
    {
        return $this->status;
    }

    /**
     * Dumps traceability securely binding sequences externally propagating cleanly.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Returns the issue timestamp.
     */
    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    /**
     * Returns the expiration timestamp, or null if no expiration.
     */
    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Returns the source entity ID if available.
     */
    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    /**
     * Returns the source entity type if available.
     */
    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    /**
     * Returns the metadata array.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Converts aggregate to array for persistence.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'initial_amount' => $this->initialAmount->getAmount(),
            'remaining_amount' => $this->remainingAmount->getAmount(),
            'type' => $this->type->value,
            'status' => $this->status->value,
            'correlation_id' => $this->correlationId,
            'issued_at' => $this->issuedAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'metadata' => $this->metadata,
        ];
    }
}
